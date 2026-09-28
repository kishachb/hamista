=== Hamista Core ===
Contributors: hamista
Tags: persian, rtl, woocommerce, jalali, toolkit
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Foundation of the HAMISTA platform: settings, modules, components and shared services.

== Description ==

Hamista Core is the base plugin of the HAMISTA platform (theme, License Manager,
Customer Dashboard and Service Orders). It provides:

* A module system: every feature can be switched on or off.
* Shared services: Jalali (Solar Hijri) dates, Persian digits, branded RTL-aware
  emails, private file storage, rate limiting, client IP detection and
  WPML/Polylang helpers.
* WooCommerce My Account endpoint registration for the other Hamista plugins.
* Inline SVG icons (Lucide and Simple Icons) and shared UI behaviours.
* The Hamista admin menu and admin design system.

Persian (fa_IR, right-to-left) is the primary language; English is fully supported.

== Installation ==

1. Upload the `hamista-core` folder to `/wp-content/plugins/`.
2. Activate "Hamista Core" in Plugins.
3. Open the "Hamista" menu in wp-admin.

On nginx, the private storage guard files have no effect. Define
`HAMISTA_PRIVATE_STORAGE_PATH` in wp-config.php to a directory outside the web
root, or deny `/wp-content/uploads/hamista-private/` in the server configuration.

== Changelog ==

= 1.0.0 =
* First release.

== Credits ==

* Lucide icons (ISC; Feather-derived icons MIT) and Simple Icons (CC0). See `assets/icons/CREDITS.md`.
* Jalali conversion follows jalaali-js (MIT) by Behrang Noruzi Niya, after Kazimierz M. Borkowski.
