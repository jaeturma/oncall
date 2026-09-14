import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_radius.dart';
import '../theme/app_shadows.dart';

/// Mirrors Laravel's `.card`/`.card-interactive` — a soft-shadowed, thin-ringed
/// surface, not a heavy Material elevation. Use this instead of a bare
/// `Card(...)` everywhere a surface needs the Oncall brand treatment.
class AppCard extends StatefulWidget {
  const AppCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding = const EdgeInsets.all(20),
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry padding;

  bool get interactive => onTap != null;

  @override
  State<AppCard> createState() => _AppCardState();
}

class _AppCardState extends State<AppCard> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final decoration = BoxDecoration(
      color: AppColors.surface,
      borderRadius: AppRadius.cardRadius,
      border: Border.all(color: _pressed ? AppColors.navy200 : AppColors.line),
      boxShadow: _pressed ? AppShadows.float : AppShadows.card,
    );

    final content = AnimatedContainer(
      duration: const Duration(milliseconds: 120),
      decoration: decoration,
      clipBehavior: Clip.antiAlias,
      child: Padding(padding: widget.padding, child: widget.child),
    );

    if (!widget.interactive) {
      return content;
    }

    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapCancel: () => setState(() => _pressed = false),
      onTapUp: (_) => setState(() => _pressed = false),
      child: Material(
        color: Colors.transparent,
        borderRadius: AppRadius.cardRadius,
        child: InkWell(
          onTap: widget.onTap,
          borderRadius: AppRadius.cardRadius,
          child: content,
        ),
      ),
    );
  }
}
