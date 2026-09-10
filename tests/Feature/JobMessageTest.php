<?php

namespace Tests\Feature;

use App\Enums\JobMessageType;
use App\Enums\UserRole;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_participants_can_record_messages_confirmations_and_evidence(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob();

        $this->actingAs($finder)->post(route('jobs.messages.store', $job), ['type' => 'CONFIRMATION', 'body' => 'I confirm the agreed scope and price.'])->assertRedirect();
        $this->actingAs($provider)->post(route('jobs.messages.store', $job), ['type' => 'EVIDENCE', 'body' => 'Work was completed at 3:00 PM.'])->assertRedirect();

        $this->assertDatabaseHas('job_messages', ['job_id' => $job->id, 'sender_id' => $finder->id, 'type' => 'CONFIRMATION', 'body' => 'I confirm the agreed scope and price.']);
        $this->assertDatabaseHas('job_messages', ['job_id' => $job->id, 'sender_id' => $provider->id, 'type' => 'EVIDENCE', 'body' => 'Work was completed at 3:00 PM.']);
    }

    public function test_outsider_cannot_record_or_read_booking_communication(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob();
        $message = JobMessage::factory()->create(['job_id' => $job->id, 'sender_id' => $finder->id, 'body' => 'Private booking evidence']);
        $outsider = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($outsider)->post(route('jobs.messages.store', $job), ['type' => 'MESSAGE', 'body' => 'Intrusion'])->assertForbidden();
        $this->actingAs($outsider)->get(route('jobs.show', $job))->assertForbidden()->assertDontSee($message->body);
        $this->assertDatabaseMissing('job_messages', ['body' => 'Intrusion']);
    }

    public function test_booking_message_validates_type_and_body(): void
    {
        [$finder, , $job] = $this->participantsAndJob();

        $this->actingAs($finder)->post(route('jobs.messages.store', $job), ['type' => 'UNTRUSTED', 'body' => ''])->assertSessionHasErrors(['type', 'body']);

        $this->assertDatabaseCount('job_messages', 0);
    }

    public function test_booking_screen_escapes_recorded_message_content(): void
    {
        [$finder, , $job] = $this->participantsAndJob();
        $body = '<script>alert("unsafe")</script> Evidence';
        JobMessage::factory()->create(['job_id' => $job->id, 'sender_id' => $finder->id, 'type' => JobMessageType::Evidence, 'body' => $body]);

        $this->actingAs($finder)->get(route('jobs.show', $job))->assertOk()->assertSee($body)->assertDontSee($body, false);
    }

    /** @return array{User, User, Job} */
    private function participantsAndJob(): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id]);

        return [$finder->refresh(), $provider->refresh(), $job];
    }
}
