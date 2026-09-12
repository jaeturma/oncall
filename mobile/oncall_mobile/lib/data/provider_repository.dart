import '../core/api_client.dart';
import '../core/api_exception.dart';
import '../models/provider_profile.dart';

class ProviderProfileInput {
  ProviderProfileInput({
    required this.provinceId,
    required this.municipalityId,
    this.bio,
    this.serviceRadiusKm,
    required this.serviceIds,
    this.credentials = const [],
  });

  final int provinceId;
  final int municipalityId;
  final String? bio;
  final int? serviceRadiusKm;
  final List<int> serviceIds;
  final List<String> credentials;

  Map<String, dynamic> toJson() => {
    'province_id': provinceId,
    'municipality_id': municipalityId,
    if (bio != null) 'bio': bio,
    if (serviceRadiusKm != null) 'service_radius_km': serviceRadiusKm,
    'service_ids': serviceIds,
    'credentials_metadata': credentials,
  };
}

/// The current provider's own business profile — never another provider's.
class ProviderRepository {
  ProviderRepository(this._client);

  final ApiClient _client;

  Future<ProviderProfile?> fetchMine() async {
    try {
      final json = await _client.get('/provider/profile');

      return ProviderProfile.fromJson(json['data'] as Map<String, dynamic>);
    } on ApiException catch (error) {
      if (error.statusCode == 404) {
        return null;
      }
      rethrow;
    }
  }

  Future<ProviderProfile> create(ProviderProfileInput input) async {
    final json = await _client.post('/provider/profile', data: input.toJson());

    return ProviderProfile.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<ProviderProfile> update(
    int providerProfileId,
    ProviderProfileInput input,
  ) async {
    final json = await _client.patch(
      '/provider/profiles/$providerProfileId',
      data: input.toJson(),
    );

    return ProviderProfile.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<ProviderProfile> updateAvailability(
    int providerProfileId,
    String availabilityStatus,
  ) async {
    final json = await _client.patch(
      '/provider/profiles/$providerProfileId/availability',
      data: {'availability_status': availabilityStatus},
    );

    return ProviderProfile.fromJson(json['data'] as Map<String, dynamic>);
  }
}
