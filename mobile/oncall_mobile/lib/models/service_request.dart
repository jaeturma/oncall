import '../core/json.dart';

class ServiceRequest {
  ServiceRequest({
    required this.id,
    required this.title,
    this.description,
    required this.urgency,
    this.neededAt,
    this.budgetMin,
    this.budgetMax,
    required this.status,
    this.service,
    this.province,
    this.municipality,
    this.serviceFinder,
    this.requestedProvider,
    this.jobId,
    this.createdAt,
  });

  factory ServiceRequest.fromJson(Map<String, dynamic> json) => ServiceRequest(
    id: json['id'] as int,
    title: json['title'] as String,
    description: json['description'] as String?,
    urgency: json['urgency'] as String,
    neededAt: parseDate(json['needed_at']),
    budgetMin: parseNum(json['budget_min']),
    budgetMax: parseNum(json['budget_max']),
    status: json['status'] as String,
    service: IdName.fromJsonOrNull(json['service']),
    province: IdName.fromJsonOrNull(json['province']),
    municipality: IdName.fromJsonOrNull(json['municipality']),
    serviceFinder: IdName.fromJsonOrNull(json['service_finder']),
    requestedProvider: IdName.fromJsonOrNull(json['requested_provider']),
    jobId: json['job_id'] as int?,
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final String title;
  final String? description;
  final String urgency;
  final DateTime? neededAt;
  final double? budgetMin;
  final double? budgetMax;
  final String status;
  final IdName? service;
  final IdName? province;
  final IdName? municipality;
  final IdName? serviceFinder;
  final IdName? requestedProvider;
  final int? jobId;
  final DateTime? createdAt;

  bool get isOpen => status == 'REQUESTED' || status == 'SEARCHING';
}
