<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicChromeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_landing_page_shows_the_emergency_disclaimer(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Oncall is not an emergency service')
            ->assertSee('911');
    }

    public function test_guests_see_sign_in_and_not_the_dashboard_link_in_the_header(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Create account')
            ->assertDontSee('href="'.route('dashboard').'"', false);
    }

    public function test_authenticated_users_see_the_dashboard_link_in_the_header(): void
    {
        $user = User::factory()->create(['name' => 'Maria Clara Santos']);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('MC'); // initials avatar
    }

    public function test_the_flash_component_renders_a_status_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'Saved for later.'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Saved for later.');
    }
}
