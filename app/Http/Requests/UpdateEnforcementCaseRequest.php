<?php

namespace App\Http\Requests;

use App\Enums\AppealStatus;
use App\Enums\EnforcementAction;
use App\Enums\RestrictedCapability;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnforcementCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $case = $this->route('enforcement_case');

        return $case instanceof EnforcementCase && ($this->user()?->can('update', $case) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(EnforcementAction::class)],
            'severity' => ['required', Rule::enum(ViolationSeverity::class)],
            'restricted_capabilities' => ['nullable', 'array'],
            'restricted_capabilities.*' => [Rule::enum(RestrictedCapability::class)],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'resolution' => ['nullable', 'string', 'max:3000'],
            'appeal_status' => ['nullable', Rule::enum(AppealStatus::class), Rule::notIn([AppealStatus::None->value, AppealStatus::Requested->value])],
        ];
    }
}
