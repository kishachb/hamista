# HAMISTA Platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task by task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Deliberate deviation from the plan template:** this plan does not inline full source code. It covers a theme and four plugins, so inlining would mean writing the product twice. Instead, the *spec* fixes every cross-package contract (names, signatures, tables, hooks, tokens, classes). Each task below lists its files, interfaces, acceptance checks and verification commands. The reviewer gate for each task is the acceptance list.

**Goal:** Build the HAMISTA WordPress ecosystem (theme + 4 plugins + demo + docs + ZIPs) described in the spec.

**Architecture:**
- `hamista-core` is the foundation: settings framework, modules, components, shared support services.
- The theme owns the design system.
- The license manager, customer dashboard and service orders are satellites. They talk to core only through the public API in spec §4.

**Tech Stack:**
- PHP 8.1+ (OOP, namespaced, WPCS), WordPress 6.5–7.1, WooCommerce 8–11.3 (HPOS), Elementor 3.20+ (optional)
- Vanilla JS (ES2019), modern CSS (logical properties, custom properties)
- esbuild + lightningcss for minification; WP-CLI for integration tests; Lighthouse and Playwright/Chromium

**Spec:** `docs/superpowers/specs/2026-09-28-hamista-platform-design.md`

## Global Constraints

Spec §3 applies to every task. In short:

- PHP 8.1 syntax floor; WordPress 6.5+; WooCommerce CRUD only (HPOS-safe).
- Prefixes: `hamista_` (PHP), `hm-` (CSS), `data-hm-` (data attributes), `{$wpdb->prefix}hamista_` (tables).
- Security on every request path: capability check + nonce + sanitize in / escape out + `$wpdb->prepare`.
- Frontend: no jQuery, no external CDN or fonts; shipped `.min` assets; conditional loading.
- English source strings with a per-package text domain; `fa_IR` translations ship.
- RTL/LTR through logical CSS properties only; never negative `letter-spacing` on Persian text.
- Every feature can be switched on or off in the settings panel.

## Test environment

A local WordPress 7.1.2 on SQLite with WooCommerce 11.3 and WP-CLI. It lives in the session scratchpad (`$ENV`) and is built by a dedicated setup task. The package directories are symlinked into it.

- `$ENV/wp …` — WP-CLI
- `$ENV/start-server.sh` → http://127.0.0.1:8080 (admin/admin, customer/customer)
- `$ENV/reset-db.sh` — restores the clean snapshot

## Phase 0 — Tooling

### Task 0.1: Repo scaffolding and build tooling
**Files:**
- Create: `package.json`, `tools/build-assets.mjs`, `tools/build-zips.sh`, `tools/i18n/compile.php`, `.gitignore`, `.editorconfig`, `README.md` (rewrite)

**Produces:**
- `npm run build` minifies every `wp-content/**/assets/{css,js}/*.{css,js}` (not `*.min.*`) into siblings named `*.min.*`.
- `npm run fonts` copies Vazirmatn-NL and Inter variable woff2 into the theme.
- `tools/build-zips.sh` writes versioned ZIPs to `dist/`, excluding `tests/`, dotfiles and sources maps.
- `php tools/i18n/compile.php <po>` writes `.mo` and `.l10n.php` next to the given `.po`.

**Acceptance:** `npm run build` exits 0. A sample CSS/JS file gets minified.

- [ ] Write the files → run `npm install && npm run build` → commit `chore: build tooling`

## Phase 1 — Core foundation (`hamista-core`)

### Task 1.1: Bootstrap, autoloader, module system
**Files:**
- `hamista-core.php`
- `includes/class-autoloader.php`, `includes/class-plugin.php`, `includes/class-installer.php`
- `includes/modules/interface-module.php`, `includes/modules/class-abstract-module.php`, `includes/modules/class-module-registry.php`
- `includes/functions.php`, `uninstall.php`

**Interfaces:**
- Produces `hamista_core()`, `hamista_module_enabled()`, the `hamista_core_loaded` and `hamista_register_modules` actions, and the `Module` interface (spec §4.6).

**Acceptance:**
- Activates with no notices.
- A test module registered via the hook boots only when enabled and when its requirements are met.

