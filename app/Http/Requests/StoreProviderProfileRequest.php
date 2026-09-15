<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\LocationSetting;
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
        return [
            'province_id' => ['required', 'exists:provinces,id'],
            'municipality_id' => ['required', Rule::exists('municipalities', 'id')->where('province_id', $this->integer('province_id'))],
            'barangay_id' => ['nullable', Rule::exists('barangays', 'id')->where('municipality_id', $this->integer('municipality_id'))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'service_radius_km' => ['nullable', 'integer', Rule::in(LocationSetting::current()->radiusChoices())],
            'credentials_metadata' => ['nullable', 'array', 'max:20'],
            'credentials_metadata.*' => ['nullable', 'string', 'max:255'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('active', true)],
        ];
    }
}
