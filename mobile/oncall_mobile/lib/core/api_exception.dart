/// A failed `/api/v1` call, carrying the server's own message and any
/// field-level validation errors (the `errors` map Laravel's default
/// validation-failure JSON shape returns) so a screen can show them inline.
class ApiException implements Exception {
  ApiException({
    required this.statusCode,
    required this.message,
    this.fieldErrors = const {},
    this.retryAfterSeconds,
  });

  final int? statusCode;
  final String message;
  final Map<String, List<String>> fieldErrors;

  /// Populated for a 429 (rate limited / cooldown) response — see
  /// `OtpService`'s cooldown/rate-limit responses on the backend.
  final int? retryAfterSeconds;

  String? firstErrorFor(String field) => fieldErrors[field]?.first;

  @override
  String toString() => message;
}
