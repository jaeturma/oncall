import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/catalog_repository.dart';
import '../../data/location_repository.dart';
import '../../data/provider_repository.dart';
import '../../models/catalog.dart';
import '../../models/provider_profile.dart';
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
  final _radiusController = TextEditingController();

  Province? _province;
  Municipality? _municipality;
  List<Municipality> _municipalities = [];
  final Set<int> _selectedServiceIds = {};
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
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
      }
    }

    return (profile, categories, provinces);
  }

  @override
  void dispose() {
    _bioController.dispose();
    _radiusController.dispose();
    super.dispose();
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
      bio: _bioController.text.trim(),
      serviceRadiusKm: int.tryParse(_radiusController.text),
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

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_error != null) ...[
                  Text(_error!, style: TextStyle(color: Colors.red.shade700)),
                  const SizedBox(height: 12),
                ],
                TextField(
                  controller: _bioController,
                  decoration: const InputDecoration(
                    labelText: 'Bio',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _radiusController,
                  decoration: const InputDecoration(
                    labelText: 'Service radius (km, optional)',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<Province>(
                  initialValue: _province,
                  decoration: const InputDecoration(
                    labelText: 'Province',
                    border: OutlineInputBorder(),
                  ),
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
                    border: OutlineInputBorder(),
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
