<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class InventoryScanTest extends TestCase
{
    public function test_guest_is_redirected_from_inventory_scan_page(): void
    {
        $this->get(route('inventory.scan'))
            ->assertRedirect();
    }

    public function test_superuser_can_view_inventory_scan_page(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.scan'))
            ->assertOk()
            ->assertSee('Inventory Scan');
    }

    public function test_lookup_finds_consumable_by_existing_barcode_item_number(): void
    {
        $consumable = Consumable::factory()->create([
            'item_no' => '012345678905',
            'name' => 'UniFi U6+ Access Point',
            'qty' => 7,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->postJson(route('inventory.scan.lookup'), ['barcode' => '012345678905'])
            ->assertOk()
            ->assertJsonPath('type', 'consumable')
            ->assertJsonPath('id', $consumable->id)
            ->assertJsonPath('name', 'UniFi U6+ Access Point')
            ->assertJsonPath('barcode', '012345678905')
            ->assertJsonPath('quantity', 7);
    }

    public function test_lookup_finds_loaner_asset_by_asset_tag(): void
    {
        $asset = Asset::factory()->create([
            'asset_tag' => '3133-LOANER-001',
            'name' => 'Loaner AP',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->postJson(route('inventory.scan.lookup'), ['barcode' => '3133-LOANER-001'])
            ->assertOk()
            ->assertJsonPath('type', 'asset')
            ->assertJsonPath('id', $asset->id)
            ->assertJsonPath('asset_tag', '3133-LOANER-001');
    }

    public function test_lookup_returns_not_found_for_unknown_barcode(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->postJson(route('inventory.scan.lookup'), ['barcode' => 'does-not-exist'])
            ->assertNotFound()
            ->assertJsonPath('message', 'No inventory item found for that barcode.');
    }
}
