<?php
/**
 * Public API of hamista-customer-dashboard (spec §8), used by the theme header bell.
 *
 * @package Hamista\Dashboard
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Dashboard\Support\Notification_Repository;
use Hamista\Dashboard\Support\Notification_Service;

if ( ! function_exists( 'hamista_dashboard_unread_count' ) ) {
	/**
	 * A user's unread notification count (object cache, transient fallback).
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id, or 0 for the current user.
	 * @return int
	 */
	function hamista_dashboard_unread_count( int $user_id = 0 ): int {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return 0;
		}

		$cache_key = Notification_Service::cache_key( $user_id );

		$cached = wp_cache_get( $cache_key, Notification_Service::CACHE_GROUP );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			wp_cache_set( $cache_key, (int) $cached, Notification_Service::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
			return (int) $cached;
		}

		$count = ( new Notification_Repository() )->count_unread( $user_id );
		wp_cache_set( $cache_key, $count, Notification_Service::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
		set_transient( $cache_key, $count, 5 * MINUTE_IN_SECONDS );
		return $count;
	}
}

if ( ! function_exists( 'hamista_dashboard_notifications_url' ) ) {
	/**
	 * URL of the notifications page on My Account.
	 *
	 * @since 1.0.0
	 *
	 * @return string '' when the endpoint is not registered or WooCommerce is inactive.
	 */
	function hamista_dashboard_notifications_url(): string {
		if ( ! function_exists( 'hamista_core' ) ) {
			return '';
		}
		return hamista_core()->account_endpoints()->url( 'notifications' );
	}
}
