<?php

namespace App\Http\Requests;

use App\Enums\AppealStatus;
use App\Models\EnforcementCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnforcementAppealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $case = $this->route('enforcement_case');

        return $case instanceof EnforcementCase && $case->user_id === $this->user()?->id && $case->action !== null && $case->appeal_status === AppealStatus::None;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appeal_reason' => ['required', 'string', 'max:3000'],
        ];
    }
}
