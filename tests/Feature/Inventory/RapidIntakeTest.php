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

    public function test_rapid_intake_page_shows_required_storage_area_selector_and_last_received_panel(): void
    {
        $consumable = Consumable::factory()->create([
            'item_no' => 'LAST-10-BARCODE',
            'name' => 'Recently Received Switch',
            'qty' => 4,
        ]);
        $actor = User::factory()->superuser()->create();
        $location = Location::factory()->create(['name' => 'office']);

        Actionlog::factory()->create([
            'action_type' => 'stock received',
            'item_type' => Consumable::class,
            'item_id' => $consumable->id,
            'quantity' => 4,
            'created_by' => $actor->id,
            'note' => 'Rapid intake to office',
            'location_id' => $location->id,
        ]);

        $this->actingAs($actor)
            ->get(route('inventory.intake'))
            ->assertOk()
            ->assertSee('autofocus', false)
            ->assertSee('Last 10 received items')
            ->assertSee('Recently Received Switch')
            ->assertSee('office bins')
            ->assertSee('tool bag')
            ->assertSee('tool chest')
            ->assertSee('office drawers')
            ->assertSee('garage')
            ->assertSee('other')
            ->assertDontSee('customer loaner pool')
            ->assertSee('name="storage_location"', false)
            ->assertSee('required', false);
    }

    public function test_quick_move_stock_changes_existing_consumable_location_without_changing_quantity(): void
    {
        $from = Location::factory()->create(['name' => 'office']);
        $to = Location::factory()->create(['name' => 'van']);
        $consumable = Consumable::factory()->create([
            'item_no' => 'MOVE-ME',
            'name' => 'Patch Cable',
            'qty' => 12,
            'location_id' => $from->id,
        ]);
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor)
            ->post(route('inventory.intake.move', $consumable), [
                'storage_location' => $to->name,
            ])
            ->assertRedirect(route('inventory.intake'));

        $consumable->refresh();
        $this->assertEquals(12, $consumable->qty);
        $this->assertEquals($to->id, $consumable->location_id);
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'update',
            'item_id' => $consumable->id,
            'item_type' => Consumable::class,
            'created_by' => $actor->id,
            'note' => 'Moved stock to van',
        ]);
    }
}
