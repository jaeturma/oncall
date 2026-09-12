import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Stores the Sanctum bearer token on-device. Nothing else about the
/// session (role, name, etc.) is persisted here — that is re-fetched from
/// `/api/v1/profile` on app start so it is never stale.
class TokenStorage {
  TokenStorage() : _storage = const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'oncall_api_token';

  Future<String?> read() => _storage.read(key: _tokenKey);

  Future<void> save(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> clear() => _storage.delete(key: _tokenKey);
}
