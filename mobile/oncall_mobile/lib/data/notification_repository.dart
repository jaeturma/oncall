import '../core/api_client.dart';
import '../models/notification.dart';
import '../models/paginated.dart';

class NotificationsPage {
  NotificationsPage({required this.page, required this.unreadCount});

  final Paginated<AppNotification> page;
  final int unreadCount;
}

class NotificationRepository {
  NotificationRepository(this._client);

  final ApiClient _client;

  Future<NotificationsPage> list({int page = 1}) async {
    final json = await _client.get('/notifications', query: {'page': page});

    return NotificationsPage(
      page: Paginated.fromJson(json, AppNotification.fromJson),
      unreadCount: json['unread_count'] as int? ?? 0,
    );
  }

  Future<void> markRead(String id) => _client.patch('/notifications/$id/read');

  Future<void> markAllRead() => _client.patch('/notifications/read-all');
}
