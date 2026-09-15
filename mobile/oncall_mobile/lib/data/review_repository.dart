import '../core/api_client.dart';
import '../models/paginated.dart';
import '../models/review.dart';
import '../models/review_eligibility.dart';

class ReviewRepository {
  ReviewRepository(this._client);

  final ApiClient _client;

  Future<Review> submitReview(
    int jobId, {
    required int rating,
    String? comment,
  }) async {
    final json = await _client.post(
      '/jobs/$jobId/reviews',
      data: {
        'rating': rating,
        if (comment != null && comment.isNotEmpty) 'comment': comment,
      },
    );

    return Review.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Review> withdraw(int reviewId) async {
    final json = await _client.patch('/reviews/$reviewId/withdraw');

    return Review.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Review> respond(int reviewId, String response) async {
    final json = await _client.post(
      '/reviews/$reviewId/response',
      data: {'response': response},
    );

    return Review.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> report(
    int reviewId, {
    required String category,
    String? description,
  }) => _client.post(
    '/reviews/$reviewId/reports',
    data: {
      'category': category,
      if (description != null && description.isNotEmpty)
        'description': description,
    },
  );

  Future<ReviewEligibility> fetchEligibility(int jobId) async {
    final json = await _client.get('/jobs/$jobId/review-eligibility');

    return ReviewEligibility.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Paginated<Review>> fetchProviderReviews(
    int providerProfileId, {
    String sort = 'newest',
    int page = 1,
  }) async {
    final json = await _client.get(
      '/providers/$providerProfileId/reviews',
      query: {'sort': sort, 'page': page},
    );

    return Paginated.fromJson(json, Review.fromJson);
  }

  Future<Paginated<Review>> fetchWritten({int page = 1}) async {
    final json = await _client.get('/reviews/written', query: {'page': page});

    return Paginated.fromJson(json, Review.fromJson);
  }

  Future<Paginated<Review>> fetchReceived({int page = 1}) async {
    final json = await _client.get(
      '/reviews/received',
      query: {'page': page},
    );

    return Paginated.fromJson(json, Review.fromJson);
  }
}
