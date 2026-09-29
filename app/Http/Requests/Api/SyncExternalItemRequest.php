<?php

namespace App\Http\Requests\Api;

use App\Models\MasterItem;
use App\Services\ExternalItemSyncService;
use App\Support\PriceCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncExternalItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barang' => ['required', 'string', 'max:10'],
            'tipe' => ['required', 'string', 'max:7'],
            'merk' => ['nullable', 'string', 'max:5'],
            'official_name' => ['nullable', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:255'],
            'sku_code' => ['nullable', 'string', 'max:255'],
            'cost_code' => ['nullable', 'string', 'max:255'],
            'pc' => ['nullable', 'string', 'max:255'],
            'kode_modal' => ['nullable', 'string', 'max:255'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price_code' => ['nullable', 'string', 'max:255'],
            'harga_jual_code' => ['nullable', 'string', 'max:255'],
            'selling_price_needs_review' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'jumlah' => ['nullable', 'integer', 'min:1'],
            'kode_supplier' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $costCode = $this->input('cost_code', $this->input('pc', $this->input('kode_modal', '')));
        $amount = $this->input('amount', $this->input('jumlah'));
        $sellingPriceCode = $this->input('selling_price_code', $this->input('harga_jual_code', ''));
        $sellingPriceNeedsReview = MasterItem::normalizePart((string) $sellingPriceCode) === '---';
        $sellingPrice = $sellingPriceNeedsReview
            ? null
            : ($this->input('selling_price') ?: PriceCode::decode((string) $sellingPriceCode));

        $this->merge([
            'barang' => MasterItem::normalizePart((string) $this->input('barang', '')),
            'tipe' => MasterItem::normalizePart((string) $this->input('tipe', '')),
            'merk' => MasterItem::normalizePart((string) $this->input('merk', '')),
            'official_name' => MasterItem::normalizeAlias((string) $this->input('official_name', '')),
            'alias' => MasterItem::normalizeAlias((string) $this->input('alias', '')),
            'sku_code' => MasterItem::normalizePart((string) $this->input('sku_code', '')),
            'cost_code' => MasterItem::normalizePart((string) $costCode),
            'pc' => MasterItem::normalizePart((string) $costCode),
            'kode_modal' => MasterItem::normalizePart((string) $costCode),
            'selling_price' => $sellingPrice,
            'selling_price_code' => MasterItem::normalizePart((string) $sellingPriceCode),
            'harga_jual_code' => MasterItem::normalizePart((string) $sellingPriceCode),
            'selling_price_needs_review' => $sellingPriceNeedsReview,
            'amount' => $amount,
            'jumlah' => $amount,
            'kode_supplier' => MasterItem::normalizePart((string) $this->input('kode_supplier', 'AVFP')),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var ExternalItemSyncService $syncService */
                $syncService = app(ExternalItemSyncService::class);

                $existingItem = $syncService->findExistingItem(
                    $this->string('barang')->value(),
                    $this->string('merk')->value(),
                    $this->string('tipe')->value(),
                );

                if (! $existingItem && blank($this->string('cost_code')->value())) {
                    $validator->errors()->add('cost_code', 'Kode modal wajib diisi saat membuat master barang baru.');
                }

                if (! $existingItem && blank($this->input('selling_price')) && ! $this->boolean('selling_price_needs_review')) {
                    $validator->errors()->add('selling_price', 'Harga jual wajib diisi saat membuat master barang baru.');
                }
            },
        ];
    }
}
