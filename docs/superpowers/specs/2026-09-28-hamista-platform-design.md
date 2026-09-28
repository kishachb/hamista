# HAMISTA Platform — Design Spec

**Date:** 2026-09-28 · **Status:** approved in chat on 2026-09-28

## خلاصه (Persian summary)

هامیستا یک اکوسیستم وردپرسی است که از پنج بسته تشکیل می‌شود:

- قالب سبک `hamista`
- افزونه‌ی پایه `hamista-core`
- `hamista-license-manager`
- `hamista-customer-dashboard`
- `hamista-service-orders`

زبان اصلی فارسی (RTL) و زبان دوم انگلیسی (LTR) است. حالت روشن پیش‌فرض است و حالت تیره هم پشتیبانی می‌شود. فروشگاه روی ووکامرس با ارز تومان و هر درگاه ایرانی کار می‌کند و پنل مشتری روی My Account ووکامرس ساخته می‌شود. همه‌ی امکانات در یک پنل «تنظیمات هامیستا» قابل روشن/خاموش کردن و تنظیم هستند. لوگو از همین تنظیمات تعریف می‌شود.

## 1. Decisions (from brainstorming Q&A)

| Topic | Decision |
|---|---|
| Logo | No logo file supplied. The logo (light + dark variants) is set in theme settings; the fallback is a typographic wordmark. The colour system and light/dark modes are built now. |
| Currency / payments | Toman (`IRT`, 0 decimals). Works with any WooCommerce gateway (Zarinpal etc. installed separately). |
| Customer panel | Built on WooCommerce **My Account** (custom endpoints and custom shell). |
| Delivery | Phase by phase, with **all** features complete. Every feature can be switched on/off, with full settings in the unified settings panel. |
| Settings home | The framework lives in `hamista-core` (plugins depend on core, never on the theme). The theme registers its own tabs. The theme works with defaults when core is inactive. |

## 2. Repository layout

```
wp-content/themes/hamista/                     theme (text domain: hamista)
wp-content/plugins/hamista-core/               foundation plugin (required by the others)
wp-content/plugins/hamista-license-manager/
wp-content/plugins/hamista-customer-dashboard/
wp-content/plugins/hamista-service-orders/
demo/elementor-templates/                      exported Elementor JSON for manual import
docs/                                          fa/ (install + user guides), en/ (developer docs), reports
tools/                                         build, i18n, test and zip tooling
dist/                                          release ZIPs
```

## 3. Global constraints

- PHP **8.1+** (no 8.2+ only syntax: no readonly classes, DNF types, typed class constants or property hooks). Tested on PHP 8.4.
- WordPress **6.5+** (plugin dependencies header, `.l10n.php` translations). Tested up to **7.1**.
- WooCommerce **8.0+**, tested up to **11.3**. HPOS (`custom_order_tables`) and `cart_checkout_blocks` compatibility must be declared. Order data is accessed **only through the WooCommerce CRUD API** — never through `get_post_meta` on orders.
- Elementor 3.20+ (containers), optional. Rank Math optional.
- Plugin headers: `Requires Plugins: hamista-core` for the three satellites. `hamista-customer-dashboard` also requires `woocommerce`.
- Object-oriented PHP with namespaces:
  - `Hamista\Theme`
  - `Hamista\Core`
  - `Hamista\License`
  - `Hamista\Dashboard`
  - `Hamista\ServiceOrders`
- File naming follows WPCS. `Hamista\Core\Admin\Settings_Page` lives in `includes/admin/class-settings-page.php`: lower-case namespace directories, `_` becomes `-`, and the file gets a `class-`, `interface-` or `trait-` prefix. Each package has its own tiny autoloader.
- WordPress Coding Standards (WPCS 3) apply.
- Every hook, option, meta key, table, handle, CSS class and JS global is prefixed:
  - PHP hooks/options/meta: `hamista_`
  - CSS classes: `hm-`
  - data attributes: `data-hm-`
  - DB tables: `{$wpdb->prefix}hamista_`
- Security, on every request path:
  - capability check;
  - nonce on every state-changing request;
  - sanitize input and escape output late;
  - `$wpdb->prepare()` for every query with variables;
  - no `extract()`, no `eval`, no unserialize of user data;
  - file uploads validated by real MIME type and stored privately.
- Performance:
  - no jQuery on frontend pages (WooCommerce checkout/cart may still load it);
  - no external CDN or font requests (Iran-friendly);
  - assets are minified (`.min.css` / `.min.js` shipped and used unless `SCRIPT_DEBUG`);
  - assets load conditionally.
- i18n: English source strings with text domains `hamista`, `hamista-core`, `hamista-license-manager`, `hamista-customer-dashboard` and `hamista-service-orders`. Full `fa_IR` translations ship as `.po`, `.mo` and `.l10n.php`. JS strings come from PHP-localised config (no `wp-i18n` dependency on the frontend).
- RTL/LTR: one stylesheet per concern, written with CSS logical properties (`margin-inline-start`, `inset-inline-end`, …). Direction-sensitive icons flip via `[dir="rtl"]`. Persian text **never** gets negative `letter-spacing`.
- Dates:
  - Solar Hijri (Jalali) formatting when the calendar setting is `jalali` and the locale is `fa_*` (default for `fa_IR`);
  - optional Persian digits for prices and dates.
- Versions: every package starts at `1.0.0`. The version constant is used for asset cache-busting.

## 4. Shared contracts

### 4.1 Design tokens (CSS custom properties)

The theme's `main.css` defines tokens on `:root` (light) and `:root[data-theme="dark"]` (dark). Core's fallback `ui.css` defines the same tokens when the Hamista theme is not active. All component and plugin CSS uses only these tokens.

