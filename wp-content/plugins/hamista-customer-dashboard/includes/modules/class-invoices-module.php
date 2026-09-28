<?php
/**
 * Module: invoices (registered now with an empty boot; filled in by task D3).
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Modules;

use Hamista\Core\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder so the module shows on the Modules tab and other code can check
 * `hamista_module_enabled( 'invoices' )` ahead of task D3, which adds the
 * `invoices` My Account endpoint and the printable Jalali invoice.
 *
 * @since 1.0.0
 */
final class Invoices_Module extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'invoices';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title(): string {
		return __( 'Invoices', 'hamista-customer-dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Printable, legally detailed invoices on My Account.', 'hamista-customer-dashboard' );
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
		// Implemented in task D3.
	}
}
