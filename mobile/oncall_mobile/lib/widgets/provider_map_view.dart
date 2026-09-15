import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart' as latlong;

import '../models/provider_profile.dart';
import '../theme/app_colors.dart';

/// Renders providers as map markers using their sanitized `area_marker`
/// (a barangay/municipality centroid — never the provider's own exact
/// coordinates, see `ProviderProfileResource`). OpenStreetMap tiles, no API
/// key required (Phase O map provider choice — see
/// docs/architecture/LOCATION_MAPS_ARCHITECTURE.md).
///
/// Tile load failure (no network, tile server unreachable) does not throw —
/// `flutter_map` just renders blank tiles — so the caller's List View
/// fallback (via the List/Map toggle) is what actually keeps the screen
/// usable when maps are unavailable, per the phase's completion checklist.
class ProviderMapView extends StatelessWidget {
  const ProviderMapView({
    super.key,
    required this.providers,
    required this.onMarkerTap,
    this.center,
  });

  final List<ProviderProfile> providers;
  final ValueChanged<ProviderProfile> onMarkerTap;

  /// Initial map center — the search origin, when known. Falls back to the
  /// first provider with a marker, then to a neutral PH-wide view.
  final ({double latitude, double longitude})? center;

  static const _fallbackCenter = latlong.LatLng(12.8797, 121.7740); // PH

  @override
  Widget build(BuildContext context) {
    final markers = providers
        .where((p) => p.areaMarker != null)
        .toList(growable: false);

    final initialCenter = center != null
        ? latlong.LatLng(center!.latitude, center!.longitude)
        : markers.isNotEmpty
        ? latlong.LatLng(
            markers.first.areaMarker!.latitude,
            markers.first.areaMarker!.longitude,
          )
        : _fallbackCenter;

    return FlutterMap(
      options: MapOptions(
        initialCenter: initialCenter,
        initialZoom: markers.isEmpty ? 6 : 12,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'ph.oncall.mobile',
        ),
        MarkerLayer(
          markers: [
            for (final provider in markers)
              Marker(
                point: latlong.LatLng(
                  provider.areaMarker!.latitude,
                  provider.areaMarker!.longitude,
                ),
                width: 40,
                height: 40,
                child: GestureDetector(
                  onTap: () => onMarkerTap(provider),
                  child: const Icon(
                    Icons.location_on,
                    color: AppColors.navy800,
                    size: 36,
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }
}
