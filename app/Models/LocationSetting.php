<?php

namespace App\Models;

use App\Enums\ProviderLocationPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding the global maps/geocoding switches and
 * admin-bounded search-radius policy. Always go through {@see current()}
 * rather than querying the table directly — mirrors {@see SmsSetting}.
 */
#[Fillable([
    'maps_enabled', 'active_map_provider', 'geocoding_enabled', 'geocoding_base_url',
    'default_search_radius_km', 'max_search_radius_km', 'allowed_radius_choices',
    'default_country', 'location_freshness_days', 'provider_location_policy',
])]
class LocationSetting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'maps_enabled' => true,
        'active_map_provider' => 'OPENSTREETMAP',
        'geocoding_enabled' => false,
        'default_search_radius_km' => 10,
        'max_search_radius_km' => 50,
        'default_country' => 'PH',
        'location_freshness_days' => 90,
        'provider_location_policy' => 'OPTIONAL',
    ];

    protected function casts(): array
    {
        return [
            'maps_enabled' => 'boolean',
            'geocoding_enabled' => 'boolean',
            'allowed_radius_choices' => 'array',
            'provider_location_policy' => ProviderLocationPolicy::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['allowed_radius_choices' => [5, 10, 15, 25, 50]]);
    }

    /**
     * @return list<int>
     */
    public function radiusChoices(): array
    {
        return array_values(array_map(intval(...), $this->allowed_radius_choices ?? [5, 10, 15, 25, 50]));
    }

    public function nextLargerRadius(int $radiusKm): ?int
    {
        $choices = $this->radiusChoices();
        sort($choices);

        foreach ($choices as $choice) {
            if ($choice > $radiusKm && $choice <= $this->max_search_radius_km) {
                return $choice;
            }
        }

        return null;
    }
}
