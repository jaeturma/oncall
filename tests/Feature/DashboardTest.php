<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_finder_dashboard_shows_real_request_and_booking_counts(): void
    {
        $finder = User::factory()->identityVerified()->create();
        $provider = User::factory()->serviceProvider()->create();

        $pending = ServiceRequest::factory()->count(2)->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'status' => ServiceRequestStatus::Requested]);
        $accepted = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'status' => ServiceRequestStatus::Accepted]);
        Job::factory()->create(['service_request_id' => $accepted->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Completed]);

        $this->actingAs($finder)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['pending_requests'] === 2 && $metrics['completed_jobs'] === 1 && $metrics['active_jobs'] === 0)
            ->assertSee($pending->first()->title)
            ->assertSee('Pending requests')
            ->assertSee('Completed services');
    }

    public function test_provider_and_admin_are_sent_to_their_own_overviews(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($provider)->get(route('dashboard'))->assertRedirect(route('provider.dashboard'));
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_see_the_queue_overview(): void
    {
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->actingAs($accounting)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Accounting overview')
            ->assertSee('Waiting for you');
    }
}
