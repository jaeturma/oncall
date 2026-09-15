import '../core/json.dart';

class Receipt {
  Receipt({
    required this.status,
    required this.purpose,
    required this.grossAmount,
    required this.platformFee,
    required this.netAmount,
    required this.refundedAmount,
    required this.refundableAmount,
    required this.jobId,
    this.receiptNumber,
    this.paymentMethod,
    this.paymentReference,
    this.confirmedAt,
    this.releasedAt,
    this.service,
    this.customerName,
    this.providerName,
  });

  factory Receipt.fromJson(Map<String, dynamic> json) => Receipt(
    status: json['status'] as String,
    purpose: json['purpose'] as String,
    grossAmount: parseNum(json['gross_amount']) ?? 0,
    platformFee: parseNum(json['platform_fee']) ?? 0,
    netAmount: parseNum(json['net_amount']) ?? 0,
    refundedAmount: parseNum(json['refunded_amount']) ?? 0,
    refundableAmount: parseNum(json['refundable_amount']) ?? 0,
    jobId: json['job_id'] as int,
    receiptNumber: json['receipt_number'] as String?,
    paymentMethod: json['payment_method'] as String?,
    paymentReference: json['payment_reference'] as String?,
    confirmedAt: parseDate(json['confirmed_at']),
    releasedAt: parseDate(json['released_at']),
    service: json['service'] as String?,
    customerName: json['customer_name'] as String?,
    providerName: json['provider_name'] as String?,
  );

  final String status;
  final String purpose;
  final double grossAmount;
  final double platformFee;
  final double netAmount;
  final double refundedAmount;
  final double refundableAmount;
  final int jobId;
  final String? receiptNumber;
  final String? paymentMethod;
  final String? paymentReference;
  final DateTime? confirmedAt;
  final DateTime? releasedAt;
  final String? service;
  final String? customerName;
  final String? providerName;
}
