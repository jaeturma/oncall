import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/review_repository.dart';
import '../../models/paginated.dart';
import '../../models/review.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/verified_service_badge.dart';

/// "Reviews Received" (Phase P §48) — a provider's own received reviews,
/// with the ability to post one public response per review.
class ReviewsReceivedScreen extends StatefulWidget {
  const ReviewsReceivedScreen({super.key});

  @override
  State<ReviewsReceivedScreen> createState() => _ReviewsReceivedScreenState();
}

class _ReviewsReceivedScreenState extends State<ReviewsReceivedScreen> {
  late Future<Paginated<Review>> _load;
  int? _respondingToReviewId;
  final _responseController = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<ReviewRepository>().fetchReceived();
  }

  @override
  void dispose() {
    _responseController.dispose();
    super.dispose();
  }

  void _refresh() =>
      setState(() => _load = context.read<ReviewRepository>().fetchReceived());

  Future<void> _submitResponse(int reviewId) async {
    if (_responseController.text.trim().isEmpty) {
      return;
    }
    setState(() => _submitting = true);
    try {
      await context.read<ReviewRepository>().respond(
        reviewId,
        _responseController.text.trim(),
      );
      _responseController.clear();
      if (mounted) {
        setState(() => _respondingToReviewId = null);
        showSuccessSnackBar(context, 'Response posted.');
        _refresh();
      }
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reviews received')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your reviews.',
              onRetry: _refresh,
            );
          }

          final reviews = snapshot.data!.items;
          if (reviews.isEmpty) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: AppEmptyState(
                icon: Icons.star_border,
                title: 'No reviews yet',
                message:
                    'Reviews appear here once a customer reviews a '
                    'completed booking with you.',
              ),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: reviews.length,
            separatorBuilder: (_, _) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final review = reviews[index];
              final isResponding = _respondingToReviewId == review.id;

              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            review.reviewer?.name ?? '—',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          Row(
                            children: [
                              for (var star = 1; star <= 5; star++)
                                Icon(
                                  star <= review.rating
                                      ? Icons.star
                                      : Icons.star_border,
                                  size: 16,
                                  color: Colors.amber,
                                ),
                            ],
                          ),
                        ],
                      ),
                      if (review.comment != null) ...[
                        const SizedBox(height: 6),
                        Text(review.comment!),
                      ],
                      const SizedBox(height: 6),
                      const VerifiedServiceBadge(),
                      if (review.hasResponse) ...[
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.surfaceContainerHighest,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Your response',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(review.response!.body),
                            ],
                          ),
                        ),
                      ] else if (isResponding) ...[
                        const SizedBox(height: 8),
                        TextField(
                          controller: _responseController,
                          maxLength: 1000,
                          maxLines: 3,
                          decoration: const InputDecoration(
                            hintText: 'Write a public response…',
                          ),
                        ),
                        Row(
                          children: [
                            TextButton(
                              onPressed: _submitting
                                  ? null
                                  : () => setState(
                                      () => _respondingToReviewId = null,
                                    ),
                              child: const Text('Cancel'),
                            ),
                            const SizedBox(width: 8),
                            FilledButton(
                              onPressed: _submitting
                                  ? null
                                  : () => _submitResponse(review.id),
                              child: const Text('Post response'),
                            ),
                          ],
                        ),
                      ] else ...[
                        const SizedBox(height: 6),
                        TextButton(
                          onPressed: () =>
                              setState(() => _respondingToReviewId = review.id),
                          child: const Text('Respond'),
                        ),
                      ],
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
