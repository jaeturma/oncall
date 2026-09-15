import '../core/json.dart';

class PaymentAttempt {
  PaymentAttempt({
    required this.id,
    required this.method,
    required this.status,
    required this.gateway,
    this.gatewayReference,
    this.rejectionReason,
    this.createdAt,
  });

  factory PaymentAttempt.fromJson(Map<String, dynamic> json) =>
      PaymentAttempt(
        id: json['id'] as int,
        method: json['method'] as String,
        status: json['status'] as String,
        gateway: json['gateway'] as String,
        gatewayReference: json['gateway_reference'] as String?,
        rejectionReason: json['rejection_reason'] as String?,
        createdAt: parseDate(json['created_at']),
      );

  final int id;
  final String method;
  final String status;
  final String gateway;
  final String? gatewayReference;
  final String? rejectionReason;
  final DateTime? createdAt;
}
