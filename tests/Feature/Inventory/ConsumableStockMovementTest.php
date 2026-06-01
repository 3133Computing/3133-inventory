<?php

namespace Tests\Feature\Inventory;

use App\Models\Actionlog;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

class ConsumableStockMovementTest extends TestCase
{
    public function test_receiving_stock_increments_consumable_quantity_and_logs_movement(): void
    {
        $consumable = Consumable::factory()->create(['qty' => 3]);
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('inventory.consumables.receive', $consumable), [
                'quantity' => 4,
                'note' => 'PO 3133-1001',
            ])
            ->assertRedirect(route('consumables.show', $consumable));

        $this->assertEquals(7, $consumable->fresh()->qty);
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'stock received',
            'item_id' => $consumable->id,
            'item_type' => Consumable::class,
            'quantity' => 4,
            'created_by' => $actor->id,
            'note' => 'PO 3133-1001',
        ]);
    }

    public function test_removing_stock_decrements_consumable_quantity_and_logs_movement(): void
    {
        $consumable = Consumable::factory()->create(['qty' => 8]);
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('inventory.consumables.remove', $consumable), [
                'quantity' => 2,
                'note' => 'Sold to customer',
            ])
            ->assertRedirect(route('consumables.show', $consumable));

        $this->assertEquals(6, $consumable->fresh()->qty);
        $this->assertEquals(1, Actionlog::where([
            'action_type' => 'stock removed',
            'item_id' => $consumable->id,
            'item_type' => Consumable::class,
            'quantity' => 2,
            'created_by' => $actor->id,
            'note' => 'Sold to customer',
        ])->count());
    }

    public function test_stock_removal_cannot_make_available_quantity_negative(): void
    {
        $consumable = Consumable::factory()->create(['qty' => 1]);

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('consumables.show', $consumable))
            ->post(route('inventory.consumables.remove', $consumable), [
                'quantity' => 2,
            ])
            ->assertRedirect(route('consumables.show', $consumable))
            ->assertSessionHasErrors('quantity');

        $this->assertEquals(1, $consumable->fresh()->qty);
    }
}
