<?php
/**
 * The `notifications` My Account endpoint.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the notifications list and handles "mark all as read".
 *
 * @since 1.0.0
 */
final class Notifications_Endpoint {

	/**
	 * Nonce action for the "mark all as read" form.
	 */
	public const MARK_ALL_NONCE = 'hamista_notifications_read_all';

	/**
	 * Prints the endpoint content. Registered as the `notifications` endpoint callback.
	 *
	 * @since 1.0.0
	 */
	public static function render(): void {
		$user_id  = get_current_user_id();
		$page     = isset( $_GET['notif-page'] ) ? max( 1, absint( wp_unslash( $_GET['notif-page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.

		$repository = new Notification_Repository();
		$result     = $repository->for_user( $user_id, $page, 20 );

		hamista_get_template(
			'hamista-customer-dashboard',
			'myaccount/notifications.php',
			[
				'items' => $result['items'],
				'page'  => $page,
				'pages' => $result['pages'],
			],
			HAMISTA_DASHBOARD_PATH . 'templates'
		);
	}

	/**
	 * Handles the "mark all as read" POST. Hooked to `template_redirect`.
	 *
	 * @since 1.0.0
	 */
	public static function handle_mark_all_read(): void {
		if ( ! isset( $_POST['hamista_action'] ) || 'notifications_mark_all_read' !== $_POST['hamista_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified explicitly below.
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}

		$redirect = function_exists( 'hamista_dashboard_notifications_url' ) ? hamista_dashboard_notifications_url() : home_url( '/' );
		$nonce    = isset( $_POST['_hamista_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_hamista_nonce'] ) ) : '';

		if ( false === wp_verify_nonce( $nonce, self::MARK_ALL_NONCE ) ) {
			wp_safe_redirect( $redirect );
			exit;
		}

		$user_id = get_current_user_id();
		( new Notification_Repository() )->mark_all_read( $user_id );
		Notification_Service::bust_cache( $user_id );

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'All notifications were marked as read.', 'hamista-customer-dashboard' ), 'success' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
