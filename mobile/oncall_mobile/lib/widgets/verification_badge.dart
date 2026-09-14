import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors Laravel's `ui/verification-badge.blade.php` — a success-toned
/// pill with a check icon. The caller decides *whether* to show one (only
/// when that verification actually exists in the data); this widget only
/// decides *how*.
class VerificationBadge extends StatelessWidget {
  const VerificationBadge({super.key, required this.type, this.onDark = false});

  /// One of: identity, mobile, email, drivers-license, professional-license,
  /// passport, provider — or any other value, which falls back to a
  /// title-cased "`type` Verified" label.
  final String type;
  final bool onDark;

  /// Maps a backend `DocumentType` wire value (`app/Enums/DocumentType.php`,
  /// e.g. `NATIONAL_ID`) onto this widget's `type` slug, or null when it
  /// doesn't correspond to a document verification badge.
  static String? typeForDocumentType(String documentType) =>
      switch (documentType) {
        'NATIONAL_ID' => 'identity',
        'DRIVERS_LICENSE' => 'drivers-license',
        'PASSPORT' => 'passport',
        'PROFESSIONAL_CREDENTIAL' => 'professional-license',
        _ => null,
      };

  String get _label => switch (type) {
    'identity' => 'Identity Verified',
    'mobile' => 'Mobile Verified',
    'email' => 'Email Verified',
    'drivers-license' => "Driver's License Verified",
    'professional-license' => 'Professional License Verified',
    'passport' => 'Passport Verified',
    'provider' => 'Approved Provider',
    _ => '${_titleCase(type)} Verified',
  };

  String _titleCase(String value) => value
      .split(RegExp('[-_]'))
      .map((w) => w.isEmpty ? '' : '${w[0].toUpperCase()}${w.substring(1)}')
      .join(' ');

  @override
  Widget build(BuildContext context) {
    final background = onDark
        ? Colors.white.withValues(alpha: 0.1)
        : AppColors.success50;
    final foreground = onDark ? Colors.white : AppColors.success800;
    final iconColor = onDark ? AppColors.gold300 : AppColors.success600;
    final border = onDark ? Colors.white24 : AppColors.success100;

    return DecoratedBox(
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: border),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.verified_outlined, size: 14, color: iconColor),
            const SizedBox(width: 5),
            Text(
              _label,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: foreground,
                height: 1,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
