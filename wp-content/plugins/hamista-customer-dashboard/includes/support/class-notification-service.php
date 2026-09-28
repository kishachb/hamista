<?php
/**
 * Listens to `hamista_notify` and stores the row.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Normalises and stores `hamista_notify( $user_id, $args )` events, and manages
 * the per-user unread-count cache (object cache, transient fallback).
 *
 * @since 1.0.0
 */
final class Notification_Service {

	/**
	 * Object cache group.
	 */
	public const CACHE_GROUP = 'hamista-dashboard';

	/**
	 * The repository.
	 *
	 * @var Notification_Repository
	 */
	private Notification_Repository $repository;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Notification_Repository $repository Repository.
	 */
	public function __construct( Notification_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Hooks `hamista_notify`.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'hamista_notify', [ $this, 'handle' ], 10, 2 );
	}

	/**
	 * Validates, sanitises and stores one notification event.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $user_id Recipient user id.
	 * @param mixed $args    `type`, `title`, `message`, `link`.
	 */
	public function handle( $user_id, $args ): void {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return;
		}
		$args = is_array( $args ) ? $args : [];

		$type = sanitize_key( (string) ( $args['type'] ?? '' ) );
		if ( '' === $type ) {
			$type = 'info';
		}
		$type = substr( $type, 0, 50 );

		$title   = mb_substr( sanitize_text_field( (string) ( $args['title'] ?? '' ) ), 0, 255 );
		$message = wp_kses( (string) ( $args['message'] ?? '' ), self::allowed_message_tags() );
		$link    = esc_url_raw( (string) ( $args['link'] ?? '' ) );

		$this->repository->create(
			[
				'user_id' => $user_id,
				'type'    => $type,
				'title'   => $title,
				'message' => $message,
				'link'    => $link,
			]
		);

		self::bust_cache( $user_id );
	}

	/**
	 * Basic inline tags allowed in a notification message.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_message_tags(): array {
		return [
			'a'      => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
			'strong' => [],
			'em'     => [],
			'b'      => [],
			'br'     => [],
			'code'   => [],
		];
	}

	/**
	 * Drops the cached unread count of a user (object cache and transient).
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 */
	public static function bust_cache( int $user_id ): void {
		wp_cache_delete( self::cache_key( $user_id ), self::CACHE_GROUP );
		delete_transient( self::cache_key( $user_id ) );
	}

	/**
	 * The cache key for a user's unread count.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return string
	 */
	public static function cache_key( int $user_id ): string {
		return 'hamista_dash_unread_' . $user_id;
	}
}
