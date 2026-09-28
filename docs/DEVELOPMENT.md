# HAMISTA — راهنمای توسعه و دیباگ / Development & Debugging Guide

> این سند مرجع فنی توسعه‌دهندگان است. The canonical design is in `docs/superpowers/specs/2026-09-28-hamista-platform-design.md` (§14 holds the binding conventions). The task-by-task plan is in `docs/superpowers/plans/`.

---

## 1. Packages

| Package | Folder | Text domain | Namespace | Status (this snapshot) |
|---|---|---|---|---|
| Theme | `wp-content/themes/hamista` | `hamista` | `Hamista\Theme` | Design system, header/footer skeleton, colour modes, fonts, style guide — **in progress** |
| Core (required) | `wp-content/plugins/hamista-core` | `hamista-core` | `Hamista\Core` | Foundation **done**: modules, option defaults, support services, admin menu, emails, private storage, account endpoints, Jalali |
| Customer Dashboard | `wp-content/plugins/hamista-customer-dashboard` | `hamista-customer-dashboard` | `Hamista\Dashboard` | In progress |
| License Manager | `wp-content/plugins/hamista-license-manager` | `hamista-license-manager` | `Hamista\License` | In progress (not in this ZIP) |
| Service Orders | `wp-content/plugins/hamista-service-orders` | `hamista-service-orders` | `Hamista\ServiceOrders` | Planned |

**Requirements:** PHP 8.1+, WordPress 6.5+ (tested on 7.1.2), WooCommerce 8+ (tested on 11.3, HPOS on), Elementor 3.20+ optional, Rank Math optional.

**Install order:**
1. WooCommerce
2. Hamista Core
3. the Hamista theme
4. the satellites
5. Elementor / Rank Math (optional)
6. a payment gateway (e.g. Zarinpal)

---

## 2. Architecture

```
               ┌──────────────── Theme (design system: tokens + primitives, templates) ────────────────┐
               │   reads hamista_theme option via Hamista\Theme\Options (works without core)          │
               └───────────────────────────────▲──────────────────────────────────────────────────────┘
                                               │ hamista_render(), hamista_icon(), hamista_get_option_i18n()
┌──────────────────────────────── hamista-core (foundation) ─────────────────────────────────────────┐
│ Plugin (singleton) → Module_Registry → modules boot on init:1 when enabled + requirements met      │
│ Settings registry (admin only) · option defaults (cheap, frontend) · Components renderer           │
│ Support: Assets, Icons, Html, Request(IP), Rate_Limiter, Jalali, Formatter, Mailer, Private_Storage│
│          Account_Endpoints (WooCommerce My Account), Flash, Multilingual, Template_Loader          │
└──────▲──────────────────────────────▲───────────────────────────────────▲──────────────────────────┘
       │ hamista_register_modules     │ hamista_register_account_endpoint │ hamista_notify / hamista_mail
 License Manager               Customer Dashboard                     Service Orders
```

**Boot sequence (core)**
- `plugins_loaded:5` — `Plugin::boot()`: register option defaults, run `Installer::maybe_upgrade()`, wire the hooks, then fire `hamista_core_loaded`.
- `init:0` — load the text domain.
- `init:1` — fire `hamista_register_modules`, then `Module_Registry::boot_enabled()`.
- `init:999` — one-time rewrite flush, when flagged.
- `wp_enqueue_scripts` / `admin_enqueue_scripts:5` — register the `hamista-ui` and `hamista-admin` handles.
- `admin_menu:9` — the top-level menu `hamista`.

**Satellite plugins** boot on `plugins_loaded:20`. When `hamista_core()` is missing, they only show an admin notice.

---

## 3. Core public API (`hamista-core/includes/functions.php`)

```php
hamista_core(): Hamista\Core\Plugin
hamista_register_option_defaults( string $option, array $defaults ): void   // call at load time, untranslated
hamista_get_option( string $option, string $key, mixed $default = null ): mixed
hamista_module_enabled( string $module_id ): bool
hamista_icon( string $name, array $attrs = [] ): string                     // Lucide + Simple Icons; 'x' = close, 'brand-x' = X logo
hamista_render( string $component, array $args = [] ): string               // templates/components/{component}.php
hamista_date( string $format, int|string|DateTimeInterface $time ): string  // Jalali when calendar=jalali & locale fa*
hamista_price_digits( string $text ): string                                // Persian digits (HTML-safe)
hamista_client_ip(): string                                                 // honours the ip_header setting (Arvan/Cloudflare)
hamista_rate_limit( string $bucket, int $limit, int $window_seconds ): bool // true = allowed
hamista_notify( int $user_id, array $args ): void                           // type,title,message,link → do_action('hamista_notify')
hamista_mail( $to, string $subject, array $args ): bool                     // heading, body, button{text,url}, footer, user_id
hamista_register_account_endpoint( string $id, array $args ): void          // title, icon, group, position, callback, badge, overview, menu
hamista_account_endpoints(): array
hamista_private_storage( string $bucket ): Hamista\Core\Support\Private_Storage
hamista_translate_string( string $value, string $name, string $context = 'hamista' ): string  // WPML / Polylang
hamista_language_switcher( array $args = [] ): string
hamista_locate_template( $plugin_slug, $template, $default_dir ): string    // theme override: {theme}/{plugin_slug}/{template}
hamista_get_template( $plugin_slug, $template, array $args, $default_dir ): void
hamista_flash( string $message, string $type = 'success' ): void
hamista_flash_messages(): array
hamista_admin_header( string $title, array $args = [] ): void
```

