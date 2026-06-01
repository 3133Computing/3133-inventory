<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use Tests\TestCase;

class DashboardSellableStockFocusTest extends TestCase
{
    public function test_dashboard_prioritizes_rapid_intake_and_sellable_stock_workflow(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'));

        $response->assertOk()
            ->assertSee(route('inventory.intake'), false)
            ->assertSee(trans('general.rapid_intake'))
            ->assertSee(trans('general.sellable_stock'))
            ->assertSee(trans('general.dashboard_sellable_stock_focus'));
    }

    public function test_top_bar_barcode_search_targets_sellable_stock(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('home'));

        $response->assertOk()
            ->assertSee('action="'.url('/consumables/bytag').'"', false)
            ->assertSee('name="assetTag"', false)
            ->assertSee(trans('general.lookup_by_sellable_stock_barcode'));
    }
}
