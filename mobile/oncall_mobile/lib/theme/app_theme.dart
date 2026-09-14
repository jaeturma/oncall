import 'package:flutter/material.dart';

import 'app_colors.dart';
import 'app_radius.dart';
import 'app_typography.dart';

/// Assembles the single `ThemeData` the whole app uses, built from the
/// Laravel-ported tokens in this directory. There is deliberately no real
/// dark theme (Laravel has none) — `lib/app.dart` forces `ThemeMode.light`
/// so a system-dark device never flips to an unrelated palette.
abstract final class AppTheme {
  static final ThemeData light = _build();

  static ThemeData _build() {
    const colorScheme = ColorScheme.light(
      primary: AppColors.navy500,
      onPrimary: Colors.white,
      primaryContainer: AppColors.navy50,
      onPrimaryContainer: AppColors.navy900,
      secondary: AppColors.gold400,
      onSecondary: AppColors.navy900,
      secondaryContainer: AppColors.gold100,
      onSecondaryContainer: AppColors.gold900,
      error: AppColors.danger500,
      onError: Colors.white,
      errorContainer: AppColors.danger50,
      onErrorContainer: AppColors.danger800,
      surface: AppColors.surface,
      onSurface: AppColors.ink,
      surfaceContainerHighest: AppColors.surfaceMuted,
      onSurfaceVariant: AppColors.inkSecondary,
      outline: AppColors.lineStrong,
      outlineVariant: AppColors.line,
    );

    final buttonShape = RoundedRectangleBorder(
      borderRadius: AppRadius.controlRadius,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: AppColors.canvas,
      fontFamily: 'Poppins',
      textTheme: AppTypography.textTheme,
      splashFactory: InkRipple.splashFactory,

      filledButtonTheme: FilledButtonThemeData(
        style:
            FilledButton.styleFrom(
              backgroundColor: AppColors.gold400,
              foregroundColor: AppColors.navy900,
              disabledBackgroundColor: AppColors.gold400.withValues(alpha: 0.5),
              disabledForegroundColor: AppColors.navy900.withValues(alpha: 0.5),
              minimumSize: const Size(64, 44),
              padding: const EdgeInsets.symmetric(horizontal: 20),
              shape: buttonShape,
              textStyle: AppTypography.textTheme.labelLarge,
            ).copyWith(
              overlayColor: WidgetStateProperty.all(
                AppColors.gold500.withValues(alpha: 0.16),
              ),
            ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.gold400,
          foregroundColor: AppColors.navy900,
          elevation: 0,
          minimumSize: const Size(64, 44),
          padding: const EdgeInsets.symmetric(horizontal: 20),
          shape: buttonShape,
          textStyle: AppTypography.textTheme.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style:
            OutlinedButton.styleFrom(
              foregroundColor: AppColors.ink,
              side: const BorderSide(color: AppColors.lineStrong),
              minimumSize: const Size(64, 44),
              padding: const EdgeInsets.symmetric(horizontal: 20),
              shape: buttonShape,
              textStyle: AppTypography.textTheme.labelLarge,
            ).copyWith(
              overlayColor: WidgetStateProperty.all(
                AppColors.navy50.withValues(alpha: 0.8),
              ),
            ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.navy800,
          minimumSize: const Size(48, 40),
          padding: const EdgeInsets.symmetric(horizontal: 12),
          shape: buttonShape,
          textStyle: AppTypography.textTheme.labelLarge,
        ).copyWith(overlayColor: WidgetStateProperty.all(AppColors.navy50)),
      ),

      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: AppRadius.cardRadius,
          side: const BorderSide(color: AppColors.line),
        ),
      ),

      chipTheme: ChipThemeData(
        backgroundColor: AppColors.slate100,
        labelStyle: AppTypography.textTheme.labelMedium?.copyWith(
          color: AppColors.slate700,
        ),
        side: BorderSide.none,
        shape: const StadiumBorder(),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      ),

      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.surface,
        indicatorColor: AppColors.gold100,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        height: 64,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return AppTypography.textTheme.labelSmall?.copyWith(
            color: selected ? AppColors.navy900 : AppColors.inkMuted,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
          );
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          final selected = states.contains(WidgetState.selected);
          return IconThemeData(
            color: selected ? AppColors.navy900 : AppColors.inkMuted,
          );
        }),
      ),

      appBarTheme: AppBarThemeData(
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.ink,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: AppTypography.textTheme.titleMedium,
        iconTheme: const IconThemeData(color: AppColors.ink),
      ),

      inputDecorationTheme: InputDecorationThemeData(
        filled: true,
        fillColor: AppColors.surface,
        hintStyle: AppTypography.textTheme.bodyMedium?.copyWith(
          color: AppColors.inkMuted,
        ),
        labelStyle: AppTypography.textTheme.bodyMedium?.copyWith(
          color: AppColors.inkSecondary,
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 12,
        ),
        border: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.lineStrong),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.lineStrong),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.navy500, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.danger500),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.danger600, width: 2),
        ),
        disabledBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlRadius,
          borderSide: const BorderSide(color: AppColors.line),
        ),
      ),

      dialogTheme: DialogThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: AppRadius.cardRadius),
        titleTextStyle: AppTypography.textTheme.titleMedium,
        contentTextStyle: AppTypography.textTheme.bodyMedium,
      ),

      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.navy900,
        contentTextStyle: AppTypography.textTheme.bodyMedium?.copyWith(
          color: Colors.white,
        ),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.control),
        ),
      ),

      dividerTheme: const DividerThemeData(
        color: AppColors.line,
        thickness: 1,
        space: 1,
      ),

      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.navy500,
      ),

      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return AppColors.navy800;
          }
          return Colors.transparent;
        }),
        side: const BorderSide(color: AppColors.lineStrong, width: 1.5),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
      ),

      radioTheme: RadioThemeData(
        fillColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return AppColors.navy800;
          }
          return AppColors.lineStrong;
        }),
      ),
    );
  }
}
