<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Province;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase O mobile-API discovery surface: GPS-origin search, admin-bounded
 * radius, the "expand search" affordance, marker/coordinate privacy, and
 * anti-scraping throttling. `tests/Feature/ProviderSearchTest.php` covers
 * the equivalent web-facing behavior; this file is API-response-shape and
 * auth-boundary specific.
 */
class LocationDiscoveryApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_response_never_exposes_a_providers_raw_coordinates(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $municipality = Municipality::factory()->for($province)->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $barangay = Barangay::factory()->for($municipality)->create(['latitude' => 10.001, 'longitude' => 123.001]);
        $service = Service::factory()->create();
        $profile = $this->searchableProfile($province, $municipality, $service, ['barangay_id' => $barangay->id, 'latitude' => 10.5555, 'longitude' => 123.5555]);
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $response = $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk();
        $raw = json_encode($response->json('data'));
        $this->assertStringNotContainsString('10.5555', (string) $raw);
        $this->assertStringNotContainsString('123.5555', (string) $raw);
        $this->assertSame(round((float) $barangay->latitude, 4), round($response->json('data.0.area_marker.latitude'), 4));
    }

    public function test_distance_precision_is_capped_to_limit_triangulation(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $municipality = Municipality::factory()->for($province)->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $service = Service::factory()->create();
        // A coordinate pair unlikely to produce a "clean" rounded distance.
        $this->searchableProfile($province, $municipality, $service, ['latitude' => 10.00137, 'longitude' => 123.00089]);
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $response = $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id, 'latitude' => 10.0, 'longitude' => 123.0]));

        $response->assertOk();
        $distance = $response->json('data.0.distance_km');
        $this->assertSame($distance, round($distance, 1));
    }

    public function test_gps_origin_search_computes_a_real_non_fabricated_distance(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $municipality = Municipality::factory()->for($province)->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $service = Service::factory()->create();
        $this->searchableProfile($province, $municipality, $service, ['latitude' => 10.009, 'longitude' => 123.0]);
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $response = $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id, 'latitude' => 10.0, 'longitude' => 123.0]));

        $response->assertOk();
        $this->assertNotNull($response->json('data.0.distance_km'));
        $this->assertLessThan(2, $response->json('data.0.distance_km'));
    }

    public function test_expand_radius_meta_is_offered_only_when_a_radius_search_returns_nothing(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        // No providers exist at all — an empty, radius-bounded search.
        $response = $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id, 'latitude' => 10.0, 'longitude' => 123.0, 'radius_km' => 5]));

        $response->assertOk()->assertJsonPath('meta.applied_radius_km', 5)->assertJsonPath('meta.expand_radius_km', 10);
    }

    public function test_search_without_radius_never_offers_an_expand_affordance(): void
    {
        $province = Province::factory()->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $response = $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk()->assertJsonPath('meta.expand_radius_km', null);
    }

    public function test_repeated_coordinate_scans_are_throttled(): void
    {
        $province = Province::factory()->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $lastStatus = null;
        for ($i = 0; $i < 31; $i++) {
            $lastStatus = $this->getJson('/api/v1/providers/search?'.http_build_query([
                'help' => 'service:'.$service->id,
                'province_id' => $province->id,
                'latitude' => 10.0 + ($i * 0.001),
                'longitude' => 123.0,
            ]))->getStatusCode();
        }

        $this->assertSame(429, $lastStatus);
    }

    public function test_reverse_geocode_is_throttled(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $lastStatus = null;
        for ($i = 0; $i < 21; $i++) {
            $lastStatus = $this->getJson('/api/v1/locations/reverse-geocode?latitude=10.0&longitude=123.0')->getStatusCode();
        }

        $this->assertSame(429, $lastStatus);
    }

    public function test_huge_radius_is_rejected_not_silently_clamped(): void
    {
        $province = Province::factory()->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $this->getJson('/api/v1/providers/search?'.http_build_query(['help' => 'service:'.$service->id, 'province_id' => $province->id, 'radius_km' => 100000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('radius_km');
    }

    public function test_reverse_geocode_resolves_local_reference_geography(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        Municipality::factory()->for($province)->create(['name' => 'Near City', 'latitude' => 10.0, 'longitude' => 123.0]);
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $response = $this->getJson('/api/v1/locations/reverse-geocode?latitude=10.001&longitude=123.001');

        $response->assertOk()->assertJsonPath('data.municipality.name', 'Near City');
    }

    private function searchableProfile(Province $province, Municipality $municipality, Service $service, array $attributes = []): ProviderProfile
    {
        $provider = User::factory()->serviceProvider()->identityVerified()->create(['status' => UserStatus::Active]);
        ProviderDocument::factory()->for($provider)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create([...$attributes, 'province_id' => $province->id, 'municipality_id' => $municipality->id, 'verification_status' => VerificationStatus::Verified]);
        ProviderService::factory()->create(['provider_profile_id' => $profile->id, 'service_id' => $service->id, 'active' => true]);

        return $profile;
    }
}
