<?php
/**
 * Activation, deactivation and upgrades.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core;

use Hamista\Core\Support\Private_Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Installs capabilities, storage and version data.
 *
 * The install() method is idempotent. It runs on activation and again from boot()
 * whenever the stored version differs from the code version, which covers
 * updates (no activation hook fires) and network activation (each site
 * installs on its first request).
 *
 * @since 1.0.0
 */
final class Installer {

	/**
	 * Capability for the Hamista admin screens and reports.
	 */
	public const CAPABILITY = 'hamista_view_reports';

	/**
	 * Option holding the installed version.
	 */
	public const VERSION_OPTION = 'hamista_core_version';

	/**
	 * Option that asks for one rewrite-rule flush on the next `init`.
	 */
	public const FLUSH_OPTION = 'hamista_core_flush_rewrite_rules';

	/**
	 * Roles that receive CAPABILITY.
	 */
	private const ROLES = [ 'administrator', 'shop_manager' ];

	/**
	 * Activation hook callback.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $network_wide Whether the plugin is network-activated. Other
	 *                            sites install on their first request.
	 */
	public static function activate( $network_wide = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- hook signature.
		self::install();
	}

	/**
	 * Deactivation hook callback: drops the stored rewrite rules.
	 *
	 * WordPress rebuilds them on the next request, when Hamista's endpoints
	 * are no longer registered. Calling flush_rewrite_rules() now would save
	 * them again, because they are still registered during this request.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $network_wide Whether the plugin is network-deactivated.
	 */
	public static function deactivate( $network_wide = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- hook signature.
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Grants capabilities, protects private storage, stores the version and
	 * schedules a rewrite flush.
	 *
	 * @since 1.0.0
	 */
	public static function install(): void {
		self::grant_capabilities();
		Private_Storage::protect_directory( Private_Storage::base_path() );

		// Existing, autoloaded options cost no query per request.
		add_option( 'hamista_core', [] );
		add_option( 'hamista_modules', [] );

		update_option( self::VERSION_OPTION, HAMISTA_CORE_VERSION, true );
		update_option( self::FLUSH_OPTION, 1, true );
	}

	/**
	 * Re-runs install() when the stored version differs from the code.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::VERSION_OPTION ) !== HAMISTA_CORE_VERSION ) {
			self::install();
		}
	}

	/**
	 * Grants CAPABILITY to administrators and, when WooCommerce created the
	 * role, shop managers. Also hooked to `woocommerce_installed`.
	 *
	 * @since 1.0.0
	 */
	public static function grant_capabilities(): void {
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
				$role->add_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * Flushes rewrite rules once after install. Hooked to `init` priority 999
	 * (WordPress itself defers the flush to `wp_loaded`).
	 *
	 * The flag stays stored as 0, so checking it never costs a query.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_flush_rewrite_rules(): void {
		if ( ! get_option( self::FLUSH_OPTION ) ) {
			return;
		}
		update_option( self::FLUSH_OPTION, 0, true );
		flush_rewrite_rules( false );
	}
}
