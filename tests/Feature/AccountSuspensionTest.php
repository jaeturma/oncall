<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountSuspensionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_suspended_user_is_redirected_away_from_authenticated_routes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('enforcement-cases.index'));

        $this->actingAs($user)->get(route('jobs.index'))
            ->assertRedirect(route('enforcement-cases.index'));
    }

    public function test_suspended_user_can_still_read_and_appeal_their_enforcement_case(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($user)->get(route('enforcement-cases.index'))->assertOk();
    }

    public function test_active_user_is_not_redirected(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
