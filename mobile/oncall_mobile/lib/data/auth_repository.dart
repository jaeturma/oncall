import '../core/api_client.dart';
import '../models/user.dart';

class AuthResult {
  AuthResult({required this.user, required this.token});

  final User user;
  final String token;
}

class AuthRepository {
  AuthRepository(this._client);

  final ApiClient _client;

  Future<AuthResult> register({
    required String name,
    required String email,
    String? phone,
    required String password,
    required UserRole role,
    String? sponsorEmail,
  }) async {
    final json = await _client.post(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        'password': password,
        'password_confirmation': password,
        'role': role.value,
        if (sponsorEmail != null && sponsorEmail.isNotEmpty)
          'sponsor_email': sponsorEmail,
      },
    );

    return _fromJson(json);
  }

  Future<AuthResult> login({
    required String email,
    required String password,
  }) async {
    final json = await _client.post(
      '/auth/login',
      data: {'email': email, 'password': password},
    );

    return _fromJson(json);
  }

  Future<void> logout() => _client.post('/auth/logout');

  AuthResult _fromJson(Map<String, dynamic> json) => AuthResult(
    user: User.fromJson(json['data'] as Map<String, dynamic>),
    token: json['token'] as String,
  );
}
