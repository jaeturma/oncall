import '../core/api_client.dart';
import '../models/paginated.dart';
import '../models/service_request.dart';

class ServiceRequestRepository {
  ServiceRequestRepository(this._client);

  final ApiClient _client;

  Future<Paginated<ServiceRequest>> list({int page = 1}) async {
    final json = await _client.get('/service-requests', query: {'page': page});

    return Paginated.fromJson(json, ServiceRequest.fromJson);
  }

  Future<ServiceRequest> show(int id) async {
    final json = await _client.get('/service-requests/$id');

    return ServiceRequest.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<ServiceRequest> create({
    required int providerProfileId,
    required int serviceId,
    required int provinceId,
    int? municipalityId,
    required String title,
    String? description,
    required String urgency,
    DateTime? neededAt,
    double? budgetMin,
    double? budgetMax,
  }) async {
    final json = await _client.post(
      '/providers/$providerProfileId/service-requests',
      data: {
        'service_id': serviceId,
        'province_id': provinceId,
        if (municipalityId != null) 'municipality_id': municipalityId,
        'title': title,
        if (description != null && description.isNotEmpty)
          'description': description,
        'urgency': urgency,
        if (neededAt != null) 'needed_at': neededAt.toIso8601String(),
        if (budgetMin != null) 'budget_min': budgetMin,
        if (budgetMax != null) 'budget_max': budgetMax,
        'safety_acknowledged': true,
      },
    );

    return ServiceRequest.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<int> accept(
    int serviceRequestId, {
    required double agreedPrice,
  }) async {
    final json = await _client.patch(
      '/service-requests/$serviceRequestId/accept',
      data: {'agreed_price': agreedPrice},
    );

    return (json['data'] as Map<String, dynamic>)['id'] as int;
  }

  Future<void> decline(int serviceRequestId) =>
      _client.patch('/service-requests/$serviceRequestId/decline');

  Future<void> cancel(int serviceRequestId) =>
      _client.patch('/service-requests/$serviceRequestId/cancel');
}
