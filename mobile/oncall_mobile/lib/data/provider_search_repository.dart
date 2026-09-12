import '../core/api_client.dart';
import '../models/paginated.dart';
import '../models/provider_profile.dart';

class ProviderSearchRepository {
  ProviderSearchRepository(this._client);

  final ApiClient _client;

  Future<Paginated<ProviderProfile>> search({
    required String help,
    required int provinceId,
    int? municipalityId,
    bool? availableOnly,
    int? minRating,
    String? sort,
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
        'page': page,
      },
    );

    return Paginated.fromJson(json, ProviderProfile.fromJson);
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
