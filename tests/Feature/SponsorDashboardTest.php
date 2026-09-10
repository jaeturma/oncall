<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SponsorDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sponsor_sees_only_directly_sponsored_users(): void
    {
        $sponsor = User::factory()->create();
        $directA = User::factory()->create(['name' => 'Direct Alpha', 'sponsor_user_id' => $sponsor->id]);
        User::factory()->create(['name' => 'Direct Beta', 'sponsor_user_id' => $sponsor->id]);
        User::factory()->create(['name' => 'Grandchild Gamma', 'sponsor_user_id' => $directA->id]);
        User::factory()->create(['name' => 'Unrelated Delta']);

        $this->actingAs($sponsor)->get(route('sponsor.referrals'))
            ->assertOk()
            ->assertSee('Direct Alpha')
            ->assertSee('Direct Beta')
            ->assertDontSee('Grandchild Gamma')
            ->assertDontSee('Unrelated Delta');
    }

    public function test_a_user_with_no_sponsees_sees_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('sponsor.referrals'))
            ->assertOk()
            ->assertSee('have not sponsored anyone');
    }
}
