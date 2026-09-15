import 'package:flutter/foundation.dart';

import '../data/catalog_repository.dart';
import '../data/location_repository.dart';
import '../services/device_location_service.dart';

/// Shared "where am I" state: the last device-location attempt's outcome,
/// and the admin-configured search-radius policy. Any screen that offers a
/// "Use my current location" action or a radius picker reads/drives this
/// rather than re-implementing the geolocator + reverse-geocode dance —
/// mirrors [AuthState]'s "single source of truth, plain mutable fields +
/// notifyListeners()" shape.
///
/// This never decides eligibility or computes distance itself — Laravel
/// remains authoritative for both (Phase O). It only carries a coordinate
/// pair (and, once resolved, a human-readable area) for the UI to display
/// and to send along with a search/profile/service-request request.
class LocationState extends ChangeNotifier {
  LocationState({
    required DeviceLocationService deviceLocationService,
    required LocationRepository locationRepository,
    required CatalogRepository catalogRepository,
  }) : _deviceLocationService = deviceLocationService,
       _locationRepository = locationRepository,
       _catalogRepository = catalogRepository;

  final DeviceLocationService _deviceLocationService;
  final LocationRepository _locationRepository;
  final CatalogRepository _catalogRepository;

  DeviceLocationStatus? lastStatus;
  double? latitude;
  double? longitude;
  ReverseGeocodeResult? resolvedArea;
  bool isLocating = false;

  List<int> radiusChoices = const [5, 10, 15, 25, 50];
  int defaultRadiusKm = 10;
  bool _radiusPolicyLoaded = false;

  bool get hasCoordinates => latitude != null && longitude != null;

  Future<void> loadRadiusPolicy() async {
    if (_radiusPolicyLoaded) {
      return;
    }
    try {
      final policy = await _catalogRepository.fetchSearchRadiusPolicy();
      radiusChoices = policy.choices;
      defaultRadiusKm = policy.defaultRadiusKm;
      _radiusPolicyLoaded = true;
      notifyListeners();
    } catch (_) {
      // Keep the built-in defaults; a radius picker still works, just
      // without the admin's exact configured choices until this succeeds.
    }
  }

  /// Requests the device's current position and, on success, resolves it to
  /// a province/municipality/barangay via the server. Every failure mode is
  /// captured in [lastStatus] rather than thrown — callers show UI based on
  /// that (denied, permanently denied, service disabled, unsupported,
  /// error) and should always keep manual selection available alongside
  /// this (Phase O §6/§28).
  Future<bool> useCurrentLocation() async {
    isLocating = true;
    notifyListeners();

    final result = await _deviceLocationService.currentPosition();
    lastStatus = result.status;

    if (!result.isGranted) {
      isLocating = false;
      notifyListeners();

      return false;
    }

    latitude = result.latitude;
    longitude = result.longitude;

    try {
      resolvedArea = await _locationRepository.reverseGeocode(
        result.latitude!,
        result.longitude!,
      );
    } catch (_) {
      // Coordinates are still usable even if the area label lookup failed —
      // the search/profile/request API calls validate lat/lng server-side
      // regardless of whether reverse geocoding succeeded here.
      resolvedArea = null;
    }

    isLocating = false;
    notifyListeners();

    return true;
  }

  void clear() {
    latitude = null;
    longitude = null;
    resolvedArea = null;
    lastStatus = null;
    notifyListeners();
  }
}
