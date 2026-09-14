import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Mirrors Laravel's `ui/alert.blade.php` — a tone-bordered box with an icon
/// and optional title, used for verification-status explanations and
/// similar inline notices.
enum AppAlertTone { info, success, warning, danger, accent }

class AppAlert extends StatelessWidget {
  const AppAlert({
    super.key,
    required this.message,
    this.tone = AppAlertTone.info,
    this.title,
    this.icon,
  });

  final String message;
  final AppAlertTone tone;
  final String? title;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final (background, border, foreground, defaultIcon) = _paletteFor(tone);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 1),
            child: Icon(icon ?? defaultIcon, size: 20, color: foreground),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (title != null)
                  Text(
                    title!,
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                      color: foreground,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                Text(
                  message,
                  style: AppTypography.textTheme.bodyMedium?.copyWith(
                    color: foreground,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  (Color, Color, Color, IconData) _paletteFor(AppAlertTone tone) =>
      switch (tone) {
        AppAlertTone.success => (
          AppColors.success50,
          AppColors.success100,
          AppColors.success800,
          Icons.check_circle_outline,
        ),
        AppAlertTone.warning => (
          AppColors.warning50,
          AppColors.warning100,
          AppColors.warning800,
          Icons.warning_amber_outlined,
        ),
        AppAlertTone.danger => (
          AppColors.danger50,
          AppColors.danger100,
          AppColors.danger800,
          Icons.warning_amber_outlined,
        ),
        AppAlertTone.accent => (
          AppColors.gold50,
          AppColors.gold200,
          AppColors.navy900,
          Icons.shield_outlined,
        ),
        AppAlertTone.info => (
          AppColors.info50,
          AppColors.info100,
          AppColors.info800,
          Icons.info_outline,
        ),
      };
}
