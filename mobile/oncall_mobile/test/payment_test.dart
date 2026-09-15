// Phase Q: payments, marketplace transactions, fees, refunds, reconciliation.
// Model-parsing tests for the new JSON shapes — mirrors test/review_test.dart.
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/models/job.dart';
import 'package:oncall_mobile/models/payment_attempt.dart';
import 'package:oncall_mobile/models/receipt.dart';
import 'package:oncall_mobile/models/refund.dart';

void main() {
  group('JobPayment.fromJson', () {
    test('parses the extended Phase Q fields', () {
      final payment = JobPayment.fromJson({
        'id': 1,
        'status': 'RELEASED',
        'gross_amount': '1000.00',
        'platform_fee': '150.00',
        'net_amount': '850.00',
        'purpose': 'SERVICE_TRANSACTION',
        'receipt_number': 'ONC-000001',
        'refunded_amount': '200.00',
        'refundable_amount': '650.00',
      });

      expect(payment.receiptNumber, 'ONC-000001');
      expect(payment.refundedAmount, 200.00);
      expect(payment.refundableAmount, 650.00);
      expect(payment.isRefundable, isTrue);
    });

    test('a fully refunded released payment is no longer refundable', () {
      final payment = JobPayment.fromJson({
        'id': 1,
        'status': 'RELEASED',
        'gross_amount': '1000.00',
        'platform_fee': '150.00',
        'net_amount': '850.00',
        'refundable_amount': '0.00',
      });

      expect(payment.isRefundable, isFalse);
    });

    test('a pending payment is never refundable regardless of amount', () {
      final payment = JobPayment.fromJson({
        'id': 1,
        'status': 'PENDING',
        'gross_amount': '1000.00',
        'platform_fee': '150.00',
        'net_amount': '850.00',
        'refundable_amount': '850.00',
      });

      expect(payment.isRefundable, isFalse);
    });
  });

  group('PaymentAttempt.fromJson', () {
    test('parses a verified attempt', () {
      final attempt = PaymentAttempt.fromJson({
        'id': 1,
        'method': 'GCash',
        'status': 'VERIFIED',
        'gateway': 'MANUAL',
        'gateway_reference': 'GC-1',
      });

      expect(attempt.method, 'GCash');
      expect(attempt.status, 'VERIFIED');
      expect(attempt.rejectionReason, isNull);
    });
  });

  group('Refund.fromJson', () {
    test('parses a requested refund as not decided', () {
      final refund = Refund.fromJson({
        'id': 1,
        'job_payment_id': 5,
        'amount': '200.00',
        'status': 'REQUESTED',
        'reason': 'Partial dissatisfaction',
      });

      expect(refund.amount, 200.00);
      expect(refund.isDecided, isFalse);
    });

    test('a completed refund is decided', () {
      final refund = Refund.fromJson({
        'id': 1,
        'job_payment_id': 5,
        'amount': '200.00',
        'status': 'COMPLETED',
        'reason': 'Partial dissatisfaction',
      });

      expect(refund.isDecided, isTrue);
    });
  });

  group('Receipt.fromJson', () {
    test('parses a full receipt payload', () {
      final receipt = Receipt.fromJson({
        'receipt_number': 'ONC-000001',
        'status': 'RELEASED',
        'purpose': 'SERVICE_TRANSACTION',
        'gross_amount': '1000.00',
        'platform_fee': '150.00',
        'net_amount': '850.00',
        'refunded_amount': '0.00',
        'refundable_amount': '850.00',
        'job_id': 9,
        'service': 'Plumbing',
        'customer_name': 'Juan',
        'provider_name': 'Maria',
      });

      expect(receipt.receiptNumber, 'ONC-000001');
      expect(receipt.netAmount, 850.00);
      expect(receipt.jobId, 9);
    });
  });
}
