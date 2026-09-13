import '../core/api_client.dart';

class OtpRequestResult {
  OtpRequestResult({
    required this.mobile,
    required this.expiresInSeconds,
    required this.resendAvailableInSeconds,
    this.demoCode,
  });

  factory OtpRequestResult.fromJson(Map<String, dynamic> json) =>
      OtpRequestResult(
        mobile: json['mobile'] as String,
        expiresInSeconds: json['expires_in_seconds'] as int,
        resendAvailableInSeconds: json['resend_available_in_seconds'] as int,
        demoCode: json['demo_code'] as String?,
      );

  final String mobile;
  final int expiresInSeconds;
  final int resendAvailableInSeconds;
  final String? demoCode;
}

/// Mobile-number OTP verification. Laravel is the sole authority here: this
/// repository never generates, stores, or checks a code locally — it only
/// relays what the server decides. See `Api\V1\MobileVerificationController`.
class MobileVerificationRepository {
  MobileVerificationRepository(this._client);

  final ApiClient _client;

  Future<OtpRequestResult> request(String mobile) async {
    final json = await _client.post(
      '/mobile-verification/request',
      data: {'mobile': mobile},
    );

    return OtpRequestResult.fromJson(json);
  }

  Future<OtpRequestResult> resend() async {
    final json = await _client.post('/mobile-verification/resend');

    return OtpRequestResult.fromJson(json);
  }

  Future<void> verify(String code) =>
      _client.post('/mobile-verification/verify', data: {'code': code});
}
