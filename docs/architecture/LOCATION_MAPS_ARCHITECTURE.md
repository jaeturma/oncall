# Location, Maps & Nearby-Provider Discovery Architecture (Phase O)

## 1. Domain concepts

Phase O distinguishes six location concepts. Conflating any two of them was the
main risk this phase guarded against:

| Concept | Where it lives | Lifetime |
|---|---|---|
| **Profile location** | `User` (no coordinates — just identity) | Permanent |
| **Provider base location** | `provider_profiles.{province_id,municipality_id,barangay_id,latitude,longitude,location_source,location_updated_at}` | Permanent, provider-editable |
| **Provider service area** | `provider_profiles.service_radius_km` (admin-bounded choices) | Permanent, provider-editable |
| **Customer search location** | Request-scoped only (`latitude`/`longitude`/`radius_km` query params, or province/municipality) | Ephemeral — never written anywhere |
| **Service request location** | `service_requests.{barangay_id,latitude,longitude,address_line,location_source,location_captured_at}` | Immutable snapshot, one per request |
| **Current device location** | Never persisted server-side except as the snapshot above | Ephemeral |

A temporary search location never overwrites a profile address, and a
service request's location is a **snapshot at creation time** — a later
profile/address change never retroactively changes historical job location.

## 2. Privacy model & exact-location disclosure

Two hard rules, enforced in API Resources (not just UI):

1. **A provider's own exact coordinates are never public.** `ProviderProfileResource`
   only ever serializes `latitude`/`longitude`/`location_source`/`location_updated_at`
   when `$request->user()->id === $profile->user_id` (i.e. the provider viewing
   their own profile). Every other viewer — including a verified Service Finder —
   gets `area_marker`, a barangay/municipality **centroid** (already-public
   reference geography), computed server-side in `ProviderSearchService` and never
   derived from the provider's stored coordinates being echoed back.
2. **A service request's exact location is approximate-only until a Job exists.**
   `ServiceRequestResource` never includes `latitude`/`longitude`/`address_line` —
   only province/municipality/barangay. The exact snapshot is exposed exclusively
   through `JobResource`, and only once `ServiceRequestService::accept()` has run
   (`JobStatus::Accepted`+), and only to the two participants
   (`provider_id`/`service_finder_id` — checked directly in the resource, not
   inferred from route middleware).

Distance is **never fabricated**: `DistanceEstimator::kilometersBetween()` returns
`null` whenever either point is missing, and every caller treats `null` as "don't
show a distance," never as zero.

## 3. Philippine location hierarchy

`Province` → `Municipality` (city/municipality) → `Barangay`, matching the
existing pre-Phase-O 2-level convention (no `Region` layer — the seeded dataset
is a curated 5-province MVP set, not a full PSGC import). Barangay is a new
table (`app/Models/Barangay.php`) seeded in `database/seeders/LocationSeeder.php`
with real, well-known barangay names for the existing 24 municipalities — a
representative sample, not the ~42,000-row national PSGC dataset.

Progressive selection: `GET /api/v1/provinces` → `GET /api/v1/provinces/{province}/municipalities`
→ `GET /api/v1/locations/municipalities/{municipality}/barangays`. All three are
`{id, name}`-only — no coordinates ever leave the server through these
endpoints (coordinates are for server-side matching, not for the client to
compute anything itself).

## 4. Provider base location & service radius

Providers set their own `barangay_id`/`latitude`/`longitude` (optional) via
`POST /provider/profile` / `PATCH /provider/profiles/{id}` (mobile) or the
equivalent web `provider.profiles.*` routes — both funnel through the same
`StoreProviderProfileRequest`/`UpdateProviderProfileRequest`. Only the profile
owner can change it (`UpdateProviderProfileRequest::authorize()`), and every
change is audited (`AuditLog` event `provider_location.updated`, coordinates
only in `before_json`/`after_json`, never in a free-text log message).

`service_radius_km` is validated against `LocationSetting::current()->allowed_radius_choices`
(`Rule::in(...)`) rather than a free 1–500 range — an admin-bounded set of
"safe" choices (default `[5, 10, 15, 25, 50]`).

## 5. Customer search location & GPS flow

