<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use Database\Factories\ProviderProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'province_id', 'municipality_id', 'bio', 'available_now', 'service_radius_km', 'verification_status', 'credentials_metadata', 'rating_cached', 'completed_jobs_cached'])]
class ProviderProfile extends Model
{
    /** @use HasFactory<ProviderProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['available_now' => 'boolean', 'credentials_metadata' => 'array', 'verification_status' => VerificationStatus::class, 'rating_cached' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function providerServices(): HasMany
    {
        return $this->hasMany(ProviderService::class);
    }

    public function canReceiveServiceRequest(Service $service): bool
    {
        return $this->isRequestable()
            && $this->providerServices()->where('service_id', $service->id)->where('active', true)->exists()
            && $service->active;
    }

    public function isRequestable(): bool
    {
        return $this->verification_status === VerificationStatus::Verified
            && $this->user->status === UserStatus::Active
            && $this->user->isIdentityVerified();
    }
}
