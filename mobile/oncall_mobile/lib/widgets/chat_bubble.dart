import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../theme/app_colors.dart';
import 'avatar.dart';

/// Mirrors the message bubble markup in Laravel's `jobs/show.blade.php`
/// "Booking record" — own messages sit right-aligned in navy-900/white with
/// a squared near corner, others sit left-aligned in surface-muted/ink, both
/// capped at ~85% width. A message whose `type` isn't the plain default
/// gets an uppercase tag next to the sender name (Laravel does the same for
/// `CONFIRMATION`/`EVIDENCE`).
class ChatBubble extends StatelessWidget {
  const ChatBubble({
    super.key,
    required this.senderName,
    required this.body,
    required this.isMine,
    this.createdAt,
    this.type,
    this.plainType = 'MESSAGE',
  });

  final String senderName;
  final String body;
  final bool isMine;
  final DateTime? createdAt;
  final String? type;
  final String plainType;

  @override
  Widget build(BuildContext context) {
    final bubbleColor = isMine ? AppColors.navy900 : AppColors.surfaceMuted;
    final textColor = isMine ? Colors.white : AppColors.ink;
    final metaColor = isMine ? Colors.white70 : AppColors.inkMuted;
    final showTag = type != null && type != plainType;

    final bubble = ConstrainedBox(
      constraints: BoxConstraints(
        maxWidth: MediaQuery.of(context).size.width * 0.85,
      ),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: bubbleColor,
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(isMine ? 16 : 4),
            topRight: Radius.circular(isMine ? 4 : 16),
            bottomLeft: const Radius.circular(16),
            bottomRight: const Radius.circular(16),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Wrap(
              crossAxisAlignment: WrapCrossAlignment.center,
              spacing: 6,
              runSpacing: 2,
              children: [
                Text(
                  isMine ? 'You' : senderName,
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isMine ? Colors.white : AppColors.ink,
                  ),
                ),
                if (showTag)
                  DecoratedBox(
                    decoration: BoxDecoration(
                      color: isMine
                          ? Colors.white.withValues(alpha: 0.15)
                          : AppColors.navy50,
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 1,
                      ),
                      child: Text(
                        type!.toUpperCase(),
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.4,
                          color: isMine ? Colors.white : AppColors.navy800,
                        ),
                      ),
                    ),
                  ),
                if (createdAt != null)
                  Text(
                    formatDateTime(createdAt),
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      color: metaColor,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 3),
            Text(
              body,
              style: TextStyle(fontFamily: 'Poppins', color: textColor),
            ),
          ],
        ),
      ),
    );

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: isMine
            ? MainAxisAlignment.end
            : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: isMine
            ? [
                Flexible(child: bubble),
                const SizedBox(width: 8),
                Avatar(
                  name: senderName,
                  size: AvatarSize.sm,
                  tone: AvatarTone.accent,
                ),
              ]
            : [
                Avatar(name: senderName, size: AvatarSize.sm),
                const SizedBox(width: 8),
                Flexible(child: bubble),
              ],
      ),
    );
  }
}
