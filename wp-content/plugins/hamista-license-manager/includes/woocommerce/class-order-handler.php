<?php
/**
 * WooCommerce order issuing, revoking and display (spec §7).
 *
 * HPOS-safe: order data is read only through the WooCommerce CRUD API.
 *
 * @package Hamista\License
 */

namespace Hamista\License\WooCommerce;

use Hamista\License\License_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Issues licenses when an order reaches the configured status, revokes them
 * on refund/cancellation, and shows issued keys to the customer and in
 * wp-admin.
 *
 * @since 1.0.0
 */
final class Order_Handler {

	/**
	 * Whether the current render is inside a WooCommerce email (set around
	 * `woocommerce_email_before_order_table` / `_after_order_table`), so
	 * `show_keys_in_emails` can be honoured without affecting the
	 * thank-you page or the My Account order view, which reuse the same
	 * `woocommerce_order_item_meta_end` hook.
	 *
	 * @var bool
	 */
	private static bool $in_email = false;

	/**
	 * License service.
	 *
	 * @var License_Service
	 */
	private License_Service $service;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param License_Service $service License service.
	 */
	public function __construct( License_Service $service ) {
		$this->service = $service;
	}

	/**
	 * Registers the WordPress/WooCommerce hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_order_status_processing', [ $this, 'maybe_issue_on_processing' ] );
		add_action( 'woocommerce_order_status_completed', [ $this, 'issue_on_completed' ] );
		add_action( 'woocommerce_payment_complete', [ $this, 'maybe_issue_on_payment_complete' ] );

		add_action( 'woocommerce_order_status_refunded', [ $this, 'maybe_revoke_on_refund' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'maybe_revoke_on_cancel' ] );

		add_action( 'woocommerce_email_before_order_table', [ $this, 'mark_email_context' ] );
		add_action( 'woocommerce_email_after_order_table', [ $this, 'unmark_email_context' ] );
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'render_keys_under_line_item' ], 10, 4 );

		add_action( 'add_meta_boxes', [ $this, 'add_admin_meta_box' ] );
	}

	/**
	 * Issues licenses when the order reaches "processing", if that is the
	 * configured issuing status.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order id.
	 */
	public function maybe_issue_on_processing( int $order_id ): void {
		if ( 'processing' === self::issue_on() ) {
			$this->issue_for_order( $order_id );
		}
	}

	/**
	 * Issues licenses when the order reaches "completed". Always runs,
	 * regardless of the `issue_on` setting, and is idempotent.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order id.
	 */
	public function issue_on_completed( int $order_id ): void {
		$this->issue_for_order( $order_id );
	}

	/**
	 * Issues licenses on payment completion (covers gateways that mark an
	 * order paid without necessarily moving it through "processing" first),
	 * when `issue_on` is "processing".
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order id.
	 */
	public function maybe_issue_on_payment_complete( int $order_id ): void {
		if ( 'processing' === self::issue_on() ) {
			$this->issue_for_order( $order_id );
		}
	}

	/**
	 * Revokes the order's licenses when refunded, if enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order id.
	 */
	public function maybe_revoke_on_refund( int $order_id ): void {
		if ( hamista_get_option( 'hamista_licenses', 'revoke_on_refund', true ) ) {
			$this->revoke_for_order( $order_id, __( 'the order was refunded', 'hamista-license-manager' ) );
		}
	}

	/**
	 * Revokes the order's licenses when cancelled, if enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order id.
	 */
	public function maybe_revoke_on_cancel( int $order_id ): void {
		if ( hamista_get_option( 'hamista_licenses', 'revoke_on_cancel', true ) ) {
			$this->revoke_for_order( $order_id, __( 'the order was cancelled', 'hamista-license-manager' ) );
		}
	}

	/**
	 * Marks that item output is currently rendering inside a WooCommerce email.
	 *
	 * @since 1.0.0
	 */
	public function mark_email_context(): void {
		self::$in_email = true;
	}

	/**
	 * Clears the email-context flag.
	 *
	 * @since 1.0.0
	 */
	public function unmark_email_context(): void {
		self::$in_email = false;
	}

