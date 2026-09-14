# Flutter Design System — Visual Alignment with Laravel

Date: 2026-09-14
Scope: port the Laravel web app's brand identity (navy/gold palette, Poppins
typography, provider cards, badges, buttons, forms, navigation) into
`mobile/oncall_mobile` as a proper Flutter design system, without cloning
desktop layouts, changing business logic, or duplicating API logic. Laravel
(`resources/css/app.css` + `resources/views/components/ui/*.blade.php`) is
the visual source of truth for every token and component decision below.

## 1. What existed before this phase

The Flutter app had zero design-system alignment: `lib/app.dart`'s only
theming was `ThemeData(colorSchemeSeed: Color(0xFF0B3D91), useMaterial3:
true)`, producing generic Material-blue defaults across all 24 screens, with
raw `Colors.*`, bare `Card`/`Chip`/`OutlineInputBorder()` scattered across
~20 files, and no shared provider-card/badge/logo components.

## 2. Design tokens (ported verbatim from Laravel)

Source: `resources/css/app.css` `@theme` block.

| Group | Values |
| --- | --- |
| Navy (brand) | 50 `#eef4f7` … 900 `#01273a`, 950 `#001824` |
| Gold (accent/CTA) | 50 `#fffaea` … 900 `#74440d`; 400 `#ffce10` is the primary button fill |
| Success | 50/100/500/600/700/800 = `#ecfdf5 #d1fae5 #10b981 #059669 #047857 #065f46` |
| Warning | `#fffbeb #fef3c7 #f59e0b #d97706 #b45309 #92400e` |
| Danger | `#fef2f2 #fee2e2 #ef4444 #dc2626 #b91c1c #991b1b` |
| Info | 50/100/600/700/800 = `#eff6ff #dbeafe #2563eb #1d4ed8 #1e40af` |
| Neutrals | canvas `#f5f7fa`, surface `#ffffff`, surface-muted `#f8fafc`, line `#e2e8f0`, line-strong `#cbd5e1`, ink `#0f172a`, ink-secondary `#475569`, ink-muted `#64748b` |
| Radius | buttons/inputs 8px, cards 16px, badges/pills fully round |
| Shadows | soft only — `card` (barely visible), `float` (pressed/elevated), `pop` (menus). Never heavy Material elevation. |
| Typography | Poppins; body 15px/1.6; h1 24–30 bold, h2 20–24 semibold, h3 18 semibold, eyebrow 12 semibold uppercase wide-tracked |

All of the above live as `static const` values in `lib/theme/app_colors.dart`,
named after the Tailwind scale (`AppColors.navy500`, `AppColors.gold400`,
…) so a diff against the CSS file is a straight value comparison, not a
semantic re-derivation.

## 3. Theme architecture

`mobile/oncall_mobile/lib/theme/`:

- `app_colors.dart` — every token above as `Color` constants.
- `app_typography.dart` — `AppTypography.textTheme`, Poppins-based, mapped
  onto Material `TextTheme` slots (`headlineMedium`→h1, `titleLarge`→h2,
  `titleMedium`→h3, `bodyLarge`→lead, `bodyMedium`→base, `bodySmall`→muted,
  `labelLarge`→button), plus a standalone `AppTypography.eyebrow` style used
  by the `EyebrowText` widget (a plain `TextStyle` can't uppercase its own
  text).
- `app_spacing.dart`, `app_radius.dart`, `app_shadows.dart` — small constant
  classes: spacing scale, radius 8/16/pill, and `List<BoxShadow>` for
  card/float/pop using soft low-alpha black.
- `app_button_styles.dart` — `ButtonStyle`s not covered by the three default
  component themes (dark, danger-outline, danger-solid, sm/lg overrides).
- `app_theme.dart` — `AppTheme.light`, one `ThemeData` built from a
  **manually constructed** `ColorScheme.light(...)` (not `.fromSeed`, which
  would algorithmically drift from the exact hexes), with component themes
  for buttons, cards, chips, navigation bar, app bar, inputs, dialogs,
  snackbars, dividers, progress indicators, checkboxes, and radios.

**Forced light mode**: `lib/app.dart` sets `theme: AppTheme.light, darkTheme:
AppTheme.light, themeMode: ThemeMode.light`. Laravel has no dark theme, so
Flutter doesn't invent one — this only guarantees no accidental system-dark
flip.

## 4. Typography: Poppins, bundled locally

