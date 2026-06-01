<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Models\Actionlog;
use App\Models\Category;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RapidIntakeController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', Consumable::class);

        return view('inventory.intake');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:191'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'storage_location' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $barcode = trim($validated['barcode']);
        $storageLocation = trim($validated['storage_location']);
        $quantity = (int) $validated['quantity'];

        if ($barcode === '') {
            throw ValidationException::withMessages([
                'barcode' => trans('validation.required', ['attribute' => trans('general.barcode')]),
            ]);
        }

        if ($storageLocation === '') {
            throw ValidationException::withMessages([
                'storage_location' => trans('validation.required', ['attribute' => trans('general.storage_location')]),
            ]);
        }

        $consumable = DB::transaction(function () use ($request, $validated, $barcode, $storageLocation, $quantity): Consumable {
            $location = $this->findOrCreateLocation($storageLocation);

            $consumable = Consumable::query()
                ->where('item_no', $barcode)
                ->lockForUpdate()
                ->first();

            if ($consumable) {
                $this->authorize('update', $consumable);
                $consumable->increment('qty', $quantity);
                $consumable->location_id = $location->id;

                if (! $consumable->save()) {
                    throw ValidationException::withMessages($consumable->getErrors()->toArray());
                }
            } else {
                $this->authorize('create', Consumable::class);
                $category = $this->findOrCreateDefaultCategory();
                $consumable = new Consumable;
                $consumable->name = trim($validated['name'] ?? '') ?: $barcode;
                $consumable->item_no = $barcode;
                $consumable->qty = $quantity;
                $consumable->min_amt = 0;
                $consumable->category_id = $category->id;
                $consumable->location_id = $location->id;
                $consumable->company_id = Company::getIdForCurrentUser($request->input('company_id'));
                $consumable->created_by = auth()->id();

                if (! $consumable->save()) {
                    throw ValidationException::withMessages($consumable->getErrors()->toArray());
                }
            }

            $this->logStockMovement($consumable->fresh(), $quantity, trans('general.rapid_intake_log_note', [
                'location' => $location->name,
            ]));

            return $consumable;
        });

        return redirect()
            ->route('inventory.intake')
            ->with('success', trans('general.rapid_intake_success', [
                'quantity' => $quantity,
                'item' => $consumable->name,
                'location' => $storageLocation,
            ]));
    }

    private function findOrCreateLocation(string $name): Location
    {
        $location = Location::query()->where('name', $name)->first();

        if ($location) {
            return $location;
        }

        $location = new Location;
        $location->name = $name;
        $location->created_by = auth()->id();

        if (! $location->save()) {
            throw ValidationException::withMessages($location->getErrors()->toArray());
        }

        return $location;
    }

    private function findOrCreateDefaultCategory(): Category
    {
        $category = Category::query()
            ->where('name', 'Uncategorized Stock')
            ->where('category_type', 'consumable')
            ->first();

        if ($category) {
            return $category;
        }

        $category = new Category;
        $category->name = 'Uncategorized Stock';
        $category->category_type = 'consumable';
        $category->created_by = auth()->id();

        if (! $category->save()) {
            throw ValidationException::withMessages($category->getErrors()->toArray());
        }

        return $category;
    }

    private function logStockMovement(Consumable $consumable, int $quantity, string $note): void
    {
        $log = new Actionlog;
        $log->item_type = Consumable::class;
        $log->item_id = $consumable->id;
        $log->quantity = $quantity;
        $log->note = $note;
        $log->created_by = auth()->id();
        $log->action_date = now();
        $log->company_id = $consumable->company_id;
        $log->logaction(ActionType::StockReceived);
    }
}
