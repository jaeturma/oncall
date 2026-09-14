import '../core/api_client.dart';

/// Registers this installation's push token with the backend (Phase M Step
/// 7). `register` doubles as the token-refresh call — the same endpoint is
/// called again whenever Firebase rotates the token, matching the backend's
/// upsert-by-`(user, installation_id)` behavior.
class DeviceRepository {
  DeviceRepository(this._client);

  final ApiClient _client;

  Future<void> register({
    required String installationId,
    required String platform,
    required String fcmToken,
  }) => _client.post(
    '/devices/register',
    data: {
      'installation_id': installationId,
      'platform': platform,
      'fcm_token': fcmToken,
    },
  );
}
