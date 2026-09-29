<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiIncomingItemRequest;
use App\Models\InvoiceEntry;
use App\Models\MasterItem;
use App\Services\IncomingCheckService;
use App\Services\PriceReviewService;
use Illuminate\Http\JsonResponse;

class IncomingItemApiController extends Controller
{
    public function __construct(
        private readonly PriceReviewService $priceReviewService,
        private readonly IncomingCheckService $incomingCheckService,
    ) {
    }

    public function store(StoreApiIncomingItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $item = filled($validated['sku'] ?? null)
            ? MasterItem::query()->where('sku', $validated['sku'])->firstOrFail()
            : MasterItem::query()
                ->where('barang', $validated['barang'])
                ->where('merk', $validated['merk'])
                ->where('tipe', $validated['tipe'])
                ->firstOrFail();

        if (filled($validated['alias_name'] ?? null) && $validated['alias_name'] !== $item->alias_name) {
            $item->update(['alias_name' => $validated['alias_name']]);
        }

        $incomingItem = InvoiceEntry::query()->create([
            'invoice_number' => 'BM-'.now()->format('Ymd-Hisv'),
            'vendor' => $validated['kode_supplier'],
            'invoice_date' => $validated['tanggal_masuk'],
            'raw_item_name' => $item->displayName(),
            'quantity' => $validated['jumlah'],
            'expected_quantity' => $validated['jumlah'],
            'cost_code' => $validated['kode_modal'] ?: null,
            'notes' => $validated['catatan'] ?? $item->notes,
            'master_item_id' => $item->id,
            'status' => InvoiceEntry::STATUS_MATCHED,
        ]);

        $incomingItem->load('masterItem');
        $this->priceReviewService->recordInvoiceCostChange($incomingItem);
        $this->incomingCheckService->ensureTaskForInvoiceEntry($incomingItem);

        return response()->json([
            'message' => 'Barang masuk berhasil diterima.',
            'data' => [
                'id' => $incomingItem->id,
                'referensi_masuk' => $incomingItem->invoice_number,
                'sku' => $item->sku,
                'official_name' => $item->official_name,
                'alias_name' => $item->alias_name,
                'jumlah' => $incomingItem->quantity,
                'kode_supplier' => $incomingItem->vendor,
                'kode_modal' => $incomingItem->cost_code,
            ],
        ], 201);
    }
}
