<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Consumable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScanController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', Consumable::class);

        return view('inventory.scan');
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('index', Consumable::class);

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:191'],
        ]);

        $barcode = trim($validated['barcode']);

        if ($barcode === '') {
            throw ValidationException::withMessages([
                'barcode' => trans('validation.required', ['attribute' => trans('general.barcode')]),
            ]);
        }

        $consumable = Consumable::query()
            ->where('item_no', $barcode)
            ->first();

        if ($consumable) {
            return response()->json([
                'type' => 'consumable',
                'id' => $consumable->id,
                'name' => $consumable->name,
                'barcode' => $consumable->item_no,
                'quantity' => $consumable->qty,
                'remaining' => $consumable->numRemaining(),
                'url' => route('consumables.show', $consumable),
                'label_url' => route('inventory.consumables.label', $consumable),
                'receive_url' => route('inventory.consumables.receive', $consumable),
                'remove_url' => route('inventory.consumables.remove', $consumable),
            ]);
        }

        $asset = Asset::query()
            ->where('asset_tag', $barcode)
            ->first();

        if ($asset) {
            $this->authorize('view', $asset);

            return response()->json([
                'type' => 'asset',
                'id' => $asset->id,
                'name' => $asset->display_name,
                'asset_tag' => $asset->asset_tag,
                'serial' => $asset->serial,
                'status' => $asset->assetstatus?->name,
                'url' => route('hardware.show', $asset),
            ]);
        }

        return response()->json([
            'message' => trans('general.inventory_barcode_not_found'),
        ], 404);
    }
}
