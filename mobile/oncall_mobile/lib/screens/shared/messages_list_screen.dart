import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/messages_repository.dart';
import '../../models/conversation.dart';
import '../../models/paginated.dart';
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

          return RefreshIndicator(
            onRefresh: _refresh,
            child: conversations.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message: 'No conversations yet.',
                        icon: Icons.chat_bubble_outline,
                      ),
                    ],
                  )
                : ListView.separated(
                    itemCount: conversations.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final c = conversations[index];

                      return ListTile(
                        leading: CircleAvatar(
                          child: Text(
                            c.withParticipant.name.isNotEmpty
                                ? c.withParticipant.name[0]
                                : '?',
                          ),
                        ),
                        title: Text(c.withParticipant.name),
                        subtitle: Text(
                          c.latestMessage?.body ?? c.service?.name ?? '',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        trailing: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              formatDate(c.latestMessage?.createdAt),
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            if (c.unreadCount > 0)
                              CircleAvatar(
                                radius: 9,
                                child: Text(
                                  '${c.unreadCount}',
                                  style: const TextStyle(fontSize: 11),
                                ),
                              ),
                          ],
                        ),
                        onTap: () => context.push('/conversations/${c.jobId}'),
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
