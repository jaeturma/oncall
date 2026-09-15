<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\PaymentSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmJobPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('confirm', $this->route('job_payment'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('payment_method')) {
            $this->merge(['payment_method' => strtoupper(trim((string) $this->input('payment_method')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class), Rule::in(PaymentSetting::current()->allowed_payment_methods)],
            'payment_reference' => ['required', 'string', 'max:120'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
