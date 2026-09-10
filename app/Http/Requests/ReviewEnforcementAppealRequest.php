<?php

namespace App\Http\Requests;

use App\Enums\AppealStatus;
use App\Models\EnforcementCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewEnforcementAppealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $case = $this->route('enforcement_case');

        return $case instanceof EnforcementCase && $case->appeal_status === AppealStatus::Requested && ($this->user()?->can('update', $case) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appeal_status' => ['required', Rule::in([AppealStatus::UnderReview->value, AppealStatus::Approved->value, AppealStatus::Denied->value])],
            'resolution' => ['required', 'string', 'max:3000'],
        ];
    }
}
