import '../core/json.dart';

class SponsorCommission {
  SponsorCommission({required this.amount, required this.status});

  factory SponsorCommission.fromJson(Map<String, dynamic> json) =>
      SponsorCommission(
        amount: parseNum(json['amount']) ?? 0,
        status: json['status'] as String,
      );

  final double amount;
  final String status;
}

class SponsoredUser {
  SponsoredUser({
    required this.id,
    required this.name,
    this.accountType,
    this.identityVerificationStatus,
    this.joinedAt,
    this.commission,
  });

  factory SponsoredUser.fromJson(Map<String, dynamic> json) => SponsoredUser(
    id: json['id'] as int,
    name: json['name'] as String,
    accountType: json['account_type'] as String?,
    identityVerificationStatus: json['identity_verification_status'] as String?,
    joinedAt: parseDate(json['joined_at']),
    commission: json['commission'] == null
        ? null
        : SponsorCommission.fromJson(
            json['commission'] as Map<String, dynamic>,
          ),
  );

  final int id;
  final String name;
  final String? accountType;
  final String? identityVerificationStatus;
  final DateTime? joinedAt;
  final SponsorCommission? commission;
}
