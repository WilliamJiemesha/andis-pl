<?php

namespace App\Http\Requests\PriceReviewTask;

use App\Models\PriceReviewTask;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewPriceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN, Role::PRICE_HANDLER]) ?? false;
    }

    public function rules(): array
    {
        return [
            'new_selling_price' => ['required', 'numeric', 'min:0'],
            'review_note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = preg_replace('/\D+/', '', (string) $this->input('new_selling_price'));

        $this->merge([
            'new_selling_price' => $normalized === '' ? null : $normalized,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $task = $this->route('price_review_task');

                if ($task->status !== PriceReviewTask::STATUS_OPEN) {
                    $validator->errors()->add('new_selling_price', __('messages.price_review_already_reviewed'));
                }
            },
        ];
    }
}
