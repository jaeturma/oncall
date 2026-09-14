import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/messages_repository.dart';
import '../../models/conversation.dart';
import '../../models/paginated.dart';
import '../../state/auth_state.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/avatar.dart';
import '../../widgets/common.dart';

class MessagesListScreen extends StatefulWidget {
  const MessagesListScreen({super.key});

  @override
  State<MessagesListScreen> createState() => _MessagesListScreenState();
}

class _MessagesListScreenState extends State<MessagesListScreen> {
  late Future<Paginated<Conversation>> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<MessagesRepository>().fetchConversations();
  }

  Future<void> _refresh() async {
    final future = context.read<MessagesRepository>().fetchConversations();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your messages.',
              onRetry: _refresh,
            );
          }

          final conversations = snapshot.data!.items;
          final myId = context.watch<AuthState>().user?.id;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: conversations.isEmpty
                ? ListView(
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 80),
                      AppEmptyState(
                        icon: Icons.chat_bubble_outline,
                        title: 'No conversations yet',
                        message:
                            'Once a booking is confirmed, messages you send '
                            'or receive on that job will appear here.',
                      ),
                    ],
                  )
                : ListView.separated(
                    itemCount: conversations.length,
                    separatorBuilder: (_, _) =>
                        const Divider(height: 1, color: AppColors.line),
                    itemBuilder: (context, index) {
                      final c = conversations[index];
                      final unread = c.unreadCount > 0;
                      final last = c.latestMessage;
                      final preview = last == null
                          ? c.service?.name ?? ''
                          : '${last.senderId == myId ? 'You: ' : ''}${last.body}';

                      return Container(
                        color: unread
                            ? AppColors.gold50.withValues(alpha: 0.6)
                            : null,
                        child: ListTile(
                          leading: Avatar(
                            name: c.withParticipant.name,
                            size: AvatarSize.md,
                          ),
                          title: Text(
                            c.withParticipant.name,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontWeight: FontWeight.w600,
                              color: AppColors.ink,
                            ),
                          ),
                          subtitle: Text(
                            preview,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontWeight: unread
                                  ? FontWeight.w600
                                  : FontWeight.w400,
                              color: unread
                                  ? AppColors.ink
                                  : AppColors.inkSecondary,
                            ),
                          ),
                          trailing: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                formatDate(c.latestMessage?.createdAt),
                                style: const TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 12,
                                  color: AppColors.inkMuted,
                                ),
                              ),
                              if (unread) ...[
                                const SizedBox(height: 6),
                                Container(
                                  width: 10,
                                  height: 10,
                                  decoration: const BoxDecoration(
                                    color: AppColors.gold500,
                                    shape: BoxShape.circle,
                                  ),
                                ),
                              ],
                            ],
                          ),
                          onTap: () =>
                              context.push('/conversations/${c.jobId}'),
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
