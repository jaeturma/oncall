<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('withdrawal'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'return', 'reject'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if (in_array($this->input('decision'), ['return', 'reject'], true) && trim((string) $this->input('notes')) === '') {
                $validator->errors()->add('notes', 'A reason is required when returning or rejecting a withdrawal.');
            }
        }];
    }
}
