<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProviderProfilePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_sees_anonymized_provider_profile_without_any_contact_details(): void
    {
        [$profile, $provider, $service] = $this->searchableProvider();

        $profile->update(['bio' => 'Contact Pedro directly at pedro.private@example.test']);

        $this->get(route('providers.show', $profile))
            ->assertOk()
            ->assertSee('Verified '.$service->name.' #')
            ->assertSee('Sign in or register')
            ->assertSee('Keep all communication, agreements, payments, and transactions within Oncall Philippines')
            ->assertDontSee($provider->name)
            ->assertDontSee($provider->email)
            ->assertDontSee((string) $provider->phone)
            ->assertDontSee($profile->bio);
    }

    public function test_verified_service_finder_sees_identity_and_request_action(): void
    {
        [$profile, $provider] = $this->searchableProvider();
        $finder = User::factory()->identityVerified()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        ProviderDocument::factory()->for($finder)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        $profile->update(['bio' => 'Fifteen years of professional driving experience.']);

        $this->actingAs($finder)->get(route('providers.show', $profile))
            ->assertOk()
            ->assertSee($provider->name)
            ->assertSee($profile->bio)
            ->assertSee(route('service-requests.create', $profile));
    }

    public function test_unverified_finder_still_cannot_see_the_name_or_request(): void
    {
        [$profile, $provider] = $this->searchableProvider();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($finder)->get(route('providers.show', $profile))
            ->assertOk()
            ->assertDontSee($provider->name)
            ->assertDontSee(route('service-requests.create', $profile));
    }

    public function test_profile_of_an_unverified_provider_is_not_public(): void
    {
        [$profile] = $this->searchableProvider();
        $profile->update(['verification_status' => VerificationStatus::Pending]);

        $this->get(route('providers.show', $profile))->assertNotFound();
    }

    public function test_reviews_left_for_the_provider_appear_on_the_public_profile(): void
    {
        [$profile, $provider] = $this->searchableProvider();
        $reviewer = User::factory()->create(['name' => 'Cristina Bautista']);
        $job = Job::factory()->create(['provider_id' => $provider->id, 'service_finder_id' => $reviewer->id]);
        Review::create(['job_id' => $job->id, 'reviewer_id' => $reviewer->id, 'reviewee_id' => $provider->id, 'rating' => 5, 'comment' => 'Arrived on time and fixed everything.']);
        $provider->update(['rating_cached' => 5, 'reviews_count' => 1]);

        $this->get(route('providers.show', $profile))
            ->assertOk()
            ->assertSee('Arrived on time and fixed everything.')
            ->assertSee('Cristina') // first name only
            ->assertDontSee('Cristina Bautista');
    }

    /**
     * @return array{0: ProviderProfile, 1: User, 2: Service}
     */
    private function searchableProvider(): array
    {
        $province = Province::factory()->create(['name' => 'Davao del Norte']);
        $municipality = Municipality::factory()->for($province)->create(['name' => 'Tagum City']);
        $service = Service::factory()->create(['name' => 'Driver']);
        $provider = User::factory()->serviceProvider()->create([
            'name' => 'Pedro Santos',
            'email' => 'pedro.private@example.test',
            'phone' => '09170001234',
            'identity_verification_status' => VerificationStatus::Verified,
        ]);
        ProviderDocument::factory()->for($provider)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create([
            'province_id' => $province->id,
            'municipality_id' => $municipality->id,
            'verification_status' => VerificationStatus::Verified,
            'available_now' => true,
            'rating_cached' => 4.9,
            'completed_jobs_cached' => 128,
        ]);
        $profile->providerServices()->create(['service_id' => $service->id, 'active' => true]);

        return [$profile, $provider, $service];
    }
}
