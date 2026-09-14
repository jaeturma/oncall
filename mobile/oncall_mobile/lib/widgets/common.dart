import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../theme/app_colors.dart';
import 'app_badge.dart';

class LoadingView extends StatelessWidget {
  const LoadingView({super.key});

  @override
  Widget build(BuildContext context) =>
      const Center(child: CircularProgressIndicator());
}

class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.message, this.onRetry});

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.error_outline,
              size: 40,
              color: AppColors.danger500,
            ),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            if (onRetry != null) ...[
              const SizedBox(height: 12),
              FilledButton(onPressed: onRetry, child: const Text('Try again')),
            ],
          ],
        ),
      ),
    );
  }
}

/// The lighter, text-only empty-state tier Laravel uses for nested/inline
/// empties (e.g. an empty wallet ledger) — no icon/card, just a message.
/// Full-section empties should use `AppEmptyState` instead.
class EmptyView extends StatelessWidget {
  const EmptyView({
    super.key,
    required this.message,
    this.icon = Icons.inbox_outlined,
  });

  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 40, color: AppColors.inkMuted),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.inkMuted),
            ),
          ],
        ),
      ),
    );
  }
}

/// Mirrors Laravel's `ui/status-badge.blade.php` bucket-mapping exactly —
/// every backend status vocabulary reads the same tone across the whole
/// product. Uses `AppBadge` for the actual pill rendering.
class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final String status;

  static const _success = {
    'ACTIVE',
    'VERIFIED',
    'COMPLETED',
    'ACCEPTED',
    'RELEASED',
    'APPROVED',
    'AVAILABLE',
    'RESOLVED',
    'DISBURSED',
    'PAID',
    'POSTED',
    'SENT',
    'DELIVERED',
  };
  static const _warning = {
    'PENDING',
    'SUBMITTED',
    'REQUESTED',
    'SEARCHING',
    'WARNING',
    'FOR_DISBURSEMENT',
    'ACCOUNTING_REVIEW',
    'BUDGET_APPROVAL',
    'OPEN',
    'RETURNED',
    'QUEUED',
  };
  static const _danger = {
    'REJECTED',
    'SUSPENDED',
    'RESTRICTED',
    'CANCELLED',
    'DISPUTED',
    'REVERSED',
    'EXPIRED',
    'DENIED',
    'UPHELD',
    'VOID',
    'DISMISSED',
    'FAILED',
  };
  static const _info = {
    'IN_PROGRESS',
    'ON_THE_WAY',
    'UNDER_REVIEW',
    'PARTIALLY_UPHELD',
  };

  @override
  Widget build(BuildContext context) {
    return AppBadge(
      label: humanizeStatus(status),
      tone: _toneFor(status),
      dot: true,
    );
  }

  AppBadgeTone _toneFor(String status) {
    if (_success.contains(status)) {
      return AppBadgeTone.success;
    }
    if (_warning.contains(status)) {
      return AppBadgeTone.warning;
    }
    if (_danger.contains(status)) {
      return AppBadgeTone.danger;
    }
    if (_info.contains(status)) {
      return AppBadgeTone.info;
    }

    return AppBadgeTone.neutral;
  }
}

Future<void> showErrorSnackBar(BuildContext context, String message) async {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(message), backgroundColor: AppColors.danger600),
  );
}

Future<void> showSuccessSnackBar(BuildContext context, String message) async {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(message), backgroundColor: AppColors.success600),
  );
}
