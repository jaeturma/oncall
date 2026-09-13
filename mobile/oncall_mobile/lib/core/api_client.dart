import 'package:dio/dio.dart';

import 'api_exception.dart';
import 'token_storage.dart';

/// Base URL for every `/api/v1` call. Override per-environment with
/// `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1`
/// (Android emulator) or a device's LAN address; defaults to the project's
/// local domain, which already resolves for this machine.
const String _defaultApiBaseUrl = 'https://oncall.app/api/v1';

/// Thin wrapper around `dio` that attaches the bearer token to every
/// request, normalizes every failure into an [ApiException], and calls
/// [onUnauthorized] once when the server reports the token is no longer
/// valid — [AuthState] wires that to signing the app out.
class ApiClient {
  ApiClient({required TokenStorage tokenStorage})
    : _tokenStorage = tokenStorage,
      _dio = Dio(
        BaseOptions(
          baseUrl: const String.fromEnvironment(
            'API_BASE_URL',
            defaultValue: _defaultApiBaseUrl,
          ),
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 15),
          headers: {'Accept': 'application/json'},
        ),
      ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final Dio _dio;
  final TokenStorage _tokenStorage;

  /// Set once by [AuthState] after construction; called when a 401 means the
  /// current token is dead (logged out elsewhere, revoked, expired).
  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? query,
  }) => _send(() => _dio.get(path, queryParameters: query));

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
  }) => _send(() => _dio.post(path, data: data));

  Future<Map<String, dynamic>> patch(
    String path, {
    Map<String, dynamic>? data,
  }) => _send(() => _dio.patch(path, data: data));

  Future<Map<String, dynamic>> uploadFile(
    String path, {
    required String fieldName,
    required String filePath,
    Map<String, dynamic> fields = const {},
  }) => _send(
    () => _dio.post(
      path,
      data: FormData.fromMap({
        ...fields,
        fieldName: MultipartFile.fromFileSync(filePath),
      }),
    ),
  );

  Future<Map<String, dynamic>> _send(
    Future<Response<dynamic>> Function() request,
  ) async {
    try {
      final response = await request();

      return _asMap(response.data);
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  Map<String, dynamic> _asMap(dynamic data) {
    if (data is Map<String, dynamic>) {
      return data;
    }

    return <String, dynamic>{};
  }

  ApiException _toApiException(DioException error) {
    final response = error.response;
    final statusCode = response?.statusCode;
    final body = response?.data;

    if (statusCode == 401) {
      onUnauthorized?.call();
    }

    if (body is Map<String, dynamic>) {
      final message =
          body['message'] as String? ?? _fallbackMessage(statusCode);
      final rawErrors = body['errors'];
      final fieldErrors = <String, List<String>>{};
      if (rawErrors is Map<String, dynamic>) {
        rawErrors.forEach((field, messages) {
          if (messages is List) {
            fieldErrors[field] = messages.map((m) => m.toString()).toList();
          }
        });
      }

      return ApiException(
        statusCode: statusCode,
        message: message,
        fieldErrors: fieldErrors,
        retryAfterSeconds: body['retry_after'] is int
            ? body['retry_after'] as int
            : null,
      );
    }

    return ApiException(
      statusCode: statusCode,
      message: _fallbackMessage(statusCode),
    );
  }

  String _fallbackMessage(int? statusCode) => switch (statusCode) {
    401 => 'Your session has expired. Please log in again.',
    403 => 'You are not allowed to do that.',
    404 => 'That could not be found.',
    422 => 'Please check the form and try again.',
    null => 'Could not reach the server. Check your connection and try again.',
    _ => 'Something went wrong. Please try again.',
  };
}
