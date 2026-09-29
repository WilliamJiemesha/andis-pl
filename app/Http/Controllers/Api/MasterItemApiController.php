<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiMasterItemRequest;
use App\Models\MasterItem;
use Illuminate\Http\JsonResponse;

class MasterItemApiController extends Controller
{
    public function store(StoreApiMasterItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if (blank($validated['sku'] ?? null)) {
            unset($validated['sku']);
        }

        $item = MasterItem::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Master barang berhasil dibuat.',
            'data' => [
                'id' => $item->id,
                'sku' => $item->sku,
                'barang' => $item->barang,
                'merk' => $item->merk,
                'tipe' => $item->tipe,
                'alias_name' => $item->alias_name,
                'system_name' => $item->official_name,
            ],
        ], 201);
    }
}
