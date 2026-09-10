<?php

namespace App\Models;

use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'status', 'identity_verification_status', 'sponsor_user_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function providerDocuments(): HasMany
    {
        return $this->hasMany(ProviderDocument::class);
    }

    public function verificationRecords(): HasMany
    {
        return $this->hasMany(VerificationRecord::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_finder_id');
    }

    public function requestedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'requested_provider_id');
    }

    public function finderJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'service_finder_id');
    }

    public function providerJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'provider_id');
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }

    public function enforcementCases(): HasMany
    {
        return $this->hasMany(EnforcementCase::class);
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function isCapabilityRestricted(RestrictedCapability $capability): bool
    {
        if ($this->status === UserStatus::Suspended) {
            return true;
        }

        return $this->enforcementCases()
            ->whereIn('status', ['RESTRICTED', 'SUSPENDED'])
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get(['restricted_capabilities'])
            ->contains(fn (EnforcementCase $case): bool => in_array(RestrictedCapability::FullAccountAccess->value, $case->restricted_capabilities ?? [], true) || in_array($capability->value, $case->restricted_capabilities ?? [], true));
    }

    public function isIdentityVerified(): bool
    {
        return $this->identity_verification_status === VerificationStatus::Verified
            && $this->providerDocuments()
                ->where('status', VerificationStatus::Verified)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
    }

    public function canRequestService(): bool
    {
        return ! config('oncall.service_requests.require_identity_verification') || $this->isIdentityVerified();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'identity_verification_status' => VerificationStatus::class,
        ];
    }
}
