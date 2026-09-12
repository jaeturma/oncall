<?php

namespace App\Http\Requests;

use App\Enums\VerificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VerificationStatus::class)->only([VerificationStatus::Verified, VerificationStatus::Rejected, VerificationStatus::Expired])],
            'notes' => ['nullable', 'required_unless:status,VERIFIED', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
