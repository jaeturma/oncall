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
use Illuminate\Support\Collection;

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
            ->select(['id', 'user_id', 'province_id', 'municipality_id', 'barangay_id', 'available_now', 'availability_status', 'verification_status', 'rating_cached', 'reputation_score', 'reviews_count', 'completed_jobs_cached', 'latitude', 'longitude', 'service_radius_km'])
            ->with([
                'province:id,name',
                'municipality:id,province_id,name,latitude,longitude',
                'barangay:id,municipality_id,name,latitude,longitude',
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

                // Prefer the provider's own base coordinates when they've set
                // them (more accurate); fall back to the municipality
                // centroid, same as before this was ever collected.
                [$targetLatitude, $targetLongitude] = $profile->hasOwnCoordinates()
                    ? [(float) $profile->latitude, (float) $profile->longitude]
                    : [
                        $profile->municipality->latitude !== null ? (float) $profile->municipality->latitude : null,
                        $profile->municipality->longitude !== null ? (float) $profile->municipality->longitude : null,
                    ];

                $profile->distance_km = $this->distanceEstimator->kilometersBetween($originLatitude, $originLongitude, $targetLatitude, $targetLongitude);

                // Sanitized marker for map display (Phase O §16): a barangay
                // or municipality centroid only — never the provider's own
                // stored latitude/longitude, which stays server-side for
                // matching but is never serialized publicly.
                $markerSource = $profile->barangay ?? $profile->municipality;
                $profile->area_marker = $markerSource?->latitude !== null && $markerSource?->longitude !== null
                    ? ['latitude' => (float) $markerSource->latitude, 'longitude' => (float) $markerSource->longitude]
                    : null;
            })
            ->when(isset($filters['radius_km']), fn (Collection $profiles) => $profiles->filter(
                fn (ProviderProfile $profile) => $profile->distance_km !== null
                    && $profile->distance_km <= $filters['radius_km']
                    && ($profile->service_radius_km === null || $profile->distance_km <= $profile->service_radius_km)
            ))
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
        // Bayesian-adjusted score for ranking only (Phase P §32/§33) — a
        // provider with a single 5-star review must not automatically
        // outrank one with hundreds of consistently strong reviews. Falls
        // back to the raw average for a provider with no reputation_score
        // yet (zero reviews). The public-facing `rating`/`rating_cached`
        // field is never touched by this — see ProviderReputationService.
        $rating = fn (ProviderProfile $a, ProviderProfile $b): int => ($b->reputation_score ?? $b->rating_cached ?? 0) <=> ($a->reputation_score ?? $a->rating_cached ?? 0);
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
        // An explicit search origin (current GPS location or a map pin) always
        // wins over the municipality/province centroid fallback — Laravel
        // still computes the actual distance server-side either way.
        if (isset($filters['latitude'], $filters['longitude'])) {
            return [(float) $filters['latitude'], (float) $filters['longitude']];
        }

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
