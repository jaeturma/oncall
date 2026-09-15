<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProviderLocationPolicy;
use App\Rules\SafeGeocodingUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the combined location/map settings form. `max_search_radius_km`
 * bounds `default_search_radius_km` and every entry of
 * `allowed_radius_choices` in {@see after()} — an admin cannot configure a
 * default or a selectable choice above the hard maximum they just set.
 */
class UpdateLocationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-location-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'maps_enabled' => ['sometimes', 'boolean'],
            'active_map_provider' => ['required', 'string', 'in:OPENSTREETMAP'],

            'geocoding_enabled' => ['sometimes', 'boolean'],
            'geocoding_base_url' => ['nullable', 'required_if:geocoding_enabled,1', 'string', 'max:500', 'url', new SafeGeocodingUrl],

            'default_search_radius_km' => ['required', 'integer', 'min:1', 'max:500'],
            'max_search_radius_km' => ['required', 'integer', 'min:1', 'max:500'],
            'allowed_radius_choices' => ['required', 'string', 'max:200'],

            'default_country' => ['required', 'string', 'size:2'],
            'location_freshness_days' => ['required', 'integer', 'between:1,365'],
            'provider_location_policy' => ['required', Rule::enum(ProviderLocationPolicy::class)],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->hasAny(['allowed_radius_choices', 'max_search_radius_km', 'default_search_radius_km'])) {
                return;
            }

            $choices = $this->radiusChoices();
            $max = $this->integer('max_search_radius_km');

            if ($choices === [] || $choices !== array_unique($choices)) {
                $validator->errors()->add('allowed_radius_choices', 'Enter a comma-separated list of distinct whole-number kilometer values.');

                return;
            }

            foreach ($choices as $choice) {
                if ($choice < 1 || $choice > $max) {
                    $validator->errors()->add('allowed_radius_choices', "Every radius choice must be between 1 and the max search radius ({$max} km).");

                    return;
                }
            }

            if ($this->integer('default_search_radius_km') > $max) {
                $validator->errors()->add('default_search_radius_km', 'The default search radius cannot exceed the max search radius.');
            }
        }];
    }

    /**
     * @return list<int>
     */
    public function radiusChoices(): array
    {
        return collect(explode(',', (string) $this->string('allowed_radius_choices')))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '' && ctype_digit($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }
}
