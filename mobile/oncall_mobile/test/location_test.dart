// Phase O: location/maps. Model-parsing tests for the new JSON shapes
// (never fabricating a coordinate that wasn't actually returned), plus
// LocationState's permission/failure-state handling via a fake
// DeviceLocationService (no real platform channel involved).
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/core/api_client.dart';
import 'package:oncall_mobile/core/json.dart';
import 'package:oncall_mobile/core/token_storage.dart';
import 'package:oncall_mobile/data/catalog_repository.dart';
import 'package:oncall_mobile/data/location_repository.dart';
import 'package:oncall_mobile/data/provider_search_repository.dart';
import 'package:oncall_mobile/models/catalog.dart';
import 'package:oncall_mobile/models/provider_profile.dart';
import 'package:oncall_mobile/services/device_location_service.dart';
import 'package:oncall_mobile/state/location_state.dart';

class _FakeDeviceLocationService extends DeviceLocationService {
  _FakeDeviceLocationService(this._result);

  final DeviceLocationResult _result;

  @override
  Future<DeviceLocationResult> currentPosition() async => _result;
}

LocationState _buildLocationState(DeviceLocationResult result) {
  final apiClient = ApiClient(tokenStorage: TokenStorage());

  return LocationState(
    deviceLocationService: _FakeDeviceLocationService(result),
    locationRepository: LocationRepository(apiClient),
    catalogRepository: CatalogRepository(apiClient),
  );
}

void main() {
  group('GeoPoint.fromJsonOrNull', () {
    test('parses a marker point', () {
      final point = GeoPoint.fromJsonOrNull({
        'latitude': '7.123400',
        'longitude': '125.654300',
      });

      expect(point!.latitude, 7.1234);
      expect(point.longitude, 125.6543);
    });

    test('returns null rather than fabricating a point', () {
      expect(GeoPoint.fromJsonOrNull(null), isNull);
    });
  });

  group('Barangay.fromJson', () {
    test('parses id and name only — no coordinates exposed client-side', () {
      final barangay = Barangay.fromJson({'id': 3, 'name': 'Poblacion'});

      expect(barangay.id, 3);
      expect(barangay.name, 'Poblacion');
    });
  });

  group('ProviderProfile.fromJson location fields', () {
    test('a search result never carries the providers own coordinates', () {
      final profile = ProviderProfile.fromJson({
        'id': 1,
        'availability_status': 'AVAILABLE',
        'available_now': true,
        'verification_status': 'VERIFIED',
        'completed_jobs': 3,
        'verified_document_types': [],
        'distance_km': '3.20',
        'service_radius_km': 25,
        'area_marker': {'latitude': 7.1, 'longitude': 125.6},
        'services': [],
        // latitude/longitude/location_source deliberately absent, as the
        // API never sends them for a viewer who isn't the provider.
      });

      expect(profile.distanceKm, 3.2);
      expect(profile.serviceRadiusKm, 25);
      expect(profile.areaMarker!.latitude, 7.1);
      expect(profile.latitude, isNull);
      expect(profile.longitude, isNull);
    });

    test('the owners own profile view includes exact coordinates', () {
      final profile = ProviderProfile.fromJson({
        'id': 1,
        'availability_status': 'AVAILABLE',
        'available_now': true,
        'verification_status': 'VERIFIED',
        'completed_jobs': 3,
        'verified_document_types': [],
        'latitude': 7.123456,
        'longitude': 125.654321,
        'location_source': 'MANUAL',
        'services': [],
      });

      expect(profile.latitude, 7.123456);
      expect(profile.longitude, 125.654321);
      expect(profile.locationSource, 'MANUAL');
    });
  });

  group('ProviderSearchResult.fromJson', () {
    test('parses the expand-radius meta', () {
      final result = ProviderSearchResult.fromJson({
        'data': [],
        'meta': {
          'current_page': 1,
          'last_page': 1,
          'total': 0,
          'applied_radius_km': 5,
          'expand_radius_km': 10,
        },
      });

      expect(result.appliedRadiusKm, 5);
      expect(result.expandRadiusKm, 10);
      expect(result.page.items, isEmpty);
    });

    test('expand radius is null when no radius search was applied', () {
      final result = ProviderSearchResult.fromJson({
        'data': [],
        'meta': {'current_page': 1, 'last_page': 1, 'total': 0},
      });

      expect(result.appliedRadiusKm, isNull);
      expect(result.expandRadiusKm, isNull);
    });
  });

  group('ReverseGeocodeResult.fromJson', () {
    test('parses resolved area references', () {
      final result = ReverseGeocodeResult.fromJson({
        'province': {'id': 1, 'name': 'Davao del Norte'},
        'municipality': {'id': 2, 'name': 'Tagum City'},
        'barangay': {'id': 3, 'name': 'Apokon'},
        'label': null,
      });

      expect(result.province?.name, 'Davao del Norte');
      expect(result.municipality?.name, 'Tagum City');
      expect(result.barangay?.name, 'Apokon');
      expect(result.label, isNull);
    });
  });

  group('LocationState.useCurrentLocation', () {
    test('captures coordinates on a granted result', () async {
      final state = _buildLocationState(
        DeviceLocationResult.granted(7.1, 125.6),
      );

      final granted = await state.useCurrentLocation();

      expect(granted, isTrue);
      expect(state.latitude, 7.1);
      expect(state.longitude, 125.6);
      expect(state.hasCoordinates, isTrue);
      expect(state.isLocating, isFalse);
    });

    test('surfaces a denied status without coordinates', () async {
      final state = _buildLocationState(
        DeviceLocationResult.status(DeviceLocationStatus.denied),
      );

      final granted = await state.useCurrentLocation();

      expect(granted, isFalse);
      expect(state.lastStatus, DeviceLocationStatus.denied);
      expect(state.hasCoordinates, isFalse);
    });

    test('surfaces a permanently-denied status', () async {
      final state = _buildLocationState(
        DeviceLocationResult.status(DeviceLocationStatus.permanentlyDenied),
      );

      await state.useCurrentLocation();

      expect(state.lastStatus, DeviceLocationStatus.permanentlyDenied);
    });

    test('surfaces a service-disabled status', () async {
      final state = _buildLocationState(
        DeviceLocationResult.status(DeviceLocationStatus.serviceDisabled),
      );

      await state.useCurrentLocation();

      expect(state.lastStatus, DeviceLocationStatus.serviceDisabled);
    });

    test('clear() resets coordinates and status', () async {
      final state = _buildLocationState(
        DeviceLocationResult.granted(7.1, 125.6),
      );
      await state.useCurrentLocation();

      state.clear();

      expect(state.hasCoordinates, isFalse);
      expect(state.lastStatus, isNull);
    });
  });
}
