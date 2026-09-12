import '../core/api_client.dart';
import '../core/json.dart';
import '../models/paginated.dart';
import '../models/sponsor.dart';

class SponsorTotals {
  SponsorTotals({
    required this.available,
    required this.pending,
    required this.count,
  });

  final double available;
  final double pending;
  final int count;
}

class SponsorReferrals {
  SponsorReferrals({required this.page, required this.totals});

  final Paginated<SponsoredUser> page;
  final SponsorTotals totals;
}

class SponsorRepository {
  SponsorRepository(this._client);

  final ApiClient _client;

  Future<SponsorReferrals> fetch({int page = 1}) async {
    final json = await _client.get('/sponsor/referrals', query: {'page': page});
    final totals = json['totals'] as Map<String, dynamic>? ?? {};

    return SponsorReferrals(
      page: Paginated.fromJson(json, SponsoredUser.fromJson),
      totals: SponsorTotals(
        available: parseNum(totals['available']) ?? 0,
        pending: parseNum(totals['pending']) ?? 0,
        count: totals['count'] as int? ?? 0,
      ),
    );
  }
}
