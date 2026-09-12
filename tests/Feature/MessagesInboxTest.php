<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Job;
use App\Models\JobMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MessagesInboxTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inbox_lists_conversations_with_the_latest_message_preview(): void
    {
        $finder = User::factory()->create();
        $provider = User::factory()->serviceProvider()->create();
        $job = $this->confirmedJob($finder, $provider);
        JobMessage::factory()->for($job)->create(['sender_id' => $provider->id, 'body' => 'Old message', 'created_at' => now()->subHour()]);
        JobMessage::factory()->for($job)->create(['sender_id' => $provider->id, 'body' => 'On my way now, see you soon', 'created_at' => now()]);

        $response = $this->actingAs($finder)->get(route('messages.index'));

        $response->assertOk()->assertSee($provider->name)->assertSee('On my way now, see you soon');
    }

    public function test_jobs_without_messages_are_excluded_from_the_inbox(): void
    {
        $finder = User::factory()->create();
        $provider = User::factory()->serviceProvider()->create();
        $this->confirmedJob($finder, $provider);

        $response = $this->actingAs($finder)->get(route('messages.index'));

        $response->assertOk()->assertSee('No conversations yet');
    }

    public function test_unread_count_clears_after_viewing_the_job(): void
    {
        $finder = User::factory()->create();
        $provider = User::factory()->serviceProvider()->create();
        $job = $this->confirmedJob($finder, $provider);
        JobMessage::factory()->for($job)->create(['sender_id' => $provider->id, 'body' => 'Hello there']);

        $this->assertSame(1, $finder->unreadJobMessagesCount());

        $this->actingAs($finder)->get(route('jobs.show', $job))->assertOk();

        $this->assertSame(0, $finder->unreadJobMessagesCount());
    }

    public function test_sending_a_message_does_not_mark_it_unread_for_the_sender(): void
    {
        $finder = User::factory()->create();
        $provider = User::factory()->serviceProvider()->create();
        $job = $this->confirmedJob($finder, $provider);
        JobMessage::factory()->for($job)->create(['sender_id' => $finder->id, 'body' => 'From me']);

        $this->assertSame(0, $finder->unreadJobMessagesCount());
        $this->assertSame(1, $provider->unreadJobMessagesCount());
    }

    public function test_guests_cannot_view_the_inbox(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    private function confirmedJob(User $finder, User $provider): Job
    {
        $serviceRequest = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id, 'status' => ServiceRequestStatus::Accepted]);

        return Job::factory()->create(['service_request_id' => $serviceRequest->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id, 'status' => JobStatus::Accepted]);
    }
}
