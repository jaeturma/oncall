<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding admin-configurable payment policy.
 * Always go through {@see current()} rather than querying the table
 * directly — mirrors {@see ReviewSetting} / {@see LocationSetting}.
 */
#[Fillable([
    'payments_enabled', 'allowed_payment_methods', 'active_gateway', 'sandbox_mode',
    'min_transaction_amount', 'max_transaction_amount', 'payment_expiry_minutes',
    'receipt_prefix', 'manual_payment_enabled', 'refund_window_days',
    'max_refund_requests_per_payment', 'platform_fee_type', 'platform_fee_value',
])]
class PaymentSetting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'payments_enabled' => true,
        'allowed_payment_methods' => '["CASH","GCASH","MAYA","BANK_TRANSFER"]',
        'active_gateway' => 'MANUAL',
        'sandbox_mode' => true,
        'min_transaction_amount' => 0,
        'max_transaction_amount' => 500000,
        'payment_expiry_minutes' => 60,
        'receipt_prefix' => 'ONC',
        'manual_payment_enabled' => true,
        'refund_window_days' => 30,
        'max_refund_requests_per_payment' => 3,
        'platform_fee_type' => CommissionType::Percentage,
        'platform_fee_value' => 15,
    ];

    protected function casts(): array
    {
        return [
            'payments_enabled' => 'boolean',
            'allowed_payment_methods' => 'array',
            'sandbox_mode' => 'boolean',
            'min_transaction_amount' => 'decimal:2',
            'max_transaction_amount' => 'decimal:2',
            'manual_payment_enabled' => 'boolean',
            'platform_fee_type' => CommissionType::class,
            'platform_fee_value' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