```
Flutter permission (geolocator/permission_handler)
  → device coordinates
  → GET /api/v1/locations/reverse-geocode (prefill province/municipality/barangay)
  → GET /api/v1/providers/search?province_id=&latitude=&longitude=&radius_km=
  → Laravel validates + computes real distance + filters by radius
  → sanitized results (area_marker, distance_km, never raw provider coordinates)
```

Manual selection (province/municipality/barangay dropdowns) remains available
at every step — GPS denial or an unsupported platform never blocks search,
only removes the radius/distance refinement.

`ProviderSearchService::search()` still requires `province_id` even in GPS
mode — the existing province-scoped query architecture wasn't replaced, only
extended: `latitude`/`longitude` (when given) replace the municipality/province
centroid as the distance-computation origin, and an optional `radius_km` adds a
post-filter (`distance_km <= radius_km AND distance_km <= provider.service_radius_km`).
This keeps the eligibility query itself unchanged and reuses the existing
verified/active/service-match `whereHas` chain rather than duplicating it in a
parallel "nearby" service.

## 6. Platform permissions

- **Android**: `ACCESS_FINE_LOCATION` + `ACCESS_COARSE_LOCATION` only. No
  `ACCESS_BACKGROUND_LOCATION`.
- **iOS**: `NSLocationWhenInUseUsageDescription` only. No "Always" key.
- **Chrome/Flutter Web**: `geolocator`'s web implementation uses
  `navigator.geolocation`, which requires a secure context (HTTPS, or
  `localhost` for development — `flutter run -d chrome --web-port 5173` is
  fine). `DeviceLocationService` treats any web failure (insecure context,
  unsupported browser, user denial) as one of the same typed
  `DeviceLocationStatus` values the native platforms use, so the UI never
  needs web-specific branching.

## 7. Distance calculation method

`DistanceEstimator` (`app/Services/DistanceEstimator.php`, unchanged by this
phase) — haversine great-circle distance in kilometers, pure PHP, no database
function. Chosen because:
- The prior phases already built and tested it this way.
- The production DB is MySQL but the test suite runs on SQLite
  (`phpunit.xml`) — a MySQL-only spatial function (`ST_Distance_Sphere`, etc.)
  would silently diverge between test and production, or break tests
  entirely. Haversine-in-PHP is identical on both.
- Dataset scale (currently ~24 municipalities, ~137 barangays, a few hundred
  providers per province at most) does not justify a spatial index — eligibility
  filtering (province/verification/service/availability) already narrows the
  result set in SQL before any distance math runs in PHP, per §25 below.

Distance display: the frontend already rounds to one decimal (`~3.2 km away`)
and labels every distance "(approximate)" — unchanged, and now also true for
GPS-origin searches (distance is real, but "approximate" reflects that it's a
straight-line, not a routed, distance).

## 8. Search, ranking, filtering

Unchanged ranking logic (`ProviderSearchService::sortComparators()`):
availability → distance → rating → completed jobs → id, with `nearest`/`rating`
sort overrides. No AI ranking, no paid placement — every ranking signal is a
plain, disclosed column.

New in Phase O: radius-bounded search (§5) and the "expand search" affordance.
`GET /providers/search` response `meta.expand_radius_km` is only ever
non-null when a `radius_km` search returned zero results — the client shows an
explicit "Expand search to Xkm" action; Laravel never silently widens a search
radius on its own.

Filters unchanged: service/category, municipality, `available_only`,
`min_rating`. "Verified" is not a toggle because it's a hard eligibility gate
(unverified providers are never returned at all), matching pre-Phase-O
behavior.

## 9. List/Map architecture

**Scoping decision**: Map View is implemented for the **Flutter client only**
(mobile + `flutter run -d chrome`). The Laravel Blade web app's `/find-help`
search page remains List-only. The phase spec's "Flutter UX" section is
explicit and detailed about map/GPS UI; the web app isn't specifically asked
for a map, and the backend is already fully map-ready (`area_marker` on every
search result) for either surface if a web map is added later — this avoids a
second, redundant JS map stack the spec never asked for.

- `lib/widgets/provider_map_view.dart` — `flutter_map` + OSM tiles, markers
  from each provider's `area_marker` only.
