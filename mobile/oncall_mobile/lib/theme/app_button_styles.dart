import 'package:flutter/material.dart';

import 'app_colors.dart';
import 'app_radius.dart';
import 'app_typography.dart';

/// Button variants Laravel has (`.btn-dark`, `.btn-danger`, `.btn-danger-solid`)
/// that don't map onto Flutter's three default button component themes
/// (`FilledButton`/`OutlinedButton`/`TextButton` already cover primary/
/// secondary/ghost via `AppTheme`). Pass these as the `style:` argument on
/// the relevant button widget. Sizes mirror `.btn-sm`/`.btn-lg`.
abstract final class AppButtonStyles {
  static final ButtonStyle dark =
      FilledButton.styleFrom(
        backgroundColor: AppColors.navy900,
        foregroundColor: Colors.white,
        disabledBackgroundColor: AppColors.navy900.withValues(alpha: 0.6),
        disabledForegroundColor: Colors.white.withValues(alpha: 0.6),
        minimumSize: const Size(64, 44),
        padding: const EdgeInsets.symmetric(horizontal: 20),
        shape: const RoundedRectangleBorder(
          borderRadius: AppRadius.controlRadius,
        ),
        textStyle: AppTypography.textTheme.labelLarge,
      ).copyWith(
        overlayColor: WidgetStateProperty.all(
          Colors.white.withValues(alpha: 0.1),
        ),
      );

  static final ButtonStyle dangerOutline =
      OutlinedButton.styleFrom(
        foregroundColor: AppColors.danger700,
        side: BorderSide(color: AppColors.danger500.withValues(alpha: 0.4)),
        minimumSize: const Size(64, 44),
        padding: const EdgeInsets.symmetric(horizontal: 20),
        shape: const RoundedRectangleBorder(
          borderRadius: AppRadius.controlRadius,
        ),
        textStyle: AppTypography.textTheme.labelLarge,
      ).copyWith(
        overlayColor: WidgetStateProperty.all(
          AppColors.danger50.withValues(alpha: 0.6),
        ),
      );

  static final ButtonStyle dangerSolid =
      FilledButton.styleFrom(
        backgroundColor: AppColors.danger600,
        foregroundColor: Colors.white,
        minimumSize: const Size(64, 44),
        padding: const EdgeInsets.symmetric(horizontal: 20),
        shape: const RoundedRectangleBorder(
          borderRadius: AppRadius.controlRadius,
        ),
        textStyle: AppTypography.textTheme.labelLarge,
      ).copyWith(
        overlayColor: WidgetStateProperty.all(
          Colors.white.withValues(alpha: 0.1),
        ),
      );

  /// `.btn-sm` — merge onto any button's style, e.g.
  /// `FilledButton(style: AppButtonStyles.sm, ...)`.
  static const ButtonStyle sm = ButtonStyle(
    minimumSize: WidgetStatePropertyAll(Size(48, 36)),
    padding: WidgetStatePropertyAll(EdgeInsets.symmetric(horizontal: 14)),
    textStyle: WidgetStatePropertyAll(TextStyle(fontSize: 13)),
  );

  /// `.btn-lg`.
  static const ButtonStyle lg = ButtonStyle(
    minimumSize: WidgetStatePropertyAll(Size(64, 52)),
    padding: WidgetStatePropertyAll(EdgeInsets.symmetric(horizontal: 28)),
    textStyle: WidgetStatePropertyAll(TextStyle(fontSize: 16)),
  );
}
