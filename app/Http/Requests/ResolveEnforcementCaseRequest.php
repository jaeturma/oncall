<?php

namespace App\Http\Requests;

use App\Models\EnforcementCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResolveEnforcementCaseRequest extends FormRequest
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
            'resolution' => ['required', 'string', 'max:3000'],
        ];
    }
}
