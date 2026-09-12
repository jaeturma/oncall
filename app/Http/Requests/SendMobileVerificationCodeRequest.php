<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMobileVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', 'regex:/^(09|\+639)\d{9}$/', Rule::unique('users', 'phone')->ignore($this->user())],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Enter a Philippine mobile number, e.g. 09171234567.'];
    }
}
