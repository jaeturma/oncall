<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseJobPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $decision = $this->input('decision');

        return in_array($decision, ['release', 'reverse'], true)
            && $this->user()->can($decision, $this->route('job_payment'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['release', 'reverse'])],
            'reason' => ['required_if:decision,reverse', 'nullable', 'string', 'max:1000'],
        ];
    }
}
