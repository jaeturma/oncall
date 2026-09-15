<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('refund'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'notes' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }
}
