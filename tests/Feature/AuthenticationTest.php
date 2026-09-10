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
}
