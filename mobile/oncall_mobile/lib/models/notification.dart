import '../core/json.dart';

class AppNotification {
  AppNotification({
    required this.id,
    required this.type,
    required this.data,
    this.readAt,
    this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: json['id'] as String,
        type: json['type'] as String,
        data: (json['data'] as Map<String, dynamic>?) ?? {},
        readAt: parseDate(json['read_at']),
        createdAt: parseDate(json['created_at']),
      );

  final String id;
  final String type;
  final Map<String, dynamic> data;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;

  String get title =>
      data['title'] as String? ?? data['message'] as String? ?? type;
}
