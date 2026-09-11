<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * A minimal province/city set for the MVP. Barangay is intentionally
     * out of scope for Phase 1; the full PSGC dataset can replace this later.
     *
     * Coordinates are approximate town/city-center points (not live GPS) used
     * only to estimate "how far away" a provider is. Good enough for demo
     * sorting, not for turn-by-turn navigation.
     *
     * @var array<string, array<string, array{type: string, lat: float, lng: float}>>
     */
    private const LOCATIONS = [
        'Davao del Norte' => [
            'Tagum City' => ['type' => 'city', 'lat' => 7.4479, 'lng' => 125.8072],
            'Panabo City' => ['type' => 'city', 'lat' => 7.3078, 'lng' => 125.6844],
            'Island Garden City of Samal' => ['type' => 'city', 'lat' => 7.0611, 'lng' => 125.7075],
            'Santo Tomas' => ['type' => 'municipality', 'lat' => 7.5305, 'lng' => 125.6217],
            'Carmen' => ['type' => 'municipality', 'lat' => 7.4188, 'lng' => 125.7086],
            'Kapalong' => ['type' => 'municipality', 'lat' => 7.6339, 'lng' => 125.6874],
            'Asuncion' => ['type' => 'municipality', 'lat' => 7.6144, 'lng' => 125.7397],
        ],
        'Davao del Sur' => [
            'Davao City' => ['type' => 'city', 'lat' => 7.1907, 'lng' => 125.4553],
            'Digos City' => ['type' => 'city', 'lat' => 6.7495, 'lng' => 125.3572],
            'Bansalan' => ['type' => 'municipality', 'lat' => 6.8081, 'lng' => 125.2478],
            'Santa Cruz' => ['type' => 'municipality', 'lat' => 6.8375, 'lng' => 125.4189],
            'Sulop' => ['type' => 'municipality', 'lat' => 6.5211, 'lng' => 125.3894],
        ],
        'Davao de Oro' => [
            'Nabunturan' => ['type' => 'municipality', 'lat' => 7.6072, 'lng' => 125.9678],
            'Monkayo' => ['type' => 'municipality', 'lat' => 7.7647, 'lng' => 126.0562],
            'Mabini' => ['type' => 'municipality', 'lat' => 7.2842, 'lng' => 126.2664],
        ],
        'Metro Manila' => [
            'Quezon City' => ['type' => 'city', 'lat' => 14.6760, 'lng' => 121.0437],
            'Manila' => ['type' => 'city', 'lat' => 14.5995, 'lng' => 120.9842],
            'Makati' => ['type' => 'city', 'lat' => 14.5547, 'lng' => 121.0244],
            'Pasig' => ['type' => 'city', 'lat' => 14.5764, 'lng' => 121.0851],
            'Taguig' => ['type' => 'city', 'lat' => 14.5176, 'lng' => 121.0509],
        ],
        'Cebu' => [
            'Cebu City' => ['type' => 'city', 'lat' => 10.3157, 'lng' => 123.8854],
            'Mandaue City' => ['type' => 'city', 'lat' => 10.3236, 'lng' => 123.9224],
            'Lapu-Lapu City' => ['type' => 'city', 'lat' => 10.3103, 'lng' => 123.9494],
            'Talisay City' => ['type' => 'city', 'lat' => 10.2447, 'lng' => 123.8494],
        ],
    ];

    /**
     * Province-level reference point, used only when a search does not
     * narrow to a specific municipality. Set to that province's capital/most
     * populous seeded city.
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private const PROVINCE_CENTERS = [
        'Davao del Norte' => ['lat' => 7.4479, 'lng' => 125.8072],
        'Davao del Sur' => ['lat' => 7.1907, 'lng' => 125.4553],
        'Davao de Oro' => ['lat' => 7.6072, 'lng' => 125.9678],
        'Metro Manila' => ['lat' => 14.5995, 'lng' => 120.9842],
        'Cebu' => ['lat' => 10.3157, 'lng' => 123.8854],
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $provinceName => $municipalities) {
            $center = self::PROVINCE_CENTERS[$provinceName];
            $province = Province::query()->firstOrCreate(['name' => $provinceName], ['latitude' => $center['lat'], 'longitude' => $center['lng']]);
            $province->fill(['latitude' => $center['lat'], 'longitude' => $center['lng']])->save();

            foreach ($municipalities as $municipalityName => $attributes) {
                $province->municipalities()->updateOrCreate(
                    ['name' => $municipalityName],
                    ['type' => $attributes['type'], 'latitude' => $attributes['lat'], 'longitude' => $attributes['lng']],
                );
            }
        }
    }
}
