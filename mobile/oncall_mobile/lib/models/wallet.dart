import '../core/json.dart';

class WalletTransaction {
  WalletTransaction({
    required this.id,
    required this.type,
    required this.status,
    required this.amount,
    this.description,
    this.createdAt,
  });

  factory WalletTransaction.fromJson(Map<String, dynamic> json) =>
      WalletTransaction(
        id: json['id'] as int,
        type: json['type'] as String,
        status: json['status'] as String,
        amount: parseNum(json['amount']) ?? 0,
        description: json['description'] as String?,
        createdAt: parseDate(json['created_at']),
      );

  final int id;
  final String type;
  final String status;
  final double amount;
  final String? description;
  final DateTime? createdAt;

  bool get isCredit => amount >= 0;
}

class Withdrawal {
  Withdrawal({
    required this.id,
    required this.amount,
    required this.status,
    required this.payoutMethod,
    required this.payoutReference,
    this.notes,
    this.createdAt,
  });

  factory Withdrawal.fromJson(Map<String, dynamic> json) => Withdrawal(
    id: json['id'] as int,
    amount: parseNum(json['amount']) ?? 0,
    status: json['status'] as String,
    payoutMethod: json['payout_method'] as String,
    payoutReference: json['payout_reference'] as String,
    notes: json['notes'] as String?,
    createdAt: parseDate(json['created_at']),
  );

  final int id;
  final double amount;
  final String status;
  final String payoutMethod;
  final String payoutReference;
  final String? notes;
  final DateTime? createdAt;

  /// Matches `WithdrawalPolicy::cancel` — only while Accounting hasn't acted yet.
  bool get isCancellable => status == 'ACCOUNTING_REVIEW';
}
