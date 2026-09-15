<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;

/**
 * The single place provider rating/reputation aggregates are computed
 * (Phase P §24/§29) — always from authoritative, published {@see Review}
 * rows, never from a client-supplied or independently-edited value. Both
 * `User.rating_cached`/`reviews_count` and `ProviderProfile.rating_cached`
 * are denormalized copies kept in sync here; {@see recalculate()} can always
 * rebuild them from scratch (see the `reputation:recalculate` command).
 */
class ProviderReputationService
{
    /**
     * A minimum-votes-weighted (Bayesian) average, used ONLY for search
     * ranking (Phase O `ProviderSearchService::sortComparators()`) — never
     * displayed. Keeps one lucky 5-star review from outranking a provider
     * with hundreds of consistently strong reviews (§32/§33), without any
     * opaque/AI ranking. The public profile always shows the real
     * arithmetic `rating_cached` average.
     */
    private const BAYESIAN_PRIOR_MEAN = 3.5;

    private const BAYESIAN_MIN_VOTES = 5;

    public function recalculate(User $provider): void
    {
        $stats = Review::query()
            ->where('reviewee_id', $provider->id)
            ->where('status', ReviewStatus::Published)
            ->selectRaw('avg(rating) as average, count(*) as total')
            ->first();

        $total = (int) $stats->total;
        $average = $total > 0 ? round((float) $stats->average, 2) : null;
        $reputationScore = $total > 0
            ? round((self::BAYESIAN_MIN_VOTES * self::BAYESIAN_PRIOR_MEAN + $total * (float) $stats->average) / (self::BAYESIAN_MIN_VOTES + $total), 3)
            : null;

        $provider->update(['rating_cached' => $average, 'reviews_count' => $total]);
        $provider->providerProfile()->update(['rating_cached' => $average, 'reputation_score' => $reputationScore, 'reviews_count' => $total]);
    }

    /**
     * The sanitized reputation payload (Phase P §29) — safe to embed in any
     * marketplace-facing response. Never exposes moderation data. Reads the
     * denormalized `rating_cached`/`completed_jobs_cached`/`reviews_count`
     * straight off the given profile (kept in sync by {@see recalculate()})
     * rather than querying `User`, so this is cheap to embed per search
     * result without an extra query or ever needing the `user` relation
     * loaded (which would risk leaking the provider's name — see
     * ProviderProfileResource's docblock).
     *
     * @return array{average_rating: ?float, rating_count: int, completed_services: int, verified_review_count: int, rating_distribution: array<string, float>}
     */
    public function summary(ProviderProfile $profile): array
    {
        $ratingCount = (int) $profile->reviews_count;

        return [
            'average_rating' => $profile->rating_cached !== null ? (float) $profile->rating_cached : null,
            'rating_count' => $ratingCount,
            'completed_services' => (int) $profile->completed_jobs_cached,
            // Every review in this table is inherently tied to a completed
            // Oncall job (Phase P §13/§29) — there is no unverified review
            // path, so this is always the same figure as rating_count.
            'verified_review_count' => $ratingCount,
            'rating_distribution' => $this->distribution($profile->user_id, $ratingCount),
        ];
    }

    /**
     * @return array<string, float> star (1-5, as string keys) => percentage of published reviews. Never fabricated for a provider with zero reviews (§26/§27).
     */
    private function distribution(int $revieweeUserId, int $ratingCount): array
    {
        $base = ['1' => 0.0, '2' => 0.0, '3' => 0.0, '4' => 0.0, '5' => 0.0];

        if ($ratingCount === 0) {
            return $base;
        }

        $counts = Review::query()
            ->where('reviewee_id', $revieweeUserId)
            ->where('status', ReviewStatus::Published)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        foreach ($counts as $rating => $count) {
            $base[(string) $rating] = round($count / $ratingCount * 100, 1);
        }

        return $base;
    }
}
