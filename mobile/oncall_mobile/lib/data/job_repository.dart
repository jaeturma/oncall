import '../core/api_client.dart';
import '../models/job.dart';
import '../models/paginated.dart';

class JobRepository {
  JobRepository(this._client);

  final ApiClient _client;

  Future<Paginated<Job>> list({int page = 1}) async {
    final json = await _client.get('/jobs', query: {'page': page});

    return Paginated.fromJson(json, Job.fromJson);
  }

  Future<Job> show(int id) async {
    final json = await _client.get('/jobs/$id');

    return Job.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<Job> updateStatus(
    int jobId, {
    required String status,
    String? notes,
  }) async {
    final json = await _client.patch(
      '/jobs/$jobId/status',
      data: {
        'status': status,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );

    return Job.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<JobMessage> sendMessage(
    int jobId, {
    required String body,
    String type = 'MESSAGE',
  }) async {
    final json = await _client.post(
      '/jobs/$jobId/messages',
      data: {'type': type, 'body': body},
    );

    return JobMessage.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<JobReview> submitReview(
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

    return JobReview.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Incident reporting on a job's counterparty (safety domain).
  Future<void> reportUser(
    int jobId, {
    required String category,
    required String description,
  }) => _client.post(
    '/jobs/$jobId/reports',
    data: {'category': category, 'description': description},
  );

  Future<JobDispute> openDispute(
    int jobId, {
    required String category,
    required String description,
  }) async {
    final json = await _client.post(
      '/jobs/$jobId/disputes',
      data: {'category': category, 'description': description},
    );

    return JobDispute.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> withdrawDispute(int disputeId) =>
      _client.patch('/disputes/$disputeId/withdraw');

  Future<JobPayment> confirmPayment(
    int jobPaymentId, {
    required String paymentMethod,
    required String paymentReference,
  }) async {
    final json = await _client.patch(
      '/job-payments/$jobPaymentId/confirm',
      data: {
        'payment_method': paymentMethod,
        'payment_reference': paymentReference,
      },
    );

    return JobPayment.fromJson(json['data'] as Map<String, dynamic>);
  }
}
