# THEME-AUDIT.md — Oncall Philippines

Audit of the supplied HTML theme in `oncall-philippines-mwe/theme-source/`.
Status: **learning phase only — no theme code has been converted.** Original theme files are untouched.

---

## 1. Theme technology

| Aspect | Finding |
| --- | --- |
| Theme name | **"Viavi Directory Listing HTML"** by Viaviwebtech (ThemeForest / Envato). Footer copyright "© 2017". |
| Purpose | Generic **business directory / classified listings** template (real estate, hotels, jobs, spas…). Not a service-matching or booking product. |
| CSS framework | **Bootstrap 3.3.4** (`css/bootstrap.min.css`, includes normalize.css 3.0.2) + **~6,250 lines of hand-written CSS** (`stylesheet.css` 5,437 lines, `responsive_style.css` 821 lines). |
| CSS methodology | None. Flat file, ID-heavy selectors (`#nav_menu_list`, `#dashboard_listing_blcok`), vendor-prefixed transitions, `!important`, a global `* { margin:0; padding:0 }` reset. No SCSS, no PostCSS, no build step. |
| Is it Tailwind? | **No.** No Tailwind, no `tailwind.config.*`, no utility classes, no `@apply`. |
| JS stack | **jQuery 2.2.4** + Bootstrap 3 JS (modal/collapse/dropdown/affix) + plugins (see §4). ~1,065 lines of plugin/custom JS. |
| Build tooling | **None.** Static `.html` files with relative `css/`, `js/`, `images/` links opened directly in a browser. |
| Dark mode | **None.** Single light theme. `<html lang="zxx">` (placeholder locale) on every page. |
| Responsiveness | Bootstrap 3 grid + ~20 additional, overlapping, partly typo'd `@media` blocks. |

---

## 2. File structure (`theme-source/`)

```
theme-source/
├── *.html                      13 page templates + index_preview.html (demo picker)
│   ├── index.html              Landing / home
│   ├── about.html              About + features + pricing
│   ├── categories.html         Category directory + search bar
│   ├── contact.html            Contact form + captcha + info blocks
│   ├── dashboard.html          User dashboard (4 stat boxes)
│   ├── edit_profile.html       Profile form (sectioned cards)
│   ├── change_password.html    Password form
│   ├── my_listing.html         User's listings (custom responsive "table")
│   ├── listing_grid.html       Search results, grid view + left sidebar
│   ├── listing_list.html       Search results, list view
│   ├── listing_left_sidebar.html / listing_right_sidebar.html / listing_fullwidth.html
│   ├── listing_detail.html     Single listing (gallery, reviews, hours, map)
│   ├── listing_submit.html     Multi-section "create listing" form
│   ├── page_error_404.html     404
│   └── index_preview.html      Envato demo selector — DISCARD
├── css/
│   ├── bootstrap.min.css       Bootstrap 3.3.4
│   ├── stylesheet.css          Main theme CSS (5,437 lines) — @imports Google Fonts
│   ├── responsive_style.css    Media queries (821 lines)
│   ├── animate.css             animate.css (2,688 lines) — used only via .flipInX etc.
│   ├── font-awesome.min.css    Font Awesome 4
│   ├── superlist.css           Gallery plugin styles (detail page only)
│   └── style_preview.css       Styles for index_preview.html only — DISCARD
├── js/
│   ├── jquery-2.2.4.min.js
│   ├── bootstrap.min.js
│   ├── jquery_custom.js        Loader fade, scroll-up, counterUp init (34 lines)
│   ├── jquery_counterup.js     + waypoints.js — animated stat counters
│   ├── homemap_custom.js       Google Maps markers for home hero (164 lines)
│   ├── superlist.js            Product image gallery (detail page)
│   ├── bootstrap-select/       Styled <select> (detail page)
│   ├── owl.carousel/           Carousel (loaded on detail page, not clearly used)
│   └── colorbox/               jQuery lightbox (detail gallery)
├── fonts/
│   ├── fontawesome-webfont5b62.{eot,svg,ttf,woff,woff2}   Font Awesome 4 glyphs
│   └── glyphicons-halflings-regular.{eot,svg,ttf,woff,woff2}   Bootstrap 3 Glyphicons
└── images/                     ~90 files: brand, UI icons, demo photos, demo screenshots
```

