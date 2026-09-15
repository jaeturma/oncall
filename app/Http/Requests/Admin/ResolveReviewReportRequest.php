<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-review-reports') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ReportStatus::Resolved->value, ReportStatus::Dismissed->value])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
