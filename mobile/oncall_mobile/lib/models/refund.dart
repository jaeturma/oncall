import '../core/json.dart';

class Refund {
  Refund({
    required this.id,
    required this.jobPaymentId,
    required this.amount,
    required this.status,
    required this.reason,
    this.decisionNotes,
    this.reviewedAt,
    this.createdAt,
  });

  factory Refund.fromJson(Map<String, dynamic> json) => Refund(
    id: json['id'] as int,
    jobPaymentId: json['job_payment_id'] as int,
    amount: parseNum(json['amount']) ?? 0,
    status: json['status'] as String,
    reason: json['reason'] as String,
    decisionNotes: json['decision_notes'] as String?,
    reviewedAt: parseDate(json['reviewed_at']),
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final int jobPaymentId;
  final double amount;
  final String status;
  final String reason;
  final String? decisionNotes;
  final DateTime? reviewedAt;
  final DateTime? createdAt;

  bool get isDecided => status != 'REQUESTED' && status != 'UNDER_REVIEW';
}
