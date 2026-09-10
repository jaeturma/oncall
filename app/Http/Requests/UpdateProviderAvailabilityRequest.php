<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\ProviderProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('provider_profile');

        return $this->user()?->role === UserRole::ServiceProvider && $profile instanceof ProviderProfile && $profile->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return ['available_now' => ['required', 'boolean']];
    }
}
