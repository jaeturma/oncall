<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\ProviderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_finder_dashboard_shows_the_checklist_until_all_steps_are_done(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Finish setting up your account')
            ->assertSee('Verify your email')
            ->assertSee('Verify your mobile number')
            ->assertSee('Verify your identity');
    }

    public function test_checklist_disappears_once_every_step_is_complete(): void
    {
        $user = User::factory()->identityVerified()->mobileVerified()->create();
        ProviderDocument::factory()->for($user)->create(['status' => VerificationStatus::Verified, 'expires_at' => null]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Finish setting up your account');
    }

    public function test_provider_dashboard_checklist_includes_profile_creation(): void
    {
        $provider = User::factory()->serviceProvider()->unverified()->create();

        $this->actingAs($provider)->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee('Finish setting up your account')
            ->assertSee('Create your provider profile');
    }
}