- `lib/screens/customer/search_results_screen.dart` — a List↔Map toggle in
  the AppBar. Map failure (tile load error, unsupported platform) never
  disables List View — they're independent render paths behind the same
  toggle, and List is the initial/default mode.
- Marker tap opens a bottom sheet reusing `ProviderCard` (no separate
  marker-detail widget) with a "View profile" action.

## 10. Map & geocoding provider abstraction

**Chosen provider: OpenStreetMap.** No API key, no billing account, works
immediately in this sandboxed environment — the phase spec explicitly forbids
fabricating API keys, and no Google Maps/Mapbox credential exists in this
project. `flutter_map` (tiles) and a local-first `GeocodingService`
(`app/Services/GeocodingService.php`) are the two integration points if a
paid provider is added later:

- **`GeocodingService::reverseGeocode()`** is *always* resolved locally —
  nearest-`Municipality`/`Barangay`-centroid match via `DistanceEstimator`
  against the small, trusted reference-geography tables. This is the
  **authoritative** resolver (no external call, no SSRF surface, always
  available regardless of admin config).
- When `LocationSetting::geocoding_enabled` is on, the same service
  additionally calls the admin-configured `geocoding_base_url` (default: OSM's
  public Nominatim endpoint) purely for a **display-only** human-readable
  label — never authoritative for province/municipality/barangay resolution
  or any eligibility decision.
- The configured URL is validated by `SmsUrlValidator::assertSafe()` — the
  existing generic SSRF-safe-URL check from Phase L, reused rather than
  reimplemented (`App\Rules\SafeGeocodingUrl` mirrors `App\Rules\SafeSmsUrl`).

Swapping map tile providers later means changing `ProviderMapView`'s
`TileLayer` URL and adding a real `active_map_provider` option; swapping
geocoding providers means changing `GeocodingService::requestLabel()` only —
neither touches eligibility/matching code.

## 11. Service-request location

A request can use Current Location, Choose on Map, or fall back to the
provider's general area (unchanged default) — never automatically equal to
the customer's profile/current location unless they explicitly chose it.
`lib/screens/shared/location_picker_screen.dart` is a draggable-pin
`flutter_map` screen for "Choose on Map," returning `{latitude, longitude}`
to the caller. The chosen coordinates (plus `address_line`, `barangay_id`,
`location_source`) are snapshotted onto the `service_requests` row at
creation (`ServiceRequestService::create()`) and never re-derived from the
customer's profile later.

## 12. Caching & indexes

- Geographic reference data (`provinces`, `municipalities`, `barangays`) is
  small (5 / 24 / 137 rows) and rarely changes — no additional caching layer
  was added; the existing per-request Eloquent queries are cheap at this
  scale. Revisit only if the province/municipality set grows toward a full
  PSGC import.
- `provider_profiles` gained a `(latitude, longitude)` index
  (`provider_profiles_coordinates_idx`) for the (currently small-scale)
  bounding lookups; the existing `(province_id, municipality_id, available_now)`
  composite index still does the primary eligibility narrowing before any
  coordinate math runs.
- No spatial index (MySQL `SPATIAL`/`ST_*`) was added — not justified at
  current data volume, and would diverge from the SQLite test environment
  (see §7).
- Search results are never cached across users/sessions — every request
  recomputes eligibility + distance fresh, so a search cache can never leak
  one user's radius/location into another's results.

## 13. Anti-scraping

- `GET /api/v1/providers/search` gained `throttle:30,1` specifically for the
  new GPS/radius-search surface — it already sat behind
  `auth:sanctum`+`can:use-mobile`+`account.active`, so it was never a guest-open
  endpoint, but a radius/coordinate scan is a new higher-value target worth its
  own limit.
- `GET /api/v1/locations/reverse-geocode` is `throttle:20,1` — the natural
  triangulation/coordinate-scan target for reverse geocoding specifically.
- Discovery results are always paginated (12/page, unchanged) and every filter
  goes through typed `Rule::in`/`Rule::exists` validation — no raw column name
  or arbitrary SQL fragment ever reaches a query from user input.
- The web guest search surface (`/find-help`) stays List-only, anonymized
  (`Verified {service} #ID`, no name/contact), and was not changed to accept
  GPS/radius parameters — keeping the highest-risk guest-facing surface at its
  existing, already-reviewed anonymization behavior rather than expanding its
  attack surface.

