<?php
/**
 * Uninstall: removes core data only when "Delete data on uninstall" is on.
 *
 * Private storage files are left in place: satellite plugins own their buckets.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes core options and the capability on the current site, if allowed.
 */
$hamista_uninstall_site = static function (): void {
	$settings = get_option( 'hamista_core' );
	if ( ! is_array( $settings ) || empty( $settings['delete_data'] ) ) {
		return;
	}

	$options = [
		'hamista_core',
		'hamista_modules',
		'hamista_core_version',
		'hamista_core_flush_rewrite_rules',
		'hamista_account_endpoints_hash',
		'hamista_theme',
	];
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	foreach ( wp_roles()->role_objects as $role ) {
		$role->remove_cap( 'hamista_view_reports' );
	}
};

if ( is_multisite() ) {
	foreach ( get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) as $hamista_site_id ) {
		switch_to_blog( (int) $hamista_site_id );
		$hamista_uninstall_site();
		restore_current_blog();
	}
} else {
	$hamista_uninstall_site();
}
