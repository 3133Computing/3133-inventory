<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class BarcodeLookupService
{
    public function lookup(string $barcode): array
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            return $this->notFound();
        }

        return Cache::remember("inventory:barcode-lookup:{$barcode}", now()->addDays(30), function () use ($barcode) {
            return $this->lookupExternal($barcode);
        });
    }

    private function lookupExternal(string $barcode): array
    {
        return $this->lookupUpcItemDb($barcode)
            ?? $this->lookupOpenFoodFacts($barcode)
            ?? $this->notFound();
    }

    private function lookupUpcItemDb(string $barcode): ?array
    {
        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->get('https://api.upcitemdb.com/prod/trial/lookup', [
                    'upc' => $barcode,
                ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $item = collect($response->json('items', []))->firstWhere('title');

        if (! $item || blank($item['title'] ?? null)) {
            return null;
        }

        return $this->found($item['title'], $item['brand'] ?? null, 'UPCitemdb');
    }

    private function lookupOpenFoodFacts(string $barcode): ?array
    {
        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->get("https://world.openfoodfacts.org/api/v2/product/{$barcode}.json");
        } catch (Throwable) {
            return null;
        }

        if (! $response->ok() || (int) $response->json('status') !== 1) {
            return null;
        }

        $product = $response->json('product', []);
        $title = $product['product_name'] ?? $product['generic_name'] ?? null;

        if (blank($title)) {
            return null;
        }

        return $this->found($title, $product['brands'] ?? null, 'Open Food Facts');
    }

    private function found(string $title, ?string $brand, string $source): array
    {
        return [
            'found' => true,
            'title' => trim($title),
            'brand' => filled($brand) ? trim($brand) : null,
            'source' => $source,
            'existing' => false,
        ];
    }

    private function notFound(): array
    {
        return [
            'found' => false,
            'title' => null,
            'brand' => null,
            'source' => null,
            'existing' => false,
        ];
    }
}
