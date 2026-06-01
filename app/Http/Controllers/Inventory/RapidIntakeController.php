<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Models\Actionlog;
use App\Models\Category;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\Location;
use App\Services\Inventory\BarcodeLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RapidIntakeController extends Controller
{
    private const STORAGE_AREAS = [
        'office bins',
        'tool bag',
        'tool chest',
        'office drawers',
        'garage',
        'other',
    ];

    public function index(): View
    {
        $this->authorize('index', Consumable::class);

        $locations = $this->storageLocations();
        $recentReceived = Actionlog::query()
            ->with(['item', 'location'])
            ->where('action_type', ActionType::StockReceived->value)
            ->where('item_type', Consumable::class)
            ->latest()
            ->limit(10)
            ->get();

        return view('inventory.intake', [
            'locations' => $locations,
            'recentReceived' => $recentReceived,
        ]);
    }

    public function lookup(Request $request, BarcodeLookupService $barcodeLookup): JsonResponse
    {
        $this->authorize('index', Consumable::class);

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:191'],
        ]);

        $barcode = trim($validated['barcode']);
        $consumable = Consumable::query()
            ->where('item_no', $barcode)
            ->first();

        if ($consumable) {
            return response()->json([
                'found' => true,
                'title' => $consumable->name,
                'brand' => null,
                'source' => '3133 Inventory',
                'existing' => true,
            ]);
        }

        return response()->json($barcodeLookup->lookup($barcode));
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

    public function move(Request $request, Consumable $consumable): RedirectResponse
    {
        $this->authorize('update', $consumable);

        $validated = $request->validate([
            'storage_location' => ['required', 'string', 'max:255'],
        ]);

        $storageLocation = trim($validated['storage_location']);

        if ($storageLocation === '') {
            throw ValidationException::withMessages([
                'storage_location' => trans('validation.required', ['attribute' => trans('general.storage_location')]),
            ]);
        }

        $location = $this->findOrCreateLocation($storageLocation);
        $consumable->location_id = $location->id;

        if (! $consumable->save()) {
            throw ValidationException::withMessages($consumable->getErrors()->toArray());
        }

        $this->logStockMove($consumable->fresh(), trans('general.move_stock_log_note', [
            'location' => $location->name,
        ]), $location);

        return redirect()
            ->route('inventory.intake')
            ->with('success', trans('general.move_stock_success', [
                'item' => $consumable->name,
                'location' => $location->name,
            ]));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Location>
     */
    private function storageLocations()
    {
        foreach (self::STORAGE_AREAS as $name) {
            $this->findOrCreateLocation($name);
        }

        return Location::query()
            ->orderByRaw('case when name in (?, ?, ?, ?, ?, ?) then 0 else 1 end', self::STORAGE_AREAS)
            ->orderBy('name')
            ->limit(75)
            ->get();
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

    private function logStockMove(Consumable $consumable, string $note, Location $location): void
    {
        $log = new Actionlog;
        $log->item_type = Consumable::class;
        $log->item_id = $consumable->id;
        $log->note = $note;
        $log->created_by = auth()->id();
        $log->action_date = now();
        $log->location_id = $location->id;
        $log->company_id = $consumable->company_id;
        $log->logaction(ActionType::Update);
    }
}
