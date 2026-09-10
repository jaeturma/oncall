<?php

namespace App\Http\Requests;

use App\Enums\CommissionType;
use App\Models\AccountType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class StoreAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AccountType::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $existing = $this->route('account_type');

        return [
            'name' => [
                'required', 'string', 'max:120',
                function (string $attribute, mixed $value, Closure $fail) use ($existing): void {
                    $taken = AccountType::query()
                        ->where('slug', Str::slug((string) $value))
                        ->when($existing instanceof AccountType, fn ($query) => $query->whereKeyNot($existing->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('An account type with this name already exists.');
                    }
                },
            ],
            'registration_fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'sponsor_commission_type' => ['required', new Enum(CommissionType::class)],
            'sponsor_commission_value' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'requires_identity_verification' => ['boolean'],
            'active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->enum('sponsor_commission_type', CommissionType::class) === CommissionType::Percentage
                && (float) $this->input('sponsor_commission_value') > 100) {
                $validator->errors()->add('sponsor_commission_value', 'A percentage commission cannot exceed 100.');
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'slug' => Str::slug($this->string('name')->trim()->value()),
            'registration_fee' => $this->input('registration_fee'),
            'sponsor_commission_type' => $this->input('sponsor_commission_type'),
            'sponsor_commission_value' => $this->input('sponsor_commission_value'),
            'requires_identity_verification' => $this->boolean('requires_identity_verification'),
            'active' => $this->boolean('active'),
        ];
    }
}
