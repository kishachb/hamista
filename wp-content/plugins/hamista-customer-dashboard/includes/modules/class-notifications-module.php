<?php
/**
 * Module: notifications.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Modules;

use Hamista\Core\Modules\Abstract_Module;
use Hamista\Dashboard\Installer;
use Hamista\Dashboard\Support\Notification_Link;
use Hamista\Dashboard\Support\Notification_Repository;
use Hamista\Dashboard\Support\Notification_Service;
use Hamista\Dashboard\Support\Notifications_Endpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Stores `hamista_notify` events and exposes the `notifications` My Account endpoint.
 *
 * @since 1.0.0
 */
final class Notifications_Module extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'notifications';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title(): string {
		return __( 'Notifications', 'hamista-customer-dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Stores hamista_notify() events and adds the Notifications page on My Account.', 'hamista-customer-dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function group(): string {
		return 'support';
	}

	/**
	 * {@inheritDoc}
	 */
	public function requires(): array {
		return [ 'woocommerce' ];
	}

	/**
	 * {@inheritDoc}
	 */
	public function boot(): void {
		$repository = new Notification_Repository();
		( new Notification_Service( $repository ) )->register();

		if ( function_exists( 'hamista_register_account_endpoint' ) && (bool) hamista_get_option( 'hamista_dashboard', 'section_notifications', true ) ) {
			hamista_register_account_endpoint(
				'notifications',
				[
					'title'    => __( 'Notifications', 'hamista-customer-dashboard' ),
					'icon'     => 'bell',
					'group'    => 'support',
					'position' => 55,
					'callback' => [ Notifications_Endpoint::class, 'render' ],
					'badge'    => static fn( int $user_id ): int => hamista_dashboard_unread_count( $user_id ),
					'overview' => static function ( int $user_id ): array {
						return [
							'label' => __( 'Unread notifications', 'hamista-customer-dashboard' ),
							'value' => (string) hamista_dashboard_unread_count( $user_id ),
							'link'  => function_exists( 'hamista_dashboard_notifications_url' ) ? hamista_dashboard_notifications_url() : '',
							'icon'  => 'bell',
						];
					},
				]
			);
		}

		add_action( 'template_redirect', [ Notifications_Endpoint::class, 'handle_mark_all_read' ] );
		add_action( 'template_redirect', [ Notification_Link::class, 'handle' ] );

		add_action(
			Installer::CRON_HOOK,
			static function () use ( $repository ): void {
				$days = (int) hamista_get_option( 'hamista_dashboard', 'notifications_retention_days', 90 );
				$repository->purge_read_older_than( max( 1, $days ) );
			}
		);
	}
}
