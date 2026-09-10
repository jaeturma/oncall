<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::ServiceProvider && ! $this->user()->providerProfile()->exists();
    }

    public function rules(): array
    {
        return ['province_id' => ['required', 'exists:provinces,id'], 'municipality_id' => ['required', Rule::exists('municipalities', 'id')->where('province_id', $this->integer('province_id'))], 'bio' => ['nullable', 'string', 'max:2000'], 'service_radius_km' => ['nullable', 'integer', 'min:1', 'max:500'], 'credentials_metadata' => ['nullable', 'array', 'max:20'], 'credentials_metadata.*' => ['nullable', 'string', 'max:255'], 'service_ids' => ['required', 'array', 'min:1'], 'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('active', true)]];
    }
}