```
Colors   --hm-color-bg | -surface | -surface-2 | -elevated | -text | -text-muted | -text-subtle
         --hm-color-border | -border-strong | -overlay
         --hm-color-brand-orange | -brand-red | -brand-purple
         --hm-color-primary | -primary-hover | -primary-contrast | -link | -link-hover | -focus
         --hm-color-success | -warning | -danger | -info  (+ "-soft" background variants)
Gradients --hm-gradient-brand (vivid, decorative)  --hm-gradient-action (AA-contrast, for text-on-gradient)
          --hm-gradient-soft (subtle tinted backgrounds)
Type     --hm-font-sans | --hm-font-mono | --hm-text-xs … --hm-text-6xl (fluid clamp) | --hm-leading-tight | -snug | -body
Shape    --hm-radius-sm | -md | -lg | -xl | -pill
Depth    --hm-shadow-xs | -sm | -md | -lg | -glow
Space    --hm-space-1 … --hm-space-12 (4px grid: 4,8,12,16,20,24,32,40,48,64,80,96) | --hm-section-y (fluid)
Layout   --hm-container (1240px) | --hm-container-narrow (760px) | --hm-gutter (fluid) | --hm-header-h
Motion   --hm-ease (cubic-bezier(.2,.8,.2,1)) | --hm-duration-fast (140ms) | -base (240ms) | -slow (420ms)
Layers   --hm-z-header | --hm-z-dropdown | --hm-z-overlay | --hm-z-modal | --hm-z-toast
```

Brand palette defaults:

- **Light theme:**
  - `bg` #FFFFFF
  - `surface` #F7F7F8
  - `surface-2` #EFEFF3
  - `text` #16161D (charcoal)
  - `muted` #5B5B6B (6.6:1)
  - `border` #E6E6EC
- **Dark theme:** `bg` #0B0B0F (luxury black), `surface` #14141B, `surface-2` #1C1C25, `text` #F4F4F6, `muted` #A3A3B2
- **Vivid brand gradient** (decorative only): `#FF7A1A → #FF3B5C → #8B5CF6`
- **Action gradient** (white text, every stop ≥ 4.5:1): `#C2410C → #BE123C → #7C3AED`
- **Links:** light #C2410C, dark #FF8A3D

### 4.2 UI primitives (class contract)

Defined by the theme's `main.css`, or by core's fallback `ui.css` when another theme is active. The style handle is `hamista-ui`; the theme registers it as an empty handle so the CSS is never loaded twice.

| Group | Classes |
|---|---|
| Layout | `hm-container`, `hm-container--narrow`, `hm-section`, `hm-section--muted`, `hm-grid`, `hm-grid--2/3/4`, `hm-stack`, `hm-cluster`, `hm-sr-only` |
| Headings | `hm-section-head` (`__eyebrow`, `__title`, `__subtitle`), `hm-gradient-text` |
| Buttons | `hm-btn` + `--primary` (action gradient), `--dark`, `--secondary`, `--ghost`, `--link`, `--sm`, `--lg`, `--block`, `--icon`; `is-loading` |
| Cards | `hm-card` (`__media`, `__body`, `__title`, `__text`, `__footer`) + `--hover`, `--glass`, `--bordered`, `--highlight` |
| Badges | `hm-badge` + `--success`, `--warning`, `--danger`, `--info`, `--neutral`, `--brand` |
| Forms | `hm-form`, `hm-form-row`, `hm-field` (`__label`, `__help`, `__error`), `hm-input`, `hm-select`, `hm-textarea`, `hm-check`, `hm-dropzone`, `hm-required` |
| Feedback | `hm-alert` + `--success`, `--error`, `--warning`, `--info`; `hm-empty` (`__icon`, `__title`, `__text`) |
| Data | `hm-table-wrap`, `hm-table` (cells carry `data-label`, so tables stack on mobile), `hm-stat` (`__label`, `__value`, `__meta`, `__icon`), `hm-code` (monospace pill) |
| Navigation | `hm-tabs` (`__list`, `__tab[aria-selected]`, `__panel`), `hm-steps` (`__item`, `is-done`, `is-current`), `hm-pagination` |
| Misc | `hm-thread` (`__item`, `--staff`, `__meta`, `__body`, `__files`), `hm-avatar`, `hm-icon` (`1em`, `currentColor`), `hm-dialog` (native `<dialog>`) |

Shared JS `hamista-ui` (core, about 2 KB, vanilla) wires these behaviours:

- `[data-hm-copy]` — copy to clipboard
- `[data-hm-tabs]` — tabs
- `[data-hm-confirm]` — confirm before continuing
- `[data-hm-dropzone]` — file dropzone
- `form[data-hm-busy]` — loading state on submit
- `[data-hm-dismiss]` — dismissible alerts

### 4.3 Core PHP API (consumed by the theme and plugins)

```php
// Lifecycle
do_action( 'hamista_core_loaded', \Hamista\Core\Plugin $core );              // plugins_loaded:5
do_action( 'hamista_register_modules', \Hamista\Core\Modules\Module_Registry $registry );
do_action( 'hamista_register_settings', \Hamista\Core\Settings\Settings_Registry $settings );

// Functions (includes/functions.php) — all guarded with function_exists in consumers
hamista_core(): \Hamista\Core\Plugin
hamista_get_option( string $option, string $key, mixed $default = null ): mixed   // merged with registered defaults
hamista_module_enabled( string $module_id ): bool
hamista_icon( string $name, array $attrs = [] ): string                    // inline SVG (escaped, safe)
hamista_render( string $component, array $args = [] ): string              // component HTML
hamista_date( string $format, int|string|\DateTimeInterface $time ): string // Jalali-aware
hamista_price_digits( string $text ): string                                // Persian digits if enabled
hamista_client_ip(): string                                                 // honours "IP header" setting
hamista_rate_limit( string $bucket, int $limit, int $window_seconds ): bool // true = allowed
hamista_notify( int $user_id, array $args ): void    // args: type,title,message,link → do_action('hamista_notify')
hamista_mail( string|array $to, string $subject, array $args ): bool        // branded HTML email
hamista_register_account_endpoint( string $id, array $args ): void          // see 4.4
hamista_account_endpoints(): array
hamista_private_storage( string $bucket ): \Hamista\Core\Support\Private_Storage
hamista_translate_string( string $value, string $name, string $context = 'hamista' ): string // WPML/Polylang
hamista_language_switcher( array $args = [] ): string
```

