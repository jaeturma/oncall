<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-review-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reviews_enabled' => ['sometimes', 'boolean'],
            'review_window_days' => ['required', 'integer', 'between:1,365'],
            'comment_required' => ['sometimes', 'boolean'],
            'max_comment_length' => ['required', 'integer', 'between:100,5000'],
            'provider_response_enabled' => ['sometimes', 'boolean'],
            'response_max_length' => ['required', 'integer', 'between:100,3000'],
            'reviews_per_page' => ['required', 'integer', 'between:5,50'],
        ];
    }
}
