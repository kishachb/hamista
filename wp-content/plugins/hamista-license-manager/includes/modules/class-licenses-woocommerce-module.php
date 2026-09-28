<?php
/**
 * WooCommerce integration module: issuing, revoking and displaying licenses.
 *
 * @package Hamista\License
 */

namespace Hamista\License\Modules;

use Hamista\Core\Modules\Abstract_Module;
use Hamista\License\License_Service;
use Hamista\License\WooCommerce\Order_Handler;
use Hamista\License\WooCommerce\Product_Fields;

defined( 'ABSPATH' ) || exit;

/**
 * `licenses-woocommerce` module: the "License" product data tab and
 * variation fields, and order issuing/revoking/display.
 *
 * @since 1.0.0
 */
final class Licenses_WooCommerce_Module extends Abstract_Module {

	/**
	 * License service, used by the order handler.
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
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function id(): string {
		return 'licenses-woocommerce';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'License issuing (WooCommerce)', 'hamista-license-manager' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function description(): string {
		return __( 'Product license settings and automatic issuing, revoking and display on WooCommerce orders.', 'hamista-license-manager' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function group(): string {
		return 'commerce';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function requires(): array {
		return [ 'woocommerce' ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
		( new Product_Fields() )->register_hooks();
		( new Order_Handler( $this->service ) )->register_hooks();
	}
}
