import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/provider_search_repository.dart';
import '../../data/review_repository.dart';
import '../../models/paginated.dart';
import '../../models/provider_profile.dart';
import '../../models/review.dart';
import '../../widgets/availability_badge.dart';
import '../../widgets/avatar.dart';
import '../../widgets/common.dart';
import '../../widgets/rating_distribution_bars.dart';
import '../../widgets/rating_summary.dart';
import '../../widgets/verification_badge.dart';
import '../../widgets/verified_service_badge.dart';

class ProviderProfileScreen extends StatefulWidget {
  const ProviderProfileScreen({super.key, required this.providerProfileId});

  final int providerProfileId;

  @override
  State<ProviderProfileScreen> createState() => _ProviderProfileScreenState();
}

class _ProviderProfileScreenState extends State<ProviderProfileScreen> {
  late Future<ProviderProfile> _load;
  late Future<Paginated<Review>> _reviews;
  final List<Review> _loadedReviews = [];
  String _sort = 'newest';
  int _page = 1;
  bool _loadingMore = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<ProviderSearchRepository>().show(
      widget.providerProfileId,
    );
    _reviews = _fetchReviews();
  }

  Future<Paginated<Review>> _fetchReviews() async {
    final result = await context.read<ReviewRepository>().fetchProviderReviews(
      widget.providerProfileId,
      sort: _sort,
      page: _page,
    );
    _loadedReviews.addAll(result.items);

    return result;
  }

  void _changeSort(String sort) {
    setState(() {
      _sort = sort;
      _page = 1;
      _loadedReviews.clear();
      _reviews = _fetchReviews();
    });
  }

  Future<void> _loadMoreReviews(int lastPage) async {
    if (_loadingMore || _page >= lastPage) {
      return;
    }
    setState(() => _loadingMore = true);
    _page += 1;
    await _fetchReviews();
    if (mounted) {
      setState(() => _loadingMore = false);
    }
  }

  Future<void> _reportReview(int reviewId) async {
    var category = 'OTHER';
    final result = await showDialog<String>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Report this review'),
          content: DropdownButtonFormField<String>(
            initialValue: category,
            items: const [
              DropdownMenuItem(value: 'HARASSMENT', child: Text('Harassment')),
              DropdownMenuItem(value: 'SPAM', child: Text('Spam')),
              DropdownMenuItem(
                value: 'PERSONAL_INFORMATION',
                child: Text('Personal information'),
              ),
              DropdownMenuItem(
                value: 'FALSE_OR_MISLEADING',
                child: Text('False or misleading'),
              ),
              DropdownMenuItem(
                value: 'OFFENSIVE_CONTENT',
                child: Text('Offensive content'),
              ),
              DropdownMenuItem(value: 'THREAT', child: Text('Threat')),
              DropdownMenuItem(
                value: 'UNRELATED_CONTENT',
                child: Text('Unrelated content'),
              ),
              DropdownMenuItem(value: 'OTHER', child: Text('Other')),
            ],
            onChanged: (value) =>
                setDialogState(() => category = value ?? category),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, category),
              child: const Text('Submit'),
            ),
          ],
        ),
      ),
    );
    if (result == null || !mounted) {
      return;
    }

    try {
      await context.read<ReviewRepository>().report(
        reviewId,
        category: result,
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Thanks — we received your report.');
      }
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Provider profile')),
      body: FutureBuilder<ProviderProfile>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load this profile.',
              onRetry: () => setState(
                () => _load = context.read<ProviderSearchRepository>().show(
                  widget.providerProfileId,
                ),
              ),
            );
          }

          final profile = snapshot.data!;
          final reputation = profile.reputation;

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
                                rating: reputation?.averageRating,
                                reviewCount: reputation?.ratingCount,
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
                if (reputation != null && reputation.ratingCount > 0) ...[
                  const SizedBox(height: 8),
                  RatingDistributionBars(
                    distribution: reputation.ratingDistribution,
                  ),
                ],
                const SizedBox(height: 12),
                FutureBuilder<Paginated<Review>>(
                  future: _reviews,
                  builder: (context, reviewsSnapshot) {
                    if (!reviewsSnapshot.hasData &&
                        !reviewsSnapshot.hasError) {
                      return const Padding(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        child: LoadingView(),
                      );
                    }
                    if (reviewsSnapshot.hasError) {
                      return const Text('Could not load reviews.');
                    }

                    final reviews = _loadedReviews;
                    if (reviews.isEmpty) {
                      return const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Text(
                          'No reviews yet. Reviews come only from '
                          'completed bookings on Oncall.',
                        ),
                      );
                    }

                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Wrap(
                          spacing: 8,
                          children: [
                            for (final (value, label) in const [
                              ('newest', 'Newest'),
                              ('highest', 'Highest rating'),
                              ('lowest', 'Lowest rating'),
                            ])
                              ChoiceChip(
                                label: Text(label),
                                selected: _sort == value,
                                onSelected: (_) => _changeSort(value),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        for (final review in reviews)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      review.reviewer?.name ?? '—',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    Text('★' * review.rating),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                const VerifiedServiceBadge(),
                                if (review.comment != null) ...[
                                  const SizedBox(height: 4),
                                  Text(review.comment!),
                                ],
                                if (review.hasResponse) ...[
                                  const SizedBox(height: 4),
                                  Container(
                                    width: double.infinity,
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: Theme.of(
                                        context,
                                      ).colorScheme.surfaceContainerHighest,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        const Text(
                                          'Response from provider',
                                          style: TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(review.response!.body),
                                      ],
                                    ),
                                  ),
                                ],
                                Align(
                                  alignment: Alignment.centerRight,
                                  child: TextButton(
                                    onPressed: () => _reportReview(review.id),
                                    child: const Text('Report'),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        if (_page < reviewsSnapshot.data!.lastPage)
                          Center(
                            child: _loadingMore
                                ? const Padding(
                                    padding: EdgeInsets.all(8),
                                    child: CircularProgressIndicator(),
                                  )
                                : TextButton(
                                    onPressed: () => _loadMoreReviews(
                                      reviewsSnapshot.data!.lastPage,
                                    ),
                                    child: const Text('Load more reviews'),
                                  ),
                          ),
                      ],
                    );
                  },
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
