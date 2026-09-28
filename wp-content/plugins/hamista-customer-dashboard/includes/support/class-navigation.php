<?php
/**
 * Grouped My Account navigation.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Groups WooCommerce's account menu items (and Hamista's own endpoints) into
 * Overview / Shop / Services / Support / Account, in that order.
 *
 * @since 1.0.0
 */
final class Navigation {

	/**
	 * Group order.
	 */
	public const GROUPS = [ 'overview', 'shop', 'services', 'support', 'account' ];

	/**
	 * Grouped, ordered menu items, ready for the navigation template.
	 *
	 * Each item: `{ id, label, icon, url, current, badge }`. The logout item
	 * is excluded here; use logout_item() for it.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<int, array<string, mixed>>> group => items, only non-empty groups.
	 */
	public static function groups(): array {
		$items      = function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : [];
		$endpoints  = function_exists( 'hamista_account_endpoints' ) ? hamista_account_endpoints() : [];
		$builtin    = self::builtin();
		$user_id    = get_current_user_id();
		$grouped    = array_fill_keys( self::GROUPS, [] );

		foreach ( $items as $id => $label ) {
			$id = (string) $id;
			if ( 'customer-logout' === $id ) {
				continue;
			}

			$endpoint = $endpoints[ $id ] ?? null;
			$builtin_entry = $builtin[ $id ] ?? null;

			$group = $endpoint['group'] ?? ( $builtin_entry['group'] ?? 'account' );
			if ( ! in_array( $group, self::GROUPS, true ) ) {
				$group = 'account';
			}

			$icon  = $endpoint['icon'] ?? ( $builtin_entry['icon'] ?? '' );
			$label = $builtin_entry['label'] ?? $label;

			$badge = 0;
			if ( null !== $endpoint && is_callable( $endpoint['badge'] ?? null ) ) {
				$badge = (int) call_user_func( $endpoint['badge'], $user_id );
			}

			$grouped[ $group ][] = [
				'id'      => $id,
				'label'   => (string) $label,
				'icon'    => (string) $icon,
				'url'     => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( $id ) : '#',
				'current' => function_exists( 'wc_is_current_account_menu_item' ) && wc_is_current_account_menu_item( $id ),
				'badge'   => max( 0, $badge ),
			];
		}

		return array_filter( $grouped );
	}

	/**
	 * The logout item, or null when WooCommerce did not register one.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>|null
	 */
	public static function logout_item(): ?array {
		$items = function_exists( 'wc_get_account_menu_items' ) ? wc_get_account_menu_items() : [];
		if ( ! isset( $items['customer-logout'] ) ) {
			return null;
		}
		return [
			'id'      => 'customer-logout',
			'label'   => (string) $items['customer-logout'],
			'icon'    => 'log-out',
			'url'     => function_exists( 'wc_logout_url' ) ? wc_logout_url() : wp_logout_url(),
			'current' => false,
			'badge'   => 0,
		];
	}

	/**
	 * Translated label of a navigation group.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group Group key.
	 * @return string
	 */
	public static function group_label( string $group ): string {
		switch ( $group ) {
			case 'overview':
				return __( 'Overview', 'hamista-customer-dashboard' );
			case 'shop':
				return __( 'Shop', 'hamista-customer-dashboard' );
			case 'services':
				return __( 'Services', 'hamista-customer-dashboard' );
			case 'support':
				return __( 'Support', 'hamista-customer-dashboard' );
			default:
				return __( 'Account', 'hamista-customer-dashboard' );
		}
	}

	/**
	 * Group and icon for WooCommerce's own menu items, and friendlier labels
	 * for a few of them (`edit-account` becomes "Profile").
	 *
	 * @return array<string, array{group:string,icon:string,label?:string}>
	 */
	private static function builtin(): array {
		return [
			'dashboard'       => [
				'group' => 'overview',
				'icon'  => 'layout-grid',
			],
			'orders'          => [
				'group' => 'shop',
				'icon'  => 'shopping-bag',
			],
			'downloads'       => [
				'group' => 'shop',
				'icon'  => 'download',
			],
			'service-orders'  => [
				'group' => 'services',
				'icon'  => 'briefcase',
			],
			'edit-address'    => [
				'group' => 'account',
				'icon'  => 'map-pin',
				'label' => __( 'Addresses', 'hamista-customer-dashboard' ),
			],
			'payment-methods' => [
				'group' => 'account',
				'icon'  => 'credit-card',
			],
			'edit-account'    => [
				'group' => 'account',
				'icon'  => 'user',
				'label' => __( 'Profile', 'hamista-customer-dashboard' ),
			],
		];
	}
}
