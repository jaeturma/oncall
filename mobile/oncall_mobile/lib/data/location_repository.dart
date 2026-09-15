import '../core/api_client.dart';
import '../core/json.dart';
import '../models/catalog.dart';

/// Result of resolving a device coordinate pair down to the PH admin
/// hierarchy — always Laravel-resolved (see `GeocodingService`), never
/// computed on-device. `label` is a display-only enrichment that may be
/// null even when `municipality` resolved.
class ReverseGeocodeResult {
  ReverseGeocodeResult({
    this.province,
    this.municipality,
    this.barangay,
    this.label,
  });

  factory ReverseGeocodeResult.fromJson(Map<String, dynamic> json) =>
      ReverseGeocodeResult(
        province: IdName.fromJsonOrNull(json['province']),
        municipality: IdName.fromJsonOrNull(json['municipality']),
        barangay: IdName.fromJsonOrNull(json['barangay']),
        label: json['label'] as String?,
      );

  final IdName? province;
  final IdName? municipality;
  final IdName? barangay;
  final String? label;
}

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

  Future<List<Barangay>> fetchBarangays(int municipalityId) async {
    final json = await _client.get(
      '/locations/municipalities/$municipalityId/barangays',
    );

    return (json['data'] as List<dynamic>)
        .map((e) => Barangay.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<ReverseGeocodeResult> reverseGeocode(
    double latitude,
    double longitude,
  ) async {
    final json = await _client.get(
      '/locations/reverse-geocode',
      query: {'latitude': latitude, 'longitude': longitude},
    );

    return ReverseGeocodeResult.fromJson(json['data'] as Map<String, dynamic>);
  }
}
