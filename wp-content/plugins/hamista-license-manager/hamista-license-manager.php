<?php
/**
 * Plugin Name:          Hamista License Manager
 * Plugin URI:           https://hamista.ir
 * Description:          License keys, activations, domain control, releases and a secure update API for software sold on WooCommerce.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Requires Plugins:     hamista-core
 * Author:               HAMISTA
 * Author URI:           https://hamista.ir
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          hamista-license-manager
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.3
 *
 * @package Hamista\License
 */

defined( 'ABSPATH' ) || exit;

// Another copy of the plugin is already loaded.
if ( defined( 'HAMISTA_LM_VERSION' ) ) {
	return;
}

define( 'HAMISTA_LM_VERSION', '1.0.0' );
define( 'HAMISTA_LM_FILE', __FILE__ );
define( 'HAMISTA_LM_PATH', plugin_dir_path( __FILE__ ) );
define( 'HAMISTA_LM_URL', plugin_dir_url( __FILE__ ) );

require_once HAMISTA_LM_PATH . 'includes/class-autoloader.php';
\Hamista\License\Autoloader::register( 'Hamista\\License\\', HAMISTA_LM_PATH . 'includes/' );
require_once HAMISTA_LM_PATH . 'includes/functions.php';

register_activation_hook( __FILE__, [ \Hamista\License\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Hamista\License\Installer::class, 'deactivate' ] );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HAMISTA_LM_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', HAMISTA_LM_FILE, true );
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
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Hamista License Manager requires the Hamista Core plugin to be installed and active.', 'hamista-license-manager' )
					);
				}
			);
			return;
		}
		\Hamista\License\Plugin::instance()->boot();
	},
	20
);