---

## 3. Overall visual design

### 3.1 Colour palette (extracted by frequency from `stylesheet.css`)

| Role | Hex | Notes |
| --- | --- | --- |
| **Primary accent (gold/yellow)** | `#ffce10` | 144 uses — nav active border, links-on-hover, buttons, badges, headings highlight |
| Accent variants | `#ffbe00`, `#ebc131`, `#f9ca40`, `#ffcc58`, `#f5c026` | button gradient "wipe" partners, hover states |
| **Primary dark (navy)** | `#01273a` | 54 uses — footer, sticky header text, count badges, button text |
| Dark variants | `#072d40`, `#00283b`, `#072d40`, `#262626` | headings, near-black body text |
| Surface / white | `#ffffff` / `#fff` | page + card background |
| Light surfaces | `#f9f9f9`, `#f1f1f1`, `#f7f7f7`, `#ededed`, `#e8e8e8` | section backgrounds, dividers |
| Body text greys | `#4a4a4a`, `#636363`, `#696969`, `#7d7d7d` | paragraph copy |
| Muted / placeholder | `#999999`, `#969595`, `#9a9a9a`, `#c2c2c2` | captions, input placeholders (low contrast) |
| Borders | `#e9e6e0`, `#ebebeb`, `#e8e8e8`, `#dadada` | card + input borders |
| Shadows | `rgba(0,0,0,0.1)` → `rgba(0,0,0,0.3)` | soft elevation |
| Bootstrap focus ring (inherited) | `rgba(102,175,233,.6)` | default BS3 input focus — off-brand blue |

**Brand direction:** gold `#ffce10` on deep navy `#01273a`, white surfaces, warm grey neutrals. High-energy "marketplace" look.

> **Decision (resolved):** the app is re-skinned to the theme's **gold/navy/yellow**. `resources/css/app.css` now defines `--color-navy-*` and `--color-gold-*` scales (anchors `#01273a` / `#ffce10`) and self-hosts **Poppins** via the Vite Bunny-fonts plugin; `resources/views/` was migrated off `teal-*` (navy for primary buttons/headings/dark surfaces, gold for funnel CTAs and accents). `slate-*` kept as the neutral, `amber-*` as warnings, `red-*` as danger.

### 3.2 Typography

| Token | Value |
| --- | --- |
| Primary font | **Poppins** (Google Fonts, weights 300/400/500/600/700) — 69 declarations |
| Secondary font | **Open Sans** (weights 300–800) — 8 declarations, mostly inputs/selects |
| Declared but ~unused | **Montserrat** (400/700) — linked in `<head>`, almost no CSS references |
| Icon font | **FontAwesome** (`font-family:"FontAwesome"`) |
| Base body | `15px` / Poppins / `color` inherits to dark grey |
| Type scale (observed) | `10, 11, 12, 13, 14 (dominant), 15, 16, 17, 18, 20, 22, 24, 26, 28, 30, 32, 40` px |
| Common weights | 400, **500**, **600**, 700 |
| Casing | `text-transform: uppercase` on buttons and section headings; `letter-spacing: 0.3px` on nav links, labels, list items |
| Section heading pattern | `.bt_heading_3`: centered `<h1>` with a coloured `<span>` word, flanked by two 60px hairlines and a tiny 7px dot/`fa-stop` icon cluster |

Font loading issues: `@import`'d **twice** (in `stylesheet.css` and each page `<head>`); one Open Sans link uses `http://` (mixed-content on HTTPS).

### 3.3 Spacing system

- No token scale. Ad-hoc `px` values.
- Section vertical rhythm: `padding: 50px–80px` top/bottom on major blocks.
- Card spacing: `margin-bottom: 30px` is the recurring unit; inner padding `20px`/`30px`.
- Grid gutter: Bootstrap 3 default `15px` (many `.nopadding` overrides).

