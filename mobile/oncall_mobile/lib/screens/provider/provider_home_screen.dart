import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/provider_repository.dart';
import '../../data/service_request_repository.dart';
import '../../models/provider_profile.dart';
import '../../models/service_request.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/stat_card.dart';

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
                padding: const EdgeInsets.all(16),
                child: AppEmptyState(
                  icon: Icons.storefront_outlined,
                  title: 'Set up your provider profile',
                  message:
                      'Tell customers what you do and where you work. Your '
                      'profile appears in searches once it is approved and '
                      'your identity is verified.',
                  actions: [
                    FilledButton(
                      onPressed: () => context.push('/provider-profile/edit'),
                      child: const Text('Create provider profile'),
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
                Row(
                  children: [
                    Expanded(
                      child: StatCard(
                        label: 'Open requests',
                        value: '${requests.length}',
                        icon: Icons.inbox_outlined,
                        tone: StatCardTone.warning,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: StatCard(
                        label: 'Completed jobs',
                        value: '${profile.completedJobs}',
                        icon: Icons.check_circle_outline,
                        tone: StatCardTone.success,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                StatCard(
                  label: 'Reviews',
                  value: profile.reputation?.ratingCount == 0
                      ? 'No reviews yet'
                      : '${profile.reputation?.averageRating?.toStringAsFixed(1) ?? '—'} ★ (${profile.reputation?.ratingCount ?? 0})',
                  icon: Icons.star_border,
                  tone: StatCardTone.neutral,
                  onTap: () => context.push('/reviews/received'),
                ),
                const SizedBox(height: 16),
                AppCard(
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
                      const SizedBox(height: 10),
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
                      const SizedBox(height: 10),
                      OutlinedButton(
                        onPressed: () => context.push('/provider-profile/edit'),
                        child: const Text('Edit profile'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  'Incoming requests',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 10),
                if (requests.isEmpty)
                  const AppEmptyState(
                    icon: Icons.inbox_outlined,
                    title: 'No new requests',
                    message:
                        'Requests from customers will appear here. Being '
                        'available now puts you first in results.',
                    compact: true,
                  ),
                for (final request in requests) ...[
                  AppCard(
                    onTap: () =>
                        context.push('/service-requests/${request.id}'),
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                request.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 15,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.ink,
                                ),
                              ),
                              if (request.service?.name != null) ...[
                                const SizedBox(height: 2),
                                Text(
                                  request.service!.name,
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 13,
                                    color: AppColors.inkSecondary,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        StatusChip(request.status),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
