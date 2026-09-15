import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/catalog_repository.dart';
import '../../data/location_repository.dart';
import '../../data/provider_repository.dart';
import '../../models/catalog.dart';
import '../../models/provider_profile.dart';
import '../../state/location_state.dart';
import '../../theme/app_colors.dart';
import '../../widgets/common.dart';

class ProviderProfileEditScreen extends StatefulWidget {
  const ProviderProfileEditScreen({super.key});

  @override
  State<ProviderProfileEditScreen> createState() =>
      _ProviderProfileEditScreenState();
}

class _ProviderProfileEditScreenState extends State<ProviderProfileEditScreen> {
  late Future<(ProviderProfile?, List<ServiceCategory>, List<Province>)> _load;
  final _bioController = TextEditingController();

  Province? _province;
  Municipality? _municipality;
  Barangay? _barangay;
  List<Municipality> _municipalities = [];
  List<Barangay> _barangays = [];
  int? _radiusKm;
  double? _latitude;
  double? _longitude;
  final Set<int> _selectedServiceIds = {};
  bool _submitting = false;
  bool _settingFromDevice = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
    context.read<LocationState>().loadRadiusPolicy();
  }

  Future<(ProviderProfile?, List<ServiceCategory>, List<Province>)>
  _fetch() async {
    final providerRepository = context.read<ProviderRepository>();
    final catalogRepository = context.read<CatalogRepository>();
    final locationRepository = context.read<LocationRepository>();
    final profile = await providerRepository.fetchMine();
    final categories = await catalogRepository.fetchCategories();
    final provinces = await locationRepository.fetchProvinces();

    if (profile != null) {
      _bioController.text = profile.bio ?? '';
      _selectedServiceIds.addAll(
        profile.services.map((s) => s.service?.id).whereType<int>(),
      );
      _radiusKm = profile.serviceRadiusKm;
      _latitude = profile.latitude;
      _longitude = profile.longitude;
      _province = provinces
          .where((p) => p.id == profile.province?.id)
          .firstOrNull;
      if (_province != null) {
        _municipalities = await locationRepository.fetchMunicipalities(
          _province!.id,
        );
        _municipality = _municipalities
            .where((m) => m.id == profile.municipality?.id)
            .firstOrNull;
        if (_municipality != null) {
          _barangays = await locationRepository.fetchBarangays(
            _municipality!.id,
          );
          _barangay = _barangays
              .where((b) => b.id == profile.barangay?.id)
              .firstOrNull;
        }
      }
    }

    return (profile, categories, provinces);
  }

  @override
  void dispose() {
    _bioController.dispose();
    super.dispose();
  }

  Future<void> _onProvinceChanged(Province? province) async {
    setState(() {
      _province = province;
      _municipality = null;
      _municipalities = [];
      _barangay = null;
      _barangays = [];
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

  Future<void> _onMunicipalityChanged(Municipality? municipality) async {
    setState(() {
      _municipality = municipality;
      _barangay = null;
      _barangays = [];
    });
    if (municipality == null) {
      return;
    }
    final barangays = await context.read<LocationRepository>().fetchBarangays(
      municipality.id,
    );
    if (mounted) {
      setState(() => _barangays = barangays);
    }
  }

  /// Sets this provider's own base coordinates from the device's current
  /// position. The provider can always override this manually afterward —
  /// this only prefills it (Phase O §5/§23: only the provider themselves
  /// may set/change their own location).
  Future<void> _setFromCurrentLocation() async {
    setState(() => _settingFromDevice = true);
    final locationState = context.read<LocationState>();
    final granted = await locationState.useCurrentLocation();
    if (!mounted) {
      return;
    }
    setState(() => _settingFromDevice = false);

    if (!granted) {
      showErrorSnackBar(
        context,
        'Could not get your current location. You can still set your area manually.',
      );

      return;
    }

    setState(() {
      _latitude = locationState.latitude;
      _longitude = locationState.longitude;
    });

    final resolvedProvince = locationState.resolvedArea?.province;
    if (resolvedProvince == null) {
      return;
    }
    final provinces = (await _load).$3;
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
        await _onMunicipalityChanged(matchedMunicipality);
        if (!mounted) {
          return;
        }
        final resolvedBarangay = locationState.resolvedArea?.barangay;
        if (resolvedBarangay != null) {
          final matchedBarangay = _barangays
              .where((b) => b.id == resolvedBarangay.id)
              .firstOrNull;
          if (matchedBarangay != null) {
            setState(() => _barangay = matchedBarangay);
          }
        }
      }
    }
  }

  Future<void> _submit(ProviderProfile? existing) async {
    if (_province == null ||
        _municipality == null ||
        _selectedServiceIds.isEmpty) {
      showErrorSnackBar(
        context,
        'Choose your location and at least one service.',
      );

      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    final input = ProviderProfileInput(
      provinceId: _province!.id,
      municipalityId: _municipality!.id,
      barangayId: _barangay?.id,
      bio: _bioController.text.trim(),
      serviceRadiusKm: _radiusKm,
      latitude: _latitude,
      longitude: _longitude,
      serviceIds: _selectedServiceIds.toList(),
    );

    try {
      final repository = context.read<ProviderRepository>();
      if (existing == null) {
        await repository.create(input);
      } else {
        await repository.update(existing.id, input);
      }
      if (mounted) {
        showSuccessSnackBar(context, 'Profile saved.');
        Navigator.of(context).pop();
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Provider profile')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return const ErrorView(message: 'Could not load your profile.');
          }

          final (profile, categories, provinces) = snapshot.data!;
          final radiusChoices = context.watch<LocationState>().radiusChoices;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_error != null) ...[
                  Text(
                    _error!,
                    style: const TextStyle(color: AppColors.danger700),
                  ),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: _bioController,
                  decoration: const InputDecoration(labelText: 'Bio'),
                  maxLines: 4,
                ),
                const SizedBox(height: 16),
                Text(
                  'Where you work',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: _settingFromDevice ? null : _setFromCurrentLocation,
                  icon: _settingFromDevice
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.my_location),
                  label: Text(
                    _latitude != null
                        ? 'Base location set from device'
                        : 'Set from my current location',
                  ),
                ),
                const SizedBox(height: 12),
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
                    labelText: 'City / municipality',
                  ),
                  items: _municipalities
                      .map(
                        (m) => DropdownMenuItem(value: m, child: Text(m.name)),
                      )
                      .toList(),
                  onChanged: _province == null ? null : _onMunicipalityChanged,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<Barangay>(
                  initialValue: _barangay,
                  decoration: const InputDecoration(
                    labelText: 'Barangay (optional)',
                  ),
                  items: _barangays
                      .map(
                        (b) => DropdownMenuItem(value: b, child: Text(b.name)),
                      )
                      .toList(),
                  onChanged: _municipality == null
                      ? null
                      : (value) => setState(() => _barangay = value),
                ),
                const SizedBox(height: 16),
                Text(
                  'How far will you travel?',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    ChoiceChip(
                      label: const Text('No fixed limit'),
                      selected: _radiusKm == null,
                      onSelected: (_) => setState(() => _radiusKm = null),
                    ),
                    for (final choice in radiusChoices)
                      ChoiceChip(
                        label: Text('${choice}km'),
                        selected: _radiusKm == choice,
                        onSelected: (_) => setState(() => _radiusKm = choice),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  'Services you offer',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                for (final category in categories) ...[
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      category.name,
                      style: Theme.of(context).textTheme.labelLarge,
                    ),
                  ),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final service in category.services)
                        FilterChip(
                          label: Text(service.name),
                          selected: _selectedServiceIds.contains(service.id),
                          onSelected: (selected) => setState(() {
                            if (selected) {
                              _selectedServiceIds.add(service.id);
                            } else {
                              _selectedServiceIds.remove(service.id);
                            }
                          }),
                        ),
                    ],
                  ),
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _submitting ? null : () => _submit(profile),
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Save profile'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
