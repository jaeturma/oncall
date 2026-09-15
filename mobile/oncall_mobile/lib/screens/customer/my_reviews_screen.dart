import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/review_repository.dart';
import '../../models/paginated.dart';
import '../../models/review.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/verified_service_badge.dart';

/// "My Reviews" (Phase P §48) — reviews the signed-in customer has written.
class MyReviewsScreen extends StatefulWidget {
  const MyReviewsScreen({super.key});

  @override
  State<MyReviewsScreen> createState() => _MyReviewsScreenState();
}

class _MyReviewsScreenState extends State<MyReviewsScreen> {
  late Future<Paginated<Review>> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<ReviewRepository>().fetchWritten();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My reviews')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your reviews.',
              onRetry: () => setState(
                () => _load = context.read<ReviewRepository>().fetchWritten(),
              ),
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
                    'Once a booking is completed, you can leave a review '
                    'from the job page.',
              ),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: reviews.length,
            separatorBuilder: (_, _) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final review = reviews[index];

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
                            review.reviewee?.name ?? '—',
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