### 4.4 My Account endpoint API (core → used by LM, Dashboard, Service Orders)

```php
hamista_register_account_endpoint( 'licenses', [
    'title'    => __( 'Licenses', 'hamista-license-manager' ),
    'icon'     => 'key',          // core icon name
    'group'    => 'shop',         // shop | services | support | account
    'position' => 30,             // menu order within all items
    'callback' => callable,       // echoes endpoint content; receives the endpoint value
    'badge'    => callable|null,  // fn( int $user_id ): int
    'overview' => callable|null,  // fn( int $user_id ): array{label:string,value:string,link:string,icon:string}
] );
```

Core registers the WooCommerce query var, the menu item, the title and the content hook. It flushes rewrite rules once whenever the set of endpoints changes (it compares a hash stored in `hamista_account_endpoints_hash`).

### 4.5 Settings framework

Tabs are declared as arrays. Each tab names the option it stores in, so a plugin reads its own option without depending on the framework at runtime.

```php
$settings->add_tab( [
  'id' => 'header', 'title' => __( 'Header', 'hamista' ), 'icon' => 'layout', 'group' => 'theme',
  'option' => 'hamista_theme', 'priority' => 20,
  'sections' => [ [ 'id' => 'layout', 'title' => '…', 'description' => '…', 'fields' => [
      [ 'id' => 'header_sticky', 'type' => 'toggle', 'label' => '…', 'default' => true,
        'description' => '…', 'show_if' => [ 'field' => 'header_enabled', 'value' => true ] ],
  ] ] ],
] );
```

- **Field types:** `text`, `textarea`, `url`, `email`, `tel`, `number` (min/max/step), `toggle`, `select`, `radio`, `choice` (visual cards), `color`, `image` (attachment ID via `wp.media`), `page` (page dropdown), `code` (CSS/HTML), `repeater` (`fields` sub-schema, add/remove/reorder), `sortable` (ordered list of toggles), `multicheck`, `html` (static info).
- **Groups (navigation order):**
  - `theme` — General, Colors, Typography, Layout, Header, Footer, Home, Blog, Pages, Shop
  - `platform` — Modules, Services, Portfolio, Licenses, Dashboard, Service Orders, Forms
  - `advanced` — Performance, Security, SEO, Integrations, Custom Code, Import/Export, System Status
- **Save:** `admin-post.php?action=hamista_save_settings` with a nonce and the `manage_options` capability. Only the submitted tab's fields are merged into its option; every field is sanitized by type. The request then redirects back with a notice.
- **Import/Export:** JSON of every registered option. On import, keys are validated against the schema and sanitized. A tab can also be reset to defaults.
- **Storage:**

  | Option | Owner |
  |---|---|
  | `hamista_theme` | theme |
  | `hamista_core` | core |
  | `hamista_modules` | module toggles |
  | `hamista_licenses` | license manager |
  | `hamista_dashboard` | customer dashboard |
  | `hamista_service_orders` | service orders |

### 4.6 Modules

```php
interface Module {
    public function id(): string;
    public function title(): string;
    public function description(): string;
    public function group(): string;          // content | commerce | integrations | performance | security | tools
    public function default_enabled(): bool;
    public function requires(): array;        // e.g. [ 'woocommerce', 'elementor' ] — unmet ⇒ not booted, shown as unavailable
    public function boot(): void;             // registers hooks; only called when enabled + requirements met
}
```

Satellite plugins register their modules on `hamista_register_modules`. Every module appears on the Modules tab with a toggle.

### 4.7 Components

`hamista_render( $component, $args )` loads `templates/components/{component}.php` from core. A theme can override it at `{theme}/hamista-core/components/{component}.php`. The renderer also enqueues the style handle `hamista-c-{component}`, if registered.

The same component is exposed three ways:

- an Elementor widget (`hamista-{component}`);
- a shortcode `[hamista_{component}]` (underscored);
- the theme's non-Elementor home sections.

Components:

| Component | What it shows |
|---|---|
| `hero` | badge, title with highlighted words, subtitle, 2 buttons, visual (image or built-in SVG composition), stats, logos; layouts `split` / `center` |
| `services` | `hamista_service` query: count, category, columns, style, price-from, features |
| `products` | WooCommerce products: count, category, orderby (latest / popular / rating / price), featured, columns, version badge |
| `product-categories` | marketplace category cards |
| `pricing` | plans from a service, or custom plans; highlighted plan; order buttons |
| `portfolio` | portfolio query, filter tabs, columns |
| `faq` | native `<details>` accordion from the FAQ group or custom items; adds FAQPage schema |
| `testimonials` | testimonial cards |
| `cta`, `stats`, `process`, `features`, `logos`, `posts` | as named |
| `contact-form` | the contact form |

Query-backed components cache their HTML fragments in transients. The cache key covers the args, the locale and a group "last changed" value (`wp_cache_get_last_changed`-style counter, bumped when a relevant post is saved).

### 4.8 Icons

Core `Support\Icons` holds 24×24 stroke icons from Lucide (ISC licence) and social icons from Simple Icons (CC0). Output is inline `<svg class="hm-icon" aria-hidden="true">` with a `currentColor` stroke. The theme has its own minimal UI icon set, so it works without core.

## 5. Theme `hamista`

