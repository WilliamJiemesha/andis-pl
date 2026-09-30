<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterItem\QuickStoreMasterItemRequest;
use App\Http\Requests\MasterItem\StoreMasterItemRequest;
use App\Http\Requests\MasterItem\UpdateMasterItemRequest;
use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\PriceHistory;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterItemController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): View
    {
        $query = MasterItem::query()->with(['creator', 'updater']);

        if ($search = trim((string) $request->string('q'))) {
            $searchUpper = mb_strtoupper($search);

            $query->where(function ($builder) use ($searchUpper) {
                $builder
                    ->where('barang', 'like', "%{$searchUpper}%")
                    ->orWhere('merk', 'like', "%{$searchUpper}%")
                    ->orWhere('tipe', 'like', "%{$searchUpper}%")
                    ->orWhere('sku', 'like', "%{$searchUpper}%")
                    ->orWhere('official_name', 'like', "%{$searchUpper}%")
                    ->orWhere('alias_name', 'like', "%{$searchUpper}%");
            });
        }

        $showInactive = $request->boolean('inactive') || $request->string('status')->value() === 'inactive';
        $query->where('is_active', ! $showInactive);

        return view('master-items.index', [
            'masterItems' => $query->latest()->paginate(12)->withQueryString(),
            'search' => $request->string('q')->value(),
            'showInactive' => $showInactive,
            'masterItemOptions' => MasterItem::query()->orderBy('official_name')->get(['id', 'sku', 'official_name', 'alias_name', 'notes']),
            'incomingHistory' => InvoiceEntry::query()
                ->with('masterItem')
                ->latest('invoice_date')
                ->latest('id')
                ->take(20)
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('master-items.create');
    }

    public function store(StoreMasterItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        if (blank($validated['sku'] ?? null)) {
            unset($validated['sku']);
        }

        $masterItem = MasterItem::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('master-items.show', $masterItem)
            ->with('status', __('messages.master_item_created'));
    }

    public function quickStore(QuickStoreMasterItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if (blank($validated['sku'] ?? null)) {
            unset($validated['sku']);
        }

        $masterItem = MasterItem::query()->create([
            ...$validated,
            'is_active' => true,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'id' => $masterItem->id,
            'official_name' => $masterItem->official_name,
            'alias_name' => $masterItem->alias_name,
            'sku' => $masterItem->sku,
            'notes' => $masterItem->notes,
        ]);
    }

    public function show(MasterItem $masterItem): View
    {
        $masterItem->load([
            'creator',
            'updater',
            'priceHistories' => fn ($builder) => $builder->with('changer')->latest('effective_at')->limit(5),
            'costHistories' => fn ($builder) => $builder->with(['recorder', 'invoiceEntry'])->latest('recorded_at')->limit(5),
        ]);

        return view('master-items.show', [
            'masterItem' => $masterItem,
        ]);
    }

    public function edit(MasterItem $masterItem): View
    {
        return view('master-items.edit', [
            'masterItem' => $masterItem,
        ]);
    }

    public function update(UpdateMasterItemRequest $request, MasterItem $masterItem): RedirectResponse
    {
        $validated = $request->validated();
        if (blank($validated['sku'] ?? null)) {
            unset($validated['sku']);
        }
        $previousPrice = $masterItem->selling_price;

        $masterItem->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        if (($previousPrice === null && $masterItem->selling_price !== null)
            || ($previousPrice !== null && (float) $previousPrice !== (float) $masterItem->selling_price)) {
            PriceHistory::query()->create([
                'master_item_id' => $masterItem->id,
                'previous_price' => $previousPrice,
                'new_price' => $masterItem->selling_price,
                'changed_by' => $request->user()->id,
                'note' => __('messages.manual_price_update_note'),
                'effective_at' => now(),
            ]);

            $notificationType = $previousPrice !== null && (float) $masterItem->selling_price < (float) $previousPrice
                ? 'price_updated_down'
                : 'price_updated_up';

            $this->notificationService->notifyRoles(
                [Role::ADMIN, Role::SALES_USER, Role::PRICE_HANDLER],
                $notificationType,
                'Perubahan Harga',
                $notificationType === 'price_updated_down'
                    ? 'Harga jual turun untuk '.$masterItem->official_name.'.'
                    : 'Harga jual naik untuk '.$masterItem->official_name.'.',
                route('master-items.price-history', $masterItem),
                MasterItem::class,
                $masterItem->id,
            );
        }

        return redirect()
            ->route('master-items.show', $masterItem)
            ->with('status', __('messages.master_item_updated'));
    }

    public function updateStatus(Request $request, MasterItem $masterItem): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $masterItem->update([
            'is_active' => (bool) $validated['is_active'],
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('master-items.show', $masterItem)
            ->with('status', __('messages.master_item_status_updated'));
    }

    public function destroy(MasterItem $masterItem): RedirectResponse
    {
        $masterItem->delete();

        return redirect()
            ->route('master-items.index')
            ->with('status', __('messages.master_item_deleted'));
    }
}
