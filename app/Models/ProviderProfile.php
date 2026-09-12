<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use Database\Factories\ProviderProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'province_id', 'municipality_id', 'bio', 'available_now', 'availability_status', 'service_radius_km', 'verification_status', 'credentials_metadata', 'rating_cached', 'completed_jobs_cached'])]
class ProviderProfile extends Model
{
    /** @use HasFactory<ProviderProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['available_now' => 'boolean', 'availability_status' => AvailabilityStatus::class, 'credentials_metadata' => 'array', 'verification_status' => VerificationStatus::class, 'rating_cached' => 'decimal:2'];
    }

    /**
     * `available_now` and `availability_status` are kept in sync so existing
     * search ordering/filters (and older callers) that only know the boolean
     * keep working, while the UI and richer filtering use the four-state enum.
     * Whichever of the pair was actually changed wins; the other is derived.
     */
    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            $statusChanged = $profile->isDirty('availability_status');
            $boolChanged = $profile->isDirty('available_now');

            if ($statusChanged && ! $boolChanged) {
                $profile->available_now = $profile->availability_status->isAvailableNow();
            } elseif ($boolChanged && ! $statusChanged) {
                $profile->availability_status = $profile->available_now ? AvailabilityStatus::Available : AvailabilityStatus::Offline;
            }
        });
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
