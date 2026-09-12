import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../data/catalog_repository.dart';
import '../../data/location_repository.dart';
import '../../models/catalog.dart';
import '../../state/auth_state.dart';
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

  @override
  void initState() {
    super.initState();
    _load = _fetch();
  }

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
    context.push(
      Uri(
        path: '/search-results',
        queryParameters: {
          'help': help,
          'province_id': '${_province!.id}',
          if (_municipality != null) 'municipality_id': '${_municipality!.id}',
          if (_availableOnly) 'available_only': 'true',
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
                  decoration: const InputDecoration(
                    labelText: 'Category',
                    border: OutlineInputBorder(),
                  ),
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
                    border: OutlineInputBorder(),
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
                    labelText: 'City / municipality (optional)',
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
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Available now only'),
                  value: _availableOnly,
                  onChanged: (value) => setState(() => _availableOnly = value),
                ),
                const SizedBox(height: 12),
                FilledButton.icon(
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
