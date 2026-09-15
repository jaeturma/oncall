<?php

namespace App\Http\Requests;

use App\Models\Refund;
use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Refund::class, $this->route('job_payment')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