### 3.4 Borders, radius, shadows

| Element | Radius | Shadow |
| --- | --- | --- |
| Buttons / pills / count badges | `20px` / `30px` / `50px` (fully rounded) | `0 5px 8px rgba(0,0,0,.25)`, `0 3px 1px rgba(0,0,0,.25)` |
| Cards (`.statusbox`, listing boxes) | `4px`–`8px` | `0 1px 10px rgba(0,0,0,.1)`, `0 2px 15px rgba(0,0,0,.1)` |
| Avatars / icon circles | `50%` | occasional glow `0 3px 8px rgba(255,255,255,.3)` |
| Inputs | `4px` | inset `3px 4px 8px rgba(0,0,0,.14)` on some search fields |
| Modal top corners | `50px 50px 0 0` | — |

### 3.5 Buttons (⚠ no unified system — at least 6 variants)

| Class | Where | Style |
| --- | --- | --- |
| `.from-list-lt .btn` | dashboard/profile submit | gold→`#ffbe00` gradient wipe, pill, uppercase, 46px, heavy shadow |
| `.comments-wrapper .comment-respond .btn` | review form | same family, 44px |
| `.btn-quote` | footer mini-form | white→gold gradient wipe, navy text |
| `.purchase-btn` | pricing cards | gold pill, appears on card hover |
| `.listing-form-field input.submit` | login/register modals | full-width solid |
| `.sidebar-listing-search-btn` | listing sidebar search | solid |
| `#nav_menu_list button.btn_login/.btn_register` | header | `#262626`→`#ffce10` split-gradient pill, 34px |

Signature interaction: **50/50 linear-gradient background at `207% 100%` size, shifting `background-position` on hover** ("colour wipe"). Worth preserving as one `x-ui.button` component.

### 3.6 Forms

- Base: Bootstrap 3 `.form-control` + custom `.form-alt` wrapper (sectioned `.submit_listing_box` cards with an `<h3>` header).
- Modal fields: `.listing-form-field` with a leading `<i class="fa">` icon inside the field.
- Contact page: `.form-float` with `.from-input-ic` icon spans + **image captcha** (`captcha.png` + refresh link) — static/demo.
- Checkboxes: `.checkbox.checkbox-success` + `.styled` (custom-styled, needs the BS checkbox plugin markup).
- Styled selects: `bootstrap-select` on the detail page only; elsewhere native `.form-control` selects with a PNG dropdown arrow (`slt_btn_cat.png`).
- File upload: `.fileupload_block` with `add_image.png` placeholder.
- **Dead code:** jQuery-UI slider markup (`#slider-range-min .ui-slider…`) for a price range on the detail sidebar, but **no jQuery UI is loaded** — renders as an inert bar.
- No inline validation states, no error message styling, no `aria-describedby`.

### 3.7 Responsive breakpoints

Bootstrap 3 tiers (`768 / 992 / 1200`) **plus** these custom queries:

```
max-width: 767 | 979 | 1024 | 639 | 479
min 768–991 | 992–1199 | 768–1024 | 980+ | 1025–1199
min 200–480 | 481–767 | 200–329 | 330–438 | 439–480 | 481–767 | 768–979 | 979–1199
```

Overlapping ranges, `979` vs `980` off-by-one seams, phone-specific micro-ranges. **Do not port; rebuild with Tailwind's `sm/md/lg/xl` only.**

### 3.8 Light/dark

No dark mode anywhere. Fixed light palette. A full-screen white preloader (`#vfx_loader_block` + `loading.gif`) covers the page until `window.load`.

---

## 4. Asset inventory

### 4.1 CSS

| File | Keep? | Reason |
| --- | --- | --- |
| `bootstrap.min.css` (3.3.4) | ❌ | Replaced by Tailwind 4. Grid + `.container` collide (see §8). |
| `stylesheet.css` | ❌ (mine for tokens) | Extract palette/radius/shadow/type into `@theme`; do not import. |
| `responsive_style.css` | ❌ | Rebuild with Tailwind breakpoints. |
| `animate.css` | ❌ | Only decorative heading flips use it. Drop or replace with 2–3 Tailwind keyframes. |
| `font-awesome.min.css` | ❌ | Replace icon font with Blade Icons / Heroicons / inline SVG. |
| `superlist.css` | ❌ | Detail-gallery plugin; not needed for MVP. |
| `style_preview.css` | ❌ | For the Envato demo page only. |

