<?php
/**
 * WooCommerce My Account endpoints (spec §4.4).
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of My Account endpoints added by Hamista packages.
 *
 * For each endpoint, core registers the WooCommerce query var, the menu
 * item (merged by position), the page title and the content callback, and
 * flushes rewrite rules once whenever the set of endpoints changes.
 *
 * Register endpoints before `init` priority 10 (when WooCommerce adds its
 * rewrite endpoints), e.g. from a module's boot() or on `init` priority 5.
 * WooCommerce hooks are only attached when WooCommerce is active; all()
 * works either way.
 *
 * @since 1.0.0
 */
final class Account_Endpoints {

	/**
	 * Option with the hash of the registered endpoint ids.
	 */
	public const HASH_OPTION = 'hamista_account_endpoints_hash';

	/**
	 * Groups used by the dashboard's grouped navigation.
	 */
	public const GROUPS = [ 'shop', 'services', 'support', 'account' ];

	/**
	 * Menu positions of WooCommerce's own items.
	 */
	private const BUILTIN_POSITIONS = [
		'dashboard'       => 0,
		'orders'          => 10,
		'downloads'       => 20,
		'edit-address'    => 60,
		'payment-methods' => 65,
		'edit-account'    => 70,
		'customer-logout' => 100,
	];

	/**
	 * Registered endpoints by id.
	 *
	 * @var array<string, array>
	 */
	private array $endpoints = [];

	/**
	 * Whether the WooCommerce hooks are attached.
	 *
	 * @var bool
	 */
	private bool $hooked = false;

	/**
	 * Endpoint ids whose content and title hooks are attached.
	 *
	 * @var array<string, true>
	 */
	private array $hooked_ids = [];

