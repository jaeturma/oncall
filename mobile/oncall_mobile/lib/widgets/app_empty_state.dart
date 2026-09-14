import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors Laravel's `components/empty-state.blade.php` — a full-section
/// empty state with an icon chip, title, optional message, and optional
/// actions. For lighter, nested/inline empties, use [EmptyView] instead
/// (Laravel genuinely has both tiers: `empty-state` for whole sections,
/// plain centered text for smaller gaps).
class AppEmptyState extends StatelessWidget {
  const AppEmptyState({
    super.key,
    required this.title,
    this.message,
    this.icon = Icons.inbox_outlined,
    this.actions = const [],
    this.compact = false,
  });

  final String title;
  final String? message;
  final IconData icon;
  final List<Widget> actions;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.line),
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(
          horizontal: 24,
          vertical: compact ? 32 : 48,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                color: AppColors.navy50,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(icon, size: 28, color: AppColors.navy700),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppColors.ink,
              ),
            ),
            if (message != null) ...[
              const SizedBox(height: 6),
              Text(
                message!,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 14,
                  color: AppColors.inkSecondary,
                  height: 1.5,
                ),
              ),
            ],
            if (actions.isNotEmpty) ...[
              const SizedBox(height: 20),
              Wrap(
                alignment: WrapAlignment.center,
                spacing: 8,
                runSpacing: 8,
                children: actions,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
