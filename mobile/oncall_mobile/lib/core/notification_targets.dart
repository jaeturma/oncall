import '../models/notification.dart';

/// Mirrors the backend's `config('notifications.screens')` whitelist (Phase
/// M Step 26) — `true` means the screen needs a `target.id` to be
/// navigable. This is the client's own independent check: a notification's
/// target is a hint about where to go, never a grant to go there — whatever
/// screen it resolves to still enforces its own authorization when it loads.
const Map<String, bool> notificationScreens = {
  'service_request': true,
  'job': true,
  'conversation': true,
  'wallet': false,
  'withdrawal': false,
  'verification': false,
  'notifications': false,
  'account_status': false,
  'sponsored_users': false,
  'enforcement_case': true,
};

/// The `go_router` path for a validated [NotificationTarget], or null when
/// the screen isn't recognized, needs an id it doesn't have, or (like
/// `account_status`/`sponsored_users`/`notifications`) is whitelisted but
/// has no dedicated screen to deep-link into today. A null result means
/// "don't navigate" — never a fallback to some guessed or raw path.
String? notificationRouteFor(NotificationTarget? target) {
  if (target == null) {
    return null;
  }

  final requiresId = notificationScreens[target.screen];
  if (requiresId == null) {
    return null;
  }
  if (requiresId && target.id == null) {
    return null;
  }

  return switch (target.screen) {
    'service_request' => '/service-requests/${target.id}',
    'job' => '/jobs/${target.id}',
    'conversation' => '/conversations/${target.id}',
    'enforcement_case' => '/enforcement-cases/${target.id}',
    'wallet' => '/wallet',
    'withdrawal' => '/wallet/withdrawals',
    'verification' => '/verification',
    _ => null,
  };
}