### 4.2 JavaScript

| File | Keep? | Replacement |
| --- | --- | --- |
| `jquery-2.2.4.min.js` | ❌ | None. Not in Laravel 13 starter; AGENTS.md discourages framework additions. |
| `bootstrap.min.js` | ❌ | Alpine.js (already common in Laravel) for modal/dropdown/collapse/sticky. |
| `jquery_custom.js` | ❌ | Trivial: preloader fade + scroll-to-top → ~10 lines of vanilla/Alpine. |
| `jquery_counterup.js` + `waypoints.js` | ❌ | Stat counters animate **fake numbers** — drop; render real values server-side. |
| `homemap_custom.js` + Google Maps API | ❌ | AGENTS.md: **no maps**. Google Maps JS now needs an API key; `sensor=false` is deprecated. |
| `superlist.js`, `colorbox`, `owl.carousel`, `bootstrap-select` | ❌ | Not required for MVP flows. Revisit per real feature need. |

### 4.3 Fonts

| Asset | Keep? |
| --- | --- |
| Poppins (Google Fonts) | ✅ **Keep as the brand font.** Self-host or load via privacy-friendly CDN; set in Tailwind `@theme { --font-sans }`. |
| Open Sans | ⚠ Optional secondary; can collapse to Poppins-only for MVP. |
| Montserrat | ❌ Unused. |
| `fonts/fontawesome-webfont*` | ❌ Icon font — replace. |
| `fonts/glyphicons-halflings*` | ❌ Bootstrap 3 artifact. |

### 4.4 Icons

- Font Awesome 4 (`<i class="fa fa-*">`) used heavily (phone, map-marker, star, search, user, lock, social, chevrons).
- Glyphicons present via Bootstrap but not directly referenced in markup.
- Custom PNG icons: `ic-call.png`, `ic-loc.png`, `ic-time.png`, `ic_1..3.png`, `form-icon-2.png`, `slt_btn_cat.png` (select arrow), `top-move.png`.
- **Recommendation:** adopt an SVG icon set (Heroicons via `blade-ui-kit/blade-heroicons`, or inline SVG partials). Map the ~15 glyphs actually used.

### 4.5 Images

| Group | Files | Disposition |
| --- | --- | --- |
| Brand | `logo.png`, `logo-preview.png`, `favicon.png` | Placeholder — replace with real Oncall brand assets. |
| Backgrounds | `banner.jpg`, `intro-bg.jpg`, `bg-map.png`, `category_bg.png`, `detail-view-bg.jpg`, `error-page-bg.jpg`, `vfx_counter_bg.png` | Keep only ones reused; treat as decorative. `banner.jpg` + `user-profile.png` already copied to `public/theme/images/`. |
| UI | `loading.gif`, `add_image.png`, `slt_btn_cat.png`, `top-move.png` | Mostly obsolete once JS/CSS rebuilt. |
| **Demo photos** | `product/img1-8.png`, `product_item/gallery-1-8.jpg`, `new-thum-1.png`, `img-01..04.jpg`, `company-logo.jpg`, `about-user.png`, `come-user-img.png`, `comment-thumb-1/2.jpg`, `captcha.png` | ❌ **Do not ship.** Fake content. |
| **Demo screenshots** | `other/1-17.jpg` | ❌ For `index_preview.html` only. |
| Prefooter | `prefooter-img1-3.png` | ❌ Not referenced in shipped pages. |

### 4.6 Third-party dependencies summary

Bootstrap 3.3.4 · jQuery 2.2.4 · Font Awesome 4 · animate.css · Owl Carousel · jQuery Colorbox · bootstrap-select · Waypoints + CounterUp · Google Maps JS API · Google Fonts (Poppins/Open Sans/Montserrat).

