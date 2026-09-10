<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_service_request(): void
    {
        $profile = ProviderProfile::factory()->create();
        $this->post(route('service-requests.store', $profile), [])->assertRedirect(route('login'));
    }

    public function test_unverified_finder_cannot_open_request_form(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        [$profile] = $this->requestableProvider();
        $this->actingAs($finder)->get(route('service-requests.create', $profile))->assertForbidden();
    }

    public function test_verified_finder_can_create_a_targeted_service_request(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $response = $this->actingAs($finder)->post(route('service-requests.store', $profile), $this->validPayload($profile, $service));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $serviceRequest = ServiceRequest::sole();
        $response->assertRedirect(route('service-requests.show', $serviceRequest));
        $this->assertSame($finder->id, $serviceRequest->service_finder_id);
        $this->assertSame($profile->user_id, $serviceRequest->requested_provider_id);
        $this->assertSame(ServiceRequestStatus::Requested, $serviceRequest->status);
        $this->assertNotNull($serviceRequest->safety_acknowledged_at);
    }

    public function test_service_request_requires_safety_acknowledgement(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $payload = $this->validPayload($profile, $service);
        unset($payload['safety_acknowledged']);

        $this->actingAs($finder)->post(route('service-requests.store', $profile), $payload)->assertSessionHasErrors('safety_acknowledged');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_request_rejects_unoffered_services_and_direct_contact_details(): void
    {
        $finder = $this->verifiedUser();
        [$profile] = $this->requestableProvider();
        $unofferedService = Service::factory()->create();
        $payload = $this->validPayload($profile, $unofferedService, ['description' => 'Email me at helper@example.com']);

        $this->actingAs($finder)->post(route('service-requests.store', $profile), $payload)->assertSessionHasErrors(['service_id', 'description']);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_scheduled_request_requires_future_date_and_valid_budget_range(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $payload = $this->validPayload($profile, $service, ['urgency' => 'SCHEDULED', 'needed_at' => now()->subMinute()->toDateTimeString(), 'budget_min' => 500, 'budget_max' => 100]);

        $this->actingAs($finder)->post(route('service-requests.store', $profile), $payload)->assertSessionHasErrors(['needed_at', 'budget_max']);
    }

    public function test_targeted_provider_can_accept_but_another_provider_cannot_respond(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $serviceRequest = $this->createRequest($finder, $profile, $service);
        $otherProvider = User::factory()->serviceProvider()->create();

        $this->actingAs($otherProvider)->get(route('service-requests.show', $serviceRequest))->assertForbidden();
        $this->actingAs($otherProvider)->patch(route('service-requests.accept', $serviceRequest), ['agreed_price' => 800])->assertForbidden();
        $this->actingAs($profile->user)->patch(route('service-requests.accept', $serviceRequest), ['agreed_price' => 800])->assertRedirect();
        $this->assertSame(ServiceRequestStatus::Accepted, $serviceRequest->fresh()->status);
        $this->actingAs($profile->user)->patch(route('service-requests.decline', $serviceRequest))->assertForbidden();
    }

    public function test_targeted_provider_can_decline_request_back_to_searching(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $serviceRequest = $this->createRequest($finder, $profile, $service);

        $this->actingAs($profile->user)->patch(route('service-requests.decline', $serviceRequest))->assertRedirect(route('service-requests.index'));
        $serviceRequest->refresh();
        $this->assertSame(ServiceRequestStatus::Searching, $serviceRequest->status);
        $this->assertNull($serviceRequest->requested_provider_id);
    }

    public function test_only_owner_can_cancel_a_pending_request(): void
    {
        $finder = $this->verifiedUser();
        [$profile, $service] = $this->requestableProvider();
        $serviceRequest = $this->createRequest($finder, $profile, $service);

        $this->actingAs($this->verifiedUser())->patch(route('service-requests.cancel', $serviceRequest))->assertForbidden();
        $this->actingAs($finder)->patch(route('service-requests.cancel', $serviceRequest))->assertRedirect();
        $this->assertSame(ServiceRequestStatus::Cancelled, $serviceRequest->fresh()->status);
    }

    private function verifiedUser(): User
    {
        $user = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder]);
        ProviderDocument::factory()->create(['user_id' => $user->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        return $user->refresh();
    }

    /** @return array{ProviderProfile, Service} */
    private function requestableProvider(): array
    {
        $provider = User::factory()->serviceProvider()->identityVerified()->create();
        ProviderDocument::factory()->create(['user_id' => $provider->id, 'status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'verification_status' => VerificationStatus::Verified]);
        $service = Service::factory()->create();
        ProviderService::factory()->create(['provider_profile_id' => $profile->id, 'service_id' => $service->id, 'active' => true]);

        return [$profile, $service];
    }

    /** @return array<string, mixed> */
    private function validPayload(ProviderProfile $profile, Service $service, array $overrides = []): array
    {
        return ['service_id' => $service->id, 'province_id' => $profile->province_id, 'municipality_id' => $profile->municipality_id, 'title' => 'Repair the kitchen sink', 'description' => 'The faucet leaks when it is opened.', 'urgency' => 'SAME_DAY', 'budget_min' => 500, 'budget_max' => 1000, 'safety_acknowledged' => '1', ...$overrides];
    }

    private function createRequest(User $finder, ProviderProfile $profile, Service $service): ServiceRequest
    {
        $attributes = $this->validPayload($profile, $service);
        unset($attributes['safety_acknowledged']);

        return ServiceRequest::factory()->create([...$attributes, 'service_finder_id' => $finder->id, 'requested_provider_id' => $profile->user_id, 'status' => ServiceRequestStatus::Requested]);
    }
}
