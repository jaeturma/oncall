import '../core/json.dart';

class JobPayment {
  JobPayment({
    required this.id,
    required this.status,
    required this.grossAmount,
    required this.platformFee,
    required this.netAmount,
    this.paymentMethod,
    this.paymentReference,
    this.confirmedAt,
    this.releasedAt,
  });

  factory JobPayment.fromJson(Map<String, dynamic> json) => JobPayment(
    id: json['id'] as int,
    status: json['status'] as String,
    grossAmount: parseNum(json['gross_amount']) ?? 0,
    platformFee: parseNum(json['platform_fee']) ?? 0,
    netAmount: parseNum(json['net_amount']) ?? 0,
    paymentMethod: json['payment_method'] as String?,
    paymentReference: json['payment_reference'] as String?,
    confirmedAt: parseDate(json['confirmed_at']),
    releasedAt: parseDate(json['released_at']),
  );

  final int id;
  final String status;
  final double grossAmount;
  final double platformFee;
  final double netAmount;
  final String? paymentMethod;
  final String? paymentReference;
  final DateTime? confirmedAt;
  final DateTime? releasedAt;
}

class JobDispute {
  JobDispute({
    required this.id,
    required this.category,
    required this.description,
    required this.status,
    this.refundAmount,
    this.resolution,
    this.resolvedAt,
  });

  factory JobDispute.fromJson(Map<String, dynamic> json) => JobDispute(
    id: json['id'] as int,
    category: json['category'] as String,
    description: json['description'] as String,
    status: json['status'] as String,
    refundAmount: parseNum(json['refund_amount']),
    resolution: json['resolution'] as String?,
    resolvedAt: parseDate(json['resolved_at']),
  );

  final int id;
  final String category;
  final String description;
  final String status;
  final double? refundAmount;
  final String? resolution;
  final DateTime? resolvedAt;

  bool get isOpen => status == 'OPEN' || status == 'UNDER_REVIEW';
}

class JobStatusLogEntry {
  JobStatusLogEntry({
    this.fromStatus,
    required this.toStatus,
    this.notes,
    this.changedBy,
    this.createdAt,
  });

  factory JobStatusLogEntry.fromJson(Map<String, dynamic> json) =>
      JobStatusLogEntry(
        fromStatus: json['from_status'] as String?,
        toStatus: json['to_status'] as String,
        notes: json['notes'] as String?,
        changedBy: json['changed_by'] as String?,
        createdAt: parseDate(json['created_at']),
      );

  final String? fromStatus;
  final String toStatus;
  final String? notes;
  final String? changedBy;
  final DateTime? createdAt;
}

class JobMessage {
  JobMessage({
    required this.id,
    required this.type,
    required this.body,
    this.sender,
    this.readAt,
    this.createdAt,
  });

  factory JobMessage.fromJson(Map<String, dynamic> json) => JobMessage(
    id: json['id'] as int,
    type: json['type'] as String,
    body: json['body'] as String,
    sender: IdName.fromJsonOrNull(json['sender']),
    readAt: parseDate(json['read_at']),
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final String type;
  final String body;
  final IdName? sender;
  final DateTime? readAt;
  final DateTime? createdAt;
}

class JobReview {
  JobReview({
    required this.id,
    required this.rating,
    this.comment,
    this.reviewer,
    this.reviewee,
    this.createdAt,
  });

  factory JobReview.fromJson(Map<String, dynamic> json) => JobReview(
    id: json['id'] as int,
    rating: json['rating'] as int,
    comment: json['comment'] as String?,
    reviewer: IdName.fromJsonOrNull(json['reviewer']),
    reviewee: IdName.fromJsonOrNull(json['reviewee']),
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final int rating;
  final String? comment;
  final IdName? reviewer;
  final IdName? reviewee;
  final DateTime? createdAt;
}

class Job {
  Job({
    required this.id,
    required this.status,
    this.agreedPrice,
    this.acceptedAt,
    this.onTheWayAt,
    this.startedAt,
    this.completedAt,
    this.cancelledAt,
    required this.allowedTransitions,
    this.service,
    this.province,
    this.municipality,
    this.serviceFinder,
    this.provider,
    this.payment,
    this.dispute,
    required this.statusLogs,
    required this.messages,
    required this.reviews,
  });

  factory Job.fromJson(Map<String, dynamic> json) => Job(
    id: json['id'] as int,
    status: json['status'] as String,
    agreedPrice: parseNum(json['agreed_price']),
    acceptedAt: parseDate(json['accepted_at']),
    onTheWayAt: parseDate(json['on_the_way_at']),
    startedAt: parseDate(json['started_at']),
    completedAt: parseDate(json['completed_at']),
    cancelledAt: parseDate(json['cancelled_at']),
    allowedTransitions: (json['allowed_transitions'] as List<dynamic>? ?? [])
        .map((e) => e.toString())
        .toList(),
    service: IdName.fromJsonOrNull(json['service']),
    province: IdName.fromJsonOrNull(json['province']),
    municipality: IdName.fromJsonOrNull(json['municipality']),
    serviceFinder: IdName.fromJsonOrNull(json['service_finder']),
    provider: IdName.fromJsonOrNull(json['provider']),
    payment: json['payment'] == null
        ? null
        : JobPayment.fromJson(json['payment'] as Map<String, dynamic>),
    dispute: json['dispute'] == null
        ? null
        : JobDispute.fromJson(json['dispute'] as Map<String, dynamic>),
    statusLogs: (json['status_logs'] as List<dynamic>? ?? [])
        .map((e) => JobStatusLogEntry.fromJson(e as Map<String, dynamic>))
        .toList(),
    messages: (json['messages'] as List<dynamic>? ?? [])
        .map((e) => JobMessage.fromJson(e as Map<String, dynamic>))
        .toList(),
    reviews: (json['reviews'] as List<dynamic>? ?? [])
        .map((e) => JobReview.fromJson(e as Map<String, dynamic>))
        .toList(),
  );

  final int id;
  final String status;
  final double? agreedPrice;
  final DateTime? acceptedAt;
  final DateTime? onTheWayAt;
  final DateTime? startedAt;
  final DateTime? completedAt;
  final DateTime? cancelledAt;
  final List<String> allowedTransitions;
  final IdName? service;
  final IdName? province;
  final IdName? municipality;
  final IdName? serviceFinder;
  final IdName? provider;
  final JobPayment? payment;
  final JobDispute? dispute;
  final List<JobStatusLogEntry> statusLogs;
  final List<JobMessage> messages;
  final List<JobReview> reviews;

  bool get isActive => !['COMPLETED', 'CANCELLED'].contains(status);
}
