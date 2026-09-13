<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_finder_can_register_with_a_direct_sponsor(): void
    {
        $sponsor = User::factory()->create();
        $response = $this->post(route('register'), ['name' => 'Finder', 'email' => 'finder@example.com', 'role' => 'SERVICE_FINDER', 'password' => 'password123', 'password_confirmation' => 'password123', 'sponsor_email' => $sponsor->email]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'finder@example.com', 'sponsor_user_id' => $sponsor->id]);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_registration_rejects_privileged_role(): void
    {
        $this->post(route('register'), ['name' => 'Intruder', 'email' => 'intruder@example.com', 'role' => 'ADMIN', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }

    /**
     * Phase L: registration used to accept `phone` in any raw format with no
     * normalization, so the same real number in a different written form
     * could bypass the uniqueness check entirely — exactly the class of bug
     * a later mobile-verification uniqueness check (which does normalize)
     * would otherwise miss against an unnormalized existing row.
     */
    public function test_registration_normalizes_phone_and_rejects_a_duplicate_in_a_different_format(): void
    {
        $this->post(route('register'), ['name' => 'Finder', 'email' => 'finder1@example.com', 'phone' => '09171234567', 'role' => 'SERVICE_FINDER', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'finder1@example.com', 'phone' => '+639171234567']);

        $this->post(route('logout'));

        $this->post(route('register'), ['name' => 'Finder Two', 'email' => 'finder2@example.com', 'phone' => '+639171234567', 'role' => 'SERVICE_FINDER', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'finder2@example.com']);
    }

    /** Phase K: registration had no rate limit at all, unlike login/forgot-password right next to it. */
    public function test_registration_is_rate_limited(): void
    {
        $sawTooManyRequests = false;
        for ($attempt = 1; $attempt <= 15 && ! $sawTooManyRequests; $attempt++) {
            $response = $this->post(route('register'), ['name' => 'Spammer', 'email' => "spammer{$attempt}@example.com", 'role' => 'SERVICE_FINDER', 'password' => 'password123', 'password_confirmation' => 'password123']);
            $sawTooManyRequests = $response->getStatusCode() === 429;
        }

        $this->assertTrue($sawTooManyRequests, 'Expected repeated registration attempts to eventually be rate limited.');
    }

    /** Phase K: submitting a password reset had no rate limit, unlike requesting one (forgot-password) right next to it. */
    public function test_password_reset_submission_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $sawTooManyRequests = false;

        for ($attempt = 1; $attempt <= 15 && ! $sawTooManyRequests; $attempt++) {
            $response = $this->post(route('password.update'), ['token' => 'bad-token', 'email' => $user->email, 'password' => 'password123', 'password_confirmation' => 'password123']);
            $sawTooManyRequests = $response->getStatusCode() === 429;
        }

        $this->assertTrue($sawTooManyRequests, 'Expected repeated password-reset submissions to eventually be rate limited.');
    }
}
