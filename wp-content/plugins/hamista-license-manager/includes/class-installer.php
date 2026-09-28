<?php
/**
 * Activation, deactivation and upgrades (spec §7, tables).
 *
 * @package Hamista\License
 */

namespace Hamista\License;

use Hamista\Core\Support\Private_Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the license manager's tables, capability, plugin secret and
 * private storage bucket.
 *
 * install() is idempotent. It runs on activation and again from boot()
 * whenever the stored DB version differs from the code version, which
 * covers plugin updates and network activation.
 *
 * @since 1.0.0
 */
final class Installer {

	/**
	 * Capability for managing licenses, releases and download logs.
	 */
	public const CAPABILITY = 'hamista_manage_licenses';

	/**
	 * Option holding the installed database schema version.
	 */
	public const DB_VERSION_OPTION = 'hamista_lm_db_version';

	/**
	 * Database schema version. Bump it and adjust the CREATE TABLE
	 * statements to add a migration; dbDelta() applies the difference.
	 */
	public const DB_VERSION = '1.0.0';

	/**
	 * Option holding the plugin secret (HMAC key for download tokens, LM2).
	 */
	public const SECRET_OPTION = 'hamista_lm_secret';

	/**
	 * Private storage bucket for uploaded release ZIPs.
	 */
	public const RELEASES_BUCKET = 'releases';

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
	 * Deactivation hook callback. Intentionally a no-op: tables, options and
	 * stored files are kept, and are only removed by uninstall.php when
	 * "Delete data on uninstall" is on.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $network_wide Whether the plugin is network-deactivated.
	 */
	public static function deactivate( $network_wide = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- hook signature.
	}

	/**
	 * Creates/updates the tables, grants the capability, generates the
	 * plugin secret and protects the releases bucket.
	 *
	 * @since 1.0.0
	 */
	public static function install(): void {
		self::create_tables();
		self::grant_capabilities();
		self::ensure_secret();

		( new Private_Storage( self::RELEASES_BUCKET ) )->ensure_protected();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, true );
	}

	/**
	 * Re-runs install() when the stored DB version differs from the code.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Grants CAPABILITY to administrators and shop managers.
	 *
	 * Also hooked to `woocommerce_installed`, so shop managers get the
	 * capability when WooCommerce is installed after this plugin.
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
	 * Generates the plugin secret once (64 random bytes, hex-encoded,
	 * not autoloaded). Used from LM2 onwards to sign download tokens; the
	 * Ed25519 key pair for REST response signing is generated in LM2.
	 *
	 * @since 1.0.0
	 */
	public static function ensure_secret(): void {
		if ( false === get_option( self::SECRET_OPTION, false ) ) {
			add_option( self::SECRET_OPTION, bin2hex( random_bytes( 64 ) ), '', false );
		}
	}

	/**
	 * Creates or updates the four license manager tables via dbDelta().
	 *
	 * @since 1.0.0
	 */
	public static function create_tables(): void {
		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$licenses         = $wpdb->prefix . 'hamista_licenses';
		$activations      = $wpdb->prefix . 'hamista_license_activations';
		$releases         = $wpdb->prefix . 'hamista_releases';
		$download_log     = $wpdb->prefix . 'hamista_download_log';

		$sql = "CREATE TABLE {$licenses} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			license_key VARCHAR(191) NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			order_item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			activation_limit INT NOT NULL DEFAULT 0,
			activation_count INT NOT NULL DEFAULT 0,
			expires_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			notes TEXT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key),
			KEY user_id (user_id),
			KEY product_id (product_id),
			KEY order_id (order_id),
			KEY status_expires (status, expires_at)
		) {$charset_collate};

		CREATE TABLE {$activations} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			license_id BIGINT UNSIGNED NOT NULL,
			domain VARCHAR(255) NOT NULL,
			instance_id VARCHAR(191) NOT NULL DEFAULT '',
			is_local TINYINT UNSIGNED NOT NULL DEFAULT 0,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			client_version VARCHAR(50) NOT NULL DEFAULT '',
			activated_at DATETIME NOT NULL,
			last_checked_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY license_domain (license_id, domain),
			KEY license_id (license_id)
		) {$charset_collate};

		CREATE TABLE {$releases} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			version VARCHAR(50) NOT NULL,
			channel VARCHAR(20) NOT NULL DEFAULT 'stable',
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			changelog LONGTEXT NULL,
			file_name VARCHAR(255) NOT NULL DEFAULT '',
			file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
			file_hash VARCHAR(64) NOT NULL DEFAULT '',
			requires_wp VARCHAR(20) NOT NULL DEFAULT '',
			tested_wp VARCHAR(20) NOT NULL DEFAULT '',
			requires_php VARCHAR(20) NOT NULL DEFAULT '',
			download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
			released_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY product_version_channel (product_id, version, channel),
			KEY product_id (product_id)
		) {$charset_collate};

		CREATE TABLE {$download_log} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			release_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			license_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			source VARCHAR(20) NOT NULL DEFAULT 'dashboard',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY release_id (release_id),
			KEY product_id (product_id),
			KEY license_id (license_id),
			KEY user_id (user_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
