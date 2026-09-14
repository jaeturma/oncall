import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/job_repository.dart';
import '../../models/job.dart';
import '../../state/auth_state.dart';
import '../../widgets/chat_bubble.dart';
import '../../widgets/common.dart';

class ConversationScreen extends StatefulWidget {
  const ConversationScreen({super.key, required this.jobId});

  final int jobId;

  @override
  State<ConversationScreen> createState() => _ConversationScreenState();
}

class _ConversationScreenState extends State<ConversationScreen> {
  late Future<Job> _load;
  final _messageController = TextEditingController();
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<JobRepository>().show(widget.jobId);
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final body = _messageController.text.trim();
    if (body.isEmpty) {
      return;
    }
    setState(() => _sending = true);
    try {
      await context.read<JobRepository>().sendMessage(widget.jobId, body: body);
      _messageController.clear();
      setState(() => _load = context.read<JobRepository>().show(widget.jobId));
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final myId = context.watch<AuthState>().user?.id;

    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<Job>(
              future: _load,
              builder: (context, snapshot) {
                if (!snapshot.hasData && !snapshot.hasError) {
                  return const LoadingView();
                }
                if (snapshot.hasError) {
                  return const ErrorView(
                    message: 'Could not load this conversation.',
                  );
                }

                final messages = snapshot.data!.messages;
                if (messages.isEmpty) {
                  return const EmptyView(
                    message: 'No messages yet. Say hello!',
                  );
                }

                return ListView.builder(
                  reverse: true,
                  padding: const EdgeInsets.all(12),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final message = messages[messages.length - 1 - index];
                    final isMine = message.sender?.id == myId;

                    return ChatBubble(
                      senderName: message.sender?.name ?? '',
                      body: message.body,
                      isMine: isMine,
                      createdAt: message.createdAt,
                      type: message.type,
                    );
                  },
                );
              },
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _messageController,
                      decoration: const InputDecoration(hintText: 'Message'),
                      minLines: 1,
                      maxLines: 4,
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _sending ? null : _send,
                    icon: const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