### Hooks

| Hook | Type | Purpose |
|---|---|---|
| `hamista_core_loaded` | action | core booted (`Plugin $core`) |
| `hamista_register_modules` | action | register modules (`Module_Registry $registry`) — fires on `init:1` |
| `hamista_register_settings` | action | add settings tabs/sections (admin settings screen only) |
| `hamista_notify` | action | in-app notification event (stored by the dashboard plugin) |
| `hamista_admin_overview_callback` | filter | landing page callback of the Hamista admin menu |
| `hamista_component_args` / `hamista_component_html` | filter | alter component args or output |
| `hamista_locate_template` | filter | template path resolution |
| `hamista_settings_field_types` | filter | custom settings field types (`render`/`sanitize`) |
| `hamista_use_jalali` / `hamista_use_persian_digits` | filter | force the calendar/digits behaviour |
| `hamista_client_ip` | filter | override client IP detection |

### Writing a module

```php
namespace My\Plugin;
use Hamista\Core\Modules\Abstract_Module;

final class Reports_Module extends Abstract_Module {
	public function id(): string { return 'my-reports'; }
	public function title(): string { return __( 'Reports', 'my-plugin' ); }        // lazy: admin only
	public function description(): string { return __( 'Extra reports.', 'my-plugin' ); }
	public function requires(): array { return [ 'woocommerce' ]; }                   // woocommerce|elementor|rank-math|hamista-theme
	public function boot(): void { add_action( 'admin_menu', [ $this, 'menu' ], 20 ); }
}
add_action( 'hamista_register_modules', fn( $r ) => $r->add( new Reports_Module() ) );
```

Module on/off state lives in the option `hamista_modules` (`id => bool`).

### Adding a My Account section

```php
hamista_register_account_endpoint( 'my-section', [
	'title'    => __( 'My section', 'my-plugin' ),
	'icon'     => 'layout-grid',
	'group'    => 'support',            // shop | services | support | account
	'position' => 45,
	'callback' => function ( $value ) { echo '…'; },
	'badge'    => fn( int $user_id ) => 0,
	'overview' => fn( int $user_id ) => [ 'label' => '…', 'value' => '3', 'link' => '…', 'icon' => 'bell' ],
	'menu'     => true,                 // false = endpoint without a menu item (e.g. printable invoice)
] );
```

Rewrite rules flush automatically when the endpoint set changes.

---

## 4. Theme

- **Options:** `hamista_theme` (single option array). Read with `Hamista\Theme\Options::get( $key )`; all defaults are in `Options::defaults()`. The settings tab schemas are in `inc/settings/*.php`. They appear in the unified panel **Hamista → Settings**, also under **Appearance → Theme Settings**, once core's settings UI is active.
- **CSS source:** `assets/css/src/` (partials). `bundles.json` defines the outputs: `main.css` for the theme, and `hamista-core/assets/css/ui.css`, the fallback UI kit for non-Hamista themes. **Never edit `main.css` / `*.min.css` directly** — edit `src/` and run `npm run build`.
- **Design tokens:** `--hm-*` custom properties in `src/tokens.css`. Light mode on `:root`; dark on `:root[data-theme="dark"]`. The UI primitives use `hm-` classes (spec §4.2).
- **Colour mode:** a head inline script sets `data-theme` before paint, and `localStorage['hamista-color-mode']` stores the choice. The default comes from the setting `color_mode_default` (light).
- **Fonts** (self-hosted, `assets/fonts/`): Vazirmatn (Persian) and Inter (Latin), split by `unicode-range`. No Google Fonts or CDN.
- **RTL/LTR:** CSS logical properties only. Directional icons flip under `[dir=rtl]`. Never put negative `letter-spacing` on Persian text.
- **Style guide:** `/?hamista-styleguide=1` (admins) shows every primitive and token, for visual QA.

---

## 5. Build tooling

