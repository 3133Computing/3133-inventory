<?php

namespace Tests\Feature\Inventory;

use App\Models\Consumable;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BarcodeLookupTest extends TestCase
{
    public function test_rapid_intake_page_exposes_free_barcode_lookup_endpoint(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.intake'))
            ->assertOk()
            ->assertSee(route('inventory.intake.lookup'), false)
            ->assertSee('Lookup title');
    }

    public function test_lookup_returns_existing_local_consumable_before_external_services(): void
    {
        Http::preventStrayRequests();
        Consumable::factory()->create([
            'item_no' => 'LOCAL-3133',
            'name' => 'Existing UniFi Access Point',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->getJson(route('inventory.intake.lookup', ['barcode' => 'LOCAL-3133']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'title' => 'Existing UniFi Access Point',
                'source' => '3133 Inventory',
                'existing' => true,
            ]);
    }

    public function test_lookup_uses_free_upcitemdb_trial_for_retail_product_titles(): void
    {
        Cache::flush();
        Http::fake([
            'api.upcitemdb.com/prod/trial/lookup*' => Http::response([
                'code' => 'OK',
                'total' => 1,
                'items' => [[
                    'title' => 'Zooz ZEN32 Scene Controller',
                    'brand' => 'Zooz',
                ]],
            ]),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->getJson(route('inventory.intake.lookup', ['barcode' => '012345678905']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'title' => 'Zooz ZEN32 Scene Controller',
                'brand' => 'Zooz',
                'source' => 'UPCitemdb',
                'existing' => false,
            ]);
    }

    public function test_lookup_falls_back_to_free_open_food_facts(): void
    {
        Cache::flush();
        Http::fake([
            'api.upcitemdb.com/prod/trial/lookup*' => Http::response(['code' => 'OK', 'total' => 0, 'items' => []]),
            'world.openfoodfacts.org/api/v2/product/*' => Http::response([
                'status' => 1,
                'product' => [
                    'product_name' => 'Tasty Test Snack',
                    'brands' => 'Open Brand',
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->getJson(route('inventory.intake.lookup', ['barcode' => '0737628064502']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'title' => 'Tasty Test Snack',
                'brand' => 'Open Brand',
                'source' => 'Open Food Facts',
                'existing' => false,
            ]);
    }

    public function test_lookup_returns_not_found_when_free_services_have_no_title_or_fail(): void
    {
        Cache::flush();
        Http::fake([
            'api.upcitemdb.com/prod/trial/lookup*' => Http::response(['code' => 'OK', 'total' => 0, 'items' => []]),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->getJson(route('inventory.intake.lookup', ['barcode' => 'NO-MATCH-3133']))
            ->assertOk()
            ->assertJson([
                'found' => false,
                'title' => null,
                'brand' => null,
                'source' => null,
                'existing' => false,
            ]);
    }
}
