import 'package:flutter/material.dart';

import '../theme/app_typography.dart';

/// Mirrors Laravel's `.eyebrow` section-label style — uppercase, wide-tracked,
/// small and muted. A plain `TextStyle` can't uppercase its own text, hence
/// this thin wrapper.
class EyebrowText extends StatelessWidget {
  const EyebrowText(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(text.toUpperCase(), style: AppTypography.eyebrow);
  }
}
