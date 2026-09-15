import 'package:geolocator/geolocator.dart';

/// Why a device-location request did or didn't produce a position — every
/// case the phase's Flutter UX section asks the UI to handle explicitly
/// (permission denied, permanently denied, services disabled, unable to
/// locate, unsupported/insecure context, network/other failure).
enum DeviceLocationStatus {
  granted,
  denied,
  permanentlyDenied,
  serviceDisabled,
  unsupported,
  error,
}

class DeviceLocationResult {
  DeviceLocationResult._(this.status, {this.latitude, this.longitude});

  factory DeviceLocationResult.granted(double latitude, double longitude) =>
      DeviceLocationResult._(
        DeviceLocationStatus.granted,
        latitude: latitude,
        longitude: longitude,
      );

  factory DeviceLocationResult.status(DeviceLocationStatus status) =>
      DeviceLocationResult._(status);

  final DeviceLocationStatus status;
  final double? latitude;
  final double? longitude;

  bool get isGranted => status == DeviceLocationStatus.granted;
}

/// Wraps `geolocator` so every call site gets a typed result instead of a
/// thrown exception — mirrors the rest of the app's "catch locally, surface
/// a typed state" convention (see `PushService`). Client GPS can be spoofed
/// and is never treated as identity/anti-fraud proof — this is a matching
/// input only (Phase O §27); Laravel remains authoritative for distance and
/// eligibility regardless of what this returns.
class DeviceLocationService {
  Future<DeviceLocationResult> currentPosition() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        return DeviceLocationResult.status(
          DeviceLocationStatus.serviceDisabled,
        );
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.deniedForever) {
        return DeviceLocationResult.status(
          DeviceLocationStatus.permanentlyDenied,
        );
      }
      if (permission == LocationPermission.denied) {
        return DeviceLocationResult.status(DeviceLocationStatus.denied);
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 15),
        ),
      );

      return DeviceLocationResult.granted(
        position.latitude,
        position.longitude,
      );
    } on LocationServiceDisabledException {
      return DeviceLocationResult.status(
        DeviceLocationStatus.serviceDisabled,
      );
    } on PermissionDefinitionsNotFoundException {
      // Platform manifest/plist is missing the usage-description keys —
      // treat as unsupported rather than crashing the request.
      return DeviceLocationResult.status(DeviceLocationStatus.unsupported);
    } catch (_) {
      // Includes the web "insecure context" / unsupported-platform cases,
      // and a plain timeout/network failure — all surfaced the same way so
      // the UI can fall back to manual location.
      return DeviceLocationResult.status(DeviceLocationStatus.error);
    }
  }
}
