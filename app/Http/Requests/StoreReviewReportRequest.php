<?php

namespace App\Http\Requests;

use App\Enums\ReviewReportCategory;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && ($this->user()?->can('report', $review) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ReviewReportCategory::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
