=== Hamista License Manager ===
Contributors: hamista
Tags: license, woocommerce, software, updates, persian
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: hamista-core
WC requires at least: 8.0
WC tested up to: 11.3
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

License keys, activations, domain control, releases and a secure update API for software sold on WooCommerce.

== Description ==

Hamista License Manager turns WooCommerce products into licensed software sales:

* License keys (Crockford base32), issued automatically from WooCommerce orders.
* Per-domain activation with a configurable limit and free local/staging domains.
* Product- and variation-level activation limit and validity overrides.
* Order-based issuing (on processing or completed) and revoking (on refund or cancellation).
* Keys shown on the thank-you page, in My Account and in order emails.
* Releases and a secure update API for the software (later releases of this plugin).

Requires the Hamista Core plugin and WooCommerce.

== Installation ==

1. Upload the `hamista-license-manager` folder to `/wp-content/plugins/`.
2. Activate "Hamista Core" first, then "Hamista License Manager".
3. Open Hamista → Licenses in wp-admin to configure key prefix, activation
   limits, validity, WooCommerce issuing and revoking rules.
4. On a product's "License" tab, turn on "Sells a license" and set its type
   and slug.

== Changelog ==

= 1.0.0 =
* Foundation: bootstrap, tables, capability, plugin secret and private
  "releases" storage bucket.
* License domain layer: Key_Generator, Domain, Version, Result.
* Data layer: License, Activation, Release and Download log repositories.
* License_Service: activate, deactivate, check, issue_for_order_item,
  revoke_for_order, renew, set_status.
* WooCommerce integration: product/variation license fields, order issuing
  and revoking, and key display on the thank-you page, My Account and emails.
