<?php

namespace App\Http\Requests\MasterItem;

use App\Models\MasterItem;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuickStoreMasterItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::INVOICE_HANDLER, Role::PRICE_HANDLER]) ?? false;
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
            'alias_name' => MasterItem::normalizeAlias((string) $this->input('alias_name', '')),
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

                $officialName = MasterItem::buildOfficialName(
                    $this->string('barang')->value(),
                    $this->string('merk')->value(),
                    $this->string('tipe')->value(),
                );

                $existing = MasterItem::query()
                    ->where('barang', $this->string('barang')->value())
                    ->where('merk', $this->string('merk')->value())
                    ->where('tipe', $this->string('tipe')->value())
                    ->first();

                if ($existing) {
                    $validator->errors()->add(
                        'barang',
                        __('messages.master_item_duplicate', ['name' => $existing->official_name]),
                    );
                }

                $existingOfficialName = MasterItem::query()
                    ->where('official_name', $officialName)
                    ->first();

                if ($existingOfficialName) {
                    $validator->errors()->add(
                        'official_name',
                        __('messages.master_item_duplicate', ['name' => $existingOfficialName->official_name]),
                    );
                }

                if (filled($this->input('sku')) && MasterItem::query()->where('sku', $this->string('sku')->value())->exists()) {
                    $validator->errors()->add('sku', __('messages.master_item_sku_duplicate'));
                }
            },
        ];
    }
}