- **Type:** classic PHP theme, no `theme.json`. Supports: `title-tag`, `post-thumbnails`, `custom-logo`, `html5`, `responsive-embeds`, `align-wide`, `editor-styles`, `woocommerce` (+ gallery zoom/lightbox/slider **off** for performance), `hamista-templates`.
- **Menus:** `primary`, `mobile`, `footer-1..3`, `footer-bottom`, `account`.
- **Sidebars:** `blog`, `shop`, `footer-1..4`.
- **Image sizes:** `hamista-card` 720×450 crop, `hamista-hero` 1440×1080, `hamista-portfolio` 960×720, `hamista-square` 480×480.
- **Templates:** `header.php`, `footer.php`, `index.php`, `front-page.php`, `home.php`, `single.php`, `page.php`, `archive.php`, `search.php`, `404.php`, `comments.php`, `searchform.php`, plus:
  - `single-hamista_service.php`, `archive-hamista_service.php`, `taxonomy-hamista_service_cat.php`
  - `single-hamista_portfolio.php`, `archive-hamista_portfolio.php`
  - `page-templates/template-full-width.php`, `page-templates/template-canvas.php` (Elementor landing pages, no header/footer)
  - `template-parts/{header,footer,content,home,blog,shop}/…`
- **No WooCommerce template overrides.** All WooCommerce customisation is done through hooks and CSS.
- **Front page:** if built with Elementor, render the Elementor content. Otherwise render the enabled Home sections in the order from settings. Sections: `hero`, `logos`, `services`, `categories`, `products`, `stats`, `process`, `pricing`, `portfolio`, `testimonials`, `faq`, `posts`, `cta`.
- **Header:** logo (light/dark/mobile), primary menu with accessible dropdowns, and actions:
  - search overlay;
  - colour-mode toggle;
  - language switcher (only when WPML/Polylang is active);
  - cart link with count;
  - account link;
  - notifications bell (logged-in users, dashboard plugin);
  - CTA button.

  It also has an optional announcement top bar, a sticky + glass header, and a mobile drawer (`<dialog>`-free, a focus-trapped panel).
- **Footer:** columns (1–4) with brand blurb, menus, contact info, social links, trust-badge HTML (e-Namad / Samandehi snippet field), copyright with `{year}` / `{site}` placeholders, bottom menu, and back-to-top.
- **Colour mode:**
  - `data-theme` on `<html>`, set by an inline head script (< 400 bytes) before paint;
  - modes `light` (default) / `dark` / `system`;
  - the visitor's choice is stored in `localStorage['hamista-color-mode']` (toggle);
  - `meta[name=theme-color]` is set per mode;
  - separate dark logo.
- **Fonts:** Vazirmatn-NL variable (Arabic script, 48 KB) + Inter variable Latin (48 KB). Both use `unicode-range`, `font-display: swap`, and preload the primary font for the current direction. Option: system fonts.
- **Assets:**
  - `main.css` — tokens, base, layout, UI primitives, header, footer; target ≤ 30 KB minified
  - `blog.css`, `shop.css`, `pages.css` — loaded conditionally
  - `main.js` — nav, colour mode, sticky header, reveal-on-scroll (IntersectionObserver, respects `prefers-reduced-motion`), back-to-top, cart count; target ≤ 6 KB minified, `defer`
- **Performance (theme side):**
  - dequeue WooCommerce CSS everywhere (the theme styles WooCommerce);
  - dequeue WooCommerce JS, order-attribution JS and `wc-blocks` styles on non-WooCommerce pages;
  - replace cart fragments with a cookie-gated `fetch('/wp-json/hamista/v1/cart')`;
  - drop block-library/global styles on views without blocks;
  - hero image preload + `fetchpriority=high`;
  - `should_load_separate_core_block_assets`.
- **Settings tabs (group `theme`, option `hamista_theme`):**
  - General / Brand — logos, logo width, brand text, contact info, social links (repeater)
  - Colors — brand stops, light/dark palettes
  - Typography — font choice, base size, Persian digits
  - Layout — container width, radius scale, card style, button style, animations
  - Header — every element toggleable plus CTA and top bar
  - Footer
  - Home — sortable sections, per-section content
  - Blog — layout, sidebar, meta toggles, author box, related posts, share buttons (Telegram / WhatsApp / X / LinkedIn / copy), reading progress, comments
  - Pages — title bar style, breadcrumbs, 404 texts
  - Shop — columns, per page, card options, sticky add-to-cart, simplified checkout for virtual carts, phone required / company hidden / order notes toggles

## 6. Plugin `hamista-core`

Modules (id → purpose):

| Module | Purpose |
|---|---|
| `services` | CPT `hamista_service` (archive `services`) + taxonomy `hamista_service_cat`. Meta: `_hamista_icon`, `_hamista_subtitle`, `_hamista_features` (string[]), `_hamista_plans` (see below), `_hamista_questionnaire` (field[]), `_hamista_process` (step[]), `_hamista_faq` (qa[]), `_hamista_price_from` (int, computed). Tabbed meta box with repeaters. |
| `portfolio` | CPT `hamista_portfolio` (archive `portfolio`) + `hamista_portfolio_cat`. Meta: client, year, project URL, related services, gallery (attachment IDs), results (label/value[]), accent colour. |
| `faq` | CPT `hamista_faq` (not publicly queryable) + `hamista_faq_group`. |
| `testimonials` | CPT `hamista_testimonial` (not public). Meta: name, role, company, rating. |
| `marketplace` (requires WooCommerce) | Product data tab "Hamista". Meta: `_hamista_product_kind` (plugin / theme / software / digital), `_hamista_version`, `_hamista_last_updated`, `_hamista_changelog` (entry[]), `_hamista_docs_url`, `_hamista_docs` (HTML), `_hamista_demo_url`, `_hamista_requirements`, `_hamista_faq`, `_hamista_highlights`, `_hamista_support_months`. Adds product tabs (Changelog, Documentation, FAQ, Requirements), a summary box near add-to-cart, structured-data enrichment, and listens to `hamista_release_published` to sync version, date and changelog. |
| `components` | Component renderer, shortcodes and their style/script registration. |
| `elementor` (requires Elementor) | Widget category "Hamista" with one widget per component. Registers theme locations for Elementor Pro. Optional "optimise Elementor" (disable Google Fonts, `font-display: swap`, inline SVG icons experiment). |
| `forms` | `[hamista_contact_form]` and widget. Honeypot + time-trap + rate limit + nonce, refreshed through `GET /hamista/v1/form-token` for cached pages. Stores messages in CPT `hamista_message` (private); emails recipients. |
| `seo` | Schema: Organization/WebSite/BreadcrumbList/BlogPosting/Service/FAQPage. Goes through Rank Math's `rank_math/json_ld` when Rank Math is active; auto-disables overlapping output when Yoast/SEOPress/AIOSEO is active. Open Graph fallback when no SEO plugin is active. |
| `multilingual` | `wpml-config.xml`; Polylang post type / taxonomy registration and `pll_register_string` for text options; language switcher helper. |
| `jalali` | Solar Hijri calendar via the `wp_date` filter (frontend and optionally admin), Persian digits for dates and prices. |
| `security` | Security headers, XML-RPC off, user-enumeration block, generic login errors, login rate limit, hide WordPress version, optional file-editor lock. |
| `performance` | Emoji, embeds, heartbeat control, jQuery Migrate removal, dashicons for visitors, speculation-rules mode, WebP output for uploads, big-image threshold, local initials avatars instead of Gravatar, DNS prefetch list. |
| `admin-dashboard` | Top-level menu "Hamista". **Overview:** KPI cards, 30-day revenue SVG chart (products vs services), recent orders, service orders, tickets, top products. **Revenue:** range presets and custom range, day/month table, CSV export. **Customers:** list table plus a profile page with sections from other plugins through `hamista_customer_profile_sections`. Aggregates use SQL on HPOS/posts tables, cached in transients and invalidated on order status change. |
| `demo` | Demo Import page (see §10). |
| `system-status` | Requirement checks: versions, permalinks, private-storage protection test, cron, loopback. |