	/**
	 * Shows the issued license keys under a line item: on the thank-you
	 * page, the My Account order view, and (when `show_keys_in_emails` is
	 * on) customer emails.
	 *
	 * @since 1.0.0
	 *
	 * @param int                    $item_id    Order item id.
	 * @param \WC_Order_Item         $item       Order item.
	 * @param \WC_Order              $order      Order.
	 * @param bool                   $plain_text Whether this is a plain-text email.
	 */
	public function render_keys_under_line_item( $item_id, $item, $order, $plain_text = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- WooCommerce hook signature; $item_id and $order are unused here.
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}
		if ( self::$in_email && ! hamista_get_option( 'hamista_licenses', 'show_keys_in_emails', true ) ) {
			return;
		}

		$ids = $item->get_meta( '_hamista_license_ids', true );
		if ( ! is_array( $ids ) || [] === $ids ) {
			return;
		}

		$keys = [];
		foreach ( $ids as $id ) {
			$license = hamista_lm_get_license( (int) $id );
			if ( null !== $license ) {
				$keys[] = $license['license_key'];
			}
		}
		if ( [] === $keys ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'License key(s):', 'hamista-license-manager' ) . ' ' . esc_html( implode( ', ', $keys ) ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped.
			return;
		}

		echo '<div class="hamista-lm-keys" style="margin-top:4px;">';
		echo '<strong>' . esc_html__( 'License key(s):', 'hamista-license-manager' ) . '</strong> ';
		echo '<code>' . esc_html( implode( ', ', $keys ) ) . '</code>';
		echo '</div>';
	}

	/**
	 * Adds the "Licenses" meta box to the order edit screen (legacy and HPOS).
	 *
	 * @since 1.0.0
	 */
	public function add_admin_meta_box(): void {
		$screen_id = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		if ( '' === $screen_id ) {
			$screen_id = 'shop_order';
		}
		add_meta_box(
			'hamista_lm_licenses',
			__( 'Licenses', 'hamista-license-manager' ),
			[ $this, 'render_admin_meta_box' ],
			$screen_id,
			'side',
			'default'
		);
	}

	/**
	 * Renders the "Licenses" meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post|\WC_Order $post_or_order The legacy screen passes a WP_Post; the HPOS screen passes the WC_Order.
	 */
	public function render_admin_meta_box( $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$ids = [];
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$item_ids = $item->get_meta( '_hamista_license_ids', true );
			if ( is_array( $item_ids ) ) {
				array_push( $ids, ...array_map( 'intval', $item_ids ) );
			}
		}

		if ( [] === $ids ) {
			echo '<p>' . esc_html__( 'No licenses issued yet.', 'hamista-license-manager' ) . '</p>';
			return;
		}

		echo '<ul class="hamista-lm-order-licenses" style="margin:0;">';
		foreach ( $ids as $id ) {
			$license = hamista_lm_get_license( $id );
			if ( null === $license ) {
				continue;
			}
			$url = admin_url( 'admin.php?page=hamista-licenses&s=' . rawurlencode( $license['license_key'] ) );
			printf(
				'<li><a href="%1$s">%2$s</a> &mdash; %3$s</li>',
				esc_url( $url ),
				esc_html( $license['license_key'] ),
				esc_html( $license['status'] )
			);
		}
		echo '</ul>';
	}

	/**
	 * Issues licenses for every license-selling line item of an order.
	 *
	 * @param int $order_id Order id.
	 */
	private function issue_for_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( ! $product instanceof \WC_Product || ! self::sells_license( $product ) ) {
				continue;
			}
			$this->service->issue_for_order_item( $order, $item );
		}
	}

	/**
	 * Revokes every license issued for an order.
	 *
	 * @param int    $order_id Order id.
	 * @param string $reason   Shown in the order note.
	 */
	private function revoke_for_order( int $order_id, string $reason ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->service->revoke_for_order( $order, $reason );
		}
	}

	/**
	 * Whether a product (or its parent, for a variation) is set to sell a license.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @return bool
	 */
	private static function sells_license( \WC_Product $product ): bool {
		$parent_id = $product->get_parent_id();
		$target    = $parent_id ? wc_get_product( $parent_id ) : $product;
		return $target instanceof \WC_Product && 'yes' === $target->get_meta( '_hamista_lm_enabled', true );
	}

	/**
	 * The `issue_on` setting.
	 *
	 * @return string
	 */
	private static function issue_on(): string {
		return (string) hamista_get_option( 'hamista_licenses', 'issue_on', 'processing' );
	}
}
