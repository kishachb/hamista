<?php
/**
 * Notification link-through: `?hamista-notification={id}&_hamista_nonce=...`.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Marks a notification read and redirects to its target, for logged-in owners only.
 *
 * A missing/foreign id or a bad nonce redirects to the notifications page with
 * an error flash and changes nothing. Hooked to `template_redirect`.
 *
 * @since 1.0.0
 */
final class Notification_Link {

	/**
	 * Query var carrying the notification id.
	 */
	public const QUERY_VAR = 'hamista-notification';

	/**
	 * Builds the link-through URL for a notification, with a nonce bound to its id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Notification id.
	 * @return string
	 */
	public static function url( int $id ): string {
		return add_query_arg(
			[
				self::QUERY_VAR => $id,
				'_hamista_nonce' => wp_create_nonce( self::nonce_action( $id ) ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Handles the link-through request. Hooked to `template_redirect`.
	 *
	 * @since 1.0.0
	 */
	public static function handle(): void {
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce is checked below, per notification id.
			return;
		}

		$fallback = function_exists( 'hamista_dashboard_notifications_url' ) ? hamista_dashboard_notifications_url() : home_url( '/' );

		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		$id     = absint( wp_unslash( $_GET[ self::QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.
		$nonce  = isset( $_GET['_hamista_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_hamista_nonce'] ) ) : '';
		$repository = new Notification_Repository();
		$row        = $id > 0 ? $repository->get( $id ) : null;
		$user_id    = get_current_user_id();

		$valid_nonce = $id > 0 && false !== wp_verify_nonce( $nonce, self::nonce_action( $id ) );
		$owned       = null !== $row && (int) $row['user_id'] === $user_id;

		if ( ! $valid_nonce || ! $owned ) {
			if ( function_exists( 'hamista_flash' ) ) {
				hamista_flash( __( 'This notification link is invalid or has expired.', 'hamista-customer-dashboard' ), 'error' );
			}
			wp_safe_redirect( $fallback );
			exit;
		}

		$repository->mark_read( $id, $user_id );
		Notification_Service::bust_cache( $user_id );

		$target = '' !== $row['link'] ? (string) $row['link'] : $fallback;
		$target = wp_validate_redirect( $target, $fallback );
		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * The nonce action bound to a notification id.
	 *
	 * @param int $id Notification id.
	 * @return string
	 */
	private static function nonce_action( int $id ): string {
		return 'hamista_notification_' . $id;
	}
}
