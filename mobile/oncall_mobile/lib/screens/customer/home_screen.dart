import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../data/catalog_repository.dart';
import '../../data/location_repository.dart';
import '../../models/catalog.dart';
import '../../services/device_location_service.dart';
import '../../state/auth_state.dart';
import '../../state/location_state.dart';
import '../../theme/app_button_styles.dart';
import '../../widgets/common.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<(List<ServiceCategory>, List<Province>)> _load;

  ServiceCategory? _category;
  Service? _service;
  Province? _province;
  Municipality? _municipality;
  List<Municipality> _municipalities = [];
  bool _availableOnly = false;
  int? _radiusKm;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
    context.read<LocationState>().loadRadiusPolicy();
  }

  Future<void> _useCurrentLocation() async {
    final locationState = context.read<LocationState>();
    final granted = await locationState.useCurrentLocation();

    if (!mounted) {
      return;
    }

    if (!granted) {
      showErrorSnackBar(context, _deniedMessage(locationState.lastStatus));

      return;
    }

    setState(() => _radiusKm ??= locationState.defaultRadiusKm);

    final resolvedProvince = locationState.resolvedArea?.province;
    if (resolvedProvince == null) {
      return;
    }

    final provinces = (await _load).$2;
    final matchedProvince = provinces
        .where((p) => p.id == resolvedProvince.id)
        .firstOrNull;
    if (matchedProvince == null || !mounted) {
      return;
    }

    await _onProvinceChanged(matchedProvince);
    if (!mounted) {
      return;
    }

    final resolvedMunicipality = locationState.resolvedArea?.municipality;
    if (resolvedMunicipality != null) {
      final matchedMunicipality = _municipalities
          .where((m) => m.id == resolvedMunicipality.id)
          .firstOrNull;
      if (matchedMunicipality != null) {
        setState(() => _municipality = matchedMunicipality);
      }
    }
  }

  String _deniedMessage(DeviceLocationStatus? status) => switch (status) {
    DeviceLocationStatus.permanentlyDenied =>
      'Location access is permanently denied. Enable it in your device settings, or choose your province manually.',
    DeviceLocationStatus.serviceDisabled =>
      'Location services are turned off on this device. Choose your province manually.',
    DeviceLocationStatus.denied =>
      'Location permission was denied. Choose your province manually.',
    DeviceLocationStatus.unsupported =>
      'Current location is not supported on this device. Choose your province manually.',
    _ => 'Could not get your current location. Choose your province manually.',
  };

  Future<(List<ServiceCategory>, List<Province>)> _fetch() async {
    final catalogRepository = context.read<CatalogRepository>();
    final locationRepository = context.read<LocationRepository>();
    final categories = await catalogRepository.fetchCategories();
    final provinces = await locationRepository.fetchProvinces();

    return (categories, provinces);
  }

  Future<void> _onProvinceChanged(Province? province) async {
    setState(() {
      _province = province;
      _municipality = null;
      _municipalities = [];
    });
    if (province == null) {
      return;
    }
    final municipalities = await context
        .read<LocationRepository>()
        .fetchMunicipalities(province.id);
    if (mounted) {
      setState(() => _municipalities = municipalities);
    }
  }

  void _search() {
    if (_province == null || (_category == null && _service == null)) {
      showErrorSnackBar(
        context,
        'Choose what you need help with and your province.',
      );

      return;
    }

    final help = _service != null
        ? 'service:${_service!.id}'
        : 'category:${_category!.id}';
    final locationState = context.read<LocationState>();
    final useGps = locationState.hasCoordinates;

    context.push(
      Uri(
        path: '/search-results',
        queryParameters: {
          'help': help,
          'province_id': '${_province!.id}',
          if (_municipality != null) 'municipality_id': '${_municipality!.id}',
          if (_availableOnly) 'available_only': 'true',
          if (useGps) 'latitude': '${locationState.latitude}',
          if (useGps) 'longitude': '${locationState.longitude}',
          if (useGps && _radiusKm != null) 'radius_km': '$_radiusKm',
        },
      ).toString(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthState>().user;

    return Scaffold(
      appBar: AppBar(
        title: Text('Hi, ${user?.name.split(' ').first ?? 'there'}'),
      ),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load services.',
              onRetry: () => setState(() => _load = _fetch()),
            );
          }

          final (categories, provinces) = snapshot.data!;
          final services = _category?.services ?? const <Service>[];

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'What do you need help with?',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<ServiceCategory>(
                  initialValue: _category,
                  decoration: const InputDecoration(labelText: 'Category'),
                  items: categories
                      .map(
                        (c) => DropdownMenuItem(value: c, child: Text(c.name)),
                      )
                      .toList(),
                  onChanged: (value) => setState(() {
                    _category = value;
                    _service = null;
                  }),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<Service>(
                  initialValue: _service,
                  decoration: const InputDecoration(
                    labelText: 'Specific service (optional)',
                  ),
                  items: services
                      .map(
                        (s) => DropdownMenuItem(value: s, child: Text(s.name)),
                      )
                      .toList(),
                  onChanged: _category == null
                      ? null
                      : (value) => setState(() => _service = value),
                ),
                const SizedBox(height: 20),
                Text(
                  'Where are you?',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 12),
                Consumer<LocationState>(
                  builder: (context, locationState, _) => Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      OutlinedButton.icon(
                        onPressed: locationState.isLocating
                            ? null
                            : _useCurrentLocation,
                        icon: locationState.isLocating
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.my_location),
                        label: Text(
                          locationState.hasCoordinates
                              ? 'Using your current location'
                              : 'Use my current location',
                        ),
                      ),
                      if (locationState.hasCoordinates) ...[
                        const SizedBox(height: 12),
                        Text(
                          'Search radius',
                          style: Theme.of(context).textTheme.labelLarge,
                        ),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 8,
                          children: [
                            for (final choice in locationState.radiusChoices)
                              ChoiceChip(
                                label: Text('${choice}km'),
                                selected:
                                    (_radiusKm ??
                                        locationState.defaultRadiusKm) ==
                                    choice,
                                onSelected: (_) =>
                                    setState(() => _radiusKm = choice),
                              ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 12),
                    ],
                  ),
                ),
                DropdownButtonFormField<Province>(
                  initialValue: _province,
                  decoration: const InputDecoration(labelText: 'Province'),
                  items: provinces
                      .map(
                        (p) => DropdownMenuItem(value: p, child: Text(p.name)),
                      )
                      .toList(),
                  onChanged: _onProvinceChanged,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<Municipality>(
                  initialValue: _municipality,
                  decoration: const InputDecoration(
                    labelText: 'City / municipality (optional)',
                  ),
                  items: _municipalities
                      .map(
                        (m) => DropdownMenuItem(value: m, child: Text(m.name)),
                      )
                      .toList(),
                  onChanged: _province == null
                      ? null
                      : (value) => setState(() => _municipality = value),
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Available now only'),
                  value: _availableOnly,
                  onChanged: (value) => setState(() => _availableOnly = value),
                ),
                const SizedBox(height: 12),
                FilledButton.icon(
                  style: AppButtonStyles.lg,
                  onPressed: _search,
                  icon: const Icon(Icons.search),
                  label: const Text('Find providers'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
