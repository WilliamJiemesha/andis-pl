<?php

namespace App\Http\Requests\InvoiceEntry;

use App\Models\MasterItem;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvoiceEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::INVOICE_HANDLER]) ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'vendor' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cost_code' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'master_item_id' => ['nullable', 'integer', Rule::exists('master_items', 'id')],
            'create_master_inline' => ['nullable', 'boolean'],
            'new_master_barang' => ['nullable', 'string', 'max:255'],
            'new_master_merk' => ['nullable', 'string', 'max:255'],
            'new_master_tipe' => ['nullable', 'string', 'max:255'],
            'new_master_sku' => ['nullable', 'string', 'max:255'],
            'new_master_alias_name' => ['nullable', 'string', 'max:255'],
            'new_master_notes' => ['nullable', 'string'],
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $masterItemId = $this->input('master_item_id');

        $this->merge([
            'vendor' => mb_strtoupper(trim((string) $this->input('vendor', ''))),
            'invoice_number' => trim((string) $this->input('invoice_number', '')),
            'invoice_date' => $this->input('invoice_date') ?: now()->toDateString(),
            'expected_quantity' => (int) $this->input('quantity', 0) ?: null,
            'master_item_id' => blank($masterItemId) ? null : (int) $masterItemId,
            'create_master_inline' => $this->boolean('create_master_inline'),
            'new_master_barang' => MasterItem::normalizePart((string) $this->input('new_master_barang', '')),
            'new_master_merk' => MasterItem::normalizePart((string) $this->input('new_master_merk', '')),
            'new_master_tipe' => MasterItem::normalizePart((string) $this->input('new_master_tipe', '')),
            'new_master_sku' => MasterItem::normalizePart((string) $this->input('new_master_sku', '')),
            'new_master_alias_name' => MasterItem::normalizeAlias((string) $this->input('new_master_alias_name', '')),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasMasterItem = filled($this->input('master_item_id'));
                $createInline = $this->boolean('create_master_inline');

                if (! $hasMasterItem && ! $createInline) {
                    $validator->errors()->add(
                        'master_item_id',
                        __('messages.invoice_entry_match_required'),
                    );
                }

                if ($hasMasterItem && $createInline) {
                    $validator->errors()->add(
                        'create_master_inline',
                        __('messages.invoice_entry_pick_one'),
                    );
                }

                if ($createInline) {
                    foreach (['new_master_barang', 'new_master_merk', 'new_master_tipe', 'new_master_alias_name'] as $field) {
                        if (blank($this->input($field))) {
                            $validator->errors()->add($field, __('messages.new_master_required'));
                        }
                    }

                    if (filled($this->input('new_master_sku')) && MasterItem::query()->where('sku', $this->input('new_master_sku'))->exists()) {
                        $validator->errors()->add('new_master_sku', __('messages.master_item_sku_duplicate'));
                    }
                }

                if ($hasMasterItem) {
                    $masterItem = MasterItem::query()->find($this->input('master_item_id'));

                    if (! $masterItem) {
                        $validator->errors()->add('master_item_id', __('validation.exists', ['attribute' => 'master item']));
                    }
                }
            },
        ];
    }
}
