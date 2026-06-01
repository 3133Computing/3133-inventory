<?php

namespace Tests\Feature\Consumables\Ui;

use App\Models\Asset;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class FindConsumableByBarcodeTest extends TestCase
{
    public function test_dashboard_barcode_search_redirects_to_matching_sellable_stock(): void
    {
        $consumable = Consumable::factory()->create([
            'item_no' => '012345678905',
            'name' => 'UniFi U6+ Access Point',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get('/consumables/bytag?assetTag=012345678905&topsearch=true')
            ->assertRedirect(route('consumables.show', $consumable))
            ->assertSessionHas('topsearch', true);
    }

    public function test_dashboard_barcode_search_prefers_sellable_stock_over_asset_tag_match(): void
    {
        $consumable = Consumable::factory()->create(['item_no' => 'SHARED-BARCODE']);
        Asset::factory()->create(['asset_tag' => 'SHARED-BARCODE']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get('/consumables/bytag?assetTag=SHARED-BARCODE&topsearch=true')
            ->assertRedirect(route('consumables.show', $consumable));
    }

    public function test_dashboard_barcode_search_falls_back_to_sellable_stock_list_with_search_when_not_unique(): void
    {
        Consumable::factory()->count(2)->create(['item_no' => 'DUPLICATE-BARCODE']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get('/consumables/bytag?assetTag=DUPLICATE-BARCODE&topsearch=true')
            ->assertRedirect(route('consumables.index'))
            ->assertSessionHas('search', 'DUPLICATE-BARCODE');
    }
}