Services data schemas:

- **Plan:** `{ id, name, price:int(IRT), price_label, description, delivery_days:int, revisions:int, features:string[], highlighted:bool, orderable:bool, cta_label }`
- **Questionnaire field:** `{ id, label, type: text|textarea|email|url|tel|number|select|radio|checkbox|file|date|color, required:bool, options:string[], placeholder, help }`

Core also carries these support classes:

- `Support\Private_Storage` — bucket dirs under `uploads/hamista-private/{bucket}`, or the `HAMISTA_PRIVATE_STORAGE_PATH` constant; `.htaccess`, `web.config` and `index.php` guards; random file names; `store_upload()`, `path()`, `send()`, `delete()`
- `Support\Mailer`
- `Support\Jalali`
- `Support\Request` (client IP)
- `Support\Rate_Limiter` (transients)
- `Support\Icons`
- `Support\Assets` (min/debug switch, register helpers)
- `Support\Html` (attribute builder, kses)

## 7. Plugin `hamista-license-manager`

### Tables

`hamista_licenses`
- **Columns:** `id`, `license_key` (unique), `product_id`, `variation_id`, `order_id`, `order_item_id`, `user_id`, `status` (active / inactive / expired / revoked), `activation_limit` (0 = unlimited), `activation_count`, `expires_at` (NULL = lifetime), `created_at`, `updated_at`, `notes`
- **Keys:** `user_id`, `product_id`, `order_id`, (`status`, `expires_at`)

`hamista_license_activations`
- **Columns:** `id`, `license_id`, `domain`, `instance_id`, `is_local`, `ip_address`, `client_version`, `activated_at`, `last_checked_at`
- **Keys:** unique (`license_id`, `domain`)

`hamista_releases`
- **Columns:** `id`, `product_id`, `version`, `channel` (stable / beta), `status` (draft / published), `changelog`, `file_name`, `file_size`, `file_hash` (sha256), `requires_wp`, `tested_wp`, `requires_php`, `download_count`, `released_at`, `created_at`
- **Keys:** unique (`product_id`, `version`, `channel`)

`hamista_download_log`
- **Columns:** `id`, `release_id`, `product_id`, `license_id`, `user_id`, `source` (dashboard / api), `ip_address`, `created_at`

### Product settings

Product data tab "License" (variation-level overrides for the activation limit):

- `_hamista_lm_enabled`
- `_hamista_lm_activation_limit`
- `_hamista_lm_validity_days` (0 = lifetime)
- `_hamista_lm_slug`
- `_hamista_lm_type` (plugin / theme / software)

### Services

- **`Key_Generator`** — format `{PREFIX}-XXXXX-XXXXX-XXXXX-XXXXX`, with prefix `HMST` by default. It uses Crockford base32 (`0123456789ABCDEFGHJKMNPQRSTVWXYZ`) and `random_int`, which gives 100 bits of entropy. It retries on collision.
- **`Domain::normalize()`** — lower-cases; strips the scheme, userinfo, port, path, query, `www.` and trailing dots; converts IDN to punycode; returns `''` when invalid.
- **`Domain::is_local()`** covers:
  - `localhost`;
  - loopback and private IPs;
  - `.local`, `.test`, `.localhost`, `.invalid`, `.example`;
  - `staging.`, `dev.`, `test.` and `local.` subdomains;
  - configurable patterns.

  Local activations don't count toward the limit when that setting is on.
- **`License_Service`**
  - Methods: `activate`, `deactivate`, `check`, `issue_for_order_item`, `revoke_for_order`, `renew`, `set_status`. All of them return a `Result { bool success, string code, string message, array data }`.
  - Error codes: `invalid_request`, `license_not_found`, `license_revoked`, `license_inactive`, `license_expired`, `activation_limit_reached`, `invalid_domain`, `not_activated`, `product_mismatch`, `rate_limited`, `api_disabled`.
- **`Download_Service`**
  - The token is a base64url `HMAC-SHA256(release_id|license_id|user_id|expires)`, keyed with the plugin secret plus `wp_salt('auth')`.
  - The license and ownership are re-validated when the file is served.
  - Files are streamed in chunks; `X-Sendfile` / `X-Accel-Redirect` modes are optional.
  - Every download is logged and increments the release counter.

### REST API — `hamista-license/v1`

These routes are public and authenticated by the license key. Each one is rate limited per IP, with a stricter limit on failed key lookups.

