import '../core/json.dart';

/// Whether the current user may review a job's other participant
/// (Phase P §47) — Laravel decides; this is presentation-only. Never
/// computed client-side.
class ReviewEligibility {
  ReviewEligibility({
    required this.canReview,
    required this.reviewed,
    this.expiresAt,
  });

  factory ReviewEligibility.fromJson(Map<String, dynamic> json) =>
      ReviewEligibility(
        canReview: json['can_review'] as bool? ?? false,
        reviewed: json['reviewed'] as bool? ?? false,
        expiresAt: parseDate(json['expires_at']),
      );

  final bool canReview;
  final bool reviewed;
  final DateTime? expiresAt;
}
