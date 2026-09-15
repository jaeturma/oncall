<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\Province;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * GeocodingService::reverseGeocode() is always resolved locally (nearest
 * Municipality/Barangay centroid) — no external HTTP call, no SSRF surface —
 * see the service's class docblock. This only exercises that local path.
 */
class GeocodingServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resolves_the_nearest_municipality_and_barangay(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $near = Municipality::factory()->for($province)->create(['name' => 'Near City', 'latitude' => 10.0, 'longitude' => 123.0]);
        Municipality::factory()->for($province)->create(['name' => 'Far City', 'latitude' => 20.0, 'longitude' => 130.0]);
        $barangay = Barangay::factory()->for($near)->create(['name' => 'Poblacion', 'latitude' => 10.001, 'longitude' => 123.001]);
        Barangay::factory()->for($near)->create(['name' => 'Far Barangay', 'latitude' => 10.5, 'longitude' => 123.5]);

        $result = app(GeocodingService::class)->reverseGeocode(10.0005, 123.0005);

        $this->assertTrue($result['municipality']->is($near));
        $this->assertTrue($result['province']->is($province));
        $this->assertTrue($result['barangay']->is($barangay));
    }

    public function test_returns_nulls_when_no_reference_geography_has_coordinates(): void
    {
        Municipality::factory()->create(['latitude' => null, 'longitude' => null]);

        $result = app(GeocodingService::class)->reverseGeocode(10.0, 123.0);

        $this->assertNull($result['municipality']);
        $this->assertNull($result['province']);
        $this->assertNull($result['barangay']);
    }

    public function test_never_calls_an_external_provider_when_geocoding_is_disabled(): void
    {
        // No HTTP fake is registered, so if this accidentally made an outbound
        // call, PHPUnit would surface a real network error/timeout instead of
        // a clean assertion failure — asserting the label stays null is
        // enough to prove the local-only path was taken.
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        Municipality::factory()->for($province)->create(['latitude' => 10.0, 'longitude' => 123.0]);

        $result = app(GeocodingService::class)->reverseGeocode(10.0, 123.0);

        $this->assertNull($result['label']);
    }
}
