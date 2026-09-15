import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart' as latlong;

import '../../theme/app_colors.dart';

/// A draggable-pin map for "Choose on Map" (Phase O §18 — a service request
/// location distinct from the customer's profile/current location). Pops
/// `{latitude, longitude}` when confirmed, or `null` if the user backs out.
/// Uses OpenStreetMap tiles — no API key required (same provider as
/// `ProviderMapView`).
class LocationPickerScreen extends StatefulWidget {
  const LocationPickerScreen({
    super.key,
    this.initialLatitude,
    this.initialLongitude,
  });

  final double? initialLatitude;
  final double? initialLongitude;

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  static const _phCenter = latlong.LatLng(12.8797, 121.7740);

  late latlong.LatLng _pin;

  @override
  void initState() {
    super.initState();
    _pin = widget.initialLatitude != null && widget.initialLongitude != null
        ? latlong.LatLng(widget.initialLatitude!, widget.initialLongitude!)
        : _phCenter;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Choose on map'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop({
              'latitude': _pin.latitude,
              'longitude': _pin.longitude,
            }),
            child: const Text('Confirm'),
          ),
        ],
      ),
      body: Stack(
        alignment: Alignment.center,
        children: [
          FlutterMap(
            options: MapOptions(
              initialCenter: _pin,
              initialZoom: widget.initialLatitude != null ? 15 : 6,
              onPositionChanged: (position, hasGesture) {
                if (hasGesture) {
                  setState(() => _pin = position.center);
                }
              },
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'ph.oncall.mobile',
              ),
            ],
          ),
          // A fixed center pin (the map pans under it) rather than a
          // draggable marker — simpler to keep in sync with the map's
          // center coordinate, and the standard pattern for this UX.
          const IgnorePointer(
            child: Icon(Icons.location_on, size: 44, color: AppColors.danger600),
          ),
        ],
      ),
    );
  }
}
