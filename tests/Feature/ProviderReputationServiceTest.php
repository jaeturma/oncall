<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;
use App\Services\ProviderReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase P §24/§26/§27/§32/§33 — aggregate correctness, the no-review state,
 * and that the Bayesian ranking score behaves sanely without ever being the
 * publicly displayed average.
 */
class ProviderReputationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_computes_average_count_and_distribution(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        Review::factory()->count(3)->create(['reviewee_id' => $provider->id, 'rating' => 5]);
        Review::factory()->create(['reviewee_id' => $provider->id, 'rating' => 3]);

        app(ProviderReputationService::class)->recalculate($provider);

        $summary = app(ProviderReputationService::class)->summary($profile->fresh());
        $this->assertSame(4.5, $summary['average_rating']);
        $this->assertSame(4, $summary['rating_count']);
        $this->assertSame(75.0, $summary['rating_distribution']['5']);
        $this->assertSame(25.0, $summary['rating_distribution']['3']);
        $this->assertSame(0.0, $summary['rating_distribution']['1']);
    }

    public function test_hidden_withdrawn_and_removed_reviews_are_excluded_from_aggregates(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        Review::factory()->create(['reviewee_id' => $provider->id, 'rating' => 5]);
        Review::factory()->hidden()->create(['reviewee_id' => $provider->id, 'rating' => 1]);
        Review::factory()->withdrawn()->create(['reviewee_id' => $provider->id, 'rating' => 1]);
        Review::factory()->removed()->create(['reviewee_id' => $provider->id, 'rating' => 1]);

        app(ProviderReputationService::class)->recalculate($provider);

        $summary = app(ProviderReputationService::class)->summary($profile->fresh());
        $this->assertSame(5.0, $summary['average_rating']);
        $this->assertSame(1, $summary['rating_count']);
    }

    public function test_provider_with_no_reviews_shows_a_null_average_not_a_fabricated_zero(): void
    {
        $provider = User::factory()->serviceProvider()->create();
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id]);

        app(ProviderReputationService::class)->recalculate($provider);

        $summary = app(ProviderReputationService::class)->summary($profile->fresh());
        $this->assertNull($summary['average_rating']);
        $this->assertSame(0, $summary['rating_count']);
        $this->assertSame(['1' => 0.0, '2' => 0.0, '3' => 0.0, '4' => 0.0, '5' => 0.0], $summary['rating_distribution']);
    }

    public function test_bayesian_reputation_score_keeps_a_single_five_star_review_from_outranking_many_strong_reviews(): void
    {
        $reputation = app(ProviderReputationService::class);

        $oneReview = User::factory()->serviceProvider()->create();
        $oneReviewProfile = ProviderProfile::factory()->create(['user_id' => $oneReview->id]);
        Review::factory()->create(['reviewee_id' => $oneReview->id, 'rating' => 5]);
        $reputation->recalculate($oneReview);

        $manyReviews = User::factory()->serviceProvider()->create();
        $manyReviewsProfile = ProviderProfile::factory()->create(['user_id' => $manyReviews->id]);
        Review::factory()->count(40)->create(['reviewee_id' => $manyReviews->id, 'rating' => 5]);
        Review::factory()->count(10)->create(['reviewee_id' => $manyReviews->id, 'rating' => 4]);
        $reputation->recalculate($manyReviews);

        $oneReviewProfile->refresh();
        $manyReviewsProfile->refresh();

        // The honest displayed average still favors the single 5-star
        // provider — it is never misrepresented.
        $this->assertSame('5.00', $oneReviewProfile->rating_cached);
        $this->assertSame('4.80', $manyReviewsProfile->rating_cached);

        // But the ranking-only score correctly favors the provider with
        // many consistently strong reviews.
        $this->assertGreaterThan((float) $oneReviewProfile->reputation_score, (float) $manyReviewsProfile->reputation_score);
    }
}
