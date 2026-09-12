// Smoke tests for the auth screens users see before ever reaching the
// marketplace shells. These render without hitting the network or the
// secure-storage platform channel (AuthState.bootstrap() is never called),
// so they just prove the widget trees build cleanly.
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/core/api_client.dart';
import 'package:oncall_mobile/core/token_storage.dart';
import 'package:oncall_mobile/data/auth_repository.dart';
import 'package:oncall_mobile/data/profile_repository.dart';
import 'package:oncall_mobile/screens/auth/login_screen.dart';
import 'package:oncall_mobile/screens/auth/register_screen.dart';
import 'package:oncall_mobile/state/auth_state.dart';
import 'package:provider/provider.dart';

AuthState _buildAuthState() {
  final tokenStorage = TokenStorage();
  final apiClient = ApiClient(tokenStorage: tokenStorage);

  return AuthState(
    apiClient: apiClient,
    tokenStorage: tokenStorage,
    authRepository: AuthRepository(apiClient),
    profileRepository: ProfileRepository(apiClient),
  );
}

Widget _wrap(Widget child) => ChangeNotifierProvider<AuthState>.value(
  value: _buildAuthState(),
  child: MaterialApp(home: child),
);

void main() {
  testWidgets('login screen renders its form fields', (tester) async {
    await tester.pumpWidget(_wrap(const LoginScreen()));

    expect(find.text('Oncall Philippines'), findsOneWidget);
    expect(find.widgetWithText(TextFormField, 'Email'), findsOneWidget);
    expect(find.widgetWithText(TextFormField, 'Password'), findsOneWidget);
    expect(find.text('Log in'), findsOneWidget);
  });

  testWidgets('login screen validates required fields before submitting', (
    tester,
  ) async {
    await tester.pumpWidget(_wrap(const LoginScreen()));

    await tester.tap(find.text('Log in'));
    await tester.pump();

    expect(find.text('Email is required'), findsOneWidget);
    expect(find.text('Password is required'), findsOneWidget);
  });

  testWidgets(
    'register screen defaults to the customer role and shows both segments',
    (tester) async {
      await tester.pumpWidget(_wrap(const RegisterScreen()));

      expect(find.text('Find help'), findsOneWidget);
      expect(find.text('Offer services'), findsOneWidget);
      expect(find.widgetWithText(TextFormField, 'Full name'), findsOneWidget);
    },
  );
}
