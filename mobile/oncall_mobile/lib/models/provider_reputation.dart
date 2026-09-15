import '../core/json.dart';

/// The sanitized reputation payload embedded on a provider profile
/// (Phase P §29) — never fabricated: `averageRating` is null and
/// `ratingDistribution` is all-zero for a provider with no reviews yet
/// (§26/§27), rather than a misleading 0.0.
class ProviderReputation {
  ProviderReputation({
    this.averageRating,
    required this.ratingCount,
    required this.completedServices,
    required this.verifiedReviewCount,
    required this.ratingDistribution,
  });

  factory ProviderReputation.fromJson(Map<String, dynamic> json) {
    final distribution = json['rating_distribution'] as Map<String, dynamic>?;

    return ProviderReputation(
      averageRating: parseNum(json['average_rating']),
      ratingCount: json['rating_count'] as int? ?? 0,
      completedServices: json['completed_services'] as int? ?? 0,
      verifiedReviewCount: json['verified_review_count'] as int? ?? 0,
      ratingDistribution: {
        for (var star = 1; star <= 5; star++)
          star: parseNum(distribution?[star.toString()]) ?? 0,
      },
    );
  }

  final double? averageRating;
  final int ratingCount;
  final int completedServices;
  final int verifiedReviewCount;

  /// star (1-5) => percentage of published reviews.
  final Map<int, double> ratingDistribution;
}
