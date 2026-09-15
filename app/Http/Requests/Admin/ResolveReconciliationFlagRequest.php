<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveReconciliationFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-payment-reconciliation') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'max:1000'],
        ];
    }
}
