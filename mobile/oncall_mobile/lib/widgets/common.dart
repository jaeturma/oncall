import 'package:flutter/material.dart';

import '../core/formatters.dart';

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
            const Icon(Icons.error_outline, size: 40, color: Colors.redAccent),
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
            Icon(icon, size: 40, color: Colors.grey),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key, this.color});

  final String status;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final resolvedColor = color ?? _colorFor(status);

    return Chip(
      label: Text(
        humanizeStatus(status),
        style: TextStyle(
          color: resolvedColor,
          fontWeight: FontWeight.w600,
          fontSize: 12,
        ),
      ),
      backgroundColor: resolvedColor.withValues(alpha: 0.12),
      side: BorderSide.none,
      padding: EdgeInsets.zero,
      visualDensity: VisualDensity.compact,
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }

  Color _colorFor(String status) {
    const positive = {
      'ACTIVE',
      'AVAILABLE',
      'VERIFIED',
      'COMPLETED',
      'ACCEPTED',
      'RELEASED',
      'POSTED',
      'APPROVED',
      'CONFIRMATION',
    };
    const negative = {
      'SUSPENDED',
      'REJECTED',
      'CANCELLED',
      'RESTRICTED',
      'REVERSED',
      'DISPUTED',
      'RETURNED',
    };
    const warning = {
      'PENDING',
      'SUBMITTED',
      'WARNING',
      'UNDER_REVIEW',
      'REQUESTED',
      'OPEN',
      'BUSY',
    };

    if (positive.contains(status)) {
      return Colors.green.shade700;
    }
    if (negative.contains(status)) {
      return Colors.red.shade700;
    }
    if (warning.contains(status)) {
      return Colors.orange.shade800;
    }

    return Colors.blueGrey;
  }
}

Future<void> showErrorSnackBar(BuildContext context, String message) async {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(message), backgroundColor: Colors.red.shade700),
  );
}

Future<void> showSuccessSnackBar(BuildContext context, String message) async {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(message), backgroundColor: Colors.green.shade700),
  );
}
