import '../core/json.dart';
import 'provider_reputation.dart';

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
    this.reputation,
    this.mobileVerified,
    this.emailVerified,
    required this.verifiedDocumentTypes,
    this.distanceKm,
    this.serviceRadiusKm,
    this.areaMarker,
    this.province,
    this.municipality,
    this.barangay,
    this.latitude,
    this.longitude,
    this.locationSource,
    this.locationUpdatedAt,
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
        reputation: json['reputation'] == null
            ? null
            : ProviderReputation.fromJson(
                json['reputation'] as Map<String, dynamic>,
              ),
        mobileVerified: json['mobile_verified'] as bool?,
        emailVerified: json['email_verified'] as bool?,
        verifiedDocumentTypes:
            (json['verified_document_types'] as List<dynamic>? ?? [])
                .map((e) => e.toString())
                .toList(),
        distanceKm: parseNum(json['distance_km']),
        serviceRadiusKm: json['service_radius_km'] as int?,
        areaMarker: GeoPoint.fromJsonOrNull(json['area_marker']),
        province: IdName.fromJsonOrNull(json['province']),
        municipality: IdName.fromJsonOrNull(json['municipality']),
        barangay: IdName.fromJsonOrNull(json['barangay']),
        // The provider's own exact coordinates only ever appear when the
        // viewer is that same provider — see ProviderProfileResource. Never
        // present in a search result.
        latitude: parseNum(json['latitude']),
        longitude: parseNum(json['longitude']),
        locationSource: json['location_source'] as String?,
        locationUpdatedAt: parseDate(json['location_updated_at']),
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
  final ProviderReputation? reputation;
  final bool? mobileVerified;
  final bool? emailVerified;
  final List<String> verifiedDocumentTypes;
  final double? distanceKm;
  final int? serviceRadiusKm;
  final GeoPoint? areaMarker;
  final IdName? province;
  final IdName? municipality;
  final IdName? barangay;
  final double? latitude;
  final double? longitude;
  final String? locationSource;
  final DateTime? locationUpdatedAt;
  final List<ProviderServiceOffering> services;

  String get displayName => name ?? 'Verified provider';
}