| Route | Parameters | Returns |
|---|---|---|
| `POST /activate` | `license_key`, `domain`, `product_id` or `slug`, `instance`?, `version`? | |
| `POST /deactivate` | same as `/activate` | |
| `POST /check` | `license_key`, `domain`, `product_id` or `slug` | license status + `activated` flag |
| `POST /update` | `license_key`, `domain`, `slug` or `product_id`, `version`, `channel`? | WordPress-compatible: `new_version`, `package` (signed URL), `requires`, `tested`, `requires_php`, `url`, `sections` |
| `GET /info` | `slug`, `license_key`?, `domain`? | `plugins_api`-style information object |

- Successful responses: `{ success: true, code, message, license: { status, expires_at, activation_limit, activation_count, product_id }, … }`.
- Error responses: HTTP 4xx with `{ success: false, code, message }`.
- Optional Ed25519 signature header `X-Hamista-Signature` (sodium; the key pair lives in an option and the public key is shown in settings).
- Downloads are served from `/?hamista-download={token}`, handled on `init`.

### Other parts

- **WooCommerce:**
  - issue licenses when the order reaches processing or completed (configurable; idempotent through the order-item meta `_hamista_license_ids`);
  - show keys on the thank-you page, in order details and in emails;
  - revoke on refund or cancellation (configurable);
  - add latest-release downloads to WooCommerce "Downloads" through `woocommerce_customer_available_downloads`.
- **Releases:** publishing a release fires `do_action( 'hamista_release_published', array $release )`. Customers with active licenses for that product are notified in batches through Action Scheduler (`hamista_lm_notify_release`), falling back to WP-Cron.
- **Admin pages** (under the Hamista menu):
  - Licenses — list table: search, filters, bulk actions; add/edit with the activations list;
  - Releases — upload to private storage, validate the version, publish/draft;
  - Download log.
- **Settings tab** `hamista_licenses`:
  - key prefix, default limit, default validity;
  - local-domain rules;
  - issue-on status, revoke rules, keys in emails;
  - API on/off, rate limit, token TTL, response signing;
  - send-file mode, allowed extensions, maximum upload size;
  - delete data on uninstall.
- **My Account:** `licenses` endpoint:
  - cards with a copyable key, status, expiry and `used / limit` activations;
  - the domain list with deactivate (nonce POST);
  - download latest version.
- **SDK:** `sdk/hamista-license-client.php` — a single-file drop-in class for the plugins and themes being sold:
  - license settings page;
  - activate/deactivate;
  - daily check;
  - update integration (`pre_set_site_transient_update_plugins` / `update_themes` and `plugins_api`);
  - optional signature verification.

## 8. Plugin `hamista-customer-dashboard` (requires WooCommerce + core)

- **Shell:**
  - replaces the WooCommerce account navigation with a grouped sidebar that has icons and badges (a horizontal scroller on mobile);
  - replaces the default dashboard content with an **Overview**: welcome header; stat cards from every endpoint's `overview` callback; product updates available; recent orders; recent tickets; quick actions.
- **Endpoints:**
  - `invoices` — the list, plus `invoice/{order_id}`: a printable Jalali invoice with the seller's legal details, an owner check and a print/PDF button through browser print;
  - `tickets` — list, new, view/reply/close; attachments stored privately;
  - `notifications` — list, mark read, mark all read;
  - profile additions: mobile phone (Iran format validated), company and website on edit-account;
  - Downloads and Orders keep WooCommerce's own endpoints, restyled.
- **Tables:**
  - `hamista_tickets`: `id`, `user_id`, `subject`, `department`, `priority` (low / normal / high / urgent), `status` (open / answered / customer-reply / on-hold / closed), `order_id`, `product_id`, `license_id`, `assigned_to`, `last_reply_at`, `last_reply_by`, `created_at`, `updated_at`
  - `hamista_ticket_replies`: `id`, `ticket_id`, `user_id`, `is_staff`, `message`, `attachments` (JSON), `created_at`
  - `hamista_notifications`: `id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`
- **Admin:** "Tickets" page — status tabs with counts, search, filters, thread view with reply, and status/priority/department/assignee changes. There is also an invoice-print button on the order screen.
- **Settings** (`hamista_dashboard`):
  - section toggles;
  - departments (repeater) and priorities;
  - attachments: types and maximum size;
  - auto-close after N days (cron);
  - email toggles;
  - invoice seller details: name, economic code, national ID, registration number, address, postal code, phone, logo, prefix, footer note;
  - welcome text and quick links.
- **Notifications:** stores `hamista_notify` events. The header bell reads the unread count for logged-in users.

## 9. Plugin `hamista-service-orders` (requires core; WooCommerce optional)

- **Data:**
  - private CPT `hamista_svc_order` with statuses `hm-pending-payment`, `hm-new`, `hm-in-progress`, `hm-awaiting-client`, `hm-delivered`, `hm-completed`, `hm-cancelled`;
  - meta `_hm_customer_id`, `_hm_service_id`, `_hm_plan` (snapshot), `_hm_answers` (snapshot with labels), `_hm_files`, `_hm_total`, `_hm_wc_order_id`, `_hm_paid_at`, `_hm_due_date`;
  - table `hamista_service_notes`: `id`, `order_id`, `user_id`, `type` (note / message / status / file), `content`, `attachments`, `created_at`;
  - order number `{prefix}{ID}`, with `SO-` as the default prefix.
- **Flow:**
  1. The service plans show an **Order** button that links to the order page `?service=ID&plan=PLAN`. The page is auto-created with `[hamista_service_order_form]` and can be picked in settings.
  2. The form shows a plan summary (the plan can be switched), then the questionnaire, file uploads, the preferred deadline and notes, and the terms checkbox.
  3. It is validated server-side (nonce, honeypot, rate limit, field types, file checks). Login is required: the form prompts login/register.
  4. A paid plan with WooCommerce active becomes a pending-payment order. A hidden virtual product "Service order", created on activation, is added to the cart with `hamista_service_order_id` cart data. The price is taken from the stored order, never from the client. The customer is then redirected to checkout.
  5. On payment the order becomes `hm-new` and is linked to the WooCommerce order. Quote-only or free plans go straight to `hm-new`.
  6. When the service order completes, the WooCommerce order can be completed automatically. WooCommerce cancellation or refund cancels the service order (configurable).
