<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ReviewSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_and_update_review_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.settings.reviews.edit'))->assertOk();

        $response = $this->actingAs($admin)->patch(route('admin.settings.reviews.update'), [
            'reviews_enabled' => '1',
            'review_window_days' => 45,
            'comment_required' => '0',
            'max_comment_length' => 1500,
            'provider_response_enabled' => '1',
            'response_max_length' => 800,
            'reviews_per_page' => 15,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.reviews.edit'));
        $settings = ReviewSetting::current();
        $this->assertSame(45, $settings->review_window_days);
        $this->assertSame(1500, $settings->max_comment_length);
        $this->assertDatabaseHas('audit_logs', ['event' => 'review_settings.updated']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function marketplaceRoles(): array
    {
        return [
            'service finder' => [UserRole::ServiceFinder->value],
            'service provider' => [UserRole::ServiceProvider->value],
        ];
    }

    public function test_marketplace_roles_cannot_manage_review_settings(): void
    {
        foreach (self::marketplaceRoles() as [$role]) {
            $user = User::factory()->create(['role' => UserRole::from($role)]);

            $this->actingAs($user)->get(route('admin.settings.reviews.edit'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.reviews.update'), [])->assertForbidden();
        }
    }

    public function test_mobile_sanctum_token_cannot_reach_admin_review_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/settings/reviews')->assertNotFound();
    }
}
