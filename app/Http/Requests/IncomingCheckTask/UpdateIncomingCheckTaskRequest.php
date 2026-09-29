<?php

namespace App\Http\Requests\IncomingCheckTask;

use App\Models\IncomingCheckTask;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIncomingCheckTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::INCOMING_CHECKER]) ?? false;
    }

    public function rules(): array
    {
        return [
            'checked_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function resolvedStatus(): string
    {
        $task = $this->route('incoming_check_task');
        $checkedQuantity = (int) $this->input('checked_quantity');

        return $checkedQuantity === (int) $task->expected_quantity
            ? IncomingCheckTask::STATUS_OK
            : IncomingCheckTask::STATUS_MISMATCH;
    }
}
