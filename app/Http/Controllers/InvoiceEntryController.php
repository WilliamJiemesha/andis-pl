<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceEntry\StoreInvoiceEntryRequest;
use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Models\Role;
use App\Services\IncomingCheckService;
use App\Services\PriceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceEntryController extends Controller
{
    public function __construct(
        private readonly PriceReviewService $priceReviewService,
        private readonly IncomingCheckService $incomingCheckService,
    )
    {
    }

    public function index(Request $request): View
    {
        $query = InvoiceEntry::query()->with(['masterItem', 'creator']);

        if ($search = trim((string) $request->string('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhereHas('masterItem', fn ($masterQuery) => $masterQuery->where('official_name', 'like', '%'.mb_strtoupper($search).'%'));
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return view('invoice-entries.index', [
            'invoiceEntries' => $query->latest()->paginate(12)->withQueryString(),
            'search' => $request->string('q')->value(),
            'status' => $request->string('status')->value(),
            'canViewDecodedCost' => $request->user()->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]),
            'canViewCostCode' => $request->user()->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER, Role::INVOICE_HANDLER]),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('master-items.index');
    }

    public function store(StoreInvoiceEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $masterItemId = $validated['master_item_id'] ?? null;

        if (! $masterItemId && $request->boolean('create_master_inline')) {
            $masterItem = MasterItem::query()->create([
                'barang' => $validated['new_master_barang'],
                'merk' => $validated['new_master_merk'],
                'tipe' => $validated['new_master_tipe'],
                'sku' => $validated['new_master_sku'] ?? null,
                'alias_name' => $validated['new_master_alias_name'],
                'notes' => $validated['new_master_notes'] ?? null,
                'is_active' => true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $masterItemId = $masterItem->id;
        }

        $entryReference = $validated['invoice_number'] ?: $this->generateEntryReference();
        $masterItem = MasterItem::query()->findOrFail($masterItemId);

        $invoiceEntry = InvoiceEntry::query()->create([
            'invoice_number' => $entryReference,
            'vendor' => $validated['vendor'],
            'invoice_date' => $validated['invoice_date'],
            'raw_item_name' => $masterItem->official_name,
            'quantity' => $validated['quantity'],
            'expected_quantity' => $validated['expected_quantity'] ?? $validated['quantity'],
            'cost_code' => $validated['cost_code'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'master_item_id' => $masterItemId,
            'status' => InvoiceEntry::STATUS_MATCHED,
            'created_by' => $request->user()->id,
        ]);

        $invoiceEntry->load('masterItem');
        $this->priceReviewService->recordInvoiceCostChange($invoiceEntry);
        $this->incomingCheckService->ensureTaskForInvoiceEntry($invoiceEntry);

        return redirect()
            ->route('invoice-entries.show', $invoiceEntry)
            ->with('status', __('messages.invoice_entry_created'));
    }

    private function generateEntryReference(): string
    {
        return 'BM-'.now()->format('Ymd-Hisv');
    }

    public function show(Request $request, InvoiceEntry $invoiceEntry): View
    {
        $invoiceEntry->load(['masterItem', 'creator', 'incomingCheckTask.checker']);

        return view('invoice-entries.show', [
            'invoiceEntry' => $invoiceEntry,
            'canViewDecodedCost' => $request->user()->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]),
            'canViewCostCode' => $request->user()->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER, Role::INVOICE_HANDLER]),
        ]);
    }
}