	/**
	 * Registers (or replaces) an endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   Endpoint slug, e.g. 'licenses' (also the URL part).
	 * @param array  $args {
	 *     Endpoint settings.
	 *
	 *     @type string        $title    Menu label and page title (translated).
	 *     @type string        $icon     Core icon name, e.g. 'key'.
	 *     @type string        $group    shop | services | support | account (default).
	 *     @type int           $position Menu order among all items (default 50).
	 *     @type callable      $callback Prints the content; receives the endpoint value (string).
	 *     @type callable|null $badge    fn( int $user_id ): int.
	 *     @type callable|null $overview fn( int $user_id ): array{label,value,link,icon}.
	 *     @type bool          $menu     Whether to show a menu item (default true), e.g. false for
	 *                                   a detail endpoint such as 'invoice/{id}'.
	 * }
	 */
	public function register( string $id, array $args ): void {
		if ( '' === $id || sanitize_key( $id ) !== $id ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Account endpoint id "%s" may only contain lower-case letters, digits, dashes and underscores.', $id ) ), '1.0.0' );
			return;
		}
		if ( ! isset( $args['callback'] ) || ! is_callable( $args['callback'] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Account endpoint "%s" needs a callable "callback".', $id ) ), '1.0.0' );
			return;
		}

		$group = (string) ( $args['group'] ?? 'account' );

		$this->endpoints[ $id ] = [
			'id'       => $id,
			'title'    => (string) ( $args['title'] ?? $id ),
			'icon'     => sanitize_key( (string) ( $args['icon'] ?? '' ) ),
			'group'    => in_array( $group, self::GROUPS, true ) ? $group : 'account',
			'position' => (int) ( $args['position'] ?? 50 ),
			'callback' => $args['callback'],
			'badge'    => isset( $args['badge'] ) && is_callable( $args['badge'] ) ? $args['badge'] : null,
			'overview' => isset( $args['overview'] ) && is_callable( $args['overview'] ) ? $args['overview'] : null,
			'menu'     => (bool) ( $args['menu'] ?? true ),
		];

		if ( $this->hooked ) {
			$this->hook_endpoint( $id );
		}
	}

	/**
	 * Every registered endpoint, ordered by position, including the `group`,
	 * `icon`, `badge` and `overview` data the dashboard shell uses.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public function all(): array {
		$endpoints = $this->endpoints;
		uasort(
			$endpoints,
			static fn( array $a, array $b ): int => $a['position'] <=> $b['position']
		);
		return $endpoints;
	}

	/**
	 * URL of an endpoint on the My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Endpoint id.
	 * @param string $value Optional endpoint value, e.g. an order ID.
	 * @return string '' when WooCommerce is not active.
	 */
	public function url( string $id, string $value = '' ): string {
		if ( ! function_exists( 'wc_get_endpoint_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
			return '';
		}
		return (string) wc_get_endpoint_url( $id, $value, wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * Attaches the WooCommerce hooks. Called by core when WooCommerce is active.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		if ( $this->hooked ) {
			return;
		}
		$this->hooked = true;

		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_query_vars' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ], 20 );
		add_action( 'wp_loaded', [ $this, 'maybe_flush_rewrite_rules' ] );

		foreach ( array_keys( $this->endpoints ) as $id ) {
			$this->hook_endpoint( $id );
		}
	}

	/**
	 * Adds the endpoints to WooCommerce's query vars.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $vars Query vars: key => endpoint slug.
	 * @return mixed
	 */
	public function add_query_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			return $vars;
		}
		foreach ( array_keys( $this->endpoints ) as $id ) {
			if ( ! isset( $vars[ $id ] ) ) {
				$vars[ $id ] = $id;
			}
		}
		return $vars;
	}

	/**
	 * Merges the endpoints into the account menu by position.
	 *
	 * Items from other plugins keep their place after the item they followed.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $items Menu items: endpoint => label.
	 * @return mixed
	 */
	public function add_menu_items( $items ) {
		if ( ! is_array( $items ) || [] === $this->endpoints ) {
			return $items;
		}

		$entries  = [];
		$sequence = 0;
		$previous = 0;
		foreach ( $items as $key => $label ) {
			$key = (string) $key;
			if ( isset( $this->endpoints[ $key ] ) ) {
				continue;
			}
			$previous  = self::BUILTIN_POSITIONS[ $key ] ?? $previous;
			$entries[] = [ $key, $label, $previous, $sequence++ ];
		}
		foreach ( $this->endpoints as $id => $endpoint ) {
			if ( $endpoint['menu'] ) {
				$entries[] = [ $id, $endpoint['title'], $endpoint['position'], $sequence++ ];
			}
		}

		usort(
			$entries,
			static fn( array $a, array $b ): int => [ $a[2], $a[3] ] <=> [ $b[2], $b[3] ]
		);

		$merged = [];
		foreach ( $entries as $entry ) {
			$merged[ $entry[0] ] = $entry[1];
		}
		return $merged;
	}

	/**
	 * Flushes rewrite rules when the set of endpoint ids has changed.
	 * Hooked to `wp_loaded`.
	 *
	 * @since 1.0.0
	 */
	public function maybe_flush_rewrite_rules(): void {
		$ids = array_keys( $this->endpoints );
		sort( $ids );
		$hash = md5( implode( ',', $ids ) );
		if ( get_option( self::HASH_OPTION ) === $hash ) {
			return;
		}
		update_option( self::HASH_OPTION, $hash, true );
		flush_rewrite_rules( false );
	}

	/**
	 * Prints an endpoint's content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Endpoint id.
	 * @param mixed  $value Endpoint value from the URL.
	 */
	public function render( string $id, $value = '' ): void {
		if ( isset( $this->endpoints[ $id ] ) ) {
			call_user_func( $this->endpoints[ $id ]['callback'], is_scalar( $value ) ? (string) $value : '' );
		}
	}

	/**
	 * Hooks one endpoint's content and title, once per id. The callbacks read
	 * the registry when they run, so a replaced endpoint needs no new hooks.
	 *
	 * @param string $id Endpoint id.
	 */
	private function hook_endpoint( string $id ): void {
		if ( isset( $this->hooked_ids[ $id ] ) ) {
			return;
		}
		$this->hooked_ids[ $id ] = true;

		add_action(
			"woocommerce_account_{$id}_endpoint",
			function ( $value = '' ) use ( $id ): void {
				$this->render( $id, $value );
			}
		);
		add_filter(
			"woocommerce_endpoint_{$id}_title",
			function ( $title ) use ( $id ) {
				return $this->endpoints[ $id ]['title'] ?? $title;
			}
		);
	}
}
