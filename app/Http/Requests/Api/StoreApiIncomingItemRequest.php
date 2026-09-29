<?php

namespace App\Http\Requests\Api;

use App\Models\MasterItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApiIncomingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_masuk' => ['nullable', 'date'],
            'kode_supplier' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barang' => ['nullable', 'string', 'max:255'],
            'merk' => ['nullable', 'string', 'max:255'],
            'tipe' => ['nullable', 'string', 'max:255'],
            'alias_name' => ['nullable', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'kode_modal' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tanggal_masuk' => $this->input('tanggal_masuk') ?: now()->toDateString(),
            'kode_supplier' => MasterItem::normalizePart((string) $this->input('kode_supplier', '')),
            'sku' => MasterItem::normalizePart((string) $this->input('sku', '')),
            'barang' => $this->filled('barang') ? MasterItem::normalizePart((string) $this->input('barang')) : null,
            'merk' => $this->filled('merk') ? MasterItem::normalizePart((string) $this->input('merk')) : null,
            'tipe' => $this->filled('tipe') ? MasterItem::normalizePart((string) $this->input('tipe')) : null,
            'alias_name' => MasterItem::normalizeAlias((string) $this->input('alias_name', $this->input('alias', ''))),
            'alias' => MasterItem::normalizeAlias((string) $this->input('alias', $this->input('alias_name', ''))),
            'kode_modal' => MasterItem::normalizePart((string) $this->input('kode_modal', '')),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $item = filled($this->input('sku'))
                    ? MasterItem::query()->where('sku', $this->string('sku')->value())->first()
                    : null;

                if (! $item && filled($this->input('barang')) && filled($this->input('merk')) && filled($this->input('tipe'))) {
                    $item = MasterItem::query()
                        ->where('barang', $this->string('barang')->value())
                        ->where('merk', $this->string('merk')->value())
                        ->where('tipe', $this->string('tipe')->value())
                        ->first();
                }

                if (! $item) {
                    $validator->errors()->add('sku', 'Master barang belum ditemukan. Kirim SKU atau Barang, Merk, dan Tipe yang cocok.');

                    return;
                }

                foreach (['barang', 'merk', 'tipe'] as $field) {
                    $incoming = $this->input($field);

                    if ($incoming !== null && $item->{$field} !== $incoming) {
                        $validator->errors()->add($field, ucfirst($field).' tidak cocok dengan SKU yang dikirim.');
                    }
                }
            },
        ];
    }
}
