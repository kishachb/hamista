<?php
/**
 * Uninstall: removes data only when the "delete data on uninstall" setting is on.
 *
 * @package Hamista\Dashboard
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drops the plugin's tables, options and capability on one site.
 */
function hamista_dashboard_uninstall_site(): void {
	global $wpdb;

	$option = get_option( 'hamista_dashboard', [] );
	if ( empty( $option['delete_data'] ) ) {
		return;
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hamista_ticket_replies" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed table name, no variables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hamista_tickets" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed table name, no variables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hamista_notifications" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed table name, no variables.

	delete_option( 'hamista_dashboard' );
	delete_option( 'hamista_dashboard_db_version' );
	delete_option( 'hamista_account_endpoints_hash' );

	foreach ( wp_roles()->roles as $role_name => $role_data ) {
		$role = get_role( $role_name );
		if ( $role && $role->has_cap( 'hamista_manage_tickets' ) ) {
			$role->remove_cap( 'hamista_manage_tickets' );
		}
	}
}

if ( is_multisite() ) {
	$hamista_dashboard_site_ids = get_sites( [ 'fields' => 'ids' ] );
	foreach ( $hamista_dashboard_site_ids as $hamista_dashboard_site_id ) {
		switch_to_blog( (int) $hamista_dashboard_site_id );
		hamista_dashboard_uninstall_site();
		restore_current_blog();
	}
} else {
	hamista_dashboard_uninstall_site();
}
