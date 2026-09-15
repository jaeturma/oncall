import '../core/json.dart';

class ReviewResponseBody {
  ReviewResponseBody({required this.body, this.respondedAt});

  factory ReviewResponseBody.fromJson(Map<String, dynamic> json) =>
      ReviewResponseBody(
        body: json['body'] as String? ?? '',
        respondedAt: parseDate(json['responded_at']),
      );

  final String body;
  final DateTime? respondedAt;
}

/// A review on a completed job — either direction (customer reviewing a
/// provider, or a provider reviewing a customer). `verifiedService` is
/// always true here: every review the API returns is inherently tied to a
/// completed Oncall job (see ReviewResource on the backend).
class Review {
  Review({
    required this.id,
    required this.rating,
    this.comment,
    this.status,
    this.response,
    this.verifiedService = true,
    this.reviewer,
    this.reviewee,
    this.createdAt,
  });

  factory Review.fromJson(Map<String, dynamic> json) => Review(
    id: json['id'] as int,
    rating: json['rating'] as int,
    comment: json['comment'] as String?,
    status: json['status'] as String?,
    response: json['response'] == null
        ? null
        : ReviewResponseBody.fromJson(json['response'] as Map<String, dynamic>),
    verifiedService: json['verified_service'] as bool? ?? true,
    reviewer: IdName.fromJsonOrNull(json['reviewer']),
    reviewee: IdName.fromJsonOrNull(json['reviewee']),
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final int rating;
  final String? comment;
  final String? status;
  final ReviewResponseBody? response;
  final bool verifiedService;
  final IdName? reviewer;
  final IdName? reviewee;
  final DateTime? createdAt;

  bool get hasResponse => response != null;
  bool get isPublished => status == null || status == 'PUBLISHED';
}
