<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_view_and_update_payment_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.settings.payments.edit'))->assertOk();

        $response = $this->actingAs($admin)->patch(route('admin.settings.payments.update'), [
            'payments_enabled' => '1',
            'allowed_payment_methods' => ['CASH', 'GCASH'],
            'sandbox_mode' => '0',
            'min_transaction_amount' => 10,
            'max_transaction_amount' => 100000,
            'payment_expiry_minutes' => 30,
            'receipt_prefix' => 'ABC',
            'manual_payment_enabled' => '1',
            'refund_window_days' => 14,
            'max_refund_requests_per_payment' => 2,
            'platform_fee_type' => 'PERCENTAGE',
            'platform_fee_value' => 12,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.payments.edit'));
        $settings = PaymentSetting::current();
        $this->assertSame(['CASH', 'GCASH'], $settings->allowed_payment_methods);
        $this->assertSame(14, $settings->refund_window_days);
        $this->assertSame('ABC', $settings->receipt_prefix);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment_settings.updated']);
    }

    public function test_min_transaction_amount_cannot_exceed_max(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->patch(route('admin.settings.payments.update'), [
            'allowed_payment_methods' => ['CASH'],
            'min_transaction_amount' => 500,
            'max_transaction_amount' => 100,
            'payment_expiry_minutes' => 30,
            'receipt_prefix' => 'ABC',
            'refund_window_days' => 14,
            'max_refund_requests_per_payment' => 2,
            'platform_fee_type' => 'PERCENTAGE',
            'platform_fee_value' => 12,
        ])->assertSessionHasErrors('max_transaction_amount');
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

    public function test_marketplace_roles_cannot_manage_payment_settings(): void
    {
        foreach (self::marketplaceRoles() as [$role]) {
            $user = User::factory()->create(['role' => UserRole::from($role)]);

            $this->actingAs($user)->get(route('admin.settings.payments.edit'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.settings.payments.update'), [])->assertForbidden();
        }
    }

    public function test_mobile_sanctum_token_cannot_reach_admin_payment_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/settings/payments')->assertNotFound();
    }
}
