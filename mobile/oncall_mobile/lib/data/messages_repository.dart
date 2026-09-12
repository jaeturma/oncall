import '../core/api_client.dart';
import '../models/conversation.dart';
import '../models/paginated.dart';

class MessagesRepository {
  MessagesRepository(this._client);

  final ApiClient _client;

  Future<Paginated<Conversation>> fetchConversations({int page = 1}) async {
    final json = await _client.get('/messages', query: {'page': page});

    return Paginated.fromJson(json, Conversation.fromJson);
  }
}