- **Customer (`service-orders` endpoint):**
  - list;
  - detail view: status stepper, plan, answers, files, message thread with attachments;
  - "Pay now" for pending payment;
  - "Approve delivery" when delivered;
  - cancel while pending or new.
- **Admin:** list columns and status views; edit screen with meta boxes (overview, answers, files with staff upload, timeline with message/private-note toggle, status box with a notify toggle); emails on status change and messages.
- **Settings** (`hamista_service_orders`):
  - order page, require payment, number prefix, terms page;
  - uploads: on/off, types, maximum MB, maximum files;
  - customer approval, auto-complete of the WooCommerce order, cancel sync;
  - email toggles and admin recipients.

## 10. Demo content and Elementor templates

- The Demo Import page (core) offers checkbox groups:
  - pages (Home, About, Services, Products → shop, Marketplace, Branding Services, Portfolio, Blog, Contact, Order Service, Terms);
  - menus;
  - 7 services (Logo Design, Brand Book Design, Business Consulting, Website Development, Plugin Development, Social Media Services, Software Development), each with 3 plans, a questionnaire, a process and FAQ;
  - 6 portfolio items, 10 FAQs, 6 testimonials, 4 blog posts;
  - 12 WooCommerce products across Plugins / Themes / Software / Digital Products, with versions, changelogs and license settings, and a generated placeholder release ZIP so the download flow works;
  - WooCommerce configuration (IRT, classic cart/checkout pages, account creation at checkout);
  - theme settings defaults;
  - Elementor layouts, when Elementor is active.
- The import is idempotent: created IDs are tracked in `hamista_demo_items`, and a "Remove demo content" button undoes it. Images are generated locally with GD as brand-gradient placeholders.
- Content is Persian (primary). The Elementor JSON templates for home, about, services, marketplace, branding, portfolio, contact and pricing are generated from PHP definitions (single source) into `hamista-core/demo/elementor/` and `demo/elementor-templates/`.

## 11. Testing and verification

