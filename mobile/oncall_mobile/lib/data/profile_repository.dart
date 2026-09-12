import '../core/api_client.dart';
import '../models/user.dart';

class ProfileRepository {
  ProfileRepository(this._client);

  final ApiClient _client;

  Future<User> fetch() async {
    final json = await _client.get('/profile');

    return User.fromJson(json['data'] as Map<String, dynamic>);
  }
}
