<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Job;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\OncallEvent;
use App\Services\JobService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accepting_a_request_notifies_the_finder(): void
    {
        [$finder, $provider, $request] = $this->pendingRequest();

        $this->actingAs($provider)->patch(route('service-requests.accept', $request), ['agreed_price' => '900.00'])->assertRedirect();

        $note = $finder->notifications()->sole();
        $this->assertSame('service_request.accepted', $note->data['key']);
        $this->assertStringContainsString('900.00', $note->data['body']);
    }

    public function test_declining_a_request_notifies_the_finder(): void
    {
        [$finder, $provider, $request] = $this->pendingRequest();

        $this->actingAs($provider)->patch(route('service-requests.decline', $request))->assertRedirect();

        $this->assertSame('service_request.declined', $finder->notifications()->sole()->data['key']);
    }

    public function test_a_job_status_change_notifies_the_other_participant(): void
    {
        [$finder, $provider] = $this->pendingRequest();
        $job = Job::factory()->create(['service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Accepted]);

        app(JobService::class)->transition($job, $provider, JobStatus::OnTheWay, null);

        $this->assertSame('job.status_changed', $finder->fresh()->notifications()->sole()->data['key']);
        $this->assertSame(0, $provider->fresh()->notifications()->count());
    }

    public function test_verification_decision_notifies_the_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $applicant = User::factory()->serviceProvider()->create(['identity_verification_status' => VerificationStatus::Submitted]);
        $document = ProviderDocument::factory()->for($applicant)->create(['status' => VerificationStatus::Submitted]);
        $applicant->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => 'VERIFIED'])->assertRedirect();

        $this->assertSame('verification.reviewed', $applicant->notifications()->sole()->data['key']);
    }

    public function test_the_inbox_lists_notifications_and_mark_as_read_works(): void
    {
        $user = User::factory()->create();
        $user->notify(new OncallEvent('demo', 'Hello', 'Body text', null));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Hello');
        $this->actingAs($user)->patch(route('notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_a_user_cannot_mark_someone_elses_notification_read(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new OncallEvent('demo', 'Private', 'x', null));
        $notification = $owner->notifications()->sole();

        $this->actingAs(User::factory()->create())->patch(route('notifications.read', $notification->id))->assertForbidden();
        $this->assertNull($notification->refresh()->read_at);
    }

    /**
     * @return array{0: User, 1: User, 2: ServiceRequest}
     */
    private function pendingRequest(): array
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        ProviderProfile::factory()->for($provider, 'user')->create(['province_id' => $province->id, 'municipality_id' => $municipality->id]);

        $request = ServiceRequest::create([
            'service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'service_id' => $service->id,
            'province_id' => $province->id, 'municipality_id' => $municipality->id, 'title' => 'Fix the sink',
            'urgency' => ServiceUrgency::SameDay, 'status' => ServiceRequestStatus::Requested, 'safety_acknowledged_at' => now(),
        ]);

        return [$finder, $provider, $request];
    }
}
