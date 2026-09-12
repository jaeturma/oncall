import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/formatters.dart';
import '../../models/job.dart';
import '../../models/provider_profile.dart';
import '../../widgets/common.dart';

class ProviderProfileScreen extends StatefulWidget {
  const ProviderProfileScreen({super.key, required this.providerProfileId});

  final int providerProfileId;

  @override
  State<ProviderProfileScreen> createState() => _ProviderProfileScreenState();
}

class _ProviderProfileScreenState extends State<ProviderProfileScreen> {
  late Future<Map<String, dynamic>> _load;

  @override
  void initState() {
    super.initState();
    _load = _fetch();
  }

  Future<Map<String, dynamic>> _fetch() =>
      context.read<ApiClient>().get('/providers/${widget.providerProfileId}');

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
            return ErrorView(
              message: 'Could not load this profile.',
              onRetry: () => setState(() => _load = _fetch()),
            );
          }

          final json = snapshot.data!;
          final profile = ProviderProfile.fromJson(
            json['data'] as Map<String, dynamic>,
          );
          final reviews = (json['reviews'] as List<dynamic>? ?? [])
              .map((e) => JobReview.fromJson(e as Map<String, dynamic>))
              .toList();
          final reviewsCount = json['reviews_count'] as int? ?? 0;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  profile.displayName,
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 4),
                Text(
                  [
                    if (profile.rating != null)
                      '★ ${profile.rating!.toStringAsFixed(1)} ($reviewsCount reviews)',
                    '${profile.completedJobs} jobs completed',
                  ].join(' · '),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    StatusChip(profile.availabilityStatus),
                    if (profile.mobileVerified == true)
                      const Chip(
                        avatar: Icon(Icons.check, size: 16),
                        label: Text('Mobile verified'),
                      ),
                    for (final type in profile.verifiedDocumentTypes)
                      Chip(
                        avatar: const Icon(Icons.verified, size: 16),
                        label: Text(humanizeStatus(type)),
                      ),
                  ],
                ),
                if (profile.bio != null) ...[
                  const SizedBox(height: 16),
                  Text(profile.bio!),
                ],
                const SizedBox(height: 16),
                Text(
                  'Services offered',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                for (final offering in profile.services)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(offering.service?.name ?? 'Service'),
                    subtitle: offering.experienceText == null
                        ? null
                        : Text(offering.experienceText!),
                    trailing: offering.rateFrom == null
                        ? null
                        : Text(formatPeso(offering.rateFrom)),
                  ),
                const SizedBox(height: 16),
                Text('Reviews', style: Theme.of(context).textTheme.titleMedium),
                if (reviews.isEmpty) const Text('No written reviews yet.'),
                for (final review in reviews)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Text('★' * review.rating),
                    title: Text(review.comment ?? ''),
                    subtitle: Text(review.reviewer?.name ?? ''),
                  ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: () => context.push(
                    '/providers/${widget.providerProfileId}/request',
                  ),
                  child: const Text('Send a service request'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
