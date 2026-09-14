import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Reproduces the Laravel mark (`resources/views/components/ui/logo.blade.php`)
/// — a navy box containing a gold shield-with-checkmark, translated directly
/// from that file's two SVG path definitions into `Path` calls in the same
/// 24x24 coordinate space. No raster/SVG asset exists to reuse; Laravel
/// itself builds the mark inline.
enum OncallLogoSize { md, lg }

class OncallLogo extends StatelessWidget {
  const OncallLogo({
    super.key,
    this.size = OncallLogoSize.md,
    this.onDark = false,
    this.showWordmark = true,
  });

  final OncallLogoSize size;
  final bool onDark;
  final bool showWordmark;

  @override
  Widget build(BuildContext context) {
    final boxSize = size == OncallLogoSize.lg ? 44.0 : 36.0;
    final iconSize = size == OncallLogoSize.lg ? 28.0 : 20.0;
    final radius = size == OncallLogoSize.lg ? 12.0 : 8.0;
    final wordSize = size == OncallLogoSize.lg ? 20.0 : 16.0;

    final mark = Container(
      width: boxSize,
      height: boxSize,
      decoration: BoxDecoration(
        color: AppColors.navy900,
        borderRadius: BorderRadius.circular(radius),
      ),
      alignment: Alignment.center,
      child: SizedBox(
        width: iconSize,
        height: iconSize,
        child: CustomPaint(painter: _ShieldCheckPainter()),
      ),
    );

    if (!showWordmark) {
      return mark;
    }

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        mark,
        const SizedBox(width: 10),
        Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Oncall',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: wordSize,
                fontWeight: FontWeight.w700,
                height: 1,
                letterSpacing: -0.2,
                color: onDark ? Colors.white : AppColors.navy900,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'PHILIPPINES',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: wordSize * 0.44,
                fontWeight: FontWeight.w600,
                height: 1,
                letterSpacing: 1.6,
                color: onDark ? AppColors.gold300 : AppColors.gold700,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

/// Shield outline + checkmark, ported 1:1 from `logo.blade.php`'s two SVG
/// `<path>` `d` values in the original 0-24 viewBox space.
class _ShieldCheckPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final scale = size.width / 24;
    final paint = Paint()
      ..color = AppColors.gold400
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2 * scale
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final shield = Path()..moveTo(12 * scale, 2.75 * scale);
    shield.cubicTo(
      9.5 * scale,
      4.75 * scale,
      6.6 * scale,
      5.75 * scale,
      3.75 * scale,
      5.85 * scale,
    );
    shield.lineTo(3.75 * scale, 12.25 * scale);
    shield.cubicTo(
      3.75 * scale,
      17.25 * scale,
      7.25 * scale,
      21.15 * scale,
      12 * scale,
      22.25 * scale,
    );
    shield.cubicTo(
      16.75 * scale,
      21.15 * scale,
      20.25 * scale,
      17.25 * scale,
      20.25 * scale,
      12.25 * scale,
    );
    shield.lineTo(20.25 * scale, 5.85 * scale);
    shield.cubicTo(
      17.4 * scale,
      5.75 * scale,
      14.5 * scale,
      4.75 * scale,
      12 * scale,
      2.75 * scale,
    );
    shield.close();

    final check = Path()..moveTo(9 * scale, 12.3 * scale);
    check.lineTo(11 * scale, 14.3 * scale);
    check.lineTo(15 * scale, 10 * scale);

    canvas.drawPath(shield, paint);
    canvas.drawPath(check, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
