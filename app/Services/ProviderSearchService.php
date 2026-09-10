<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProviderSearchService
{
    public function search(array $filters, ?User $viewer): LengthAwarePaginator
    {
        [$helpType, $helpId] = explode(':', $filters['help'], 2);
        $canRevealIdentity = $viewer?->role === UserRole::ServiceFinder && $viewer->isIdentityVerified();

        return ProviderProfile::query()
            ->select(['id', 'user_id', 'province_id', 'municipality_id', 'available_now', 'verification_status', 'rating_cached', 'completed_jobs_cached'])
            ->with([
                'province:id,name',
                'municipality:id,province_id,name',
                'providerServices' => fn ($query) => $query->select(['id', 'provider_profile_id', 'service_id'])->where('active', true)->with('service:id,name'),
            ])
            ->when($canRevealIdentity, fn (Builder $query) => $query->with('user:id,name'))
            ->where('province_id', $filters['province_id'])
            ->where('verification_status', VerificationStatus::Verified)
            ->whereHas('user', fn (Builder $query) => $query
                ->where('status', UserStatus::Active)
                ->where('identity_verification_status', VerificationStatus::Verified)
                ->whereHas('providerDocuments', fn (Builder $query) => $query
                    ->where('status', VerificationStatus::Verified)
                    ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))))
            ->whereHas('providerServices', function (Builder $query) use ($filters, $helpType, $helpId): void {
                $query->where('active', true)
                    ->whereHas('service', function (Builder $query) use ($filters, $helpType, $helpId): void {
                        $query->where('active', true)
                            ->when(isset($filters['service_id']), fn (Builder $query) => $query->whereKey($filters['service_id']))
                            ->when(! isset($filters['service_id']) && $helpType === 'service', fn (Builder $query) => $query->whereKey($helpId))
                            ->when(! isset($filters['service_id']) && $helpType === 'category', fn (Builder $query) => $query->where('service_category_id', $helpId));
                    });
            })
            ->when(isset($filters['municipality_id']), fn (Builder $query) => $query->where('municipality_id', $filters['municipality_id']))
            ->orderByDesc('available_now')
            ->orderByDesc('rating_cached')
            ->orderByDesc('completed_jobs_cached')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();
    }
}