### Task 1.2: Settings framework and panel
**Files:**
- `includes/settings/class-settings-registry.php`, `class-field-types.php` (render + sanitize per type), `class-settings-page.php` (admin UI, save handler, import/export/reset)
- `assets/admin/settings.css`, `assets/admin/settings.js` (repeater, sortable, `show_if`, media picker, colour, tab navigation)
- `includes/settings/tabs/` — core tabs: Modules, Performance, Security, SEO, Integrations, Custom Code, Import/Export, System Status

**Interfaces:**
- Produces `hamista_register_settings`, `hamista_get_option()`, and the tab/field schema (spec §4.5).

**Acceptance:**
- Saving a tab merges only that tab's keys into its option.
- Each field type is sanitized: a `number` is clamped, a `url` is escaped, `code` has tags stripped for users without `unfiltered_html`, and a `repeater` drops unknown keys.
- A bad nonce or missing capability gives a 403.
- Export → import round-trip is lossless.

### Task 1.3: Support services
**Files:** `includes/support/`:
- `class-assets.php`, `class-icons.php` (Lucide and Simple Icons subsets), `class-html.php`
- `class-request.php`, `class-rate-limiter.php`, `class-jalali.php`, `class-mailer.php`
- `class-private-storage.php`, `class-account-endpoints.php`

Also `templates/emails/base.php`, `assets/css/ui.css` (fallback tokens and primitives, spec §4.1–4.2) and `assets/js/ui.js`.

**Tests:** `tools/tests/unit/JalaliTest.php`, `RateLimiterTest.php` (no WordPress; run with `php tools/tests/run.php`).

**Acceptance:**
- Jalali: 2025-03-21 → 1404-01-01; 2024-03-20 → 1403-01-01; 2023-03-21 → 1402-01-01; round-trips over 1,000 random dates.
- Private storage writes the guard files and rejects `.php` / `.phtml` / `.phar` and double extensions.
- Endpoints registered through `hamista_register_account_endpoint` appear in My Account.

### Task 1.4: Performance, security, Jalali, multilingual and SEO modules
**Files:** `includes/modules/{performance,security,jalali,multilingual,seo}/class-*-module.php`, `wpml-config.xml`

**Acceptance:**
- Each module is toggleable and its settings take effect: the headers are present, emoji is removed, `wp_date` returns a Jalali string when enabled, and schema JSON-LD is valid and suppressed when Rank Math or Yoast is present.

Commit after each task: `feat(core): …`

## Phase 2 — Theme (`hamista`)

### Task 2.1: Theme skeleton and design system
**Files:**
- `style.css`, `functions.php`
- `inc/class-autoloader.php`, `inc/class-theme.php`, `inc/class-setup.php`, `inc/class-options.php` (+ `inc/settings/*.php` tab schemas)
- `inc/class-assets.php`, `inc/class-color-mode.php`, `inc/class-icons.php`, `inc/template-tags.php`
- `assets/css/main.css` (tokens, base, layout, primitives, header, footer), `assets/js/main.js`, `assets/fonts/*`

**Acceptance:**
- Renders with and without core.
- Light is the default; the toggle switches to dark with no flash on reload (the head script runs before CSS paint).
- fa_IR renders RTL; en_US renders LTR.
- `main.min.css` ≤ 30 KB and `main.min.js` ≤ 6 KB.

### Task 2.2: Templates (header, footer, blog, pages, search, 404, CPT templates, page templates)
**Acceptance:**
- Accessible navigation: keyboard dropdowns, a focus-trapped mobile drawer, and a skip link.
- Every header and footer element follows its setting toggle.
- Breadcrumbs come from Rank Math when available, otherwise from the theme.

### Task 2.3: WooCommerce and Elementor integration + front page sections
**Files:** `inc/class-woocommerce.php`, `inc/class-elementor.php`, `inc/class-multilingual.php`, `assets/css/{shop,blog,pages}.css`, `template-parts/home/*.php`

