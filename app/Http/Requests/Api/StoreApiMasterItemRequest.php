<?php

namespace App\Http\Requests\Api;

use App\Models\MasterItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApiMasterItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barang' => ['required', 'string', 'max:255'],
            'merk' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'string', 'max:255'],
            'alias_name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barang' => MasterItem::normalizePart((string) $this->input('barang', '')),
            'merk' => MasterItem::normalizePart((string) $this->input('merk', '')),
            'tipe' => MasterItem::normalizePart((string) $this->input('tipe', '')),
            'alias_name' => MasterItem::normalizeAlias((string) $this->input('alias_name', $this->input('alias', ''))),
            'sku' => MasterItem::normalizePart((string) $this->input('sku', '')),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (filled($this->input('sku')) && MasterItem::query()->where('sku', $this->string('sku')->value())->exists()) {
                    $validator->errors()->add('sku', 'SKU sudah dipakai.');
                }

                if (MasterItem::query()
                    ->where('barang', $this->string('barang')->value())
                    ->where('merk', $this->string('merk')->value())
                    ->where('tipe', $this->string('tipe')->value())
                    ->exists()) {
                    $validator->errors()->add('barang', 'Barang, merk, dan tipe sudah ada.');
                }
            },
        ];
    }
}
