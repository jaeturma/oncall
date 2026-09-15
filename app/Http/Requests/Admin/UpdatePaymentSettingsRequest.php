<?php

namespace App\Http\Requests\Admin;

use App\Enums\CommissionType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-payment-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payments_enabled' => ['sometimes', 'boolean'],
            'allowed_payment_methods' => ['required', 'array', 'min:1'],
            'allowed_payment_methods.*' => [Rule::enum(PaymentMethod::class)],
            'sandbox_mode' => ['sometimes', 'boolean'],
            'min_transaction_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'max_transaction_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'payment_expiry_minutes' => ['required', 'integer', 'between:5,1440'],
            'receipt_prefix' => ['required', 'string', 'max:10', 'alpha_num'],
            'manual_payment_enabled' => ['sometimes', 'boolean'],
            'refund_window_days' => ['required', 'integer', 'between:1,365'],
            'max_refund_requests_per_payment' => ['required', 'integer', 'between:1,20'],
            'platform_fee_type' => ['required', Rule::enum(CommissionType::class)],
            'platform_fee_value' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('min_transaction_amount') && $this->filled('max_transaction_amount')
                && (float) $this->input('min_transaction_amount') > (float) $this->input('max_transaction_amount')) {
                $validator->errors()->add('max_transaction_amount', 'The maximum transaction amount must be greater than or equal to the minimum.');
            }
        });
    }
}
