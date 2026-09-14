import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/formatters.dart';
import '../../models/job.dart';
import '../../models/provider_profile.dart';
import '../../widgets/availability_badge.dart';
import '../../widgets/avatar.dart';
import '../../widgets/common.dart';
import '../../widgets/rating_summary.dart';
import '../../widgets/verification_badge.dart';

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
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Avatar(
                      name: profile.name,
                      anonymous: profile.name == null,
                      size: AvatarSize.xl,
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          AvailabilityBadge(status: profile.availabilityStatus),
                          const SizedBox(height: 8),
                          Text(
                            profile.displayName,
                            style: Theme.of(context).textTheme.headlineSmall,
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              RatingSummary(
                                rating: profile.rating,
                                reviewCount: reviewsCount,
                              ),
                              const SizedBox(width: 10),
                              Text(
                                '${profile.completedJobs} completed',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    if (profile.mobileVerified == true)
                      const VerificationBadge(type: 'mobile'),
                    for (final type in profile.verifiedDocumentTypes)
                      if (VerificationBadge.typeForDocumentType(type)
                          case final mapped?)
                        VerificationBadge(type: mapped),
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
