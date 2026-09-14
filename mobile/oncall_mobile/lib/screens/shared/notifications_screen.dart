import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../core/notification_targets.dart';
import '../../data/notification_repository.dart';
import '../../services/push_service.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_empty_state.dart';
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
    // A foreground push doesn't create anything client-side — the backend
    // already wrote it — this just prompts a refresh while this screen is
    // the one visible (Step 31).
    context.read<PushService>().addListener(_refresh);
  }

  @override
  void dispose() {
    context.read<PushService>().removeListener(_refresh);
    super.dispose();
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
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 80),
                      AppEmptyState(
                        icon: Icons.notifications_none,
                        title: 'No notifications yet',
                        message:
                            'Updates about your requests, bookings, wallet, '
                            'and account will land here.',
                      ),
                    ],
                  )
                : ListView.separated(
                    itemCount: notifications.length,
                    separatorBuilder: (_, _) =>
                        const Divider(height: 1, color: AppColors.line),
                    itemBuilder: (context, index) {
                      final n = notifications[index];

                      return Container(
                        color: n.isUnread
                            ? AppColors.gold50.withValues(alpha: 0.6)
                            : null,
                        child: ListTile(
                          leading: Padding(
                            padding: const EdgeInsets.only(top: 6),
                            child: Container(
                              width: 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: n.isUnread
                                    ? AppColors.gold500
                                    : Colors.transparent,
                                shape: BoxShape.circle,
                              ),
                            ),
                          ),
                          title: Text(
                            n.title,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontWeight: FontWeight.w600,
                              color: AppColors.ink,
                            ),
                          ),
                          subtitle: Text(
                            n.body.isEmpty
                                ? formatDateTime(n.createdAt)
                                : '${n.body}\n${formatDateTime(n.createdAt)}',
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              color: AppColors.inkSecondary,
                            ),
                          ),
                          isThreeLine: n.body.isNotEmpty,
                          onTap: () async {
                            final route = notificationRouteFor(n.target);
                            await context
                                .read<NotificationRepository>()
                                .markRead(n.id);
                            if (!context.mounted) {
                              return;
                            }
                            await _refresh();
                            if (route != null && context.mounted) {
                              context.push(route);
                            }
                          },
                        ),
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
