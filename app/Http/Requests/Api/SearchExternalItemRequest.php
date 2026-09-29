<?php

namespace App\Http\Requests\Api;

use App\Models\MasterItem;
use Illuminate\Foundation\Http\FormRequest;

class SearchExternalItemRequest extends FormRequest
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
            'merk' => ['required', 'string', 'max:5'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barang' => MasterItem::normalizePart((string) $this->input('barang', '')),
            'tipe' => MasterItem::normalizePart((string) $this->input('tipe', '')),
            'merk' => MasterItem::normalizePart((string) $this->input('merk', '')),
        ]);
    }
}
