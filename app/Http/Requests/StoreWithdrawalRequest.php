<?php

namespace App\Http\Requests;

use App\Models\Withdrawal;
use App\Services\WalletLedger;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Withdrawal::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'decimal:0,2'],
            'payout_method' => ['required', 'string', 'max:40'],
            'payout_reference' => ['required', 'string', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->has('amount')) {
                return;
            }

            $available = app(WalletLedger::class)->availableBalance($this->user());
            if (bccomp((string) $this->input('amount'), $available, 2) === 1) {
                $validator->errors()->add('amount', 'You can withdraw at most PHP '.$available.'.');
            }
        }];
    }
}
