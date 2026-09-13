<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\MobileNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendMobileVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('phone')) {
                return;
            }

            $normalized = app(MobileNumberNormalizer::class)->normalize($this->string('phone')->value());
            if ($normalized === null) {
                $validator->errors()->add('phone', 'Enter a Philippine mobile number, e.g. 09171234567.');

                return;
            }

            $exists = User::query()
                ->where('phone', $normalized)
                ->when($this->user(), fn ($query) => $query->whereKeyNot($this->user()))
                ->exists();

            if ($exists) {
                $validator->errors()->add('phone', 'This mobile number is already registered to another account.');
            }
        }];
    }
}
