<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Enums\ActionType;
use App\Models\Actionlog;
use App\Models\Consumable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsumableStockController extends Controller
{
    public function receive(Request $request, Consumable $consumable): RedirectResponse
    {
        $this->authorize('update', $consumable);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($consumable, $validated): void {
            $lockedConsumable = Consumable::query()->lockForUpdate()->findOrFail($consumable->id);

            $lockedConsumable->increment('qty', $validated['quantity']);
            $this->logStockMovement($lockedConsumable, ActionType::StockReceived, $validated['quantity'], $validated['note'] ?? null);
        });

        return redirect()
            ->route('consumables.show', $consumable)
            ->with('success', trans('general.receive_stock'));
    }

    public function remove(Request $request, Consumable $consumable): RedirectResponse
    {
        $this->authorize('update', $consumable);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($consumable, $validated): void {
            $lockedConsumable = Consumable::query()->lockForUpdate()->findOrFail($consumable->id);

            if ($validated['quantity'] > $lockedConsumable->numRemaining()) {
                throw ValidationException::withMessages([
                    'quantity' => trans('general.stock_remove_too_many'),
                ])->redirectTo(route('consumables.show', $lockedConsumable));
            }

            $lockedConsumable->decrement('qty', $validated['quantity']);
            $this->logStockMovement($lockedConsumable, ActionType::StockRemoved, $validated['quantity'], $validated['note'] ?? null);
        });

        return redirect()
            ->route('consumables.show', $consumable)
            ->with('success', trans('general.remove_stock'));
    }

    private function logStockMovement(Consumable $consumable, ActionType $actionType, int $quantity, ?string $note): void
    {
        $log = new Actionlog;
        $log->item_type = Consumable::class;
        $log->item_id = $consumable->id;
        $log->quantity = $quantity;
        $log->note = $note;
        $log->created_by = auth()->id();
        $log->action_date = now();
        $log->company_id = $consumable->company_id;
        $log->logaction($actionType);
    }
}