**Acceptance:**
- No WooCommerce CSS anywhere, and no WooCommerce JS on non-WooCommerce pages.
- The cart count is served by `GET /hamista/v1/cart` (no-store).
- The simplified checkout drops address fields for virtual-only carts.
- The front page renders its sections in the configured order.

## Phase 3 — Core content, components, Elementor

### Task 3.1: Content types + meta boxes (services, portfolio, FAQ, testimonials, messages)
### Task 3.2: Components + shortcodes + CSS (`templates/components/*.php`, `assets/css/components/*.css`)
### Task 3.3: Elementor widgets (one per component, via a shared abstract widget)
### Task 3.4: Marketplace product data (WooCommerce product tab, product tabs, summary box, structured data, release sync)
### Task 3.5: Contact form + messages admin + form-token endpoint

**Acceptance (phase):**
- Services save and sanitize plans and questionnaires (rejecting unknown field types).
- Components render from shortcode and from Elementor with the same markup.
- Fragment caches invalidate when a post is saved.
- The contact form rejects bad nonces, honeypot hits and floods, and stores + emails valid submissions.

## Phase 4 — License Manager (parallelisable after Phase 1)

### Task 4.1: Schema + repositories + Key_Generator + Domain (unit-tested first)
### Task 4.2: License_Service + WooCommerce order integration + product/variation fields
### Task 4.3: REST API + rate limiting + signing + download tokens/streaming
### Task 4.4: Admin pages (licenses, releases, download log) + settings tab
### Task 4.5: My Account `licenses` endpoint + downloads integration + SDK client

**Acceptance:**
- Over real HTTP against the local server:
  - issue → activate (limit enforced, local domains free) → check → update (newer version returns a signed package URL) → download (sha256 matches) → deactivate;
  - a revoked license fails with `license_revoked`;
  - flooding returns 429.

## Phase 5 — Customer Dashboard (parallelisable after Phase 1)

### Task 5.1: Account shell (grouped navigation, overview) + profile fields
### Task 5.2: Tickets (tables, customer UI, admin UI, attachments, emails, auto-close cron)
### Task 5.3: Notifications (store `hamista_notify`, endpoint, header bell count)
### Task 5.4: Invoices (list, printable Jalali invoice, admin print button) + settings tab

**Acceptance:**
- Ticket lifecycle works end to end.
- IDOR: customer B gets 404/403 on customer A's ticket, attachment and invoice.

## Phase 6 — Service Orders (parallelisable after Phase 3.1)

### Task 6.1: CPT, statuses, notes table, settings tab
### Task 6.2: Order form (questionnaire rendering + validation + uploads) + WooCommerce cart/checkout bridge
### Task 6.3: Customer endpoint (stepper, thread, pay now, approve) + admin screens + emails

**Acceptance:**
- Submit → cart holds the server-side price → order paid → `hm-new` → staff message → customer reply → delivered → approved → completed (and the WooCommerce order completes).

## Phase 7 — Admin dashboard (core `admin-dashboard` module)

### Task 7.1: Overview KPIs + revenue SVG chart + recent lists; Revenue page + CSV; Customers list + profile

**Acceptance:** Totals match `wc_get_orders` sums on the seeded data. Caches are invalidated when an order status changes.

## Phase 8 — Demo, Elementor templates, translations, docs, reports, ZIPs

### Task 8.1: Demo importer + Persian demo dataset + generated images + Elementor template generator
### Task 8.2: POT extraction + complete fa_IR translations for all five text domains (compiled `.mo` + `.l10n.php`)
### Task 8.3: Docs (fa installation + user guide; en developer docs + License API/SDK) + security report
### Task 8.4: Verification sweep:
- PHPCS / `php -l`;
- integration scripts;
- IDOR / security checks;
- Playwright screenshots (RTL/LTR × light/dark × mobile/desktop);
- Lighthouse → `docs/performance-report.md`;
- theme `screenshot.png`;
- `tools/build-zips.sh` → `dist/`.

## Execution notes

- Phases 4, 5 and 6 run in parallel isolated worktrees once Phases 1 and 3.1 have landed. They are merged back — the directory trees are disjoint.
- Every phase ends with a commit and a push to `claude/eloquent-bohr-79h7tz`.
