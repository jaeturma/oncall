import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../data/provider_search_repository.dart';
import '../../models/provider_profile.dart';
import '../../state/location_state.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/provider_card.dart';
import '../../widgets/provider_map_view.dart';

enum _ViewMode { list, map }

class SearchResultsScreen extends StatefulWidget {
  const SearchResultsScreen({super.key, required this.queryParameters});

  final Map<String, String> queryParameters;

  @override
  State<SearchResultsScreen> createState() => _SearchResultsScreenState();
}

class _SearchResultsScreenState extends State<SearchResultsScreen> {
  late Future<ProviderSearchResult> _load;
  _ViewMode _mode = _ViewMode.list;
  int? _radiusKm;

  @override
  void initState() {
    super.initState();
    final q = widget.queryParameters;
    _radiusKm = q['radius_km'] != null ? int.tryParse(q['radius_km']!) : null;
    _load = _fetch();
  }

  Future<ProviderSearchResult> _fetch() {
    final q = widget.queryParameters;

    return context.read<ProviderSearchRepository>().search(
      help: q['help']!,
      provinceId: int.parse(q['province_id']!),
      municipalityId: q['municipality_id'] != null
          ? int.parse(q['municipality_id']!)
          : null,
      availableOnly: q['available_only'] == 'true',
      latitude: q['latitude'] != null ? double.tryParse(q['latitude']!) : null,
      longitude: q['longitude'] != null
          ? double.tryParse(q['longitude']!)
          : null,
      radiusKm: _radiusKm,
    );
  }

  void _expandRadius(int radiusKm) {
    setState(() {
      _radiusKm = radiusKm;
      _load = _fetch();
    });
  }

  void _showProviderPreview(ProviderProfile provider) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          left: 16,
          right: 16,
          top: 16,
          bottom: MediaQuery.of(context).viewInsets.bottom + 16,
        ),
        child: ProviderCard(
          profile: provider,
          onTap: () {
            Navigator.of(context).pop();
            context.push('/providers/${provider.id}');
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Providers near you'),
        actions: [
          IconButton(
            tooltip: _mode == _ViewMode.list ? 'Map view' : 'List view',
            icon: Icon(_mode == _ViewMode.list ? Icons.map : Icons.list),
            onPressed: () => setState(
              () => _mode = _mode == _ViewMode.list
                  ? _ViewMode.map
                  : _ViewMode.list,
            ),
          ),
        ],
      ),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load results.',
              onRetry: () => setState(() => _load = _fetch()),
            );
          }

          final result = snapshot.data!;
          final providers = result.page.items;

          if (providers.isEmpty) {
            return Padding(
              padding: const EdgeInsets.all(16),
              child: AppEmptyState(
                icon: Icons.search_off,
                title: 'No providers found',
                message: result.expandRadiusKm != null
                    ? "We couldn't find providers within ${result.appliedRadiusKm}km. "
                          'Try a wider radius, a broader service, or search a nearby province.'
                    : "We couldn't find providers matching those filters. Try "
                          'a broader service, remove the municipality filter, or '
                          'search a nearby province.',
                actions: result.expandRadiusKm != null
                    ? [
                        FilledButton(
                          onPressed: () =>
                              _expandRadius(result.expandRadiusKm!),
                          child: Text(
                            'Expand search to ${result.expandRadiusKm}km',
                          ),
                        ),
                      ]
                    : const [],
              ),
            );
          }

          // Map failure (tile load error, unsupported platform) must never
          // make List View unusable — the toggle keeps List reachable
          // regardless of whether the map itself renders correctly.
          if (_mode == _ViewMode.map) {
            final locationState = context.watch<LocationState>();
            final center = locationState.hasCoordinates
                ? (
                    latitude: locationState.latitude!,
                    longitude: locationState.longitude!,
                  )
                : null;

            return ProviderMapView(
              providers: providers,
              center: center,
              onMarkerTap: _showProviderPreview,
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: providers.length,
            separatorBuilder: (_, _) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final p = providers[index];

              return ProviderCard(
                profile: p,
                onTap: () => context.push('/providers/${p.id}'),
              );
            },
          );
        },
      ),
    );
  }
}
