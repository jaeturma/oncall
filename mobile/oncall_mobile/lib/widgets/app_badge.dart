import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Mirrors Laravel's `.badge`/`.badge-*` pill component
/// (`resources/views/components/ui/badge.blade.php`).
enum AppBadgeTone {
  neutral,
  brand,
  accent,
  success,
  warning,
  danger,
  info,
  outline,
}

class AppBadge extends StatelessWidget {
  const AppBadge({
    super.key,
    required this.label,
    this.tone = AppBadgeTone.neutral,
    this.dot = false,
    this.icon,
  });

  final String label;
  final AppBadgeTone tone;
  final bool dot;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final (background, foreground, border) = _colorsFor(tone);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: background,
        shape: BoxShape.rectangle,
        borderRadius: BorderRadius.circular(999),
        border: border != null ? Border.all(color: border) : null,
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (dot) ...[
              Container(
                width: 6,
                height: 6,
                decoration: BoxDecoration(
                  color: foreground,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
            ],
            if (icon != null) ...[
              Icon(icon, size: 14, color: foreground),
              const SizedBox(width: 4),
            ],
            Text(
              label,
              style: AppTypography.textTheme.labelSmall?.copyWith(
                color: foreground,
                height: 1,
              ),
            ),
          ],
        ),
      ),
    );
  }

  (Color, Color, Color?) _colorsFor(AppBadgeTone tone) => switch (tone) {
    AppBadgeTone.neutral => (AppColors.slate100, AppColors.slate700, null),
    AppBadgeTone.brand => (AppColors.navy50, AppColors.navy800, null),
    AppBadgeTone.accent => (AppColors.gold100, AppColors.gold900, null),
    AppBadgeTone.success => (AppColors.success50, AppColors.success800, null),
    AppBadgeTone.warning => (AppColors.warning50, AppColors.warning800, null),
    AppBadgeTone.danger => (AppColors.danger50, AppColors.danger800, null),
    AppBadgeTone.info => (AppColors.info50, AppColors.info800, null),
    AppBadgeTone.outline => (
      AppColors.surface,
      AppColors.inkSecondary,
      AppColors.line,
    ),
  };
}
