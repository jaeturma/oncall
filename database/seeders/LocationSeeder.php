<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * A minimal province/city set for the MVP. Barangay coverage (Phase O) is
     * a curated sample of real, well-known barangay names per municipality —
     * not an exhaustive PSGC import (~42,000 barangays nationwide) — which
     * can replace this later.
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

    /**
     * Real, well-known barangay names per municipality (Phase O). Not an
     * exhaustive PSGC import — a representative sample so progressive
     * Province → City → Barangay selection has real data to work with.
     * Coordinates are not individually surveyed; each is offset a small,
     * deterministic amount from the municipality centroid above so distance
     * estimates stay in the right neighborhood without claiming precision
     * the seed data doesn't have.
     *
     * @var array<string, list<string>>
     */
    private const BARANGAYS = [
        'Tagum City' => ['Apokon', 'Bincungan', 'Canocotan', 'Magugpo Poblacion', 'Mankilam', 'Pandapan', 'Visayan Village'],
        'Panabo City' => ['A.O. Floirendo', 'Cagangohan', 'Gredu (Poblacion)', 'Kasilak', 'Little Panay', 'San Vicente', 'Santo Niño'],
        'Island Garden City of Samal' => ['Anonang', 'Babak (Poblacion)', 'Caliclic', 'Cogon', 'Kaputian (Poblacion)', 'Peñaplata (Poblacion)', 'San Remigio'],
        'Santo Tomas' => ['Kimamon', 'Magdum', 'New Katipunan', 'Poblacion', 'San Miguel', 'Tibal-og'],
        'Carmen' => ['Anibongan', 'Katipunan', 'Poblacion', 'Sto. Niño', 'Tuganay'],
        'Kapalong' => ['Florida', 'Gabuyan', 'Mabantao', 'Poblacion', 'Sua-on'],
        'Asuncion' => ['Ganday', 'Kapalong', 'Naboc', 'New Del Monte', 'Poblacion'],
        'Davao City' => ['Agdao', 'Bucana', 'Buhangin', 'Bunawan', 'Matina', 'Poblacion', 'Talomo', 'Toril'],
        'Digos City' => ['Aplaya', 'Dawis', 'Goma', 'Mahayahay', 'Poblacion', 'Zone I (Poblacion)'],
        'Bansalan' => ['Anonang', 'Managa', 'Poblacion', 'Rizal', 'San Roque'],
        'Santa Cruz' => ['Astorga', 'Coronon', 'Darapuay', 'Inawayan', 'Poblacion'],
        'Sulop' => ['Balasinon', 'Kiblagon', 'Poblacion', 'Tagolilong'],
        'Nabunturan' => ['Anislagan', 'Magading', 'Poblacion', 'San Isidro', 'Tagbaros'],
        'Monkayo' => ['Awao', 'Poblacion', 'Tubo-tubo', 'Union'],
        'Mabini' => ['Golden Valley', 'Poblacion', 'San Vicente', 'Tagnanan'],
        'Quezon City' => ['Bagong Pag-asa', 'Batasan Hills', 'Commonwealth', 'Diliman', 'Fairview', 'Novaliches Proper', 'Project 6'],
        'Manila' => ['Binondo', 'Ermita', 'Intramuros', 'Malate', 'Paco', 'Sampaloc', 'Tondo'],
        'Makati' => ['Bel-Air', 'Bangkal', 'Guadalupe Nuevo', 'Poblacion', 'San Lorenzo', 'Urdaneta'],
        'Pasig' => ['Kapitolyo', 'Manggahan', 'Maybunga', 'Ortigas Center', 'Pinagbuhatan', 'San Antonio'],
        'Taguig' => ['Bagumbayan', 'Bambang', 'Fort Bonifacio', 'Hagonoy', 'Ususan', 'Western Bicutan'],
        'Cebu City' => ['Apas', 'Banilad', 'Capitol Site', 'Guadalupe', 'Lahug', 'Mabolo', 'Talamban'],
        'Mandaue City' => ['Bakilid', 'Banilad', 'Centro (Poblacion)', 'Subangdaku', 'Tipolo'],
        'Lapu-Lapu City' => ['Agus', 'Babag', 'Gun-ob', 'Pajo', 'Poblacion'],
        'Talisay City' => ['Biasong', 'Dumlog', 'Lawaan I', 'Poblacion', 'Tabunok'],
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $provinceName => $municipalities) {
            $center = self::PROVINCE_CENTERS[$provinceName];
            $province = Province::query()->firstOrCreate(['name' => $provinceName], ['latitude' => $center['lat'], 'longitude' => $center['lng']]);
            $province->fill(['latitude' => $center['lat'], 'longitude' => $center['lng']])->save();

            foreach ($municipalities as $municipalityName => $attributes) {
                $municipality = $province->municipalities()->updateOrCreate(
                    ['name' => $municipalityName],
                    ['type' => $attributes['type'], 'latitude' => $attributes['lat'], 'longitude' => $attributes['lng']],
                );

                foreach (self::BARANGAYS[$municipalityName] ?? [] as $index => $barangayName) {
                    // Small deterministic offset (not a real survey point) so
                    // barangays within a municipality aren't all stacked on
                    // the exact same coordinate.
                    $angle = ($index / max(count(self::BARANGAYS[$municipalityName]), 1)) * 2 * M_PI;
                    $offset = 0.015;

                    $municipality->barangays()->updateOrCreate(
                        ['name' => $barangayName],
                        ['latitude' => $attributes['lat'] + $offset * cos($angle), 'longitude' => $attributes['lng'] + $offset * sin($angle)],
                    );
                }
            }
        }
    }
}
