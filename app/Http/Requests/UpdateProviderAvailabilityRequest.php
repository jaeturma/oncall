<?php

namespace App\Http\Requests;

use App\Enums\AvailabilityStatus;
use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Models\ProviderProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProviderAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('provider_profile');

        return $this->user()?->role === UserRole::ServiceProvider
            && $profile instanceof ProviderProfile
            && $profile->user_id === $this->user()->id
            && ! $this->user()->isCapabilityRestricted(RestrictedCapability::AvailabilityChanges);
    }

    public function rules(): array
    {
        return ['availability_status' => ['required', new Enum(AvailabilityStatus::class)]];
    }
}
