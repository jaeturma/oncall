import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors Laravel's `ui/rating.blade.php` — a filled gold star + bold value
/// when a rating exists, or an outline star + "New" when it doesn't. Review
/// count is optional since not every context has one (e.g. `ProviderProfile`
/// tracks `completedJobs`, not a review count, matching Laravel's own
/// provider-card which renders those as two separate adjacent pieces rather
/// than folding a count into this component).
class RatingSummary extends StatelessWidget {
  const RatingSummary({super.key, this.rating, this.reviewCount});

  final double? rating;
  final int? reviewCount;

  @override
  Widget build(BuildContext context) {
    final hasRating = rating != null;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(
          hasRating ? Icons.star : Icons.star_outline,
          size: 16,
          color: hasRating ? AppColors.gold500 : AppColors.slate300,
        ),
        const SizedBox(width: 4),
        if (hasRating)
          Text(
            rating!.toStringAsFixed(1),
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.ink,
            ),
          )
        else
          const Text(
            'New',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              color: AppColors.inkMuted,
            ),
          ),
        if (reviewCount != null && reviewCount! > 0) ...[
          const SizedBox(width: 4),
          Text(
            '($reviewCount ${reviewCount == 1 ? 'review' : 'reviews'})',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              color: AppColors.inkMuted,
            ),
          ),
        ],
      ],
    );
  }
}
