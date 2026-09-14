<?php

namespace Tests\Feature;

use App\Enums\DevicePlatform;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Notifications\DeviceTokenService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase M Steps 6-10 — device-token registration, refresh, ownership
 * reassignment, and per-device logout.
 */
class DeviceTokenTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_marketplace_user_can_register_a_device(): void
    {
        $user = $this->marketplaceUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'fcm-token-value',
            'device_name' => 'Pixel 8',
            'app_version' => '1.2.0',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'installation_id' => 'install-abc-123',
            'fcm_token' => 'fcm-token-value',
            'platform' => DevicePlatform::Android->value,
            'is_active' => 1,
        ]);

        // The raw FCM token is never echoed back — Flutter already has its own copy.
        $this->assertArrayNotHasKey('fcm_token', $response->json('data'));
    }

    public function test_unauthenticated_registration_is_rejected(): void
    {
        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'fcm-token-value',
        ])->assertUnauthorized();
    }

    public function test_registration_requires_valid_input(): void
    {
        Sanctum::actingAs($this->marketplaceUser());

        $this->postJson('/api/v1/devices/register', [])
            ->assertInvalid(['installation_id', 'platform', 'fcm_token']);

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => 'DESKTOP',
            'fcm_token' => 'fcm-token-value',
        ])->assertInvalid(['platform']);
    }

    public function test_a_back_office_user_cannot_register_a_device(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'fcm-token-value',
        ])->assertForbidden();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_a_malicious_user_id_in_the_payload_is_ignored(): void
    {
        $user = $this->marketplaceUser();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices/register', [
            'user_id' => $otherUser->id,
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'fcm-token-value',
        ])->assertCreated();

        $this->assertDatabaseHas('device_tokens', ['fcm_token' => 'fcm-token-value', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('device_tokens', ['fcm_token' => 'fcm-token-value', 'user_id' => $otherUser->id]);
    }

    public function test_reregistering_the_same_installation_refreshes_the_token_without_duplicating_the_row(): void
    {
        $user = $this->marketplaceUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'old-token',
        ])->assertCreated();

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-abc-123',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'new-token',
        ])->assertCreated();

        $this->assertSame(1, DeviceToken::query()->where('user_id', $user->id)->where('installation_id', 'install-abc-123')->count());
        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'installation_id' => 'install-abc-123', 'fcm_token' => 'new-token']);
        $this->assertDatabaseMissing('device_tokens', ['fcm_token' => 'old-token']);
    }

    public function test_a_token_reassigned_to_another_user_stops_delivering_to_the_previous_owner(): void
    {
        $previousOwner = User::factory()->create();
        $newOwner = $this->marketplaceUser();
        $devices = app(DeviceTokenService::class);

        $devices->register($previousOwner, 'install-shared-device', DevicePlatform::Android, 'shared-fcm-token');
        $this->assertCount(1, $devices->activeTokensFor($previousOwner));

        Sanctum::actingAs($newOwner);
        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-shared-device',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'shared-fcm-token',
        ])->assertCreated();

        $this->assertSame(1, DeviceToken::query()->where('fcm_token', 'shared-fcm-token')->count());
        $this->assertDatabaseHas('device_tokens', ['fcm_token' => 'shared-fcm-token', 'user_id' => $newOwner->id]);
        $this->assertCount(0, $devices->activeTokensFor($previousOwner));
        $this->assertCount(1, $devices->activeTokensFor($newOwner));
    }

    public function test_a_user_can_register_multiple_devices(): void
    {
        $user = $this->marketplaceUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-phone',
            'platform' => DevicePlatform::Android->value,
            'fcm_token' => 'phone-token',
        ])->assertCreated();

        $this->postJson('/api/v1/devices/register', [
            'installation_id' => 'install-tablet',
            'platform' => DevicePlatform::Ios->value,
            'fcm_token' => 'tablet-token',
        ])->assertCreated();

        $this->assertCount(2, app(DeviceTokenService::class)->activeTokensFor($user));
    }

    public function test_a_user_cannot_unregister_another_users_device(): void
    {
        $owner = User::factory()->create();
        $attacker = $this->marketplaceUser();
        $device = app(DeviceTokenService::class)->register($owner, 'install-victim', DevicePlatform::Android, 'victim-token');

        Sanctum::actingAs($attacker);
        $this->deleteJson("/api/v1/devices/{$device->id}")->assertForbidden();

        $this->assertTrue($device->fresh()->is_active);
    }

    public function test_a_user_can_unregister_one_device_without_affecting_others(): void
    {
        $user = $this->marketplaceUser();
        $devices = app(DeviceTokenService::class);
        $phone = $devices->register($user, 'install-phone', DevicePlatform::Android, 'phone-token');
        $tablet = $devices->register($user, 'install-tablet', DevicePlatform::Ios, 'tablet-token');

        Sanctum::actingAs($user);
        $this->deleteJson("/api/v1/devices/{$phone->id}")->assertOk();

        $this->assertFalse($phone->fresh()->is_active);
        $this->assertTrue($tablet->fresh()->is_active);
    }

    public function test_logout_deactivates_only_the_specified_installation(): void
    {
        $user = $this->marketplaceUser();
        $devices = app(DeviceTokenService::class);
        $phone = $devices->register($user, 'install-phone', DevicePlatform::Android, 'phone-token');
        $tablet = $devices->register($user, 'install-tablet', DevicePlatform::Ios, 'tablet-token');

        $token = $user->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout', ['installation_id' => 'install-phone'])
            ->assertOk();

        $this->assertFalse($phone->fresh()->is_active);
        $this->assertTrue($tablet->fresh()->is_active);
        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * `User::factory()->create()` relies on the `users` table's DB-level
     * `role`/`status` column defaults, which the in-memory model instance
     * never re-fetches after the insert — so the freshly created object's
     * `role`/`status` attributes are null and `canUseMobile()` returns false
     * even though the row itself defaults to an active Service Finder.
     * Every actor exercising the `can:use-mobile` gate must set them explicitly.
     */
    private function marketplaceUser(): User
    {
        return User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
    }
}
