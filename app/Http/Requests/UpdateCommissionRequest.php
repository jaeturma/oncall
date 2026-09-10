<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $decision = $this->input('decision');

        return in_array($decision, ['approve', 'reverse'], true)
            && $this->user()->can($decision, $this->route('commission'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reverse'])],
            'reason' => ['required_if:decision,reverse', 'nullable', 'string', 'max:1000'],
        ];
    }
}
