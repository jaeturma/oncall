import '../core/api_client.dart';
import '../models/job.dart';
import '../models/receipt.dart';
import '../models/refund.dart';

/// Payment-side calls split out of [JobRepository] (Phase Q): confirming a
/// job payment, the server-controlled payment-method list, receipts, and
/// refund requests. Wallet/withdrawal browsing stays in
/// `wallet_repository.dart`/`withdrawal_repository.dart` — untouched.
class PaymentRepository {
  PaymentRepository(this._client);

  final ApiClient _client;

  /// The only payment methods Flutter is allowed to offer — never
  /// hard-coded client-side, always sourced from Laravel's PaymentSetting.
  Future<List<String>> fetchPaymentMethods() async {
    final json = await _client.get('/payment-methods');
    final data = json['data'];

    return data is List ? data.map((e) => e.toString()).toList() : const [];
  }

  Future<JobPayment> confirmPayment(
    int jobPaymentId, {
    required String paymentMethod,
    required String paymentReference,
  }) async {
    final json = await _client.patch(
      '/job-payments/$jobPaymentId/confirm',
      data: {
        'payment_method': paymentMethod,
        'payment_reference': paymentReference,
      },
    );

    return JobPayment.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Receipt> fetchReceipt(int jobPaymentId) async {
    final json = await _client.get('/payments/$jobPaymentId/receipt');

    return Receipt.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<List<Refund>> fetchRefunds(int jobPaymentId) async {
    final json = await _client.get('/payments/$jobPaymentId/refund-requests');
    final data = json['data'];

    return data is List
        ? data
              .map((e) => Refund.fromJson(e as Map<String, dynamic>))
              .toList()
        : const [];
  }

  Future<Refund> requestRefund(
    int jobPaymentId, {
    required String amount,
    required String reason,
  }) async {
    final json = await _client.post(
      '/payments/$jobPaymentId/refund-requests',
      data: {'amount': amount, 'reason': reason},
    );

    return Refund.fromJson(json['data'] as Map<String, dynamic>);
  }
}
