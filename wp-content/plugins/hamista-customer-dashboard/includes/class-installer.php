<?php
/**
 * Activation, deactivation and upgrades.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the plugin's tables, grants capabilities and schedules the daily cron.
 *
 * install() is idempotent. It runs on activation and again from boot() whenever
 * the stored schema version differs from the code version (spec §14 "Database").
 *
 * @since 1.0.0
 */
final class Installer {

	/**
	 * Capability granted to staff who manage tickets (spec §14).
	 */
	public const CAPABILITY = 'hamista_manage_tickets';

	/**
	 * Option holding the installed schema version.
	 */
	public const VERSION_OPTION = 'hamista_dashboard_db_version';

	/**
	 * Daily cron hook: purges old read notifications and (later) autocloses tickets.
	 */
	public const CRON_HOOK = 'hamista_dashboard_daily';

	/**
	 * Roles that receive CAPABILITY.
	 */
	private const ROLES = [ 'administrator', 'shop_manager' ];

	/**
	 * Activation hook callback.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $network_wide Whether the plugin is network-activated.
	 */
	public static function activate( $network_wide = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- hook signature.
		self::install();
	}

	/**
	 * Deactivation hook callback: unschedules the daily cron.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $network_wide Whether the plugin is network-deactivated.
	 */
	public static function deactivate( $network_wide = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- hook signature.
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Creates/updates the tables, grants capabilities, schedules the cron and stores the version.
	 *
	 * @since 1.0.0
	 */
	public static function install(): void {
		self::create_tables();
		self::grant_capabilities();

		add_option( 'hamista_dashboard', [] );
		update_option( self::VERSION_OPTION, HAMISTA_DASHBOARD_VERSION, true );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Re-runs install() when the stored schema version differs from the code.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::VERSION_OPTION ) !== HAMISTA_DASHBOARD_VERSION ) {
			self::install();
		}
	}

	/**
	 * Grants CAPABILITY to administrators and, when present, shop managers.
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
	 * Creates the three tables from spec §8 with dbDelta.
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$tickets          = $wpdb->prefix . 'hamista_tickets';
		$ticket_replies   = $wpdb->prefix . 'hamista_ticket_replies';
		$notifications    = $wpdb->prefix . 'hamista_notifications';

		$sql = "CREATE TABLE {$tickets} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			subject varchar(255) NOT NULL DEFAULT '',
			department varchar(50) NOT NULL DEFAULT '',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			status varchar(20) NOT NULL DEFAULT 'open',
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			license_id bigint(20) unsigned NOT NULL DEFAULT 0,
			assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
			last_reply_at datetime NULL DEFAULT NULL,
			last_reply_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_status (user_id, status),
			KEY status_updated (status, updated_at)
		) {$charset_collate};
		CREATE TABLE {$ticket_replies} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			is_staff tinyint(1) unsigned NOT NULL DEFAULT 0,
			message longtext NOT NULL,
			attachments longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id)
		) {$charset_collate};
		CREATE TABLE {$notifications} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(50) NOT NULL DEFAULT 'info',
			title varchar(255) NOT NULL DEFAULT '',
			message longtext NULL,
			link varchar(255) NOT NULL DEFAULT '',
			is_read tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_read_created (user_id, is_read, created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
