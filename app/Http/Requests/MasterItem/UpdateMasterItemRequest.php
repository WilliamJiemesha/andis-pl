<?php

namespace App\Http\Requests\MasterItem;

use App\Models\MasterItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMasterItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'price_handler']) ?? false;
    }

    public function rules(): array
    {
        return [
            'barang' => ['required', 'string', 'max:255'],
            'merk' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'string', 'max:255'],
            'alias_name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barang' => MasterItem::normalizePart((string) $this->input('barang', '')),
            'merk' => MasterItem::normalizePart((string) $this->input('merk', '')),
            'tipe' => MasterItem::normalizePart((string) $this->input('tipe', '')),
            'alias_name' => MasterItem::normalizeAlias((string) $this->input('alias_name', '')),
            'sku' => MasterItem::normalizePart((string) $this->input('sku', '')),
            'selling_price' => $this->normalizeMoney($this->input('selling_price')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function normalizeMoney(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $value);

        return $normalized === null || $normalized === '' ? null : $normalized;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $masterItem = $this->route('master_item');
                $officialName = MasterItem::buildOfficialName(
                    $this->string('barang')->value(),
                    $this->string('merk')->value(),
                    $this->string('tipe')->value(),
                );

                $conflict = MasterItem::query()
                    ->where('id', '!=', $masterItem->id)
                    ->where('barang', $this->string('barang')->value())
                    ->where('merk', $this->string('merk')->value())
                    ->where('tipe', $this->string('tipe')->value())
                    ->first();

                if ($conflict) {
                    $validator->errors()->add(
                        'barang',
                        __('messages.master_item_duplicate', ['name' => $conflict->official_name]),
                    );
                }

                $officialConflict = MasterItem::query()
                    ->where('id', '!=', $masterItem->id)
                    ->where('official_name', $officialName)
                    ->first();

                if ($officialConflict) {
                    $validator->errors()->add(
                        'official_name',
                        __('messages.master_item_duplicate', ['name' => $officialConflict->official_name]),
                    );
                }

                if (filled($this->input('sku')) && MasterItem::query()
                    ->where('id', '!=', $masterItem->id)
                    ->where('sku', $this->string('sku')->value())
                    ->exists()) {
                    $validator->errors()->add('sku', __('messages.master_item_sku_duplicate'));
                }
            },
        ];
    }
}
