<?php

namespace App\Http\Requests\ItemResolutionTicket;

use App\Models\ItemResolutionTicket;
use App\Models\MasterItem;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateMasterItemFromTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]) ?? false;
    }

    public function rules(): array
    {
        return [
            'barang' => ['required', 'string', 'max:255'],
            'merk' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'string', 'max:255'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'resolution_note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barang' => MasterItem::normalizePart((string) $this->input('barang', '')),
            'merk' => MasterItem::normalizePart((string) $this->input('merk', '')),
            'tipe' => MasterItem::normalizePart((string) $this->input('tipe', '')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ticket = $this->route('item_resolution_ticket');

                if ($ticket->status !== ItemResolutionTicket::STATUS_OPEN) {
                    $validator->errors()->add('barang', __('messages.ticket_already_resolved'));

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

                $officialConflict = MasterItem::query()
                    ->where('official_name', $officialName)
                    ->first();

                if ($officialConflict) {
                    $validator->errors()->add(
                        'official_name',
                        __('messages.master_item_duplicate', ['name' => $officialConflict->official_name]),
                    );
                }
            },
        ];
    }
}
