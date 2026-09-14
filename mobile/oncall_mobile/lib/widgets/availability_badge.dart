import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors Laravel's `ui/availability-badge.blade.php`. Switches directly on
/// the wire strings `AvailabilityStatus` sends (`AVAILABLE`/`BUSY`/
/// `BY_APPOINTMENT`/`OFFLINE`, see `app/Enums/AvailabilityStatus.php`) rather
/// than a separate Dart enum — `ProviderProfile.availabilityStatus` is
/// already one of these. Text always accompanies the dot; color is never the
/// only signal.
class AvailabilityBadge extends StatefulWidget {
  const AvailabilityBadge({
    super.key,
    required this.status,
    this.onDark = false,
  });

  final String status;
  final bool onDark;

  @override
  State<AvailabilityBadge> createState() => _AvailabilityBadgeState();
}

class _AvailabilityBadgeState extends State<AvailabilityBadge>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat();
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  (Color dot, String label, bool pulse) get _meta => switch (widget.status) {
    'AVAILABLE' => (AppColors.success500, 'Available now', true),
    'BY_APPOINTMENT' => (AppColors.info600, 'By appointment', false),
    'BUSY' => (AppColors.warning500, 'Busy', false),
    _ => (AppColors.slate400, 'Offline', false),
  };

  @override
  Widget build(BuildContext context) {
    final (dot, label, pulse) = _meta;
    final tone = widget.onDark
        ? Colors.white.withValues(alpha: 0.1)
        : switch (widget.status) {
            'AVAILABLE' => AppColors.success50,
            'BY_APPOINTMENT' => AppColors.info50,
            'BUSY' => AppColors.warning50,
            _ => AppColors.slate100,
          };
    final textColor = widget.onDark
        ? Colors.white
        : switch (widget.status) {
            'AVAILABLE' => AppColors.success800,
            'BY_APPOINTMENT' => AppColors.info800,
            'BUSY' => AppColors.warning800,
            _ => AppColors.slate600,
          };

    return DecoratedBox(
      decoration: BoxDecoration(
        color: tone,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 8,
              height: 8,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  if (pulse)
                    AnimatedBuilder(
                      animation: _pulseController,
                      builder: (context, child) {
                        final t = _pulseController.value;
                        return Opacity(
                          opacity: (1 - t) * 0.6,
                          child: Transform.scale(
                            scale: 1 + t * 1.8,
                            child: Container(
                              decoration: BoxDecoration(
                                color: dot,
                                shape: BoxShape.circle,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  Container(
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: dot,
                      shape: BoxShape.circle,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: textColor,
                height: 1,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
