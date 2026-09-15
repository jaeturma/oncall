import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/payment_repository.dart';
import '../../models/receipt.dart';
import '../../widgets/app_card.dart';
import '../../widgets/common.dart';

/// Read-only view of {@see \App\Services\ReceiptService::forPayment()}'s
/// sanitized payload (Phase Q §44). No PDF — rendered fields only, to avoid
/// pulling in a new dependency for a proportionate feature.
class ReceiptScreen extends StatefulWidget {
  const ReceiptScreen({super.key, required this.jobPaymentId});

  final int jobPaymentId;

  @override
  State<ReceiptScreen> createState() => _ReceiptScreenState();
}

class _ReceiptScreenState extends State<ReceiptScreen> {
  late Future<Receipt> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<PaymentRepository>().fetchReceipt(
      widget.jobPaymentId,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Receipt')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load this receipt.',
              onRetry: () => setState(
                () => _load = context.read<PaymentRepository>().fetchReceipt(
                  widget.jobPaymentId,
                ),
              ),
            );
          }

          final receipt = snapshot.data!;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              AppCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      receipt.receiptNumber ?? 'Receipt pending',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    if (receipt.service != null)
                      Text(receipt.service!, style: Theme.of(context).textTheme.bodySmall),
                    const SizedBox(height: 12),
                    _row('Customer', receipt.customerName ?? '—'),
                    _row('Provider', receipt.providerName ?? '—'),
                    _row('Payment method', receipt.paymentMethod ?? '—'),
                    _row('Reference', receipt.paymentReference ?? '—'),
                    _row('Confirmed', formatDateTime(receipt.confirmedAt)),
                    const Divider(height: 24),
                    _row('Gross amount', formatPeso(receipt.grossAmount)),
                    _row('Platform fee', formatPeso(receipt.platformFee)),
                    _row(
                      'Net amount',
                      formatPeso(receipt.netAmount),
                      emphasize: true,
                    ),
                    if (receipt.refundedAmount > 0) ...[
                      _row(
                        'Refunded',
                        '-${formatPeso(receipt.refundedAmount)}',
                      ),
                      _row(
                        'Refundable remaining',
                        formatPeso(receipt.refundableAmount),
                        emphasize: true,
                      ),
                    ],
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _row(String label, String value, {bool emphasize = false}) =>
      Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label),
            Text(
              value,
              style: emphasize
                  ? const TextStyle(fontWeight: FontWeight.bold)
                  : null,
            ),
          ],
        ),
      );
}
