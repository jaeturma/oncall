import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/provider_repository.dart';
import '../../data/service_request_repository.dart';
import '../../models/provider_profile.dart';
import '../../models/service_request.dart';
import '../../widgets/common.dart';

const _availabilityOptions = [
  ('AVAILABLE', 'Available'),
  ('BUSY', 'Busy'),
  ('BY_APPOINTMENT', 'By appointment'),
  ('OFFLINE', 'Offline'),
];

class ProviderHomeScreen extends StatefulWidget {
  const ProviderHomeScreen({super.key});

  @override
  State<ProviderHomeScreen> createState() => _ProviderHomeScreenState();
}

class _ProviderHomeScreenState extends State<ProviderHomeScreen> {
  late Future<(ProviderProfile?, List<ServiceRequest>)> _load;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
  }

  Future<(ProviderProfile?, List<ServiceRequest>)> _fetch() async {
    final providerRepository = context.read<ProviderRepository>();
    final serviceRequestRepository = context.read<ServiceRequestRepository>();
    final profile = await providerRepository.fetchMine();
    final requests = await serviceRequestRepository.list();

    return (profile, requests.items.where((r) => r.isOpen).toList());
  }

  Future<void> _refresh() async {
    final future = _fetch();
    setState(() => _load = future);
    await future;
  }

  Future<void> _setAvailability(ProviderProfile profile, String status) async {
    try {
      await context.read<ProviderRepository>().updateAvailability(
        profile.id,
        status,
      );
      await _refresh();
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Provider dashboard')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your dashboard.',
              onRetry: _refresh,
            );
          }

          final (profile, requests) = snapshot.data!;

          if (profile == null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'Set up your provider profile to start receiving requests.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: () => context.push('/provider-profile/edit'),
                      child: const Text('Create profile'),
                    ),
                  ],
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              'Availability',
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            StatusChip(profile.verificationStatus),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          children: [
                            for (final (value, label) in _availabilityOptions)
                              ChoiceChip(
                                label: Text(label),
                                selected: profile.availabilityStatus == value,
                                onSelected: (_) =>
                                    _setAvailability(profile, value),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        OutlinedButton(
                          onPressed: () =>
                              context.push('/provider-profile/edit'),
                          child: const Text('Edit profile'),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Incoming requests',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                if (requests.isEmpty)
                  const EmptyView(message: 'No incoming requests right now.'),
                for (final request in requests)
                  Card(
                    child: ListTile(
                      title: Text(request.title),
                      subtitle: Text(request.service?.name ?? ''),
                      trailing: StatusChip(request.status),
                      onTap: () =>
                          context.push('/service-requests/${request.id}'),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