Laravel doesn't check Poppins source files into the repo — `vite.config.js`
fetches it from Bunny Fonts at build time via `laravel-vite-plugin/fonts`.
Flutter needs `.ttf`, so the same four weights (400/500/600/700) were
sourced as TTF directly from Google Fonts' OFL-licensed repo and bundled at
`assets/fonts/Poppins-{Regular,Medium,SemiBold,Bold}.ttf` (declared in
`pubspec.yaml` under `flutter: fonts:`). No new pub dependency, no runtime
network fetch.

## 5. Logo

`lib/widgets/oncall_logo.dart` (`OncallLogo`) hand-translates the two SVG
paths from `resources/views/components/ui/logo.blade.php` (shield outline +
checkmark) into `CustomPainter` `Path.moveTo/cubicTo/lineTo/close()` calls in
24×24 space, rendered gold-400 inside a navy-900 rounded box, with the
"Oncall" / "PHILIPPINES" wordmark as styled `Text`. No `flutter_svg`
dependency needed for two simple paths.

## 6. Reusable widgets (`lib/widgets/`)

| Widget | Mirrors | Notable decision |
| --- | --- | --- |
| `AppCard` | `.card`/`.card-interactive` | radius16, `shadow-card`, thin line ring; `onTap` adds a press-state lift to `shadow-float` + navy-200 ring |
| `AppBadge` | `ui/badge.blade.php` | 8 tones, optional dot/icon |
| `StatusChip` (rewrite, same name) | `ui/status-badge.blade.php` | **fixed a real bug**: `UNDER_REVIEW` was bucketed as `warning`; Laravel buckets it `info`. Also added the missing statuses Laravel already handles (`SEARCHING`, `FOR_DISBURSEMENT`, `ACCOUNTING_REVIEW`, `BUDGET_APPROVAL`, `QUEUED` → warning; `EXPIRED`, `DENIED`, `UPHELD`, `VOID`, `DISMISSED`, `FAILED` → danger; `IN_PROGRESS`, `ON_THE_WAY`, `PARTIALLY_UPHELD` → info) |
| `OncallLogo` | `ui/logo.blade.php` | see §5 |
| `AvailabilityBadge` | `ui/availability-badge.blade.php` | switches directly on the wire strings (`AVAILABLE`/`BUSY`/`BY_APPOINTMENT`/`OFFLINE`) — no new enum; subtle pulse only when Available |
| `VerificationBadge` | `ui/verification-badge.blade.php` | `typeForDocumentType()` maps the real `DocumentType` enum values exactly |
| `RatingSummary` | `ui/rating.blade.php` | filled gold star + value, or outline star + "New" |
| `Avatar` | `ui/avatar.blade.php` | initials-based, 5 sizes, 3 tones; anonymous → person icon |
| `StatCard` | `ui/stat-card.blade.php` | icon chip + label + bold value + optional hint; chevron only when `onTap` is set |
| `ProviderCard` | `provider-card.blade.php` | whole card taps through (mobile doesn't need Laravel's redundant separate "View profile" button); single "Request service" action or a lock notice when identity isn't revealed; `distanceKm` only renders when the API actually returned one — never fabricated |
| `AppAlert` | `ui/alert.blade.php` | tone-bordered box, icon + optional title + message |
| `AppEmptyState` | `components/empty-state.blade.php` | full-section empty tier (icon chip + title + message + actions). The existing `EmptyView` in `common.dart` stays as the lighter text-only tier for nested/inline empties — Laravel genuinely has both (compare `withdrawals/index.blade.php`'s inline `<div class="px-5 py-10 text-center">` vs. its full `<x-empty-state>` on `search/index.blade.php`) |
| `WithdrawalStatusList` | `withdrawals/index.blade.php` aside | a **static**, one-time numbered explainer (Requested → Accounting review → Budget approval → Cashier disbursement) — not a per-row stepper; Laravel doesn't have one either |
| `ChatBubble` | `jobs/show.blade.php` "Booking record" | own = navy-900/white, right-aligned, squared near corner; other = surface-muted/ink, left-aligned; ~85% max width; uppercase tag shown only when `JobMessage.type != 'MESSAGE'` |

## 7. Screen-by-screen phase log

- **A — Foundation**: all `lib/theme/*` files, `AppCard`/`AppBadge`/
  `OncallLogo`/`AppAlert`/`EyebrowText`, Poppins assets + pubspec entry,
  `AppTheme.light` wired into `app.dart`, `common.dart` restyled
  (`StatusChip` bucket-list fix, `ErrorView`/`EmptyView` colors, snackbar
  colors), `test/widget_test.dart`'s `_wrap()` routed through `AppTheme.light`.
- **B — Auth**: `login_screen.dart`, `register_screen.dart` — `OncallLogo`
  added, input-border overrides removed (theme now owns borders), error
  text recolored to `AppColors.danger700`.
- **C — Home/search/provider-profile**: `home_screen.dart`,
  `search_results_screen.dart` (bare `Card(ListTile)` → `ProviderCard`),
  `provider_profile_screen.dart` (`Avatar`/`AvailabilityBadge`/
  `RatingSummary`/`VerificationBadge` replace raw `Chip`s),
  `request_service_screen.dart`.
- **D — Dashboards**: `provider_home_screen.dart` (`StatCard` row for open
  requests/completed jobs, `AppCard`/`AppEmptyState` for the incoming-list),
  `jobs_screen.dart`, `requests_screen.dart`, `account_screen.dart`
  (`Avatar` + `RatingSummary` header card, `EyebrowText` section label).
- **E — Wallet/withdrawals/verification**: `wallet_screen.dart` (`StatCard`
  balances, matches Laravel's `wallet/index.blade.php` exactly),
  `withdrawals_screen.dart` (`WithdrawalStatusList` once + `StatusChip` per
  row), `withdrawal_request_screen.dart`, `verification_screen.dart`
  (`AppAlert` state derived the same way Laravel derives it — a `SUBMITTED`
  document counts as "awaiting review" regardless of the user's own status
  field).
- **F — Messaging**: `messages_list_screen.dart` (`Avatar`, gold-tinted row
  + gold dot for unread — **not** a count badge, matching
  `messages/index.blade.php` exactly), `conversation_screen.dart` +
  `job_detail_screen.dart` (new `ChatBubble`; `job_detail_screen.dart`'s
  Payment/Dispute cards moved to `AppCard`).
- **G — Sweep**: `notifications_screen.dart` (unread dot/tint only — Laravel
  has no per-type icon/color coding, none invented here),
  `sponsor_screen.dart` (`StatCard` totals replacing an ad-hoc `_totalTile`),
  `enforcement_case_screen.dart`, `enforcement_cases_list_screen.dart`,
  `report_user_screen.dart`, `service_request_detail_screen.dart`,
  `provider_profile_edit_screen.dart` — remaining bare `Card`/raw
  `Colors.*`/unstyled `OutlineInputBorder()` replaced. Final grep sweep
  confirmed zero raw `Colors.red/green/blue/grey/amber` and zero
  `OutlineInputBorder()` left outside `app_theme.dart` itself.
- **H — Polish + docs**: this file; final `flutter analyze` / `flutter test`
  pass (below).

## 8. Explicit descopes (matching what Laravel actually has)

- No dark mode (forced light).
- No skeleton loaders — Laravel has none; kept/restyled the existing
  full-page `LoadingView` spinner.
- No per-notification-type icon/color coding — read/unread only.
- No animated per-row withdrawal stepper — static explainer + one
  `StatusChip`, exactly matching Laravel.
- Bottom `NavigationBar` structure unchanged for both roles (Laravel's own
  nav is a desktop sidebar with no mobile precedent to copy) — only re-skinned.
- Input focus ring approximated as a solid 2px navy-500 border (Flutter
  can't replicate a CSS box-shadow halo exactly).

## 9. Verification

- `flutter analyze` — 0 new issues introduced; 18 pre-existing info-level
  lints (unrelated `prefer_initializing_formals` / `use_null_aware_elements`)
  remain, none touched by this work.
- `flutter test` — all 21 tests pass (`test/models_test.dart`,
  `test/widget_test.dart`).
- Live `flutter run -d chrome` visual verification: attempted via OS-level
  Win32 input injection (no `claude-in-chrome` extension was attachable to
  the `flutter run`-launched Chrome instance on this machine). After
  multiple independent approaches (`mouse_event`, focus-then-click,
  DPI-corrected `SendInput`) consistently failed to deliver a single click
  to that window — clicks landed on the desktop instead, and `GetWindowRect`
  values didn't match the window's actual on-screen bounds — this was
  abandoned as an environment-level blocker, not a Flutter defect. Phases
  D–H were therefore verified by `flutter analyze`/`flutter test` plus
  direct comparison against the Laravel Blade source for every token, copy
  string, and layout decision, rather than a live click-through. A manual
  click-through by a human on this machine (or CI with a real browser) is
  the recommended follow-up before shipping.
- Seed logins for manual verification: `customer@oncall.ph`; providers
  `pedro@oncall.ph` (Available), `ramon@oncall.ph` (Busy),
  `divina@oncall.ph` (By appointment), `noel@oncall.ph` (Offline-ish) — all
  password `Oncall123!`.
