import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors Laravel's `ui/avatar.blade.php` — initials-based circular avatar.
enum AvatarSize { xs, sm, md, lg, xl }

enum AvatarTone { light, dark, accent }

class Avatar extends StatelessWidget {
  const Avatar({
    super.key,
    this.name,
    this.size = AvatarSize.md,
    this.tone = AvatarTone.light,
    this.anonymous = false,
  });

  final String? name;
  final AvatarSize size;
  final AvatarTone tone;
  final bool anonymous;

  double get _dimension => switch (size) {
    AvatarSize.xs => 28,
    AvatarSize.sm => 36,
    AvatarSize.md => 48,
    AvatarSize.lg => 64,
    AvatarSize.xl => 88,
  };

  double get _fontSize => switch (size) {
    AvatarSize.xs => 11,
    AvatarSize.sm => 13,
    AvatarSize.md => 16,
    AvatarSize.lg => 22,
    AvatarSize.xl => 30,
  };

  double get _iconSize => switch (size) {
    AvatarSize.xs => 14,
    AvatarSize.sm => 16,
    AvatarSize.md => 20,
    AvatarSize.lg => 28,
    AvatarSize.xl => 40,
  };

  (Color, Color, Color?) get _palette => switch (tone) {
    AvatarTone.dark => (
      Colors.white.withValues(alpha: 0.1),
      AppColors.gold300,
      Colors.white24,
    ),
    AvatarTone.accent => (AppColors.gold400, AppColors.navy900, null),
    AvatarTone.light => (
      AppColors.navy50,
      AppColors.navy800,
      AppColors.navy100,
    ),
  };

  String get _initials {
    final trimmed = name?.trim() ?? '';
    if (trimmed.isEmpty) {
      return '';
    }

    return trimmed
        .split(RegExp(r'\s+'))
        .take(2)
        .map((part) => part.isEmpty ? '' : part[0].toUpperCase())
        .join();
  }

  @override
  Widget build(BuildContext context) {
    final (background, foreground, ring) = _palette;
    final initials = _initials;

    return Container(
      width: _dimension,
      height: _dimension,
      decoration: BoxDecoration(
        color: background,
        shape: BoxShape.circle,
        border: ring != null ? Border.all(color: ring) : null,
      ),
      alignment: Alignment.center,
      child: anonymous || initials.isEmpty
          ? Icon(Icons.person_outline, size: _iconSize, color: foreground)
          : Text(
              initials,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: _fontSize,
                fontWeight: FontWeight.w600,
                color: foreground,
                letterSpacing: 0.5,
              ),
            ),
    );
  }
}
