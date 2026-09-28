<?php
/**
 * Data for the My Account overview (dashboard) page.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the welcome header, stat cards, updates, recent orders/notifications
 * and quick actions shown on the Overview screen.
 *
 * @since 1.0.0
 */
final class Overview {

	/**
	 * Welcome header data.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_User $user Current user.
	 * @return array{avatar:string,name:string,member_since:string,welcome_text:string}
	 */
	public static function welcome( \WP_User $user ): array {
		$member_since = '';
		if ( '' !== $user->user_registered && '0000-00-00 00:00:00' !== $user->user_registered ) {
			$member_since = function_exists( 'hamista_date' )
				? hamista_date( 'j F Y', $user->user_registered )
				: wp_date( get_option( 'date_format' ), strtotime( $user->user_registered . ' UTC' ) );
		}

		return [
			'avatar'       => get_avatar( $user->ID, 64 ),
			'name'         => $user->display_name,
			'member_since' => $member_since,
			'welcome_text' => (string) hamista_get_option( 'hamista_dashboard', 'welcome_text', '' ),
		];
	}

	/**
	 * Quick actions: settings quick links, else sensible defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{label:string,url:string,icon:string}>
	 */
	public static function quick_actions(): array {
		$configured = (array) hamista_get_option( 'hamista_dashboard', 'quick_links', [] );
		$links      = [];
		foreach ( $configured as $link ) {
			if ( ! is_array( $link ) || empty( $link['label'] ) || empty( $link['url'] ) ) {
				continue;
			}
			$links[] = [
				'label' => (string) $link['label'],
				'url'   => (string) $link['url'],
				'icon'  => sanitize_key( (string) ( $link['icon'] ?? 'external-link' ) ),
			];
		}
		if ( [] !== $links ) {
			return $links;
		}

		$endpoints = function_exists( 'hamista_account_endpoints' ) ? hamista_account_endpoints() : [];

		if ( isset( $endpoints['tickets'] ) && (bool) hamista_get_option( 'hamista_dashboard', 'section_tickets', true ) ) {
			$links[] = [
				'label' => __( 'New support ticket', 'hamista-customer-dashboard' ),
				'url'   => hamista_core()->account_endpoints()->url( 'tickets' ),
				'icon'  => 'ticket',
			];
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$links[] = [
				'label' => __( 'Browse the shop', 'hamista-customer-dashboard' ),
				'url'   => (string) wc_get_page_permalink( 'shop' ),
				'icon'  => 'store',
			];
		}

		if ( defined( 'HAMISTA_SO_VERSION' ) ) {
			$links[] = [
				'label' => __( 'Order a service', 'hamista-customer-dashboard' ),
				'url'   => home_url( '/' ),
				'icon'  => 'briefcase',
			];
		}

		return $links;
	}

	/**
	 * Stat cards: one per registered endpoint with an `overview` callback,
	 * plus the built-in Orders and Downloads cards.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return array<int, array{label:string,value:string,link:string,icon:string}>
	 */
	public static function stat_cards( int $user_id ): array {
		$cards = [
			[
				'label' => __( 'Orders', 'hamista-customer-dashboard' ),
				'value' => (string) self::order_count( $user_id ),
				'link'  => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) : '',
				'icon'  => 'shopping-bag',
			],
			[
				'label' => __( 'Downloads', 'hamista-customer-dashboard' ),
				'value' => (string) self::download_count( $user_id ),
				'link'  => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'downloads', '', wc_get_page_permalink( 'myaccount' ) ) : '',
				'icon'  => 'download',
			],
		];

		foreach ( function_exists( 'hamista_account_endpoints' ) ? hamista_account_endpoints() : [] as $endpoint ) {
			if ( ! is_callable( $endpoint['overview'] ?? null ) ) {
				continue;
			}
			$card = call_user_func( $endpoint['overview'], $user_id );
			if ( ! is_array( $card ) || ! isset( $card['label'], $card['value'] ) ) {
				continue;
			}
			$cards[] = [
				'label' => (string) $card['label'],
				'value' => (string) $card['value'],
				'link'  => (string) ( $card['link'] ?? '' ),
				'icon'  => (string) ( $card['icon'] ?? '' ),
			];
		}

		return $cards;
	}

	/**
	 * Available product updates: `apply_filters( 'hamista_dashboard_updates', [], $user_id )`.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return array<int, array{product_name:string,current_version:string,new_version:string,url:string}>
	 */
	public static function updates( int $user_id ): array {
		return (array) apply_filters( 'hamista_dashboard_updates', [], $user_id );
	}

	/**
	 * The customer's most recent orders.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @param int $limit   Maximum orders.
	 * @return \WC_Order[]
	 */
	public static function recent_orders( int $user_id, int $limit = 3 ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return [];
		}
		$orders = wc_get_orders(
			[
				'customer_id' => $user_id,
				'limit'       => $limit,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'objects',
			]
		);
		return is_array( $orders ) ? $orders : [];
	}

	/**
	 * The customer's most recent notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @param int $limit   Maximum notifications.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent_notifications( int $user_id, int $limit = 5 ): array {
		$result = ( new Notification_Repository() )->for_user( $user_id, 1, $limit );
		return $result['items'];
	}

	/**
	 * Extra panels: `apply_filters( 'hamista_dashboard_overview_panels', [], $user_id )`.
	 * Each item's `html` is passed through `wp_kses_post()` by the template.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return array<int, array{id:string,title:string,html:string,priority:int}>
	 */
	public static function panels( int $user_id ): array {
		$panels = (array) apply_filters( 'hamista_dashboard_overview_panels', [], $user_id );
		usort(
			$panels,
			static fn( $a, $b ): int => ( (int) ( $a['priority'] ?? 10 ) ) <=> ( (int) ( $b['priority'] ?? 10 ) )
		);
		return $panels;
	}

	/**
	 * The `hm-badge--*` modifier for a WooCommerce order status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Order status (without the `wc-` prefix).
	 * @return string
	 */
	public static function status_badge( string $status ): string {
		switch ( $status ) {
			case 'completed':
				return 'success';
			case 'processing':
				return 'info';
			case 'on-hold':
				return 'warning';
			case 'cancelled':
			case 'failed':
				return 'danger';
			default:
				return 'neutral';
		}
	}

	/**
	 * Cached order count for a customer.
	 *
	 * @param int $user_id User id.
	 * @return int
	 */
	private static function order_count( int $user_id ): int {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}
		$cache_key = 'hamista_dash_order_count_' . $user_id;
		$count     = get_transient( $cache_key );
		if ( false !== $count ) {
			return (int) $count;
		}
		$results = wc_get_orders(
			[
				'customer_id' => $user_id,
				'limit'       => 1,
				'paginate'    => true,
				'return'      => 'ids',
			]
		);
		$count = is_object( $results ) && isset( $results->total ) ? (int) $results->total : 0;
		set_transient( $cache_key, $count, 5 * MINUTE_IN_SECONDS );
		return $count;
	}

	/**
	 * The count of downloadable files available to a customer.
	 *
	 * @param int $user_id User id.
	 * @return int
	 */
	private static function download_count( int $user_id ): int {
		if ( ! function_exists( 'wc_get_customer_available_downloads' ) ) {
			return 0;
		}
		return count( wc_get_customer_available_downloads( $user_id ) );
	}
}
