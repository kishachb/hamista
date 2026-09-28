=== Hamista Customer Dashboard ===
Contributors: hamista
Tags: woocommerce, my-account, tickets, invoices, notifications
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
WC requires at least: 8.0
WC tested up to: 11.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium customer panel on WooCommerce My Account: overview, licenses, invoices, support tickets and notifications.

== Description ==

Requires `hamista-core` and WooCommerce. Replaces the WooCommerce My Account
navigation with a grouped, icon-and-badge sidebar and the default dashboard
with an Overview screen (welcome header, stat cards, recent orders and
notifications, quick actions). Adds mobile phone, company and website fields
to the customer's profile, and a full notification center (`notifications`
My Account endpoint, header-bell helpers, a daily cleanup cron).

Support tickets and invoices are declared as modules and settings now; their
My Account endpoints and admin screens are implemented in follow-up tasks.

== Layout ==

* `includes/` — classes (`Hamista\Dashboard\...`); `functions.php` for the
  public API (`hamista_dashboard_unread_count()`, `hamista_dashboard_notifications_url()`).
* `templates/myaccount/` — `navigation.php`, `overview.php`, `notifications.php`;
  each is theme-overridable at `{theme}/hamista-customer-dashboard/myaccount/...`.
* `assets/{css,js}/` — `dashboard.css` and `dashboard.js`, enqueued only on
  `is_account_page()`.
* `tests/{unit,integration}/` — excluded from release ZIPs.

== Database ==

Three tables (`{$wpdb->prefix}hamista_tickets`, `hamista_ticket_replies`,
`hamista_notifications`), created with `dbDelta()` and versioned in the
`hamista_dashboard_db_version` option. Nothing is dropped on uninstall unless
"Delete data on uninstall" is switched on in Settings → Customer Dashboard.

== Changelog ==

= 1.0.0 =
* Plugin foundation, WooCommerce My Account shell, overview, profile fields
  and notifications (task D1). Tickets and invoices are stubs, filled in by
  tasks D2 and D3.