```bash
npm install
npm run build       # bundles theme CSS partials, minifies every assets/**/*.css|js into .min, copies fonts, prints size budget
npm run zip         # dist/*.zip per package (excludes tests/, node_modules)
php tools/i18n/compile.php --all       # .po → .mo + .l10n.php
php tools/icons/generate-icons.mjs     # (node) regenerates core icon PHP arrays from lucide-static / simple-icons
```

**Budgets:** `main.min.css` ≤ 30 KB and `main.min.js` ≤ 6 KB; each component ≤ 4 KB. `SCRIPT_DEBUG=true` loads the unminified files.

---

## 6. Testing

```bash
php tools/tests/run.php [path]                       # unit tests (no WordPress): tests/unit/*Test.php, test_* functions
ENV=/path/to/env tools/tests/run-integration.sh [path]  # integration: tests/integration/*.php via WP-CLI eval-file
```

- Assertions: `assert_same`, `assert_true`, `assert_false`, `assert_contains`, `assert_throws`.
- Integration tests also get `hm_http( $method, $path, $args )` for real HTTP requests.
- Tests create uniquely named fixtures and delete them. They never reset the DB.

**Local environment (no Docker):** WordPress + SQLite drop-in + PHP's built-in server:
- `php -S 127.0.0.1:8080 -t wordpress router.php`
- WP-CLI for everything else

The private storage `.htaccess` guards do **not** apply under `php -S` or nginx (see §8).

**Static checks:**
```bash
phpcs --standard=WordPress,PHPCompatibilityWP --runtime-set testVersion 8.1- --extensions=php wp-content/plugins/hamista-core
```

---

## 7. Debugging

1. **Turn on logging** in `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   define( 'SCRIPT_DEBUG', true );   // load unminified CSS/JS
   ```
   Then watch `wp-content/debug.log`.
2. **Module not working?** Check **Hamista → Settings → Modules**: it is either switched off, or its requirement (WooCommerce/Elementor) is missing — the unavailable reason is shown. From the CLI: `wp eval 'var_dump( hamista_module_enabled( "services" ) );'`.
3. **Option values:** `wp option get hamista_theme --format=json`, `wp option get hamista_core --format=json`, `wp option get hamista_modules`.
4. **My Account section returns 404:** re-save Settings → Permalinks, or `wp rewrite flush`. Hamista flushes automatically when its endpoints change (option `hamista_account_endpoints_hash`).
5. **Notices like `Module_Registry::add was called incorrectly … already booted`:** a module was registered twice or after booting. Register modules only inside the `hamista_register_modules` callback.
6. **Translations don't load:** make sure no `__()` runs before `init` (WP 6.7+ logs `_load_textdomain_just_in_time`). Compile the `.po` files with `tools/i18n/compile.php`.
7. **RTL looks wrong:** check that `<html dir="rtl">` is set (the site language is fa_IR and the core fa_IR language pack is installed). Search the CSS for `left` / `right`: layout must use logical properties.
8. **Dark-mode flash:** the inline head script must print at `wp_head` priority 0. Optimisation plugins must not defer or delay it — exclude `hamista-color-mode` in LiteSpeed/WP Rocket.
9. **Emails not arriving:** use an SMTP plugin; many Iranian hosts block PHP `mail()`. `hamista_mail()` uses `wp_mail()`.
10. **Rate limit locked you out** (login/API): delete the transients with `wp transient delete --all`, or wait for the window (default 15 min for login).

---

## 8. Production notes (Iran hosting)

- **nginx:** protect the private storage with
  ```nginx
  location ^~ /wp-content/uploads/hamista-private/ { deny all; return 404; }
  ```
  or define `HAMISTA_PRIVATE_STORAGE_PATH` outside the web root.
- **Behind ArvanCloud/Cloudflare:** set Hamista → Settings → Integrations → Client IP header (`HTTP_AR_REAL_IP` / `HTTP_CF_CONNECTING_IP`), so rate limits see real IPs.
- **Caching plugins:** exclude `/my-account/*`, `/cart/`, `/checkout/`. The cart count is loaded client-side (`/wp-json/hamista/v1/cart`), so public pages stay cacheable.
- **Currency:** WooCommerce `IRT` (Toman), 0 decimals.

---

## 9. Conventions checklist (for every change)

- Prefixes: `hamista_` for PHP, `hm-` for CSS, `data-hm-` for JS hooks, `{$wpdb->prefix}hamista_` for tables.
- Capability check + nonce on every state change. Sanitize input and escape output late. Use `$wpdb->prepare()`.
- Customer-owned objects: include `user_id` in the SQL WHERE (IDOR protection).
- No `extract()`, `eval`, or unserialize of user data. Uploads go only through `Private_Storage`.
- No jQuery on the frontend. No external CDNs. Ship `.min` assets and load them conditionally.
- English source strings with the package text domain; Persian in `languages/*-fa_IR.po`.
- Hook callbacks take untyped params and cast. Internal methods are typed. PHP 8.1 syntax floor.
