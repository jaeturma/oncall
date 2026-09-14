import '../core/json.dart';

/// A validated deep-link target ({screen: 'job', id: 42}) or null when the
/// notification has none, or the app doesn't (yet) recognize the screen —
/// see [notificationRouteFor] for the client's own whitelist. The backend
/// already validates `screen` against `config('notifications.screens')`
/// before it ever stores or sends this (Phase M Step 26), but the client
/// re-checks independently rather than trusting it blindly.
class NotificationTarget {
  NotificationTarget({required this.screen, this.id});

  factory NotificationTarget.fromJson(Map<String, dynamic> json) =>
      NotificationTarget(screen: json['screen'] as String, id: json['id']);

  final String screen;
  final dynamic id;
}

class AppNotification {
  AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    this.target,
    this.readAt,
    this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) =>
      AppNotification(
        id: json['id'] as String,
        type: json['type'] as String,
        title: json['title'] as String? ?? json['type'] as String,
        body: json['body'] as String? ?? '',
        target: json['target'] == null
            ? null
            : NotificationTarget.fromJson(
                json['target'] as Map<String, dynamic>,
              ),
        readAt: parseDate(json['read_at']),
        createdAt: parseDate(json['created_at']),
      );

  final String id;
  final String type;
  final String title;
  final String body;
  final NotificationTarget? target;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;
}
