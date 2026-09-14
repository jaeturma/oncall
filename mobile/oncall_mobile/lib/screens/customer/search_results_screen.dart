import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../data/provider_search_repository.dart';
import '../../models/paginated.dart';
import '../../models/provider_profile.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/provider_card.dart';

class SearchResultsScreen extends StatefulWidget {
  const SearchResultsScreen({super.key, required this.queryParameters});

  final Map<String, String> queryParameters;

  @override
  State<SearchResultsScreen> createState() => _SearchResultsScreenState();
}

class _SearchResultsScreenState extends State<SearchResultsScreen> {
  late Future<Paginated<ProviderProfile>> _load;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
  }

  Future<Paginated<ProviderProfile>> _fetch() {
    final q = widget.queryParameters;

    return context.read<ProviderSearchRepository>().search(
      help: q['help']!,
      provinceId: int.parse(q['province_id']!),
      municipalityId: q['municipality_id'] != null
          ? int.parse(q['municipality_id']!)
          : null,
      availableOnly: q['available_only'] == 'true',
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Providers near you')),
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

          final providers = snapshot.data!.items;
          if (providers.isEmpty) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: AppEmptyState(
                icon: Icons.search_off,
                title: 'No providers found',
                message:
                    "We couldn't find providers matching those filters. Try "
                    'a broader service, remove the municipality filter, or '
                    'search a nearby province.',
              ),
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
