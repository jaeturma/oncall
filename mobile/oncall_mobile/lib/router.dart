import 'package:go_router/go_router.dart';

import 'models/user.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/customer/customer_shell.dart';
import 'screens/customer/provider_profile_screen.dart';
import 'screens/customer/request_service_screen.dart';
import 'screens/customer/search_results_screen.dart';
import 'screens/provider/provider_profile_edit_screen.dart';
import 'screens/provider/provider_shell.dart';
import 'screens/shared/conversation_screen.dart';
import 'screens/shared/enforcement_case_screen.dart';
import 'screens/shared/enforcement_cases_list_screen.dart';
import 'screens/shared/job_detail_screen.dart';
import 'screens/shared/notifications_screen.dart';
import 'screens/shared/report_user_screen.dart';
import 'screens/shared/service_request_detail_screen.dart';
import 'screens/shared/sponsor_screen.dart';
import 'screens/shared/verification_screen.dart';
import 'screens/shared/withdrawal_request_screen.dart';
import 'screens/shared/withdrawals_screen.dart';
import 'state/auth_state.dart';

/// Every route a marketplace user can reach. There is deliberately no route
/// here for anything back-office (user/role management, verification
/// *approval*, accounting/budget/cashier queues, enforcement administration,
/// settings, audit logs) — those exist only on the web `/admin` and
/// `/staff` surfaces (Phase B/C) and have no Flutter screen at all.
GoRouter buildRouter(AuthState authState) {
  return GoRouter(
    initialLocation: '/customer',
    refreshListenable: authState,
    redirect: (context, state) {
      final path = state.matchedLocation;
      final isAuthRoute = path == '/login' || path == '/register';

      if (authState.status == AuthStatus.unknown) {
        return null;
      }

      if (authState.status == AuthStatus.signedOut) {
        return isAuthRoute ? null : '/login';
      }

      // Signed in.
      final isProvider = authState.user?.role == UserRole.serviceProvider;
      final home = isProvider ? '/provider' : '/customer';

      if (isAuthRoute) {
        return home;
      }
      if (isProvider && path.startsWith('/customer')) {
        return home;
      }
      if (!isProvider && path.startsWith('/provider')) {
        return home;
      }

      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/customer',
        builder: (context, state) => const CustomerShell(),
      ),
      GoRoute(
        path: '/provider',
        builder: (context, state) => const ProviderShell(),
      ),
      GoRoute(
        path: '/search-results',
        builder: (context, state) =>
            SearchResultsScreen(queryParameters: state.uri.queryParameters),
      ),
      GoRoute(
        path: '/providers/:id',
        builder: (context, state) => ProviderProfileScreen(
          providerProfileId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/providers/:id/request',
        builder: (context, state) => RequestServiceScreen(
          providerProfileId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/service-requests/:id',
        builder: (context, state) => ServiceRequestDetailScreen(
          serviceRequestId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/jobs/:id',
        builder: (context, state) =>
            JobDetailScreen(jobId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/jobs/:id/report',
        builder: (context, state) =>
            ReportUserScreen(jobId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/conversations/:jobId',
        builder: (context, state) => ConversationScreen(
          jobId: int.parse(state.pathParameters['jobId']!),
        ),
      ),
      GoRoute(
        path: '/wallet/withdrawals',
        builder: (context, state) => const WithdrawalsScreen(),
      ),
      GoRoute(
        path: '/wallet/withdrawals/new',
        builder: (context, state) => const WithdrawalRequestScreen(),
      ),
      GoRoute(
        path: '/sponsor',
        builder: (context, state) => const SponsorScreen(),
      ),
      GoRoute(
        path: '/verification',
        builder: (context, state) => const VerificationScreen(),
      ),
      GoRoute(
        path: '/notifications',
        builder: (context, state) => const NotificationsScreen(),
      ),
      GoRoute(
        path: '/enforcement-cases',
        builder: (context, state) => const EnforcementCasesListScreen(),
      ),
      GoRoute(
        path: '/enforcement-cases/:id',
        builder: (context, state) => EnforcementCaseScreen(
          enforcementCaseId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/provider-profile/edit',
        builder: (context, state) => const ProviderProfileEditScreen(),
      ),
    ],
  );
}
