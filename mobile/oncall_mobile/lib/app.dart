import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/token_storage.dart';
import 'data/auth_repository.dart';
import 'data/catalog_repository.dart';
import 'data/enforcement_repository.dart';
import 'data/job_repository.dart';
import 'data/location_repository.dart';
import 'data/messages_repository.dart';
import 'data/mobile_verification_repository.dart';
import 'data/notification_repository.dart';
import 'data/profile_repository.dart';
import 'data/provider_repository.dart';
import 'data/provider_search_repository.dart';
import 'data/service_request_repository.dart';
import 'data/sponsor_repository.dart';
import 'data/verification_repository.dart';
import 'data/wallet_repository.dart';
import 'data/withdrawal_repository.dart';
import 'router.dart';
import 'state/auth_state.dart';

class OncallApp extends StatefulWidget {
  const OncallApp({super.key});

  @override
  State<OncallApp> createState() => _OncallAppState();
}

class _OncallAppState extends State<OncallApp> {
  late final TokenStorage _tokenStorage;
  late final ApiClient _apiClient;
  late final AuthState _authState;
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    _tokenStorage = TokenStorage();
    _apiClient = ApiClient(tokenStorage: _tokenStorage);
    _authState = AuthState(
      apiClient: _apiClient,
      tokenStorage: _tokenStorage,
      authRepository: AuthRepository(_apiClient),
      profileRepository: ProfileRepository(_apiClient),
    );
    _router = buildRouter(_authState);
    _authState.bootstrap();
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthState>.value(value: _authState),
        Provider<ApiClient>.value(value: _apiClient),
        Provider<AuthRepository>(create: (_) => AuthRepository(_apiClient)),
        Provider<ProfileRepository>(
          create: (_) => ProfileRepository(_apiClient),
        ),
        Provider<ProviderRepository>(
          create: (_) => ProviderRepository(_apiClient),
        ),
        Provider<CatalogRepository>(
          create: (_) => CatalogRepository(_apiClient),
        ),
        Provider<LocationRepository>(
          create: (_) => LocationRepository(_apiClient),
        ),
        Provider<ProviderSearchRepository>(
          create: (_) => ProviderSearchRepository(_apiClient),
        ),
        Provider<ServiceRequestRepository>(
          create: (_) => ServiceRequestRepository(_apiClient),
        ),
        Provider<JobRepository>(create: (_) => JobRepository(_apiClient)),
        Provider<MessagesRepository>(
          create: (_) => MessagesRepository(_apiClient),
        ),
        Provider<NotificationRepository>(
          create: (_) => NotificationRepository(_apiClient),
        ),
        Provider<WalletRepository>(create: (_) => WalletRepository(_apiClient)),
        Provider<WithdrawalRepository>(
          create: (_) => WithdrawalRepository(_apiClient),
        ),
        Provider<SponsorRepository>(
          create: (_) => SponsorRepository(_apiClient),
        ),
        Provider<VerificationRepository>(
          create: (_) => VerificationRepository(_apiClient),
        ),
        Provider<MobileVerificationRepository>(
          create: (_) => MobileVerificationRepository(_apiClient),
        ),
        Provider<EnforcementRepository>(
          create: (_) => EnforcementRepository(_apiClient),
        ),
      ],
      child: MaterialApp.router(
        title: 'Oncall',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorSchemeSeed: const Color(0xFF0B3D91),
          useMaterial3: true,
        ),
        routerConfig: _router,
      ),
    );
  }
}
