<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('moderate-reviews') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // WITHDRAWN is deliberately excluded — that transition is
            // reviewer-only (ReviewService::withdraw()), never admin-set.
            'status' => ['required', Rule::in([ReviewStatus::Published->value, ReviewStatus::Hidden->value, ReviewStatus::Removed->value])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
