<?php

namespace Tests\Feature;

use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_provider_creates_profile_with_services_and_credentials(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $response = $this->actingAs($provider)->post(route('provider.profiles.store'), ['province_id' => $province->id, 'municipality_id' => $municipality->id, 'bio' => 'Licensed technician', 'service_radius_km' => 30, 'credentials_metadata' => ['TESDA NC II'], 'service_ids' => [$service->id]]);
        $response->assertRedirect(route('provider.dashboard'));
        $this->assertDatabaseHas('provider_profiles', ['user_id' => $provider->id, 'municipality_id' => $municipality->id, 'bio' => 'Licensed technician']);
        $profile = ProviderProfile::whereBelongsTo($provider)->firstOrFail();
        $this->assertDatabaseHas('provider_services', ['provider_profile_id' => $profile->id, 'service_id' => $service->id]);
    }

    public function test_service_finder_cannot_create_provider_profile(): void
    {
        $finder = User::factory()->create();
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $this->actingAs($finder)->post(route('provider.profiles.store'), ['province_id' => $province->id, 'municipality_id' => $municipality->id, 'service_ids' => [$service->id]])->assertForbidden();
        $this->assertDatabaseMissing('provider_profiles', ['user_id' => $finder->id]);
    }

    public function test_provider_cannot_edit_another_providers_profile(): void
    {
        $ownerProfile = ProviderProfile::factory()->create();
        $otherProvider = User::factory()->serviceProvider()->create();
        $this->actingAs($otherProvider)->get(route('provider.profiles.edit', $ownerProfile))->assertForbidden();
    }

    public function test_profile_rejects_municipality_from_another_province(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $province = Province::factory()->create();
        $otherMunicipality = Municipality::factory()->create();
        $service = Service::factory()->create();
        $this->actingAs($provider)->post(route('provider.profiles.store'), ['province_id' => $province->id, 'municipality_id' => $otherMunicipality->id, 'service_ids' => [$service->id]])->assertSessionHasErrors('municipality_id');
        $this->assertDatabaseMissing('provider_profiles', ['user_id' => $provider->id]);
    }

    public function test_profile_owner_toggles_availability(): void
    {
        $profile = ProviderProfile::factory()->create(['available_now' => false]);
        $this->actingAs($profile->user)->patch(route('provider.availability.update', $profile), ['available_now' => true])->assertRedirect();
        $this->assertDatabaseHas('provider_profiles', ['id' => $profile->id, 'available_now' => true]);
    }

    public function test_provider_cannot_toggle_another_providers_availability(): void
    {
        $profile = ProviderProfile::factory()->create(['available_now' => false]);
        $otherProvider = User::factory()->serviceProvider()->create();

        $this->actingAs($otherProvider)
            ->patch(route('provider.availability.update', $profile), ['available_now' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('provider_profiles', ['id' => $profile->id, 'available_now' => false]);
    }

    public function test_provider_updates_profile_and_replaces_services(): void
    {
        $profile = ProviderProfile::factory()->create();
        $oldService = Service::factory()->create();
        $newService = Service::factory()->create();
        $profile->providerServices()->create(['service_id' => $oldService->id]);

        $this->actingAs($profile->user)->put(route('provider.profiles.update', $profile), [
            'province_id' => $profile->province_id,
            'municipality_id' => $profile->municipality_id,
            'bio' => 'Updated provider profile',
            'service_ids' => [$newService->id],
        ])->assertRedirect(route('provider.dashboard'));

        $this->assertDatabaseHas('provider_profiles', ['id' => $profile->id, 'bio' => 'Updated provider profile']);
        $this->assertDatabaseMissing('provider_services', ['provider_profile_id' => $profile->id, 'service_id' => $oldService->id]);
        $this->assertDatabaseHas('provider_services', ['provider_profile_id' => $profile->id, 'service_id' => $newService->id]);
    }

    public function test_provider_cannot_attach_inactive_service(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $inactiveService = Service::factory()->create(['active' => false]);

        $this->actingAs($provider)->post(route('provider.profiles.store'), [
            'province_id' => $province->id,
            'municipality_id' => $municipality->id,
            'service_ids' => [$inactiveService->id],
        ])->assertSessionHasErrors('service_ids.0');

        $this->assertDatabaseMissing('provider_profiles', ['user_id' => $provider->id]);
    }

    public function test_provider_dashboard_escapes_profile_bio(): void
    {
        $profile = ProviderProfile::factory()->create(['bio' => '<script>alert(1)</script>']);
        $service = Service::factory()->create();
        $profile->providerServices()->create(['service_id' => $service->id]);
        $response = $this->actingAs($profile->user)->get(route('provider.dashboard'));
        $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