**None should be installed as project dependencies.** AGENTS.md: "Prefer framework-native features before adding packages… Do not introduce … maps … unless explicitly approved."

---

## 5. Tailwind / CSS architecture decision

- The theme is **Bootstrap 3 + bespoke CSS**, fundamentally incompatible in approach with the project's committed **Tailwind CSS 4** (`@tailwindcss/vite`, CSS-first config in `resources/css/app.css`).
- Tailwind 4 has **no `tailwind.config.js`** — design tokens live in `@theme { --color-*, --font-*, --radius-* }` inside `app.css`. There is nothing to "migrate"; tokens must be **transcribed by hand** from §3.
- **Do not import any theme CSS globally.** `stylesheet.css` uses unscoped element selectors (`body`, `a`, `*`, `option`) and `!important`; importing it would restyle every already-built Blade view.
- Correct path: **treat the theme as a visual reference (a style guide), rebuild in Tailwind utilities + a small set of Blade components.** Screenshot/keep the HTML open while building; copy nothing wholesale.

---

## 6. Responsive behaviour observations

| Viewport | Theme behaviour | Note for Oncall (mobile-first for Service Finders) |
| --- | --- | --- |
| Desktop ≥1200 | Fixed `.container` (1170px), multi-column grids (4-up listing cards, 3-up pricing, sidebar + content). | Fine as a max-width; use `max-w-7xl mx-auto`. |
| Tablet 768–1199 | Cards drop to 2-up; sidebar stacks above content on some pages, stays beside on others (inconsistent). | Define one predictable rule: sidebar collapses **below** content < `lg`. |
| Mobile ≤767 | Bootstrap `navbar-collapse` hamburger (`#thrift-1`); nav becomes vertical; login/register buttons stack full-width; search fields stack; counter blocks go full-width. | Rebuild the collapsible nav with Alpine `x-data`/`x-show`. Ensure tap targets ≥44px. |
| Small phones ≤480 | Extra micro-breakpoints shrink headings/padding. | Tailwind base + `sm:` is enough; skip the micro-tuning. |

Concerns for a phone-heavy audience:
- Sticky header (`data-spy="affix"`) has no mobile-height reduction → eats vertical space.
- Preloader overlay delays first paint on slow mobile networks — **do not reproduce**.
- Icon-only action links (`fa-heart`, `fa-share`, pencil/trash in `my_listing`) are tiny and unlabeled.
- Low-contrast grey text (`#999`, `#c2c2c2`) fails WCAG AA on white.
- Custom "table" in `my_listing.html` (`.tg-listing` with `data-title`/`data-viewed` labels) is the one genuinely mobile-friendly pattern — a stacking card table. Worth reimplementing as `x-data-table`.

---

## 7. Existing demo functionality vs. real functionality

**Purely visual/demo (must NOT carry into production):**

| Demo artefact | Location |
| --- | --- |
| Listing titles "Hello Directory Listing", "Local Business Directory" | index, grid, list, detail |
| Fake phone `+91 087 654 3210`, `+001 245 0154`, "Your City Here" | cards, detail hero, contact |
| Fake emails `directorylisting@gmail.com`, `info@directorylisting.com` | detail hero, contact, footer |
| Exposed provider address `124/47 22nd Avenue, New York City` | listing_detail hero |
| Animated stat counters `496 / 245 / 96 / 274` (Listings/Users/Categories/Types) | index counter block |
| Dashboard stat boxes `$16,00 / $19,00 / $22,00 / $26,00` (Balance/Progress/Payments/Avg Salary) | dashboard |
| Pricing tiers Basic/Premium/Plus `$24 / $49 / $99 per month` | index, about |
| Prefilled profile values "New York", "Everton Eve", zip `121211` | edit_profile |
| `my_listing` rows "joe's coffee", "Payment Mode: Paypal", viewed/favorites counts | my_listing |
| Amenities checklist (WiFi, Parking, Vine, Terrace, Bar…) | listing_detail, listing_submit |
| YouTube embed, image galleries, "Bookmark" / "Give Heart" buttons | listing_detail |
| Image captcha + refresh | contact |
| Google Maps embeds & hero map with markers | index, listing_submit, listing_detail |
| Social links (Facebook/Twitter/G+/Pinterest/YouTube), "Follow Us" | footer everywhere |
| "Popular Tags" (Amazing, Envato, Themes, SEO…), "Archives" (January 2016…) widgets | listing sidebars |
| "Recent Listing" footer widget (`new-thum-1.png`, "Price: $117") | footer everywhere |
| `index_preview.html` demo selector, `other/*.jpg` screenshots | preview page |

