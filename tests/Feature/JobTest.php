<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_request_records_price_job_and_initial_audit_log(): void
    {
        [$finder, $provider, $serviceRequest] = $this->participantsAndRequest();

        $this->actingAs($provider)->patch(route('service-requests.accept', $serviceRequest), ['agreed_price' => '1250.50'])->assertRedirect();

        $job = Job::sole();
        $this->assertSame($finder->id, $job->service_finder_id);
        $this->assertSame($provider->id, $job->provider_id);
        $this->assertSame('1250.50', $job->agreed_price);
        $this->assertSame(JobStatus::Accepted, $job->status);
        $this->assertNotNull($job->accepted_at);
        $this->assertDatabaseHas('job_status_logs', ['job_id' => $job->id, 'from_status' => null, 'to_status' => 'ACCEPTED', 'changed_by' => $provider->id]);
        $this->assertSame(ServiceRequestStatus::Accepted, $serviceRequest->fresh()->status);
    }

    public function test_acceptance_requires_a_valid_agreed_price_and_does_not_partially_write(): void
    {
        [, $provider, $serviceRequest] = $this->participantsAndRequest();

        $this->actingAs($provider)->patch(route('service-requests.accept', $serviceRequest), ['agreed_price' => 0])->assertSessionHasErrors('agreed_price');

        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(ServiceRequestStatus::Requested, $serviceRequest->fresh()->status);
    }

    public function test_confirmed_booking_reveals_contacts_only_to_assigned_participants(): void
    {
        [$finder, $provider, $serviceRequest] = $this->participantsAndRequest();
        $job = $this->acceptedJob($serviceRequest, $finder, $provider);
        $outsider = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($finder)->get(route('service-requests.show', $serviceRequest))->assertDontSee($provider->email);
        $this->actingAs($finder)->get(route('jobs.show', $job))->assertOk()->assertSee($provider->email)->assertSee($finder->email);
        $this->actingAs($provider)->get(route('jobs.show', $job))->assertOk()->assertSee($finder->email);
        $this->actingAs($outsider)->get(route('jobs.show', $job))->assertForbidden()->assertDontSee($provider->email);
    }

    public function test_provider_can_progress_job_in_order_and_each_change_is_audited(): void
    {
        [$finder, $provider, $serviceRequest] = $this->participantsAndRequest();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'completed_jobs_cached' => 0]);
        $job = $this->acceptedJob($serviceRequest, $finder, $provider);

        foreach ([JobStatus::OnTheWay, JobStatus::InProgress, JobStatus::Completed] as $status) {
            $this->actingAs($provider)->patch(route('jobs.status.update', $job), ['status' => $status->value, 'notes' => 'Status evidence'])->assertRedirect();
            $job->refresh();
            $this->assertSame($status, $job->status);
        }

        $this->assertNotNull($job->on_the_way_at);
        $this->assertNotNull($job->started_at);
        $this->assertNotNull($job->completed_at);
        $this->assertSame(4, $job->statusLogs()->count());
        $this->assertSame(1, $profile->fresh()->completed_jobs_cached);
    }

    public function test_finder_cannot_progress_job_but_can_cancel_an_active_job(): void
    {
        [$finder, $provider, $serviceRequest] = $this->participantsAndRequest();
        $job = $this->acceptedJob($serviceRequest, $finder, $provider);

        $this->actingAs($finder)->patch(route('jobs.status.update', $job), ['status' => JobStatus::OnTheWay->value])->assertSessionHasErrors('status');
        $this->actingAs($finder)->patch(route('jobs.status.update', $job), ['status' => JobStatus::Cancelled->value, 'notes' => 'No longer needed'])->assertRedirect();

        $this->assertSame(JobStatus::Cancelled, $job->fresh()->status);
        $this->assertNotNull($job->fresh()->cancelled_at);
    }

    public function test_participant_can_dispute_completed_job_and_terminal_states_cannot_progress(): void
    {
        [$finder, $provider, $serviceRequest] = $this->participantsAndRequest();
        $job = $this->acceptedJob($serviceRequest, $finder, $provider, JobStatus::Completed);

        $this->actingAs($finder)->patch(route('jobs.status.update', $job), ['status' => JobStatus::Disputed->value, 'notes' => 'Work outcome disputed'])->assertRedirect();
        $this->assertSame(JobStatus::Disputed, $job->fresh()->status);
        $this->actingAs($provider)->patch(route('jobs.status.update', $job), ['status' => JobStatus::Completed->value])->assertForbidden();
    }

    /** @return array{User, User, ServiceRequest} */
    private function participantsAndRequest(): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'email' => 'finder@example.test', 'phone' => '09170000001']);
        $provider = User::factory()->serviceProvider()->create(['email' => 'provider@example.test', 'phone' => '09170000002']);
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'status' => ServiceRequestStatus::Requested]);

        return [$finder->refresh(), $provider->refresh(), $serviceRequest];
    }

    private function acceptedJob(ServiceRequest $serviceRequest, User $finder, User $provider, JobStatus $status = JobStatus::Accepted): Job
    {
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => $status]);
        $job->statusLogs()->create(['from_status' => null, 'to_status' => JobStatus::Accepted, 'changed_by' => $provider->id]);

        return $job;
    }
}