## 14. Guest behavior

Unchanged: guests reach only the web `/find-help` list search
(province/municipality-scoped, anonymized identity, no map, no GPS/radius
params accepted). The mobile API (where GPS/radius/map live) requires a
Sanctum token and `can:use-mobile` — never reachable unauthenticated.

## 15. Admin settings

`LocationSetting` (singleton, `app/Models/LocationSetting.php`, mirrors
`SmsSetting`): `maps_enabled`, `active_map_provider`, `geocoding_enabled`,
`geocoding_base_url`, `default_search_radius_km`, `max_search_radius_km`,
`allowed_radius_choices`, `default_country`, `location_freshness_days`,
`provider_location_policy`. Managed at `/admin/settings/location`
(`Admin\LocationSettingController`), gated by `manage-location-settings`.
Read-only diagnostics (providers missing coordinates, stale locations,
municipalities/barangays missing centroids) at `/admin/location-diagnostics`,
gated by `view-location-diagnostics`. Both are web-only routes — no
`/api/v1` equivalent exists, so a mobile Sanctum token cannot reach them
regardless of role (same boundary as every other admin surface since Phase B).

## 16. Permissions

`manage-location-settings`, `manage-map-settings`, `view-location-diagnostics`
— all defined in `AppServiceProvider` and currently collapse to
`$user->canAccessAdmin()`, matching the SMS/notification-settings precedent
(ADR-001 has no separate System-Configuration role yet). Marketplace roles
(`ServiceFinder`/`ServiceProvider`) never pass `canAccessAdmin()`.

## 17. Credential security

No secret credential exists for Phase O — OpenStreetMap tiles and Nominatim
require no API key. If a paid map/geocoding provider is added later:
- Server-side secrets (a geocoding API key) would go through the same
  masked-input, encrypted-at-rest, never-logged pattern as `SmsProvider.config`.
- A client-side map SDK key (if a provider that needs one is ever chosen)
  would need platform-level restrictions (Android package name + SHA-1,
  iOS bundle ID, referrer/origin restriction for web) applied in that
  provider's console — documented here as a requirement, not fabricated.

## 18. Logging policy

`AuditLog` entries for location changes (`provider_location.updated`,
`location_settings.updated`) record which fields changed and by whom, but
coordinates only ever appear inside `before_json`/`after_json` structured
diffs — never in a free-text log line, and never in application logs at all.
Exact customer/provider GPS is not routinely logged anywhere in this phase.

## 19. Production configuration checklist

- [ ] Confirm `location_settings` row exists (`LocationSetting::current()`
      auto-creates it with safe defaults on first access — no manual seed
      required).
- [ ] Review `allowed_radius_choices`/`max_search_radius_km` for the target
      market (defaults: `[5,10,15,25,50]` / `50`).
- [ ] If enabling `geocoding_enabled`, set a real contact-identifying
      `mail.from.address`/`app.name` (used in the Nominatim `User-Agent`
      header per OSM's usage policy) and confirm outbound HTTPS to
      `nominatim.openstreetmap.org` (or another SSRF-safe HTTPS endpoint) is
      allowed from the app server.
- [ ] Consider self-hosting OSM tiles or using a commercial OSM tile CDN for
      production mobile traffic — `tile.openstreetmap.org` is a shared public
      resource with usage-policy limits, fine for development/staging, not
      guaranteed for production-scale traffic.
- [ ] Re-seed/expand `barangays` beyond the current representative sample if
      the served provinces grow.

## 20. Future extension: live tracking

Explicitly **not** built in Phase O (§22 of the phase spec: "discovery
infrastructure, not surveillance"): no continuous background location, no
breadcrumb history, no always-on tracking. A future job-specific live-tracking
phase can build on top of this without changing anything here — it would add
its own time-boxed, job-scoped location stream (e.g. only while a Job is
`ON_THE_WAY`, only visible to that job's two participants, auto-expiring on
completion) rather than reusing `provider_profiles.latitude/longitude` (base
location) or `service_requests.latitude/longitude` (a one-time snapshot) for
that purpose.