**Structural patterns that ARE reusable (shape only, not data):** header/nav, footer skeleton, breadcrumb bar, section-heading treatment, card grid, stat card, dashboard left-nav, sectioned form cards, responsive table, pagination, review/rating list, opening-hours list, 404 layout, login/register form fields.

**Business logic in theme JS:** essentially none beyond the counter reading numbers from the DOM and the map plotting hardcoded markers. Nothing to salvage; nothing that encodes rules.

---

## 8. Potential conflicts with Laravel 13 / Tailwind 4

1. **Two grid systems / class collisions.** Bootstrap `.container` is a fixed-width float grid; Tailwind's `container` utility is different. Also overlapping names: `.hidden`, `.block`, `.fixed`, `.active`, `.close`, `.btn`, `.row`, `.pull-right`, `.text-center`. Loading both CSS layers produces unpredictable cascade.
2. **Global resets fight Tailwind Preflight.** `* { margin:0; padding:0 }` and `a { text-decoration: none !important }` and `img { max-width:100% }` in `stylesheet.css` override/duplicate Preflight and, via `!important`, beat Tailwind utilities.
3. **jQuery requirement.** Every interactive piece (modal login/register, nav collapse, "Listing" dropdown, sticky affix, bootstrap-select, colorbox) needs jQuery + Bootstrap 3 JS. The Laravel 13 stack ships no jQuery; adding it (plus 5 plugins) contradicts AGENTS.md "prefer framework-native features."
4. **Tailwind 4 config model.** No `tailwind.config.js` to receive a theme. Palette/spacing/font must be written as CSS custom properties under `@theme` in `resources/css/app.css`. The theme provides no machine-readable tokens — manual transcription only.
5. **Icon font vs. SVG.** `<i class="fa">` everywhere; Font Awesome 4 is EOL. Need a deliberate icon strategy (Blade Icons + Heroicons recommended) and a glyph-mapping pass.
6. **Maps.** `https://maps.googleapis.com/maps/api/js?sensor=false` — deprecated params, now requires a billed API key, and is explicitly out of scope per AGENTS.md.
7. **Mixed content & i18n.** `http://fonts.googleapis.com/...` link (blocked on HTTPS); `<html lang="zxx">` must become `lang="en"` (or `en-PH`).
8. **Existing implementation divergence.** `resources/views/` already contains ~20 Blade files, 3 layouts (`public`, `app`, `admin`), and components (`search-form`, `provider-card`) built in **Tailwind teal/slate**. The theme is **gold/navy**. Reconciling means either re-skinning the existing views to the theme, or keeping the current palette and taking only layout ideas from the theme.
9. **Accessibility debt** that must not be inherited: empty `<a class="dropdown-toggle">` toggles, `aria-hidden="true"` on interactive close buttons, `#` hrefs, unlabeled icon buttons, sub-AA text contrast, no focus-visible styling (relies on removed outlines: `a { outline: 0 !important }`).
10. **Pagination.** Theme `.vfx-pagination` is hand-rolled markup; Laravel paginator emits its own Tailwind-oriented markup — style the paginator view, don't hand-build.

---

## 9. Migration / integration risks

