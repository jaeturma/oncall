import '../core/api_client.dart';
import '../models/enforcement_case.dart';
import '../models/paginated.dart';

class EnforcementRepository {
  EnforcementRepository(this._client);

  final ApiClient _client;

  Future<Paginated<EnforcementCase>> list({int page = 1}) async {
    final json = await _client.get('/enforcement-cases', query: {'page': page});

    return Paginated.fromJson(json, EnforcementCase.fromJson);
  }

  Future<EnforcementCase> show(int id) async {
    final json = await _client.get('/enforcement-cases/$id');

    return EnforcementCase.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<EnforcementCase> appeal(int id, {required String reason}) async {
    final json = await _client.post(
      '/enforcement-cases/$id/appeal',
      data: {'appeal_reason': reason},
    );

    return EnforcementCase.fromJson(json['data'] as Map<String, dynamic>);
  }
}
