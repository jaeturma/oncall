// Phase Q: the receipt screen renders the sanitized payload
// ReceiptService::forPayment() builds, without a real network call (a fake
// PaymentRepository stands in, mirroring how other widget tests in this
// suite avoid hitting the network — see submit_review_widget_test.dart).
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/core/api_client.dart';
import 'package:oncall_mobile/core/token_storage.dart';
import 'package:oncall_mobile/data/payment_repository.dart';
import 'package:oncall_mobile/models/receipt.dart';
import 'package:oncall_mobile/screens/shared/receipt_screen.dart';
import 'package:oncall_mobile/theme/app_theme.dart';
import 'package:provider/provider.dart';

class _FakePaymentRepository extends PaymentRepository {
  _FakePaymentRepository() : super(ApiClient(tokenStorage: TokenStorage()));

  @override
  Future<Receipt> fetchReceipt(int jobPaymentId) async => Receipt.fromJson({
    'receipt_number': 'ONC-000042',
    'status': 'RELEASED',
    'purpose': 'SERVICE_TRANSACTION',
    'gross_amount': '1000.00',
    'platform_fee': '150.00',
    'net_amount': '850.00',
    'refunded_amount': '0.00',
    'refundable_amount': '850.00',
    'job_id': 7,
    'service': 'Plumbing repair',
    'customer_name': 'Juan Dela Cruz',
    'provider_name': 'Maria Santos',
  });
}

Widget _wrap(Widget child) => Provider<PaymentRepository>.value(
  value: _FakePaymentRepository(),
  child: MaterialApp(theme: AppTheme.light, home: child),
);

void main() {
  testWidgets('renders the receipt number, parties, and net amount', (
    tester,
  ) async {
    await tester.pumpWidget(_wrap(const ReceiptScreen(jobPaymentId: 42)));
    await tester.pumpAndSettle();

    expect(find.text('ONC-000042'), findsOneWidget);
    expect(find.text('Juan Dela Cruz'), findsOneWidget);
    expect(find.text('Maria Santos'), findsOneWidget);
    expect(find.textContaining('850'), findsWidgets);
  });

  testWidgets('does not show a refunded line when nothing was refunded', (
    tester,
  ) async {
    await tester.pumpWidget(_wrap(const ReceiptScreen(jobPaymentId: 42)));
    await tester.pumpAndSettle();

    expect(find.text('Refunded'), findsNothing);
  });
}
