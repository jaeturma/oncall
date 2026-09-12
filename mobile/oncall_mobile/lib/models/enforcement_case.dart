import '../core/json.dart';

class EnforcementCase {
  EnforcementCase({
    required this.id,
    required this.violationCategory,
    required this.severity,
    required this.status,
    this.action,
    required this.restrictedCapabilities,
    this.startsAt,
    this.endsAt,
    this.resolution,
    required this.appealStatus,
    this.appealReason,
    this.createdAt,
  });

  factory EnforcementCase.fromJson(Map<String, dynamic> json) =>
      EnforcementCase(
        id: json['id'] as int,
        violationCategory: json['violation_category'] as String,
        severity: json['severity'] as String,
        status: json['status'] as String,
        action: json['action'] as String?,
        restrictedCapabilities:
            (json['restricted_capabilities'] as List<dynamic>? ?? [])
                .map((e) => e.toString())
                .toList(),
        startsAt: parseDate(json['starts_at']),
        endsAt: parseDate(json['ends_at']),
        resolution: json['resolution'] as String?,
        appealStatus: json['appeal_status'] as String,
        appealReason: json['appeal_reason'] as String?,
        createdAt: parseDate(json['created_at']),
      );

  final int id;
  final String violationCategory;
  final String severity;
  final String status;
  final String? action;
  final List<String> restrictedCapabilities;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? resolution;
  final String appealStatus;
  final String? appealReason;
  final DateTime? createdAt;

  bool get canAppeal => appealStatus == 'NONE' && status != 'RESOLVED';
}
