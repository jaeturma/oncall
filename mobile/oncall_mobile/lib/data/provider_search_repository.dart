import '../core/api_client.dart';
import '../models/paginated.dart';
import '../models/provider_profile.dart';

/// A search page plus the radius-search metadata the API returns alongside
/// it (Phase O §11) — `expandRadiusKm` is only ever non-null when a radius
/// search came back with zero results, so the UI can offer an explicit
/// "Expand search to Xkm" action rather than silently widening the search.
class ProviderSearchResult {
  ProviderSearchResult({
    required this.page,
    this.appliedRadiusKm,
    this.expandRadiusKm,
  });

  factory ProviderSearchResult.fromJson(Map<String, dynamic> json) {
    final meta = json['meta'] as Map<String, dynamic>? ?? {};

    return ProviderSearchResult(
      page: Paginated.fromJson(json, ProviderProfile.fromJson),
      appliedRadiusKm: meta['applied_radius_km'] as int?,
      expandRadiusKm: meta['expand_radius_km'] as int?,
    );
  }

  final Paginated<ProviderProfile> page;
  final int? appliedRadiusKm;
  final int? expandRadiusKm;
}

class ProviderSearchRepository {
  ProviderSearchRepository(this._client);

  final ApiClient _client;

  Future<ProviderSearchResult> search({
    required String help,
    required int provinceId,
    int? municipalityId,
    bool? availableOnly,
    int? minRating,
    String? sort,
    double? latitude,
    double? longitude,
    int? radiusKm,
    int page = 1,
  }) async {
    final json = await _client.get(
      '/providers/search',
      query: {
        'help': help,
        'province_id': provinceId,
        if (municipalityId != null) 'municipality_id': municipalityId,
        if (availableOnly != null) 'available_only': availableOnly,
        if (minRating != null) 'min_rating': minRating,
        if (sort != null) 'sort': sort,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
        if (radiusKm != null) 'radius_km': radiusKm,
        'page': page,
      },
    );

    return ProviderSearchResult.fromJson(json);
  }

  Future<ProviderProfile> show(
    int providerProfileId, {
    int? fromProvinceId,
    int? fromMunicipalityId,
  }) async {
    final json = await _client.get(
      '/providers/$providerProfileId',
      query: {
        if (fromProvinceId != null) 'from_province_id': fromProvinceId,
        if (fromMunicipalityId != null)
          'from_municipality_id': fromMunicipalityId,
      },
    );

    return ProviderProfile.fromJson(json['data'] as Map<String, dynamic>);
  }
}
