import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/notification_repository.dart';
import '../../widgets/common.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<NotificationsPage> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<NotificationRepository>().list();
  }

  Future<void> _refresh() async {
    final future = context.read<NotificationRepository>().list();
    setState(() => _load = future);
    await future;
  }

  Future<void> _markAllRead() async {
    await context.read<NotificationRepository>().markAllRead();
    await _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all),
            tooltip: 'Mark all as read',
            onPressed: _markAllRead,
          ),
        ],
      ),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load notifications.',
              onRetry: _refresh,
            );
          }

          final notifications = snapshot.data!.page.items;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: notifications.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message: 'No notifications yet.',
                        icon: Icons.notifications_none,
                      ),
                    ],
                  )
                : ListView.separated(
                    itemCount: notifications.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final n = notifications[index];

                      return ListTile(
                        leading: Icon(
                          n.isUnread ? Icons.circle : Icons.circle_outlined,
                          size: 12,
                          color: n.isUnread ? Colors.blue : Colors.grey,
                        ),
                        title: Text(n.title),
                        subtitle: Text(formatDateTime(n.createdAt)),
                        onTap: () async {
                          await context.read<NotificationRepository>().markRead(
                            n.id,
                          );
                          _refresh();
                        },
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
