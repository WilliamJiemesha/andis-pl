<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchExternalItemRequest;
use App\Http\Requests\Api\SyncExternalItemRequest;
use App\Models\MasterItem;
use App\Services\ExternalItemSyncService;
use App\Support\PriceCode;
use Illuminate\Http\JsonResponse;

class ExternalItemController extends Controller
{
    public function __construct(private readonly ExternalItemSyncService $syncService)
    {
    }

    public function search(SearchExternalItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $item = $this->syncService->search(
            $validated['barang'],
            $validated['merk'],
            $validated['tipe'],
        );

        if (! $item) {
            return response()->json([
                'success' => true,
                'exists' => false,
                'message' => 'Master barang belum tersedia untuk kombinasi barang, tipe, dan merk tersebut.',
                'data' => [
                    'barang' => $validated['barang'],
                    'tipe' => $validated['tipe'],
                    'merk' => $validated['merk'],
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'exists' => true,
            'message' => 'Master barang ditemukan.',
            'data' => $this->transformItem($item),
        ]);
    }

    public function sync(SyncExternalItemRequest $request): JsonResponse
    {
        $result = $this->syncService->sync(
            $request->validated(),
        );

        /** @var MasterItem $item */
        $item = $result['item'];
        $incomingEntry = $result['incoming_entry'];

        return response()->json([
            'success' => true,
            'exists' => $result['exists'],
            'action' => $result['action'],
            'message' => $result['exists']
                ? 'Master barang sudah ada. Data SKU diperbarui jika ada perubahan.'
                : 'Master barang baru dibuat dan masuk ke Review Harga.',
            'data' => [
                ...$this->transformItem($item),
                'price_review_created' => $result['price_review_created'],
                'selling_price_updated' => $result['selling_price_updated'],
                'incoming_created' => $incomingEntry !== null,
                'incoming_reference' => $incomingEntry?->invoice_number,
            ],
        ], $result['exists'] ? 200 : 201);
    }

    private function transformItem(MasterItem $item): array
    {
        $latestCost = $item->costHistories()
            ->latest('recorded_at')
            ->latest('id')
            ->first();

        return [
            'id' => $item->id,
            'barang' => $item->barang,
            'tipe' => $item->tipe,
            'merk' => $item->merk,
            'official_name' => $item->official_name,
            'alias' => $item->alias_name,
            'sku_code' => $item->sku,
            'pc' => $latestCost?->cost_code,
            'kode_modal' => $latestCost?->cost_code,
            'selling_price' => $item->selling_price === null ? null : (float) $item->selling_price,
            'selling_price_code' => PriceCode::encode($item->selling_price),
            'harga_jual_code' => PriceCode::encode($item->selling_price),
        ];
    }
}
