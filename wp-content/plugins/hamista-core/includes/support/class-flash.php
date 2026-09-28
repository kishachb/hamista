<?php
/**
 * One-time feedback messages across a redirect.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Flash messages for Post/Redirect/Get outside My Account (spec §14).
 *
 * Logged-in users: transient `hamista_flash_u{ID}`. Guests: an httponly
 * cookie `hamista_flash` holds a random token, and the messages live in
 * transient `hamista_flash_g{token}`. Messages expire after 5 minutes.
 *
 * Messages are stored as given; escape them when printing (esc_html()).
 *
 * @since 1.0.0
 */
final class Flash {

	/**
	 * Guest token cookie.
	 */
	public const COOKIE = 'hamista_flash';

	/**
	 * Accepted message types (the `hm-alert--*` variants).
	 */
	public const TYPES = [ 'success', 'error', 'warning', 'info' ];

	/**
	 * Transient lifetime in seconds.
	 */
	private const TTL = 300;

	/**
	 * Guest token in use during this request.
	 *
	 * @var string
	 */
	private static string $guest_token = '';

	/**
	 * Queues a message for the next page view.
	 *
	 * For guests this sets a cookie, so call it before any output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message text.
	 * @param string $type    success | error | warning | info (anything else becomes info).
	 */
	public static function add( string $message, string $type = 'success' ): void {
		$message = trim( $message );
		if ( '' === $message ) {
			return;
		}
		$key = self::storage_key( true );
		if ( '' === $key ) {
			return;
		}

		$messages   = get_transient( $key );
		$messages   = is_array( $messages ) ? $messages : [];
		$messages[] = [
			'message' => $message,
			'type'    => in_array( $type, self::TYPES, true ) ? $type : 'info',
		];
		set_transient( $key, $messages, self::TTL );
	}

	/**
	 * Returns the queued messages and clears them.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{message:string,type:string}>
	 */
	public static function pull(): array {
		$key = self::storage_key( false );
		if ( '' === $key ) {
			return [];
		}
		$messages = get_transient( $key );
		if ( false === $messages ) {
			return [];
		}
		delete_transient( $key );

		$clean = [];
		foreach ( (array) $messages as $message ) {
			if ( isset( $message['message'], $message['type'] ) ) {
				$clean[] = [
					'message' => (string) $message['message'],
					'type'    => in_array( $message['type'], self::TYPES, true ) ? (string) $message['type'] : 'info',
				];
			}
		}
		return $clean;
	}

	/**
	 * Transient key for the current visitor.
	 *
	 * @param bool $create Whether to start a guest token when there is none.
	 * @return string '' when a guest has no token (and none could be created).
	 */
	private static function storage_key( bool $create ): string {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return 'hamista_flash_u' . $user_id;
		}
		$token = self::guest_token( $create );
		return '' === $token ? '' : 'hamista_flash_g' . $token;
	}

	/**
	 * The guest's token from the cookie, or a new one.
	 *
	 * @param bool $create Whether to create and send a new token.
	 * @return string 32 hex characters, or ''.
	 */
	private static function guest_token( bool $create ): string {
		if ( '' !== self::$guest_token ) {
			return self::$guest_token;
		}

		$cookie = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		if ( 1 === preg_match( '/^[a-f0-9]{32}$/', $cookie ) ) {
			self::$guest_token = $cookie;
			return $cookie;
		}
		if ( ! $create || headers_sent() ) {
			return '';
		}

		$token = bin2hex( random_bytes( 16 ) );
		setcookie(
			self::COOKIE,
			$token,
			[
				'expires'  => 0,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
		self::$guest_token = $token;
		return $token;
	}
}
