<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase D — mobile API auth. `/api/v1` must be marketplace-only: a
 * back-office account (Admin/Accounting/Budget/Cashier) must never obtain a
 * mobile token, even with a correct password, and a request without one
 * must never reach a protected route.
 */
class MobileApiAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_issues_a_token_for_a_marketplace_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'SERVICE_FINDER',
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['id', 'role'], 'token']);
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com', 'role' => UserRole::ServiceFinder]);
    }

    public function test_registration_rejects_a_privileged_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Fake Admin',
            'email' => 'fake-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'ADMIN',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'fake-admin@example.com']);
    }

    public function test_login_issues_a_token_for_a_marketplace_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceProvider, 'status' => UserStatus::Active, 'password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password123']);

        $response->assertOk()->assertJsonStructure(['data' => ['id'], 'token']);
    }

    public function test_login_rejects_a_back_office_role_despite_the_correct_password(): void
    {
        foreach ([UserRole::Admin, UserRole::Accounting, UserRole::Budget, UserRole::Cashier] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => UserStatus::Active, 'password' => 'password123']);

            $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password123']);

            $response->assertUnprocessable();
            $this->assertSame(0, $user->tokens()->count(), "{$role->value} should not have received a mobile token");
        }
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_protected_route_requires_a_token(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_a_back_office_token_is_still_rejected_by_the_use_mobile_gate(): void
    {
        // Simulates a token that outlives a role change, bypassing login entirely.
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile')
            ->assertForbidden();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
}
