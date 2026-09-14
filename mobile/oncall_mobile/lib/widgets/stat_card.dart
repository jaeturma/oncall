import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import 'app_card.dart';

/// Mirrors Laravel's `ui/stat-card.blade.php` — an icon chip, a muted label,
/// a bold value, and an optional hint, with a trailing chevron only when the
/// card is tappable.
enum StatCardTone { neutral, brand, accent, success, warning, danger }

class StatCard extends StatelessWidget {
  const StatCard({
    super.key,
    required this.label,
    required this.value,
    this.icon,
    this.hint,
    this.tone = StatCardTone.neutral,
    this.onTap,
  });

  final String label;
  final String value;
  final IconData? icon;
  final String? hint;
  final StatCardTone tone;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final (chipBackground, chipForeground) = _colorsFor(tone);

    return AppCard(
      onTap: onTap,
      padding: const EdgeInsets.all(16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (icon != null) ...[
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: chipBackground,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, size: 20, color: chipForeground),
            ),
            const SizedBox(width: 14),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: AppColors.inkMuted,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                    color: AppColors.ink,
                    height: 1.1,
                  ),
                ),
                if (hint != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    hint!,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: AppColors.inkSecondary,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (onTap != null) ...[
            const SizedBox(width: 4),
            const Icon(
              Icons.chevron_right,
              size: 18,
              color: AppColors.inkMuted,
            ),
          ],
        ],
      ),
    );
  }

  (Color, Color) _colorsFor(StatCardTone tone) => switch (tone) {
    StatCardTone.neutral => (AppColors.navy50, AppColors.navy800),
    StatCardTone.brand => (AppColors.navy900, AppColors.gold300),
    StatCardTone.accent => (AppColors.gold100, AppColors.gold800),
    StatCardTone.success => (AppColors.success50, AppColors.success700),
    StatCardTone.warning => (AppColors.warning50, AppColors.warning700),
    StatCardTone.danger => (AppColors.danger50, AppColors.danger700),
  };
}
