import '../core/api_client.dart';
import '../core/json.dart';
import '../models/paginated.dart';
import '../models/wallet.dart';

class WithdrawalsPage {
  WithdrawalsPage({
    required this.availableBalance,
    required this.canRequest,
    required this.page,
  });

  final double availableBalance;
  final bool canRequest;
  final Paginated<Withdrawal> page;
}

class WithdrawalRepository {
  WithdrawalRepository(this._client);

  final ApiClient _client;

  Future<WithdrawalsPage> list({int page = 1}) async {
    final json = await _client.get(
      '/wallet/withdrawals',
      query: {'page': page},
    );

    return WithdrawalsPage(
      availableBalance: parseNum(json['available_balance']) ?? 0,
      canRequest: json['can_request'] as bool? ?? false,
      page: Paginated.fromJson(json, Withdrawal.fromJson),
    );
  }

  Future<Withdrawal> request({
    required double amount,
    required String payoutMethod,
    required String payoutReference,
  }) async {
    final json = await _client.post(
      '/wallet/withdrawals',
      data: {
        'amount': amount,
        'payout_method': payoutMethod,
        'payout_reference': payoutReference,
      },
    );

    return Withdrawal.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> cancel(int withdrawalId) =>
      _client.patch('/wallet/withdrawals/$withdrawalId/cancel');
}
