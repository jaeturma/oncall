import '../core/json.dart';

class ProviderDocument {
  ProviderDocument({
    required this.id,
    required this.documentType,
    required this.status,
    this.notes,
    this.reviewedAt,
    this.expiresAt,
    this.createdAt,
  });

  factory ProviderDocument.fromJson(Map<String, dynamic> json) =>
      ProviderDocument(
        id: json['id'] as int,
        documentType: json['document_type'] as String,
        status: json['status'] as String,
        notes: json['notes'] as String?,
        reviewedAt: parseDate(json['reviewed_at']),
        expiresAt: parseDate(json['expires_at']),
        createdAt: parseDate(json['created_at']),
      );

  final int id;
  final String documentType;
  final String status;
  final String? notes;
  final DateTime? reviewedAt;
  final DateTime? expiresAt;
  final DateTime? createdAt;
}
