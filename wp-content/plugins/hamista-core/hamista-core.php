<?php
/**
 * Plugin Name:          Hamista Core
 * Plugin URI:           https://hamista.ir
 * Description:          Foundation of the HAMISTA platform: settings, modules, components and shared services.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Author:               HAMISTA
 * Author URI:           https://hamista.ir
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          hamista-core
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.3
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

// Another copy of the plugin is already loaded.
if ( defined( 'HAMISTA_CORE_VERSION' ) ) {
	return;
}

define( 'HAMISTA_CORE_VERSION', '1.0.0' );
define( 'HAMISTA_CORE_FILE', __FILE__ );
define( 'HAMISTA_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'HAMISTA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once HAMISTA_CORE_PATH . 'includes/class-autoloader.php';
\Hamista\Core\Autoloader::register( 'Hamista\\Core\\', HAMISTA_CORE_PATH . 'includes/' );
require_once HAMISTA_CORE_PATH . 'includes/functions.php';
require_once HAMISTA_CORE_PATH . 'includes/settings/register-core-tabs.php';

register_activation_hook( __FILE__, [ \Hamista\Core\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \Hamista\Core\Installer::class, 'deactivate' ] );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HAMISTA_CORE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', HAMISTA_CORE_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\Hamista\Core\Plugin::instance()->boot();
	},
	5
);