| Risk | Severity | Mitigation |
| --- | --- | --- |
| **Privacy leak by copy-paste.** Theme cards & detail hero display provider name, phone, email, exact address. Oncall RULES.md #3–#5 and AGENTS.md forbid this for guests / pre-booking. | **High** | Rebuild `provider-card` and any detail view from scratch with a policy gate; never port the theme's contact blocks. Guest card shows only: anonymized label, availability, rating, completed count, verification badges, municipality/province. |
| **Directory model ≠ Oncall model.** "Submit Listing", "My Listing", pricing tiers, amenities, bookmarks, "Give Heart", opening hours as free text map poorly onto Service Request → Booking → Job → Rating. | **High** | Use theme screens only as layout inspiration. Design flows from `docs/ARCHITECTURE.md` / phase docs, not from theme page names or URLs. |
| **Auth as Bootstrap modals** vs. Laravel's dedicated auth routes, CSRF, validation error bags, rate limiting, password reset. | Medium | Build real `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password` pages (already scaffolded) in an `layouts/auth` shell. Drop the modals. |
| **Big-bang CSS adoption.** 6,250 lines of specific, ID-based CSS resists partial use; importing any of it risks global regressions on already-built views. | Medium | Token-extraction only (§3 → `@theme`). Component-by-component rebuild. No `@import` of theme CSS. |
| **Dependency creep.** Easy to `npm install` bootstrap/jquery/owl/fontawesome "to save time," then be stuck with them. | Medium | Hard rule: no new runtime deps without approval. Alpine (already idiomatic) + Blade Icons is the ceiling. |
| **Palette conflict with shipped views.** | ~~Medium~~ Resolved | Re-skinned to gold/navy (option a). Tokens in `app.css` `@theme`; `teal-*` removed from views. |
| **Responsive regressions on mobile** if the theme's tangled media queries are imitated. | Medium | Rebuild responsive with Tailwind `sm/md/lg` only; test at 360 / 414 / 768 / 1024 / 1280. |
| **Decorative JS (preloader, counters, map)** perceived as "part of the theme" and reproduced. | Low | Explicitly out of scope; note here so nobody re-adds them. |
| **Fake data into seeders.** Theme strings are memorable and could leak into `DatabaseSeeder`/factories. | Medium | Seeders use `docs/reference/SEED-SERVICES.md` + Faker with PH locale; never theme copy. |

---

## 10. Recommended Blade layout structure

Existing layouts (`resources/views/layouts/`) are **minimal and should be extended, not replaced**, to carry the theme's visual language:

| Layout | Audience | Contents (from theme structure) |
| --- | --- | --- |
| `layouts/public.blade.php` | Guests, marketing, search | Sticky slim header (logo left; nav: Home, How it works, Browse services, Safety; `Sign in` + `Register` on the right, gold pill). `{{ $slot }}`. Footer: About / Quick links / Safety / (no social, no captcha newsletter unless real). Uses `x-partials.public-header`, `x-partials.public-footer`. |
| `layouts/app.blade.php` | Authenticated Service Finder / Provider | Extends `public`. Adds breadcrumb bar + `x-partials.dashboard-sidebar` (role-aware vertical nav: Dashboard, Service requests, Bookings & jobs, Verification, Safety cases, Wallet, Profile) + content column. |
| `layouts/admin.blade.php` | Admin / Accounting / Budget / Cashier | Back-office chrome: condensed top bar + admin sidebar (Users, Providers, Verifications, Enforcement, Reports, Finance workflow, Audit logs). Denser tables, muted palette. |
| `layouts/auth.blade.php` *(new)* | Login / register / password reset | Centered card on a navy/gradient background, logo above, `{{ $slot }}`, small print links. Replaces theme modals. |

Shared partials: `partials/public-header`, `partials/public-footer`, `partials/dashboard-sidebar`, `partials/breadcrumbs`, `partials/flash` (session alerts).

---

## 11. Recommended Blade component structure

Namespace under `resources/views/components/`. **Bold = directly derived from a theme structure.**

