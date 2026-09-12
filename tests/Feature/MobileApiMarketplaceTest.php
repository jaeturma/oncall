<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase D — the mobile API's allowed marketplace domains actually work end
 * to end, and the forbidden back-office domains have no route to reach at
 * all (not merely a 403 — no `/api/v1/admin*` or `/api/v1/staff*` route
 * exists, matching the phase goal that `/api/v1` is marketplace-only).
 */
class MobileApiMarketplaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_no_back_office_route_exists_under_api_v1(): void
    {
        $paths = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1'))
            ->map(fn ($route) => $route->uri());

        $this->assertNotEmpty($paths);
        foreach (['admin', 'staff', 'accounting', 'budget', 'cashier', 'audit'] as $forbidden) {
            $this->assertTrue($paths->every(fn (string $uri) => ! str_contains($uri, $forbidden)), "no api/v1 route should contain '{$forbidden}'");
        }
    }

    public function test_authenticated_marketplace_user_can_browse_catalog_and_provinces(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Sanctum::actingAs($finder);

        $this->getJson('/api/v1/catalog')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/provinces')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.role', 'SERVICE_FINDER');
    }

    public function test_verified_finder_can_create_a_service_request_and_provider_can_accept_it_into_a_job(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        Sanctum::actingAs($finder);

        $createResponse = $this->postJson("/api/v1/providers/{$profile->id}/service-requests", [
            'service_id' => $service->id,
            'province_id' => $profile->province_id,
            'municipality_id' => $profile->municipality_id,
            'title' => 'Repair the kitchen sink',
            'description' => 'The faucet leaks when it is opened.',
            'urgency' => 'SAME_DAY',
            'budget_min' => 500,
            'budget_max' => 1000,
            'safety_acknowledged' => '1',
        ]);

        $createResponse->assertCreated();
        $serviceRequestId = $createResponse->json('data.id');
        $this->assertDatabaseHas('service_requests', ['id' => $serviceRequestId, 'status' => ServiceRequestStatus::Requested->value]);

        Sanctum::actingAs($profile->user);
        $acceptResponse = $this->patchJson("/api/v1/service-requests/{$serviceRequestId}/accept", ['agreed_price' => 800]);

        $acceptResponse->assertCreated()->assertJsonPath('data.status', JobStatus::Accepted->value);
        $this->assertDatabaseHas('jobs', ['service_request_id' => $serviceRequestId, 'status' => JobStatus::Accepted->value]);
    }

    public function test_another_provider_cannot_view_or_accept_someone_elses_service_request(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        Sanctum::actingAs($finder);
        $serviceRequestId = $this->postJson("/api/v1/providers/{$profile->id}/service-requests", [
            'service_id' => $service->id,
            'province_id' => $profile->province_id,
            'municipality_id' => $profile->municipality_id,
            'title' => 'Repair the kitchen sink',
            'description' => 'The faucet leaks when it is opened.',
            'urgency' => 'SAME_DAY',
            'budget_min' => 500,
            'budget_max' => 1000,
            'safety_acknowledged' => '1',
        ])->json('data.id');

        $otherProvider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        Sanctum::actingAs($otherProvider);

        $this->getJson("/api/v1/service-requests/{$serviceRequestId}")->assertForbidden();
        $this->patchJson("/api/v1/service-requests/{$serviceRequestId}/accept", ['agreed_price' => 800])->assertForbidden();
    }

    private function verifiedUser(): User
    {
        $user = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        ProviderDocument::factory()->create(['user_id' => $user->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        return $user->refresh();
    }

    /** @return array{ProviderProfile, Service} */
    private function requestableProvider(): array
    {
        $provider = User::factory()->serviceProvider()->identityVerified()->create(['status' => UserStatus::Active]);
        ProviderDocument::factory()->create(['user_id' => $provider->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'verification_status' => VerificationStatus::Verified]);
        $service = Service::factory()->create();
        ProviderService::factory()->create(['provider_profile_id' => $profile->id, 'service_id' => $service->id, 'active' => true]);

        return [$profile, $service];
    }
}
