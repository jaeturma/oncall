import '../core/json.dart';

class ProviderServiceOffering {
  ProviderServiceOffering({
    this.id,
    this.service,
    this.experienceText,
    this.rateType,
    this.rateFrom,
    this.rateTo,
  });

  factory ProviderServiceOffering.fromJson(Map<String, dynamic> json) =>
      ProviderServiceOffering(
        id: json['id'] as int?,
        service: IdName.fromJsonOrNull(json['service']),
        experienceText: json['experience_text'] as String?,
        rateType: json['rate_type'] as String?,
        rateFrom: parseNum(json['rate_from']),
        rateTo: parseNum(json['rate_to']),
      );

  final int? id;
  final IdName? service;
  final String? experienceText;
  final String? rateType;
  final double? rateFrom;
  final double? rateTo;
}

/// A provider's marketplace profile. `name` is only present when the API
/// deliberately revealed it (a verified Service Finder viewing a search
/// result or provider page) — never assume it is set.
class ProviderProfile {
  ProviderProfile({
    required this.id,
    this.name,
    this.bio,
    required this.availabilityStatus,
    required this.availableNow,
    required this.verificationStatus,
    this.rating,
    required this.completedJobs,
    this.mobileVerified,
    this.emailVerified,
    required this.verifiedDocumentTypes,
    this.distanceKm,
    this.province,
    this.municipality,
    required this.services,
  });

  factory ProviderProfile.fromJson(Map<String, dynamic> json) =>
      ProviderProfile(
        id: json['id'] as int,
        name: json['name'] as String?,
        bio: json['bio'] as String?,
        availabilityStatus: json['availability_status'] as String,
        availableNow: json['available_now'] as bool? ?? false,
        verificationStatus: json['verification_status'] as String,
        rating: parseNum(json['rating']),
        completedJobs: json['completed_jobs'] as int? ?? 0,
        mobileVerified: json['mobile_verified'] as bool?,
        emailVerified: json['email_verified'] as bool?,
        verifiedDocumentTypes:
            (json['verified_document_types'] as List<dynamic>? ?? [])
                .map((e) => e.toString())
                .toList(),
        distanceKm: parseNum(json['distance_km']),
        province: IdName.fromJsonOrNull(json['province']),
        municipality: IdName.fromJsonOrNull(json['municipality']),
        services: (json['services'] as List<dynamic>? ?? [])
            .map(
              (s) =>
                  ProviderServiceOffering.fromJson(s as Map<String, dynamic>),
            )
            .toList(),
      );

  final int id;
  final String? name;
  final String? bio;
  final String availabilityStatus;
  final bool availableNow;
  final String verificationStatus;
  final double? rating;
  final int completedJobs;
  final bool? mobileVerified;
  final bool? emailVerified;
  final List<String> verifiedDocumentTypes;
  final double? distanceKm;
  final IdName? province;
  final IdName? municipality;
  final List<ProviderServiceOffering> services;

  String get displayName => name ?? 'Verified provider';
}
