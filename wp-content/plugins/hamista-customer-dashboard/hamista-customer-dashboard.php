<?php
/**
 * Plugin Name:          Hamista Customer Dashboard
 * Plugin URI:           https://hamista.ir
 * Description:          A premium customer panel on WooCommerce My Account: overview, licenses, invoices, support tickets and notifications.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce, hamista-core
 * Author:               HAMISTA
 * Author URI:           https://hamista.ir
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          hamista-customer-dashboard
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.3
 *
 * @package Hamista\Dashboard
 */

defined( 'ABSPATH' ) || exit;

// Another copy of the plugin is already loaded.
if ( defined( 'HAMISTA_DASHBOARD_VERSION' ) ) {
	return;
}

define( 'HAMISTA_DASHBOARD_VERSION', '1.0.0' );
define( 'HAMISTA_DASHBOARD_FILE', __FILE__ );
define( 'HAMISTA_DASHBOARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'HAMISTA_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

require_once HAMISTA_DASHBOARD_PATH . 'includes/class-autoloader.php';
\Hamista\Dashboard\Autoloader::register( 'Hamista\\Dashboard\\', HAMISTA_DASHBOARD_PATH . 'includes/' );
require_once HAMISTA_DASHBOARD_PATH . 'includes/functions.php';

register_activation_hook( __FILE__, [ \Hamista\Dashboard\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Hamista\Dashboard\Installer::class, 'deactivate' ] );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HAMISTA_DASHBOARD_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', HAMISTA_DASHBOARD_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'hamista_core' ) ) {
			add_action(
				'admin_notices',
				static function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Hamista Customer Dashboard requires the Hamista Core plugin to be installed and active.', 'hamista-customer-dashboard' )
					);
				}
			);
			return;
		}
		\Hamista\Dashboard\Plugin::instance()->boot();
	},
	20
);