### UI primitives (`components/ui/`)
- **`ui.button`** — variants `primary` (gold wipe), `dark` (navy), `ghost`; sizes `sm/md/lg`; optional leading icon. Unifies the 6 theme button styles.
- **`ui.section-heading`** — the `.bt_heading_3` centered title (highlight `<span>`, hairlines, dot cluster). Decorative; optional.
- **`ui.breadcrumbs`** — from `#breadcrum-inner-block`.
- **`ui.card`** / **`ui.stat-card`** — from `.statusbox` (label, icon chip, value, sub-caption). Real values only.
- **`ui.badge`** — base pill.
- **`ui.verification-badge`** — verified/pending/rejected; icon + label; policy-driven.
- **`ui.status-badge`** — maps enforcement states (ACTIVE/WARNING/UNDER_REVIEW/RESTRICTED/SUSPENDED) and job/request statuses to colour.
- **`ui.alert`** — success / info / warning / danger; used for flash + safety notices.
- **`ui.modal`** — Alpine-powered (`x-data`, focus trap, `Esc`); **not** Bootstrap.
- **`ui.rating-stars`** — display + input modes (from `.rating-box`).
- **`ui.pagination`** — Laravel paginator view themed to `.vfx-pagination` pills.
- **`ui.empty-state`** — from the 404 pattern; for "no results", "no requests yet".
- **`ui.avatar`** — circular, fallback initials.

### Form components (`components/forms/`)
- **`forms.field`** (label + slot + error + hint wrapper, mirrors `.form-group` in `.form-alt`)
- **`forms.input`**, **`forms.select`**, **`forms.textarea`**, **`forms.checkbox`** (from `.checkbox-success`), **`forms.file`** (from `.fileupload_block`)
- `forms.form-section` — the `.submit_listing_box` titled card

### Domain components
- **`search-form`** *(exists)* — 2 primary selectors: "What help do you need?" (service **or** category) + "Where?" (province); results page adds municipality + specific service. Align styling to theme search bar.
- **`provider-card`** *(exists — rebuild)* — from `.feature-item-container-box`. **Guest variant** (anonymized) vs **verified-finder variant** (identity per policy, contact still gated). No phone/email/address in guest mode.
- `provider-availability` / **`opening-hours`** — from `.working-hours`.
- **`data-table`** — from `my_listing.html` `.tg-listing` responsive stacking table. Reused by service requests, jobs, wallet ledger, withdrawals, audit logs.
- `dashboard-nav` — role-aware vertical nav (from `.dashboard_nav_item`).
- `review-item` / `review-form` — from `.media-list` / `.comment-respond` (ratings & reports phase).
- `stat-row` — dashboard KPI strip (real figures).

### Explicitly NOT componentized (drop)
Preloader, animated counters, Google Map hero, carousel, colorbox gallery, pricing table, amenities checklist, bookmark/heart actions, captcha, social bar, tags/archives widgets, "Recent Listing" footer widget.

---

## 12. Summary recommendation

1. **Keep:** Poppins font; the gold `#ffce10` / navy `#01273a` / warm-grey palette *(pending §9 palette decision)*; the layout archetypes (public shell, dashboard shell + left nav, sectioned form cards, responsive stacking table, stat cards, section headings); the button "colour-wipe" interaction as one component.
2. **Rebuild in Tailwind 4 + Blade + Alpine:** every page and component. Transcribe tokens into `@theme`. No theme CSS/JS imported.
3. **Drop entirely:** Bootstrap 3, jQuery, Font Awesome font, animate.css, Owl, Colorbox, bootstrap-select, Waypoints/CounterUp, Google Maps, all demo images, all demo copy, the preloader, the modals-as-auth pattern, `index_preview.html`.
4. **Never inherit:** exposed provider contact details, directory/CMS flows ("submit listing", pricing, amenities, bookmarks), fake stats, static contact info.
5. **Get a decision on:** theme gold/navy vs. the already-shipped teal/slate palette — then record it via `record-rule`.
6. Keep business architecture (auth, roles, verification, search ranking, requests, bookings, safety enforcement, sponsorship, fees, commissions, wallet ledger, withdrawals, Accounting→Budget→Cashier, audit) **entirely independent of the theme**, per `docs/RULES.md` and `AGENTS.md`.

**Theme files remain unmodified. No implementation performed. Awaiting go-ahead before converting anything.**
