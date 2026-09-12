import '../core/api_client.dart';
import '../models/catalog.dart';

class CatalogRepository {
  CatalogRepository(this._client);

  final ApiClient _client;

  Future<List<ServiceCategory>> fetchCategories() async {
    final json = await _client.get('/catalog');

    return (json['data'] as List<dynamic>)
        .map((e) => ServiceCategory.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}