- **Unit (plain PHP, no WordPress):** Jalali conversion (known Nowruz dates), key generator, domain normalize/local, token sign/verify/expiry, version compare, schema sanitizers.
- **Integration** (local WordPress 7.1.2 + SQLite + WooCommerce 11.3, via WP-CLI `eval-file` and HTTP):
  - activation of all packages;
  - demo import and removal;
  - license issue → activate/check/update/deactivate over real HTTP → signed download with hash match;
  - ticket lifecycle;
  - service order → cart → order → status sync;
  - notifications;
  - settings save/import/export;
  - **IDOR checks** (a customer cannot read another customer's tickets, licenses, invoices or files);
  - nonce / capability rejection;
  - PHP uploads rejected.
- **Static:** `php -l`; PHPCS (WordPress-Extra + security sniffs; PHPCompatibility for 8.1+) where the tooling can be installed.
- **Visual:** Playwright/Chromium screenshots of the key pages: fa_IR RTL and en_US LTR, light and dark, 390px and 1440px.
- **Performance:** Lighthouse (mobile profile) on home, service, shop, product and blog post. The results go into `docs/performance-report.md`.

## 12. Deliverables

- `dist/`: `hamista-theme-1.0.0.zip`, `hamista-core-1.0.0.zip`, `hamista-license-manager-1.0.0.zip`, `hamista-customer-dashboard-1.0.0.zip`, `hamista-service-orders-1.0.0.zip`, `hamista-elementor-templates-1.0.0.zip`, `hamista-complete-1.0.0.zip`
- Persian docs in `docs/fa/`: installation guide, user guide (settings reference)
- English docs in `docs/en/`: developer documentation (architecture, hooks, APIs), License API reference and SDK guide
- Reports: `docs/security.md`, `docs/performance-report.md`

## 13. Out of scope

- Payment gateway plugins (Zarinpal etc.) — installed separately.
- Elementor Pro Theme Builder templates — Elementor Pro locations are supported.
- Subscriptions and automatic license renewal billing — manual renewal is supported.
- An English demo dataset.

## 14. Implementation conventions (binding for every package)

**Bootstrap (every plugin)**
- The main file defines constants:
  - core: `HAMISTA_CORE_{VERSION,FILE,PATH,URL}`
  - license manager: `HAMISTA_LM_*`
  - customer dashboard: `HAMISTA_DASHBOARD_*`
  - service orders: `HAMISTA_SO_*`
  - theme: `HAMISTA_THEME_{VERSION,DIR,URI}`
- It requires `includes/class-autoloader.php` and registers the package namespace.
- Activation and deactivation are hooked to `Installer`.
- Boot timing:
  - core boots on `plugins_loaded` priority 5 and fires `hamista_core_loaded`;
  - a satellite boots on `plugins_loaded` priority 20. If `function_exists( 'hamista_core' )` is false, it only shows an admin notice.
- Satellites register their features as modules on `hamista_register_modules`.

**Autoloader:** `Autoloader::register( string $prefix, string $base_dir )`.
- Namespace segments after the prefix become lower-case directories, with `_` turned into `-`.
- The class short name is lower-cased with `_` turned into `-`.
- Candidate files, tried in order: `class-{slug}.php`, `interface-{slug}.php`, `trait-{slug}.php`.
- Example: `Hamista\Core\Modules\Abstract_Module` → `includes/modules/class-abstract-module.php`.

**Plugin layout:**
- `{slug}.php`, `uninstall.php`, `readme.txt`
- `includes/` — classes; `functions.php` for global helpers
- `templates/` — theme-overridable at `{theme}/{plugin-slug}/…`
- `assets/{css,js,admin,images}/`
- `languages/`
- `tests/{unit,integration}/` — excluded from ZIPs

**Templates:** use the core helpers:
- `hamista_locate_template( string $plugin_slug, string $template, string $default_dir ): string`
- `hamista_get_template( string $plugin_slug, string $template, array $args, string $default_dir ): void`

Both check `{child-theme}/{plugin_slug}/{template}`, then `{parent-theme}/…`, then the default. `$args` is passed as a variable called `$args`; never use `extract()`.

**Options and defaults**
- Each package declares cheap, untranslated defaults at load time with `hamista_register_option_defaults( string $option, array $defaults )`.
- `hamista_get_option()` returns the saved value, else the registered default, else the `$default` argument.
- Settings tabs, which carry translated labels, are built **only** in wp-admin on the settings screen. A field without `default` falls back to the registered default.
- The theme reads its option through `Hamista\Theme\Options::get()` with its own defaults array, so it works without core. It also registers those same defaults with core when core is present.

**Translations timing:**
- No `__()` before `init`. `Module::title()` and `description()` are called lazily, only in admin.
- Plugins call `load_plugin_textdomain()` on `init`. The theme calls `load_theme_textdomain()` on `after_setup_theme`.

**Hook callbacks:**
- Public methods used as WordPress hook callbacks take **untyped / `mixed`** parameters and cast inside. WordPress passes strings, nulls and objects unpredictably.
- Internal methods are fully typed.
- No `declare(strict_types=1)` in files that hold hook callbacks.

**Capabilities** (granted on activation to `administrator`, plus `shop_manager` when present):

| Capability | Package |
|---|---|
| `hamista_view_reports` | core |
| `hamista_manage_licenses` | license manager |
| `hamista_manage_tickets` | customer dashboard |
| `hamista_manage_service_orders` | service orders |

Settings require `manage_options`.

**Admin menu:** core registers the top-level `hamista` menu on `admin_menu` priority 9. Its landing page is Overview, or Settings when the admin-dashboard module is off. Satellites add submenus at priority 20.

| Slug | Position |
|---|---|
| `hamista` (Overview) | 0 |
| `edit.php?post_type=hamista_svc_order` | 10 |
| `hamista-tickets` | 20 |
| `hamista-licenses` | 30 |
| `hamista-releases` | 31 |
| `hamista-downloads` | 32 |
| `hamista-customers` | 40 |
| `hamista-revenue` | 41 |
| messages (`edit.php?post_type=hamista_message`) | 50 |
| `hamista-settings` | 90 |
| `hamista-demo` | 95 |
| `hamista-status` | 96 |

Content CPTs (services, portfolio, FAQ, testimonials) keep their own top-level menus with a `menu_position` around 25–28.

**Admin UI**
- Style/script handle `hamista-admin`, from core. It covers the page header, cards, badges and list-table polish, and is RTL-aware.
- Every Hamista admin page starts with `hamista_admin_header( string $title, array $args = [] )`, where args are `subtitle` and `actions` (a list of `{label,url,primary}`).

**Frontend forms**
- The form POSTs to the current URL. It includes `hamista_action` and a nonce field named `_hamista_nonce` whose action is bound to the object ID (for example `hamista_ticket_reply_{id}`).
- It is handled on `template_redirect` by the owning package, following Post/Redirect/Get.
- Feedback uses `wc_add_notice()` inside My Account, and the core flash helper elsewhere: `hamista_flash( string $message, string $type = 'success' )` and `hamista_flash_messages(): array`. Flash messages are stored per user, or per guest cookie token, in a short transient.
- Forms on cacheable public pages refresh their nonce from `GET /wp-json/hamista/v1/form-token?action=…` before submitting.

**REST:**
- Core and theme utility routes use `hamista/v1`; the license API uses `hamista-license/v1`.
- Responses carrying per-user data or nonces send `Cache-Control: no-store`.

**Private files:** a file record is `{ name, path (relative to bucket), size, mime, uploaded_at }`. Rules:
- The owning package checks permissions first, then calls `Private_Storage::send( $record )`.
- Download URLs carry an object-bound nonce.
- Never expose `path` in HTML.

**Emails:** `hamista_mail( $to, $subject, [ 'heading', 'body' (HTML), 'button' => [ 'text', 'url' ], 'footer' ] )`. It renders `templates/emails/base.php`, which is RTL-aware, and can be overridden by a theme.

**Frontend UI:** plugin screens use only the §4.2 primitives, the §4.1 tokens and core icons. Each package's own CSS is limited to its layout and is enqueued only on its screens. JS is vanilla and deferred. Runtime config comes from `wp_add_inline_script( handle, 'window.hamistaX = …', 'before' )`.

**Database:**
- Tables are created with `dbDelta`.
- Each package stores its schema version in `hamista_{pkg}_db_version` and re-runs its installer on `plugins_loaded` when the version differs.
- Dates are stored as UTC `datetime`.
- `uninstall.php` drops tables and options only when the package's "delete data on uninstall" setting is on.

**Tests**
- **Unit tests:** `tests/unit/*Test.php` files define `test_*` functions. They use the assertion helpers from `tools/tests/bootstrap.php` (`assert_same`, `assert_true`, `assert_false`, `assert_contains`, `assert_throws`) and run with `php tools/tests/run.php [path]` — no WordPress.
- **Integration tests:** `tests/integration/*.php` run inside WordPress through `tools/tests/run-integration.sh [path]`, which calls `$ENV/wp eval-file` with `tools/tests/wp-bootstrap.php`. That bootstrap adds the same assertions plus `hm_http( method, path, args )` for real HTTP calls against `http://127.0.0.1:8080`.
- Tests create their own uniquely-named fixtures and delete them. They never reset the shared database.

**Commits:** conventional-commit subjects scoped by package, for example `feat(core): …` or `feat(theme): …`. Stage **only your package's paths** — other lanes commit to the same branch concurrently. Every message ends with the two trailer lines given in the task brief.

**Third-party assets:** Vazirmatn and Inter (OFL-1.1) go in the theme's `assets/fonts/` with their licence files. Lucide (ISC) and Simple Icons (CC0) are credited in core `assets/icons/CREDITS.md`.
