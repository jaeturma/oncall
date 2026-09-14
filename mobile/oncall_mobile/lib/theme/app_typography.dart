import 'package:flutter/material.dart';

import 'app_colors.dart';

/// Poppins-based type scale, ported from Laravel's `.display`/`.h1`/`.h2`/
/// `.h3`/`.eyebrow`/`.lead`/`.text-muted` classes and the 15px/1.6 body
/// default in `resources/css/app.css`.
abstract final class AppTypography {
  static const _family = 'Poppins';

  /// Section-label style (`.eyebrow`) — callers should uppercase the text
  /// themselves (e.g. via the `EyebrowText` widget), since `TextStyle` can't.
  static const eyebrow = TextStyle(
    fontFamily: _family,
    fontSize: 12,
    fontWeight: FontWeight.w600,
    letterSpacing: 1.4,
    color: AppColors.inkMuted,
  );

  static const TextTheme textTheme = TextTheme(
    // .display
    displayMedium: TextStyle(
      fontFamily: _family,
      fontSize: 40,
      fontWeight: FontWeight.w700,
      letterSpacing: -0.5,
      height: 1.1,
      color: AppColors.ink,
    ),
    // .h1
    headlineMedium: TextStyle(
      fontFamily: _family,
      fontSize: 26,
      fontWeight: FontWeight.w700,
      letterSpacing: -0.3,
      color: AppColors.ink,
    ),
    // .h2
    headlineSmall: TextStyle(
      fontFamily: _family,
      fontSize: 22,
      fontWeight: FontWeight.w600,
      letterSpacing: -0.2,
      color: AppColors.ink,
    ),
    titleLarge: TextStyle(
      fontFamily: _family,
      fontSize: 22,
      fontWeight: FontWeight.w600,
      letterSpacing: -0.2,
      color: AppColors.ink,
    ),
    // .h3
    titleMedium: TextStyle(
      fontFamily: _family,
      fontSize: 18,
      fontWeight: FontWeight.w600,
      color: AppColors.ink,
    ),
    titleSmall: TextStyle(
      fontFamily: _family,
      fontSize: 15,
      fontWeight: FontWeight.w600,
      color: AppColors.ink,
    ),
    // .lead
    bodyLarge: TextStyle(
      fontFamily: _family,
      fontSize: 17,
      fontWeight: FontWeight.w400,
      color: AppColors.inkSecondary,
      height: 1.5,
    ),
    // base body
    bodyMedium: TextStyle(
      fontFamily: _family,
      fontSize: 15,
      fontWeight: FontWeight.w400,
      color: AppColors.ink,
      height: 1.6,
    ),
    // .text-muted
    bodySmall: TextStyle(
      fontFamily: _family,
      fontSize: 14,
      fontWeight: FontWeight.w400,
      color: AppColors.inkMuted,
      height: 1.5,
    ),
    // button label
    labelLarge: TextStyle(
      fontFamily: _family,
      fontSize: 14,
      fontWeight: FontWeight.w600,
    ),
    labelMedium: TextStyle(
      fontFamily: _family,
      fontSize: 13,
      fontWeight: FontWeight.w600,
    ),
    labelSmall: TextStyle(
      fontFamily: _family,
      fontSize: 12,
      fontWeight: FontWeight.w600,
    ),
  );
}
