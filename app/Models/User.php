<?php

namespace App\Models;

use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'status', 'identity_verification_status', 'sponsor_user_id', 'account_type_id', 'rating_cached', 'reviews_count'])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements MustVerifyEmail
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

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_user_id');
    }

    /** Users this user directly sponsored (single level only – no recursion). */
    public function sponsoredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'sponsor_user_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function commissionsEarned(): HasMany
    {
        return $this->hasMany(Commission::class, 'sponsor_user_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
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
     * Marketplace vs. back-office role boundary (Phase B). These delegate to
     * {@see UserRole} so the actual role sets are defined in exactly one
     * place; call these instead of comparing `$user->role` inline.
     */
    public function canUseMarketplace(): bool
    {
        return $this->role?->canUseMarketplace() ?? false;
    }

    public function canUseMobile(): bool
    {
        return $this->role?->canUseMobile() ?? false;
    }

    public function canAccessAdmin(): bool
    {
        return $this->role?->canAccessAdmin() ?? false;
    }

    public function canAccessBackOffice(): bool
    {
        return $this->role?->canAccessBackOffice() ?? false;
    }

    public function isMobileVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Whether email/mobile is verified, per user id, without ever loading a
     * name, email address, or phone number. Safe to use when rendering
     * provider results for guests, who must never receive contact details.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, array{mobile: bool, email: bool}>
     */
    public static function contactVerificationByIds(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return static::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'phone_verified_at', 'email_verified_at'])
            ->keyBy('id')
            ->map(fn (self $user): array => ['mobile' => $user->isMobileVerified(), 'email' => $user->hasVerifiedEmail()]);
    }

    /** Unread booking messages across every job this user is a party to, for the header/inbox badge. */
    public function unreadJobMessagesCount(): int
    {
        return JobMessage::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $this->id)
            ->whereHas('job', fn ($query) => $query->where('service_finder_id', $this->id)->orWhere('provider_id', $this->id))
            ->count();
    }

    /**
     * Up to two uppercase initials derived from the name, for avatar fallbacks.
     *
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode(''));
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
            'rating_cached' => 'decimal:2',
        ];
    }
}
