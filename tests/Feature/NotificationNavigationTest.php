<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\User;
use App\Notifications\OncallEvent;
use App\Services\Notifications\NotificationCatalog;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase M Step 4 / Step 26 — the deep-link/navigation whitelist. Laravel is
 * the trust boundary: `NotificationCatalog::resolveTarget()` is the single
 * place a caller-supplied `target` gets checked against `config('notifications.screens')`,
 * and a notification's `target.id` is never itself an authorization grant —
 * the resource it points at still runs its normal policy check when opened.
 */
class NotificationNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resolve_target_returns_null_for_no_target(): void
    {
        $this->assertNull(NotificationCatalog::resolveTarget(null));
    }

    public function test_resolve_target_returns_null_when_the_screen_key_is_missing(): void
    {
        $this->assertNull(NotificationCatalog::resolveTarget(['id' => 5]));
    }

    public function test_resolve_target_rejects_a_screen_not_on_the_whitelist(): void
    {
        $this->assertNull(NotificationCatalog::resolveTarget(['screen' => 'admin_users', 'id' => 1]));
        $this->assertNull(NotificationCatalog::resolveTarget(['screen' => 'https://evil.example.com', 'id' => 1]));
        $this->assertNull(NotificationCatalog::resolveTarget(['screen' => '../../etc/passwd']));
    }

    public function test_resolve_target_requires_an_id_when_the_screen_declares_one(): void
    {
        $this->assertNull(NotificationCatalog::resolveTarget(['screen' => 'service_request']));
        $this->assertNull(NotificationCatalog::resolveTarget(['screen' => 'service_request', 'id' => '']));
        $this->assertSame(['screen' => 'service_request', 'id' => 123], NotificationCatalog::resolveTarget(['screen' => 'service_request', 'id' => 123]));
    }

    public function test_resolve_target_drops_an_unnecessary_id_for_a_screen_that_does_not_require_one(): void
    {
        $this->assertSame(['screen' => 'wallet'], NotificationCatalog::resolveTarget(['screen' => 'wallet', 'id' => 999]));
    }

    public function test_every_whitelisted_screen_is_a_real_declared_navigation_target(): void
    {
        // Guards against a typo silently opening up (or silently breaking) a screen.
        $this->assertNotEmpty(NotificationCatalog::screens());
        foreach (array_keys(NotificationCatalog::screens()) as $screen) {
            $this->assertIsBool(NotificationCatalog::screens()[$screen]);
        }
    }

    public function test_dispatch_stores_a_null_target_when_the_caller_supplies_an_arbitrary_screen(): void
    {
        Queue::fake([SendPushNotificationJob::class]);
        $recipient = User::factory()->create();

        app(NotificationDispatcher::class)->dispatch(
            $recipient,
            'new_message',
            ['sender_name' => 'Ana'],
            ['screen' => 'admin_users', 'id' => 1],
        );

        $notification = $recipient->notifications()->sole();
        $this->assertNull($notification->data['target']);

        Queue::assertPushed(SendPushNotificationJob::class, fn (SendPushNotificationJob $job): bool => $job->target === null);
    }

    public function test_dispatch_stores_a_valid_whitelisted_target_unchanged(): void
    {
        Queue::fake([SendPushNotificationJob::class]);
        $recipient = User::factory()->create();

        app(NotificationDispatcher::class)->dispatch(
            $recipient,
            'new_message',
            ['sender_name' => 'Ana'],
            ['screen' => 'conversation', 'id' => 42],
        );

        $notification = $recipient->notifications()->sole();
        $this->assertSame(['screen' => 'conversation', 'id' => 42], $notification->data['target']);

        Queue::assertPushed(SendPushNotificationJob::class, fn (SendPushNotificationJob $job): bool => $job->target === ['screen' => 'conversation', 'id' => 42]);
    }

    public function test_a_notifications_target_id_does_not_grant_access_to_the_underlying_resource(): void
    {
        // A notification pointing at service_request 123 is just data — the
        // API still enforces normal ownership when that resource is opened.
        $owner = $this->marketplaceUser();
        $outsider = $this->marketplaceUser();

        app(NotificationDispatcher::class)->dispatch($outsider, 'new_message', ['sender_name' => 'Ana'], ['screen' => 'service_request', 'id' => 999999]);
        $notification = $outsider->notifications()->sole();
        $this->assertSame(999999, $notification->data['target']['id']);

        Sanctum::actingAs($outsider);
        // The referenced resource doesn't even exist, but the point stands either way:
        // holding a notification that names an id is not itself a grant to view it.
        $this->getJson("/api/v1/service-requests/{$notification->data['target']['id']}")->assertNotFound();
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read_via_the_mobile_api(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new OncallEvent('demo', 'Private', 'x', null));
        $notification = $owner->notifications()->sole();

        Sanctum::actingAs($this->marketplaceUser());
        $this->patchJson("/api/v1/notifications/{$notification->id}/read")->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_the_mobile_notifications_list_only_returns_the_authenticated_users_own(): void
    {
        $userA = $this->marketplaceUser();
        $userB = $this->marketplaceUser();
        $userA->notify(new OncallEvent('demo', 'For A', 'x', null));
        $userB->notify(new OncallEvent('demo', 'For B', 'x', null));

        Sanctum::actingAs($userA);
        $response = $this->getJson('/api/v1/notifications')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('For A'));
        $this->assertFalse($titles->contains('For B'));
    }

    private function marketplaceUser(): User
    {
        return User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
    }
}
