import 'package:flutter/material.dart';

import 'app_badge.dart';

/// "Verified Service" — every review the API returns is inherently tied to
/// a completed Oncall job (Phase P §13). This does not mean Oncall
/// independently verified every factual claim in the review text.
class VerifiedServiceBadge extends StatelessWidget {
  const VerifiedServiceBadge({super.key});

  @override
  Widget build(BuildContext context) => const AppBadge(
    label: 'Verified Service',
    tone: AppBadgeTone.success,
    icon: Icons.verified_outlined,
  );
}
