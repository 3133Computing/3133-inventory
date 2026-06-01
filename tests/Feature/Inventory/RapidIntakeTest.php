<?php

namespace Tests\Feature\Inventory;

use App\Models\Actionlog;
use App\Models\Category;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class RapidIntakeTest extends TestCase
{
    public function test_superuser_can_view_rapid_intake_page(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.intake'))
            ->assertOk()
            ->assertViewIs('inventory.intake')
            ->assertSee('Rapid Intake');
    }

    public function test_rapid_intake_receives_existing_barcode_and_updates_storage_location(): void
    {
        $consumable = Consumable::factory()->create([
            'item_no' => '012345678905',
            'name' => 'UniFi U6+ Access Point',
            'qty' => 2,
        ]);
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('inventory.intake.store'), [
                'barcode' => '012345678905',
                'quantity' => 5,
                'storage_location' => 'Bin A1',
            ])
            ->assertRedirect(route('inventory.intake'));

        $location = Location::where('name', 'Bin A1')->firstOrFail();
        $consumable->refresh();

        $this->assertEquals(7, $consumable->qty);
        $this->assertEquals($location->id, $consumable->location_id);
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'stock received',
            'item_id' => $consumable->id,
            'item_type' => Consumable::class,
            'quantity' => 5,
            'created_by' => $actor->id,
            'note' => 'Rapid intake to Bin A1',
        ]);
    }

    public function test_rapid_intake_creates_consumable_for_new_barcode_with_default_category_and_location(): void
    {
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('inventory.intake.store'), [
                'barcode' => 'NEW-3133-BARCODE',
                'quantity' => 3,
                'storage_location' => 'Shelf 2',
                'name' => 'Zooz ZEN32 Scene Controller',
            ])
            ->assertRedirect(route('inventory.intake'));

        $category = Category::where([
            'name' => 'Uncategorized Stock',
            'category_type' => 'consumable',
        ])->firstOrFail();
        $location = Location::where('name', 'Shelf 2')->firstOrFail();
        $consumable = Consumable::where('item_no', 'NEW-3133-BARCODE')->firstOrFail();

        $this->assertEquals('Zooz ZEN32 Scene Controller', $consumable->name);
        $this->assertEquals(3, $consumable->qty);
        $this->assertEquals($category->id, $consumable->category_id);
        $this->assertEquals($location->id, $consumable->location_id);
        $this->assertEquals($actor->id, $consumable->created_by);
        $this->assertEquals(1, Actionlog::where([
            'action_type' => 'stock received',
            'item_id' => $consumable->id,
            'item_type' => Consumable::class,
            'quantity' => 3,
            'created_by' => $actor->id,
            'note' => 'Rapid intake to Shelf 2',
        ])->count());
    }

    public function test_new_barcode_name_defaults_to_barcode_when_name_is_blank(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('inventory.intake.store'), [
                'barcode' => 'NO-NAME-BARCODE',
                'quantity' => 1,
                'storage_location' => 'Counter',
                'name' => '',
            ])
            ->assertRedirect(route('inventory.intake'));

        $this->assertDatabaseHas('consumables', [
            'item_no' => 'NO-NAME-BARCODE',
            'name' => 'NO-NAME-BARCODE',
            'qty' => 1,
        ]);
    }
}
