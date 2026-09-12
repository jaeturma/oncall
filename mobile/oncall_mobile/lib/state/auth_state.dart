import 'dart:async';

import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../core/token_storage.dart';
import '../data/auth_repository.dart';
import '../data/profile_repository.dart';
import '../models/user.dart';

enum AuthStatus { unknown, signedOut, signedIn }

/// The single source of truth for "who is signed in" — the router redirects
/// off of [status]/[user], and every screen reads the current [user] from
/// here rather than re-fetching `/profile` itself.
class AuthState extends ChangeNotifier {
  AuthState({
    required ApiClient apiClient,
    required TokenStorage tokenStorage,
    required AuthRepository authRepository,
    required ProfileRepository profileRepository,
  }) : _apiClient = apiClient,
       _tokenStorage = tokenStorage,
       _authRepository = authRepository,
       _profileRepository = profileRepository {
    _apiClient.onUnauthorized = _handleUnauthorized;
  }

  final ApiClient _apiClient;
  final TokenStorage _tokenStorage;
  final AuthRepository _authRepository;
  final ProfileRepository _profileRepository;

  AuthStatus status = AuthStatus.unknown;
  User? user;
  String? lastError;

  /// Runs once at app start: if a token is already stored, verify it is
  /// still valid by re-fetching the profile rather than trusting it blindly.
  Future<void> bootstrap() async {
    final token = await _tokenStorage.read();
    if (token == null) {
      status = AuthStatus.signedOut;
      notifyListeners();

      return;
    }

    try {
      user = await _profileRepository.fetch();
      status = AuthStatus.signedIn;
    } catch (_) {
      await _tokenStorage.clear();
      status = AuthStatus.signedOut;
    }
    notifyListeners();
  }

  Future<bool> login({required String email, required String password}) =>
      _attempt(() => _authRepository.login(email: email, password: password));

  Future<bool> register({
    required String name,
    required String email,
    String? phone,
    required String password,
    required UserRole role,
    String? sponsorEmail,
  }) => _attempt(
    () => _authRepository.register(
      name: name,
      email: email,
      phone: phone,
      password: password,
      role: role,
      sponsorEmail: sponsorEmail,
    ),
  );

  Future<bool> _attempt(Future<AuthResult> Function() action) async {
    lastError = null;
    try {
      final result = await action();
      await _tokenStorage.save(result.token);
      user = result.user;
      status = AuthStatus.signedIn;
      notifyListeners();

      return true;
    } catch (error) {
      lastError = error.toString();
      notifyListeners();

      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _authRepository.logout();
    } catch (_) {
      // Token may already be dead server-side; still clear it locally.
    }
    await _signOutLocally();
  }

  /// Refreshes [user] after an action that could change it (e.g. submitting
  /// identity verification, which updates `identity_verification_status`).
  Future<void> refreshUser() async {
    try {
      user = await _profileRepository.fetch();
      notifyListeners();
    } catch (_) {
      // Leave the last-known user in place; the next navigation/bootstrap
      // will retry, and `_handleUnauthorized` already covers a dead token.
    }
  }

  void _handleUnauthorized() {
    unawaited(_signOutLocally());
  }

  Future<void> _signOutLocally() async {
    await _tokenStorage.clear();
    user = null;
    status = AuthStatus.signedOut;
    notifyListeners();
  }
}
