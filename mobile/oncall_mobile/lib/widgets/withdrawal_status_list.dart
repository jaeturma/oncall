import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Mirrors the static "How payouts work" explainer on Laravel's
/// `withdrawals/index.blade.php` aside — a one-time numbered list, not a
/// per-row stepper. Each withdrawal's own progress stays a single
/// [StatusChip], exactly like Laravel.
class WithdrawalStatusList extends StatelessWidget {
  const WithdrawalStatusList({super.key});

  static const _steps = [
    ('Requested', 'The amount is reserved from your balance.'),
    ('Accounting review', 'Staff check the request and payout details.'),
    ('Budget approval', 'The payout is approved for release.'),
    ('Cashier disbursement', 'Funds are sent to your payout account.'),
  ];

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'How payouts work',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 15,
            fontWeight: FontWeight.w600,
            color: AppColors.ink,
          ),
        ),
        const SizedBox(height: 12),
        for (var i = 0; i < _steps.length; i++) ...[
          if (i > 0) const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 24,
                height: 24,
                alignment: Alignment.center,
                decoration: const BoxDecoration(
                  color: AppColors.navy50,
                  shape: BoxShape.circle,
                ),
                child: Text(
                  '${i + 1}',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.navy800,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _steps[i].$1,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: AppColors.ink,
                      ),
                    ),
                    Text(
                      _steps[i].$2,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        color: AppColors.inkSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ],
    );
  }
}
