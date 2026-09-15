import '../core/api_client.dart';
import '../models/catalog.dart';

/// The admin-configured search-radius policy, bundled onto `/catalog` since
/// that's already the client's general-config bootstrap call (Phase O) —
/// never the map/geocoding switches themselves, those stay admin-only.
class SearchRadiusPolicy {
  SearchRadiusPolicy({required this.choices, required this.defaultRadiusKm});

  factory SearchRadiusPolicy.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};

    return SearchRadiusPolicy(
      choices:
          (meta['search_radius_choices'] as List<dynamic>?)
              ?.map((e) => e as int)
              .toList() ??
          const [5, 10, 15, 25, 50],
      defaultRadiusKm: meta['default_search_radius_km'] as int? ?? 10,
    );
  }

  final List<int> choices;
  final int defaultRadiusKm;
}

class CatalogRepository {
  CatalogRepository(this._client);

  final ApiClient _client;

  Future<List<ServiceCategory>> fetchCategories() async {
    final json = await _client.get('/catalog');

    return (json['data'] as List<dynamic>)
        .map((e) => ServiceCategory.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<SearchRadiusPolicy> fetchSearchRadiusPolicy() async {
    final json = await _client.get('/catalog');

    return SearchRadiusPolicy.fromJson(json);
  }
}
