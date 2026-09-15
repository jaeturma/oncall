import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Star-by-star rating breakdown (Phase P §26). Never rendered for a
/// provider with zero reviews — callers should check `ratingCount > 0`
/// first (see `RatingSummary`'s "New" state for the zero-review case).
class RatingDistributionBars extends StatelessWidget {
  const RatingDistributionBars({super.key, required this.distribution});

  /// star (1-5) => percentage of published reviews.
  final Map<int, double> distribution;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var star = 5; star >= 1; star--)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 2),
            child: Row(
              children: [
                SizedBox(
                  width: 28,
                  child: Text(
                    '$star ★',
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: AppColors.inkMuted,
                    ),
                  ),
                ),
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(999),
                    child: LinearProgressIndicator(
                      value: (distribution[star] ?? 0) / 100,
                      minHeight: 6,
                      backgroundColor: AppColors.surfaceMuted,
                      valueColor: const AlwaysStoppedAnimation(
                        AppColors.gold400,
                      ),
                    ),
                  ),
                ),
                SizedBox(
                  width: 40,
                  child: Text(
                    '${(distribution[star] ?? 0).toStringAsFixed(0)}%',
                    textAlign: TextAlign.right,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: AppColors.inkMuted,
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}
