<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Job;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every mobile API resource class only gets exercised at serialization time
 * (a fresh, unsaved-relation model can crash a resource in ways static
 * review misses — see the `status` null bug this suite already caught in
 * registration). This smoke-tests every GET endpoint for both marketplace
 * roles and asserts none of them 500.
 */
class MobileApiSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_every_list_endpoint_responds_without_error_for_a_finder(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        foreach ($this->listEndpoints() as $endpoint) {
            $this->getJson($endpoint)->assertSuccessful();
        }
    }

    public function test_every_list_endpoint_responds_without_error_for_a_provider(): void
    {
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        Sanctum::actingAs($provider);

        foreach ($this->listEndpoints() as $endpoint) {
            $this->getJson($endpoint)->assertSuccessful();
        }

        $this->getJson('/api/v1/provider/profile')->assertNotFound();
    }

    public function test_show_endpoints_respond_without_error_for_their_owners(): void
    {
        $province = Province::factory()->create();
        $finder = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        ProviderDocument::factory()->create(['user_id' => $finder->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        $provider = User::factory()->serviceProvider()->identityVerified()->create(['status' => UserStatus::Active]);
        ProviderDocument::factory()->create(['user_id' => $provider->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'province_id' => $province->id, 'verification_status' => VerificationStatus::Verified]);
        $service = Service::factory()->create();
        ProviderService::factory()->create(['provider_profile_id' => $profile->id, 'service_id' => $service->id, 'active' => true]);

        $serviceRequest = ServiceRequest::factory()->create([
            'service_finder_id' => $finder->id,
            'requested_provider_id' => $provider->id,
            'service_id' => $service->id,
            'province_id' => $province->id,
            'municipality_id' => $profile->municipality_id,
            'status' => ServiceRequestStatus::Accepted,
        ]);
        $job = Job::factory()->create([
            'service_request_id' => $serviceRequest->id,
            'service_finder_id' => $finder->id,
            'provider_id' => $provider->id,
            'status' => JobStatus::Accepted,
        ]);

        Sanctum::actingAs($finder);
        $this->getJson("/api/v1/providers/{$profile->id}")->assertSuccessful();
        $this->getJson("/api/v1/service-requests/{$serviceRequest->id}")->assertSuccessful();
        $this->getJson("/api/v1/jobs/{$job->id}")->assertSuccessful();
    }

    /** @return list<string> */
    private function listEndpoints(): array
    {
        return [
            '/api/v1/profile',
            '/api/v1/verification',
            '/api/v1/catalog',
            '/api/v1/provinces',
            '/api/v1/service-requests',
            '/api/v1/jobs',
            '/api/v1/messages',
            '/api/v1/notifications',
            '/api/v1/wallet',
            '/api/v1/wallet/withdrawals',
            '/api/v1/sponsor/referrals',
            '/api/v1/enforcement-cases',
        ];
    }
}
