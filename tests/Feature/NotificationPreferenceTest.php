<?php

namespace Tests\Feature;

use App\Enums\NotificationCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase M Step 21 — marketplace-facing notification preferences. The
 * category-level toggle only ever governs a category's optional events;
 * NotificationPreferenceService never even queries this table for a
 * mandatory event, so there is no way to write a row that suppresses one
 * (see the assertions against `pushAllowed()`/`smsAllowed()` below).
 */
class NotificationPreferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/notification-preferences')->assertUnauthorized();
    }

    public function test_index_lists_every_category_with_safe_defaults(): void
    {
        Sanctum::actingAs($this->marketplaceUser());

        $response = $this->getJson('/api/v1/notification-preferences')->assertOk();

        $categories = collect($response->json('data'))->keyBy('category');
        $this->assertSame(count(NotificationCategory::cases()), $categories->count());

        $serviceUpdates = $categories['SERVICE_UPDATES'];
        $this->assertTrue($serviceUpdates['push_enabled']);
        $this->assertTrue($serviceUpdates['sms_enabled']);
        $this->assertFalse($serviceUpdates['has_mandatory_events']);
        $this->assertTrue($categories['VERIFICATION']['has_mandatory_events']);
        $this->assertTrue($categories['SECURITY']['has_mandatory_events']);
    }

    public function test_index_reflects_a_users_saved_preference(): void
    {
        $user = $this->marketplaceUser();
        NotificationPreference::query()->create(['user_id' => $user->id, 'category' => NotificationCategory::ServiceUpdates, 'push_enabled' => false, 'sms_enabled' => true]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notification-preferences')->assertOk();
        $category = collect($response->json('data'))->firstWhere('category', 'SERVICE_UPDATES');

        $this->assertFalse($category['push_enabled']);
        $this->assertTrue($category['sms_enabled']);
    }

    public function test_a_user_can_disable_push_for_an_optional_category(): void
    {
        $user = $this->marketplaceUser();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::ServiceUpdates->value, 'push_enabled' => false]],
        ])->assertOk();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'category' => NotificationCategory::ServiceUpdates->value,
            'push_enabled' => 0,
        ]);

        $preferences = app(NotificationPreferenceService::class);
        $this->assertFalse($preferences->pushAllowed($user, 'service_request_accepted'));
    }

    public function test_disabling_a_category_does_not_suppress_its_mandatory_events(): void
    {
        $user = $this->marketplaceUser();
        Sanctum::actingAs($user);

        // Wallet has both optional events (job_payment_released) and
        // mandatory ones (withdrawal_submitted) in the same category.
        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::Wallet->value, 'push_enabled' => false, 'sms_enabled' => false]],
        ])->assertOk();

        $preferences = app(NotificationPreferenceService::class);
        $this->assertFalse($preferences->pushAllowed($user, 'job_payment_released'));
        $this->assertTrue($preferences->pushAllowed($user, 'withdrawal_submitted'));
        $this->assertTrue($preferences->smsAllowed($user, 'withdrawal_submitted'));
    }

    public function test_updating_one_channel_does_not_reset_the_other(): void
    {
        $user = $this->marketplaceUser();
        NotificationPreference::query()->create(['user_id' => $user->id, 'category' => NotificationCategory::ServiceUpdates, 'push_enabled' => false, 'sms_enabled' => false]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::ServiceUpdates->value, 'push_enabled' => true]],
        ])->assertOk();

        $preference = NotificationPreference::query()->where('user_id', $user->id)->where('category', NotificationCategory::ServiceUpdates)->sole();
        $this->assertTrue($preference->push_enabled);
        $this->assertFalse($preference->sms_enabled);
    }

    public function test_a_users_preference_does_not_affect_another_user(): void
    {
        $userA = $this->marketplaceUser();
        $userB = $this->marketplaceUser();
        Sanctum::actingAs($userA);

        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::ServiceUpdates->value, 'push_enabled' => false]],
        ])->assertOk();

        $preferences = app(NotificationPreferenceService::class);
        $this->assertFalse($preferences->pushAllowed($userA, 'service_request_accepted'));
        $this->assertTrue($preferences->pushAllowed($userB, 'service_request_accepted'));
        $this->assertSame(0, NotificationPreference::query()->where('user_id', $userB->id)->count());
    }

    public function test_update_requires_a_known_category(): void
    {
        Sanctum::actingAs($this->marketplaceUser());

        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => 'NOT_A_REAL_CATEGORY', 'push_enabled' => false]],
        ])->assertInvalid(['preferences.0.category']);
    }

    public function test_update_requires_boolean_channel_values(): void
    {
        Sanctum::actingAs($this->marketplaceUser());

        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::ServiceUpdates->value, 'push_enabled' => 'not-a-bool']],
        ])->assertInvalid(['preferences.0.push_enabled']);
    }

    public function test_update_requires_at_least_one_preference(): void
    {
        Sanctum::actingAs($this->marketplaceUser());

        $this->patchJson('/api/v1/notification-preferences', ['preferences' => []])->assertInvalid(['preferences']);
    }

    public function test_a_back_office_user_cannot_access_marketplace_preferences(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/notification-preferences')->assertForbidden();
        $this->patchJson('/api/v1/notification-preferences', [
            'preferences' => [['category' => NotificationCategory::ServiceUpdates->value, 'push_enabled' => false]],
        ])->assertForbidden();
    }

    private function marketplaceUser(): User
    {
        return User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
    }
}
