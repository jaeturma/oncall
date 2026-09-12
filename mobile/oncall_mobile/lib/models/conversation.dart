import '../core/json.dart';

class LatestMessagePreview {
  LatestMessagePreview({
    required this.body,
    required this.senderId,
    this.createdAt,
  });

  factory LatestMessagePreview.fromJson(Map<String, dynamic> json) =>
      LatestMessagePreview(
        body: json['body'] as String,
        senderId: json['sender_id'] as int,
        createdAt: parseDate(json['created_at']),
      );

  final String body;
  final int senderId;
  final DateTime? createdAt;
}

class Conversation {
  Conversation({
    required this.jobId,
    this.service,
    required this.withParticipant,
    required this.unreadCount,
    this.latestMessage,
  });

  factory Conversation.fromJson(Map<String, dynamic> json) => Conversation(
    jobId: json['job_id'] as int,
    service: IdName.fromJsonOrNull(json['service']),
    withParticipant: IdName.fromJson(json['with'] as Map<String, dynamic>),
    unreadCount: json['unread_count'] as int? ?? 0,
    latestMessage: json['latest_message'] == null
        ? null
        : LatestMessagePreview.fromJson(
            json['latest_message'] as Map<String, dynamic>,
          ),
  );

  final int jobId;
  final IdName? service;
  final IdName withParticipant;
  final int unreadCount;
  final LatestMessagePreview? latestMessage;
}
