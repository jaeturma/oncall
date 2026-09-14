import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../core/device_identity.dart';
import '../core/notification_targets.dart';
import '../data/device_repository.dart';
import '../models/notification.dart';

/// Coordinates Firebase Cloud Messaging: device registration, token
/// refresh, and turning a tapped push into a validated in-app navigation
/// (Phase M Steps 7-10, 26, 30-31).
///
/// Every public method is a deliberate no-op when Firebase isn't actually
/// configured for this build (no `google-services.json` /
/// `GoogleService-Info.plist` yet — see [initializeFirebase]) or when
/// running on a platform push isn't built for (web/desktop). Push is an
/// enhancement, never a requirement to use the app (Step 48) — nothing here
/// may throw out to the caller.
///
/// Foreground pushes don't create anything client-side (the backend already
/// wrote the database notification); this only notifies listeners so a
/// visible [NotificationsScreen] can refresh itself.
class PushService extends ChangeNotifier {
  PushService({
    required DeviceRepository deviceRepository,
    required DeviceIdentity deviceIdentity,
  }) : _deviceRepository = deviceRepository,
       _deviceIdentity = deviceIdentity;

  final DeviceRepository _deviceRepository;
  final DeviceIdentity _deviceIdentity;

  /// Set when a background/terminated tap resolves to a known route, so the
  /// app can navigate once auth state is confirmed (Step 30) instead of
  /// racing the router. Cleared by whoever consumes it.
  final ValueNotifier<String?> pendingRoute = ValueNotifier(null);

  static bool _firebaseReady = false;

  static bool get _supportsPush =>
      !kIsWeb && (Platform.isAndroid || Platform.isIOS);

  /// Call once, before `runApp()`, so a cold-start tap's background handler
  /// is registered before any message can arrive. Static (and safe to call
  /// before any [PushService] instance exists) since it must run ahead of
  /// the widget tree/dependency injection in `main()`.
  static Future<void> initializeFirebase() async {
    if (!_supportsPush) {
      return;
    }

    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(
        _firebaseMessagingBackgroundHandler,
      );
      _firebaseReady = true;
    } catch (error) {
      // No Firebase project wired up yet (or platform setup incomplete) —
      // push is unavailable this run, the rest of the app is unaffected.
      _firebaseReady = false;
      debugPrint('PushService: Firebase unavailable, push disabled ($error)');
    }
  }

  /// Call once the widget tree exists, to receive foreground/tap events.
  void attachMessageListeners() {
    if (!_firebaseReady) {
      return;
    }

    FirebaseMessaging.onMessage.listen((_) => notifyListeners());
    FirebaseMessaging.onMessageOpenedApp.listen(_handleOpenedMessage);
    FirebaseMessaging.instance.getInitialMessage().then((message) {
      if (message != null) {
        _handleOpenedMessage(message);
      }
    });
    FirebaseMessaging.instance.onTokenRefresh.listen(
      (_) => registerCurrentDevice(),
    );
  }

  /// Requests permission (a no-op with the OS if already decided) and, if
  /// granted, registers the current FCM token. Safe to call every time the
  /// user becomes signed-in — never re-prompts on its own.
  Future<void> registerCurrentDevice() async {
    if (!_firebaseReady) {
      return;
    }

    try {
      final settings = await FirebaseMessaging.instance.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.denied) {
        return;
      }

      final token = await FirebaseMessaging.instance.getToken();
      if (token == null) {
        return;
      }

      await _deviceRepository.register(
        installationId: await _deviceIdentity.installationId(),
        platform: Platform.isIOS ? 'IOS' : 'ANDROID',
        fcmToken: token,
      );
    } catch (error) {
      // Best-effort: registration is retried the next time the user reaches
      // a signed-in state (app resume, next login) or the token rotates.
      debugPrint('PushService: device registration failed ($error)');
    }
  }

  void _handleOpenedMessage(RemoteMessage message) {
    final target = _targetFrom(message);
    final route = notificationRouteFor(target);
    if (route != null) {
      pendingRoute.value = route;
    }
  }

  NotificationTarget? _targetFrom(RemoteMessage message) {
    final screen = message.data['screen'];
    if (screen is! String) {
      return null;
    }

    return NotificationTarget(screen: screen, id: message.data['target_id']);
  }
}

/// Must be a top-level (or static) function — Firebase runs it in a
/// separate isolate with no access to the running app's state, so it
/// re-initializes Firebase itself. Deliberately does nothing else: the OS
/// already renders the notification from the FCM payload's `notification`
/// block, and the backend already created the database record.
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}
