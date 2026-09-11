<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_renders_two_primary_search_selectors(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Home Repair']);
        $service = Service::factory()->for($category, 'category')->create(['name' => 'Plumbing']);
        $province = Province::factory()->create(['name' => 'Cebu']);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('What help do you need?')
            ->assertSee('Where do you need help?')
            ->assertSee('Find Help')
            ->assertSee($service->name)
            ->assertSee($province->name);
    }

    public function test_guest_searches_service_and_sees_anonymized_provider_without_contact_details(): void
    {
        $province = Province::factory()->create(['name' => 'Cebu']);
        $municipality = Municipality::factory()->for($province)->create(['name' => 'Lapu-Lapu City']);
        $service = Service::factory()->create(['name' => 'Emergency Plumbing']);
        $provider = User::factory()->serviceProvider()->create(['name' => 'Sensitive Provider Name', 'email' => 'private@example.test', 'phone' => '09171234567']);
        $profile = $this->searchableProfile($provider, $province, $municipality, $service, ['bio' => 'Call me at 09171234567']);

        $response = $this->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk()
            ->assertSee('Verified '.$service->name.' #')
            ->assertSee($municipality->name)
            ->assertSee($service->name)
            ->assertDontSee($provider->name)
            ->assertDontSee($provider->email)
            ->assertDontSee($provider->phone)
            ->assertDontSee($profile->bio);
    }

    public function test_available_providers_are_listed_first_for_verified_service_finder(): void
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $unavailableProvider = User::factory()->serviceProvider()->create(['name' => 'Unavailable Provider']);
        $availableProvider = User::factory()->serviceProvider()->create(['name' => 'Available Provider']);
        $this->searchableProfile($unavailableProvider, $province, $municipality, $service, ['available_now' => false, 'rating_cached' => 5]);
        $this->searchableProfile($availableProvider, $province, $municipality, $service, ['available_now' => true, 'rating_cached' => 1]);
        $viewer = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder]);
        ProviderDocument::factory()->for($viewer)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $this->assertTrue($viewer->isIdentityVerified());

        $response = $this->actingAs($viewer)->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk()->assertViewHas('canRevealIdentity', true)->assertSeeInOrder([$availableProvider->name, $unavailableProvider->name]);
    }

    public function test_nearest_available_provider_is_listed_first_and_shown_an_approximate_distance(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $nearMunicipality = Municipality::factory()->for($province)->create(['name' => 'Near City', 'latitude' => 10.0, 'longitude' => 123.0]);
        $farMunicipality = Municipality::factory()->for($province)->create(['name' => 'Far City', 'latitude' => 11.0, 'longitude' => 124.0]);
        $service = Service::factory()->create();
        $nearProvider = User::factory()->serviceProvider()->create(['name' => 'Near Provider']);
        $farProvider = User::factory()->serviceProvider()->create(['name' => 'Far Provider']);
        $this->searchableProfile($farProvider, $province, $farMunicipality, $service, ['available_now' => true, 'rating_cached' => 5]);
        $this->searchableProfile($nearProvider, $province, $nearMunicipality, $service, ['available_now' => true, 'rating_cached' => 1]);
        $viewer = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder]);
        ProviderDocument::factory()->for($viewer)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        $response = $this->actingAs($viewer)->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk()->assertSeeInOrder([$nearProvider->name, $farProvider->name])
            ->assertSee('0.0 km away')
            ->assertSee('(approximate)');
    }

    public function test_provider_profile_shows_approximate_distance_when_arriving_from_search_results(): void
    {
        $province = Province::factory()->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $originMunicipality = Municipality::factory()->for($province)->create(['latitude' => 10.0, 'longitude' => 123.0]);
        $providerMunicipality = Municipality::factory()->for($province)->create(['latitude' => 11.0, 'longitude' => 124.0]);
        $service = Service::factory()->create();
        $profile = $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $providerMunicipality, $service);

        $response = $this->get(route('providers.show', $profile).'?from_province_id='.$province->id.'&from_municipality_id='.$originMunicipality->id);

        $response->assertOk()->assertSee('km from your search location')->assertSee('approximate');
    }

    public function test_broad_category_search_returns_matching_services_only(): void
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $wantedCategory = ServiceCategory::factory()->create();
        $otherCategory = ServiceCategory::factory()->create();
        $wantedService = Service::factory()->for($wantedCategory, 'category')->create(['name' => 'Wanted Service']);
        $otherService = Service::factory()->for($otherCategory, 'category')->create(['name' => 'Other Service']);
        $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $municipality, $wantedService);
        $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $municipality, $otherService);

        $response = $this->get(route('providers.search', ['help' => 'category:'.$wantedCategory->id, 'province_id' => $province->id]));

        $response->assertOk()->assertViewHas('providers', fn ($providers): bool => $providers->total() === 1 && $providers->first()->providerServices->first()->service->is($wantedService));
    }

    public function test_service_and_municipality_refinements_narrow_results(): void
    {
        $province = Province::factory()->create();
        $selectedMunicipality = Municipality::factory()->for($province)->create(['name' => 'Selected City']);
        $otherMunicipality = Municipality::factory()->for($province)->create(['name' => 'Other City']);
        $category = ServiceCategory::factory()->create();
        $selectedService = Service::factory()->for($category, 'category')->create(['name' => 'Selected Service']);
        $otherService = Service::factory()->for($category, 'category')->create(['name' => 'Other Service']);
        $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $selectedMunicipality, $selectedService);
        $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $otherMunicipality, $selectedService);
        $this->searchableProfile(User::factory()->serviceProvider()->create(), $province, $selectedMunicipality, $otherService);

        $response = $this->get(route('providers.search', ['help' => 'category:'.$category->id, 'province_id' => $province->id, 'service_id' => $selectedService->id, 'municipality_id' => $selectedMunicipality->id]));

        $response->assertOk()->assertSee('1 provider found');
    }

    public function test_unverified_inactive_and_expired_providers_are_excluded(): void
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $activeProvider = User::factory()->serviceProvider()->create();
        $this->searchableProfile($activeProvider, $province, $municipality, $service);
        $unverified = ProviderProfile::factory()->for(User::factory()->serviceProvider(), 'user')->create(['province_id' => $province->id, 'municipality_id' => $municipality->id]);
        $unverified->providerServices()->create(['service_id' => $service->id]);
        $expiredProvider = User::factory()->serviceProvider()->create();
        $this->searchableProfile($expiredProvider, $province, $municipality, $service, [], now()->subDay());
        $suspendedProvider = User::factory()->serviceProvider()->create(['status' => UserStatus::Suspended]);
        $this->searchableProfile($suspendedProvider, $province, $municipality, $service);
        $inactiveOfferingProvider = User::factory()->serviceProvider()->create();
        $inactiveOffering = $this->searchableProfile($inactiveOfferingProvider, $province, $municipality, $service);
        $inactiveOffering->providerServices()->update(['active' => false]);

        $response = $this->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id]));

        $response->assertOk()->assertSee('1 provider found');
    }

    public function test_municipality_refinement_must_belong_to_selected_province(): void
    {
        $province = Province::factory()->create();
        $otherMunicipality = Municipality::factory()->create();
        $service = Service::factory()->create();

        $response = $this->from(route('home'))->get(route('providers.search', ['help' => 'service:'.$service->id, 'province_id' => $province->id, 'municipality_id' => $otherMunicipality->id]));

        $response->assertRedirect(route('home'))->assertSessionHasErrors('municipality_id');
    }

    private function searchableProfile(User $provider, Province $province, Municipality $municipality, Service $service, array $attributes = [], ?DateTimeInterface $expiresAt = null): ProviderProfile
    {
        $provider->update(['identity_verification_status' => VerificationStatus::Verified]);
        ProviderDocument::factory()->for($provider)->create(['status' => VerificationStatus::Verified, 'expires_at' => $expiresAt]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create([...$attributes, 'province_id' => $province->id, 'municipality_id' => $municipality->id, 'verification_status' => VerificationStatus::Verified]);
        $profile->providerServices()->create(['service_id' => $service->id]);

        return $profile;
    }
}
