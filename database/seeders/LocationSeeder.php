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
     * @var array<string, array<string, string>>
     */
    private const LOCATIONS = [
        'Davao del Norte' => [
            'Tagum City' => 'city',
            'Panabo City' => 'city',
            'Island Garden City of Samal' => 'city',
            'Santo Tomas' => 'municipality',
            'Carmen' => 'municipality',
            'Kapalong' => 'municipality',
            'Asuncion' => 'municipality',
        ],
        'Davao del Sur' => [
            'Davao City' => 'city',
            'Digos City' => 'city',
            'Bansalan' => 'municipality',
            'Santa Cruz' => 'municipality',
            'Sulop' => 'municipality',
        ],
        'Davao de Oro' => [
            'Nabunturan' => 'municipality',
            'Monkayo' => 'municipality',
            'Mabini' => 'municipality',
        ],
        'Metro Manila' => [
            'Quezon City' => 'city',
            'Manila' => 'city',
            'Makati' => 'city',
            'Pasig' => 'city',
            'Taguig' => 'city',
        ],
        'Cebu' => [
            'Cebu City' => 'city',
            'Mandaue City' => 'city',
            'Lapu-Lapu City' => 'city',
            'Talisay City' => 'city',
        ],
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $provinceName => $municipalities) {
            $province = Province::query()->firstOrCreate(['name' => $provinceName]);

            foreach ($municipalities as $municipalityName => $type) {
                $province->municipalities()->updateOrCreate(
                    ['name' => $municipalityName],
                    ['type' => $type],
                );
            }
        }
    }
}
