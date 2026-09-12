<?php

namespace App\Services;

use App\Enums\AvailabilityStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProviderSearchService
{
    private const PER_PAGE = 12;

    public function __construct(private readonly DistanceEstimator $distanceEstimator) {}

    public function search(array $filters, ?User $viewer, Request $request): LengthAwarePaginatorContract
    {
        [$helpType, $helpId] = explode(':', $filters['help'], 2);
        $canRevealIdentity = $viewer?->role === UserRole::ServiceFinder && $viewer->isIdentityVerified();
        [$originLatitude, $originLongitude] = $this->originCoordinates($filters);

        $profiles = ProviderProfile::query()
            ->select(['id', 'user_id', 'province_id', 'municipality_id', 'available_now', 'availability_status', 'verification_status', 'rating_cached', 'completed_jobs_cached'])
            ->with([
                'province:id,name',
                'municipality:id,province_id,name,latitude,longitude',
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
            ->when(! empty($filters['available_only']), fn (Builder $query) => $query->where('availability_status', AvailabilityStatus::Available))
            ->when(isset($filters['min_rating']), fn (Builder $query) => $query->where('rating_cached', '>=', $filters['min_rating']))
            ->get();

        $userIds = $profiles->pluck('user_id')->unique()->values()->all();
        $verifiedTypes = ProviderDocument::verifiedTypesByUser($userIds);
        $contactVerification = User::contactVerificationByIds($userIds);

        $profiles = $profiles
            ->each(function (ProviderProfile $profile) use ($originLatitude, $originLongitude, $verifiedTypes, $contactVerification): void {
                $profile->verified_document_types = $verifiedTypes->get($profile->user_id, []);
                $profile->mobile_verified = $contactVerification->get($profile->user_id)['mobile'] ?? false;
                $profile->email_verified = $contactVerification->get($profile->user_id)['email'] ?? false;
                $profile->distance_km = $this->distanceEstimator->kilometersBetween(
                    $originLatitude,
                    $originLongitude,
                    $profile->municipality->latitude !== null ? (float) $profile->municipality->latitude : null,
                    $profile->municipality->longitude !== null ? (float) $profile->municipality->longitude : null,
                );
            })
            ->sortBy($this->sortComparators($filters['sort'] ?? null))
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $profiles->forPage($page, self::PER_PAGE)->values(),
            $profiles->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * Comparator chain for the results list. The default ("recommended")
     * order is unchanged from before availability/rating/distance sort
     * options existed: available providers first, then nearest, then
     * highest rated. Explicit "rating" or "nearest" sorts re-prioritize one
     * of those signals while keeping the rest as tie-breakers.
     *
     * @return list<callable(ProviderProfile, ProviderProfile): int>
     */
    private function sortComparators(?string $sort): array
    {
        $availability = fn (ProviderProfile $a, ProviderProfile $b): int => $a->availability_status->rank() <=> $b->availability_status->rank();
        $distance = fn (ProviderProfile $a, ProviderProfile $b): int => ($a->distance_km ?? PHP_FLOAT_MAX) <=> ($b->distance_km ?? PHP_FLOAT_MAX);
        $rating = fn (ProviderProfile $a, ProviderProfile $b): int => $b->rating_cached <=> $a->rating_cached;
        $completed = fn (ProviderProfile $a, ProviderProfile $b): int => $b->completed_jobs_cached <=> $a->completed_jobs_cached;
        $id = fn (ProviderProfile $a, ProviderProfile $b): int => $a->id <=> $b->id;

        return match ($sort) {
            'rating' => [$rating, $availability, $distance, $completed, $id],
            'nearest' => [$distance, $availability, $rating, $completed, $id],
            default => [$availability, $distance, $rating, $completed, $id],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: ?float, 1: ?float}
     */
    private function originCoordinates(array $filters): array
    {
        if (isset($filters['municipality_id'])) {
            $municipality = Municipality::query()->find($filters['municipality_id'], ['latitude', 'longitude']);

            if ($municipality?->latitude !== null && $municipality?->longitude !== null) {
                return [(float) $municipality->latitude, (float) $municipality->longitude];
            }
        }

        $province = Province::query()->find($filters['province_id'], ['latitude', 'longitude']);

        return [
            $province?->latitude !== null ? (float) $province->latitude : null,
            $province?->longitude !== null ? (float) $province->longitude : null,
        ];
    }
}
