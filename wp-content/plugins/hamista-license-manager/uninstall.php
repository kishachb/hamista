<?php
/**
 * Uninstall: drops tables and options only when "Delete data on uninstall" is on.
 *
 * Stored release files (private storage) are left in place, like core leaves
 * bucket files for satellite plugins to own.
 *
 * @package Hamista\License
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Drops the license manager's tables and options on the current site, if allowed.
 */
$hamista_lm_uninstall_site = static function (): void {
	$settings = get_option( 'hamista_licenses' );
	if ( ! is_array( $settings ) || empty( $settings['delete_data'] ) ) {
		return;
	}

	global $wpdb;
	foreach ( [ 'hamista_download_log', 'hamista_license_activations', 'hamista_releases', 'hamista_licenses' ] as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- fixed table names.
	}

	foreach ( [ 'hamista_licenses', 'hamista_lm_db_version', 'hamista_lm_secret' ] as $option ) {
		delete_option( $option );
	}

	foreach ( wp_roles()->role_objects as $role ) {
		$role->remove_cap( 'hamista_manage_licenses' );
	}
};

if ( is_multisite() ) {
	foreach ( get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) as $hamista_lm_site_id ) {
		switch_to_blog( (int) $hamista_lm_site_id );
		$hamista_lm_uninstall_site();
		restore_current_blog();
	}
} else {
	$hamista_lm_uninstall_site();
}
