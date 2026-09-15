// Phase P: ratings, reviews, provider reputation. Model-parsing tests for
// the new JSON shapes — mirrors test/location_test.dart's conventions.
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/models/provider_reputation.dart';
import 'package:oncall_mobile/models/review.dart';
import 'package:oncall_mobile/models/review_eligibility.dart';

void main() {
  group('Review.fromJson', () {
    test('parses a published review with no response', () {
      final review = Review.fromJson({
        'id': 1,
        'rating': 5,
        'comment': 'Great work.',
        'status': 'PUBLISHED',
        'verified_service': true,
        'reviewer': {'id': 2, 'name': 'Juan'},
        'reviewee': {'id': 3, 'name': 'Maria'},
        'created_at': '2026-09-15T00:00:00.000000Z',
      });

      expect(review.rating, 5);
      expect(review.isPublished, isTrue);
      expect(review.hasResponse, isFalse);
      expect(review.verifiedService, isTrue);
    });

    test('parses a provider response when present', () {
      final review = Review.fromJson({
        'id': 1,
        'rating': 4,
        'status': 'PUBLISHED',
        'response': {
          'body': 'Thank you!',
          'responded_at': '2026-09-15T01:00:00.000000Z',
        },
      });

      expect(review.hasResponse, isTrue);
      expect(review.response!.body, 'Thank you!');
    });

    test('a hidden review is not treated as published', () {
      final review = Review.fromJson({
        'id': 1,
        'rating': 1,
        'status': 'HIDDEN',
      });

      expect(review.isPublished, isFalse);
    });
  });

  group('ReviewEligibility.fromJson', () {
    test('parses an eligible result', () {
      final eligibility = ReviewEligibility.fromJson({
        'can_review': true,
        'reviewed': false,
        'expires_at': '2026-10-15T00:00:00.000000Z',
      });

      expect(eligibility.canReview, isTrue);
      expect(eligibility.reviewed, isFalse);
      expect(eligibility.expiresAt, isNotNull);
    });

    test('defaults to ineligible when fields are missing', () {
      final eligibility = ReviewEligibility.fromJson({});

      expect(eligibility.canReview, isFalse);
      expect(eligibility.reviewed, isFalse);
    });
  });

  group('ProviderReputation.fromJson', () {
    test('parses a full reputation payload', () {
      final reputation = ProviderReputation.fromJson({
        'average_rating': '4.75',
        'rating_count': 4,
        'completed_services': 10,
        'verified_review_count': 4,
        'rating_distribution': {
          '1': 0.0,
          '2': 0.0,
          '3': 0.0,
          '4': 25.0,
          '5': 75.0,
        },
      });

      expect(reputation.averageRating, 4.75);
      expect(reputation.ratingCount, 4);
      expect(reputation.ratingDistribution[5], 75.0);
      expect(reputation.ratingDistribution[1], 0.0);
    });

    test('a provider with no reviews has a null average, not a fabricated zero', () {
      final reputation = ProviderReputation.fromJson({
        'average_rating': null,
        'rating_count': 0,
        'completed_services': 0,
        'verified_review_count': 0,
        'rating_distribution': {
          '1': 0.0,
          '2': 0.0,
          '3': 0.0,
          '4': 0.0,
          '5': 0.0,
        },
      });

      expect(reputation.averageRating, isNull);
      expect(reputation.ratingCount, 0);
    });
  });
}
