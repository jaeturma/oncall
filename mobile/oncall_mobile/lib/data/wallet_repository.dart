import '../core/api_client.dart';
import '../core/json.dart';
import '../models/paginated.dart';
import '../models/wallet.dart';

class WalletOverview {
  WalletOverview({
    required this.availableBalance,
    required this.pendingBalance,
    required this.page,
  });

  final double availableBalance;
  final double pendingBalance;
  final Paginated<WalletTransaction> page;
}

class WalletRepository {
  WalletRepository(this._client);

  final ApiClient _client;

  Future<WalletOverview> fetch({int page = 1}) async {
    final json = await _client.get('/wallet', query: {'page': page});

    return WalletOverview(
      // `WalletLedger::availableBalance()`/`pendingBalance()` return
      // formatted decimal strings (e.g. "300.00"), not JSON numbers.
      availableBalance: parseNum(json['available_balance']) ?? 0,
      pendingBalance: parseNum(json['pending_balance']) ?? 0,
      page: Paginated.fromJson(json, WalletTransaction.fromJson),
    );
  }
}
