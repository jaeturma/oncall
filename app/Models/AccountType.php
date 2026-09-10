<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'registration_fee', 'sponsor_commission_type', 'sponsor_commission_value', 'platform_commission_percent', 'requires_identity_verification', 'active'])]
class AccountType extends Model
{
    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
            'sponsor_commission_type' => CommissionType::class,
            'sponsor_commission_value' => 'decimal:2',
            'platform_commission_percent' => 'decimal:2',
            'requires_identity_verification' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasSponsorCommission(): bool
    {
        return $this->sponsor_commission_type !== CommissionType::None
            && bccomp((string) $this->sponsor_commission_value, '0', 2) === 1;
    }

    /**
     * Commission amount a sponsor earns for a user of this account type, as a "0.00" string.
     */
    public function sponsorCommissionAmount(): string
    {
        return match ($this->sponsor_commission_type) {
            CommissionType::Fixed => number_format((float) $this->sponsor_commission_value, 2, '.', ''),
            CommissionType::Percentage => bcmul((string) $this->registration_fee, bcdiv((string) $this->sponsor_commission_value, '100', 6), 2),
            CommissionType::None => '0.00',
        };
    }
}
