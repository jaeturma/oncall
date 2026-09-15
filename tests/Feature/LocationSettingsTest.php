<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LocationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_and_update_location_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.settings.location.edit'))->assertOk();

        $response = $this->actingAs($admin)->patch(route('admin.settings.location.update'), [
            'maps_enabled' => '1',
            'active_map_provider' => 'OPENSTREETMAP',
            'geocoding_enabled' => '0',
            'default_search_radius_km' => 15,
            'max_search_radius_km' => 50,
            'allowed_radius_choices' => '5, 10, 15, 25, 50',
            'default_country' => 'PH',
            'location_freshness_days' => 60,
            'provider_location_policy' => 'OPTIONAL',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.location.edit'));
        $settings = LocationSetting::current();
        $this->assertSame(15, $settings->default_search_radius_km);
        $this->assertSame([5, 10, 15, 25, 50], $settings->allowed_radius_choices);
        $this->assertDatabaseHas('audit_logs', ['event' => 'location_settings.updated']);
    }

    public function test_radius_choice_above_the_configured_max_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.settings.location.update'), [
            'active_map_provider' => 'OPENSTREETMAP',
            'default_search_radius_km' => 15,
            'max_search_radius_km' => 50,
            'allowed_radius_choices' => '5, 10, 999',
            'default_country' => 'PH',
            'location_freshness_days' => 60,
            'provider_location_policy' => 'OPTIONAL',
        ])->assertSessionHasErrors('allowed_radius_choices');
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

    public function test_marketplace_roles_cannot_manage_location_settings(): void
    {
        foreach (self::marketplaceRoles() as [$role]) {
            $user = User::factory()->create(['role' => UserRole::from($role)]);

            $this->actingAs($user)->get(route('admin.settings.location.edit'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.location.update'), [])->assertForbidden();
        }
    }

    public function test_marketplace_roles_cannot_view_location_diagnostics(): void
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($finder)->get(route('admin.location-diagnostics.index'))->assertForbidden();
    }

    public function test_admin_can_view_location_diagnostics(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.location-diagnostics.index'))->assertOk();
    }

    public function test_mobile_sanctum_token_cannot_reach_admin_location_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        // No /api/v1 route exists for this at all — it is web-only by
        // construction (routes/web.php), so this 404s rather than 403s.
        $this->getJson('/api/v1/admin/settings/location')->assertNotFound();
    }
}
