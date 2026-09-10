<?php

namespace App\Http\Requests;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('dispute'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['start_review', 'uphold', 'reject', 'partial'])],
            'resolution' => ['required_unless:action,start_review', 'nullable', 'string', 'max:2000'],
            'refund_amount' => ['required_if:action,partial', 'nullable', 'numeric', 'min:0.01'],
            'open_enforcement' => ['boolean'],
            'enforce_against' => ['required_if:open_enforcement,1', 'nullable', Rule::in(['raiser', 'respondent'])],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('action') !== 'partial' || $validator->errors()->has('refund_amount')) {
                return;
            }

            /** @var Dispute $dispute */
            $dispute = $this->route('dispute');
            $net = $dispute->job->jobPayment?->net_amount;

            if ($net !== null && bccomp((string) $this->input('refund_amount'), (string) $net, 2) === 1) {
                $validator->errors()->add('refund_amount', 'The refund cannot exceed the provider net of PHP '.$net.'.');
            }
        }];
    }
}
