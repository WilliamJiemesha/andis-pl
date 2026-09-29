<?php

namespace App\Http\Requests\ItemResolutionTicket;

use App\Models\ItemResolutionTicket;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkTicketMasterItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]) ?? false;
    }

    public function rules(): array
    {
        return [
            'master_item_id' => ['required', 'integer', Rule::exists('master_items', 'id')],
            'resolution_note' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $ticket = $this->route('item_resolution_ticket');

                if ($ticket->status !== ItemResolutionTicket::STATUS_OPEN) {
                    $validator->errors()->add('master_item_id', __('messages.ticket_already_resolved'));
                }
            },
        ];
    }
}
