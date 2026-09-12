import '../core/api_client.dart';
import '../models/catalog.dart';

class LocationRepository {
  LocationRepository(this._client);

  final ApiClient _client;

  Future<List<Province>> fetchProvinces() async {
    final json = await _client.get('/provinces');

    return (json['data'] as List<dynamic>)
        .map((e) => Province.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<Municipality>> fetchMunicipalities(int provinceId) async {
    final json = await _client.get('/provinces/$provinceId/municipalities');

    return (json['data'] as List<dynamic>)
        .map((e) => Municipality.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
