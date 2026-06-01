<?php

namespace Tests\Feature\Inventory;

use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class ConsumableLabelTest extends TestCase
{
    public function test_superuser_can_print_brother_friendly_consumable_qr_label(): void
    {
        $consumable = Consumable::factory()->create([
            'item_no' => '012345678905',
            'name' => 'UniFi U6+ Access Point',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.consumables.label', $consumable))
            ->assertOk()
            ->assertViewIs('inventory.consumable-label')
            ->assertSee('3133', false)
            ->assertSee('UniFi U6+ Access Point', false)
            ->assertSee('012345678905', false)
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee(route('inventory.scan', ['barcode' => '012345678905']), false);
    }
}
