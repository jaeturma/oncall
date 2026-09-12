import '../core/json.dart';

enum UserRole {
  serviceFinder('SERVICE_FINDER'),
  serviceProvider('SERVICE_PROVIDER');

  const UserRole(this.value);

  final String value;

  static UserRole? fromValue(String? value) => switch (value) {
    'SERVICE_FINDER' => UserRole.serviceFinder,
    'SERVICE_PROVIDER' => UserRole.serviceProvider,
    // A back-office role (ADMIN/ACCOUNTING/BUDGET/CASHIER) can never reach
    // this app — the API's `use-mobile` gate refuses it a token — so any
    // other value is treated as "not a marketplace role" rather than
    // guessed at.
    _ => null,
  };
}

class User {
  User({
    required this.id,
    required this.name,
    required this.initials,
    required this.email,
    required this.role,
    this.phone,
    required this.status,
    required this.emailVerified,
    required this.mobileVerified,
    this.identityVerificationStatus,
    required this.identityVerified,
    this.rating,
    required this.reviewsCount,
    this.accountTypeName,
    this.sponsorEmail,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id'] as int,
    name: json['name'] as String,
    initials: json['initials'] as String? ?? '',
    email: json['email'] as String,
    phone: json['phone'] as String?,
    role: UserRole.fromValue(json['role'] as String?),
    status: json['status'] as String,
    emailVerified: json['email_verified'] as bool? ?? false,
    mobileVerified: json['mobile_verified'] as bool? ?? false,
    identityVerificationStatus: json['identity_verification_status'] as String?,
    identityVerified: json['identity_verified'] as bool? ?? false,
    rating: parseNum(json['rating']),
    reviewsCount: json['reviews_count'] as int? ?? 0,
    accountTypeName:
        (json['account_type'] as Map<String, dynamic>?)?['name'] as String?,
    sponsorEmail: json['sponsor_email'] as String?,
  );

  final int id;
  final String name;
  final String initials;
  final String email;
  final String? phone;
  final UserRole? role;
  final String status;
  final bool emailVerified;
  final bool mobileVerified;
  final String? identityVerificationStatus;
  final bool identityVerified;
  final double? rating;
  final int reviewsCount;
  final String? accountTypeName;
  final String? sponsorEmail;

  bool get isProvider => role == UserRole.serviceProvider;

  bool get isSuspended => status == 'SUSPENDED';
}
