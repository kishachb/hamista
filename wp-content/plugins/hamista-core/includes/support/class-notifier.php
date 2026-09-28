<?php
/**
 * User notifications (event only).
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Normalises notification events and fires `hamista_notify`.
 *
 * Core only announces events; the customer dashboard plugin stores and shows them.
 *
 * @since 1.0.0
 */
final class Notifier {

	/**
	 * Fires `hamista_notify` for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id Recipient user ID.
	 * @param array $args    `type`, `title`, `message`, `link`.
	 */
	public static function notify( int $user_id, array $args ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		/**
		 * Fires when a user should be notified.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $user_id Recipient user ID.
		 * @param array $args {
		 *     @type string $type    Machine type, e.g. 'ticket_reply' (sanitize_key; default 'info').
		 *     @type string $title   Plain-text title.
		 *     @type string $message Plain-text message (line breaks kept).
		 *     @type string $link    Absolute URL, or ''.
		 * }
		 */
		do_action( 'hamista_notify', $user_id, self::normalize( $args ) );
	}

	/**
	 * Sanitizes notification arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Raw arguments.
	 * @return array{type:string,title:string,message:string,link:string}
	 */
	public static function normalize( array $args ): array {
		$type = sanitize_key( (string) ( $args['type'] ?? '' ) );
		return [
			'type'    => '' === $type ? 'info' : $type,
			'title'   => sanitize_text_field( (string) ( $args['title'] ?? '' ) ),
			'message' => sanitize_textarea_field( (string) ( $args['message'] ?? '' ) ),
			'link'    => esc_url_raw( (string) ( $args['link'] ?? '' ) ),
		];
	}
}
