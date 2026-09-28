<?php
/**
 * Module: tickets (registered now with an empty boot; filled in by task D2).
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Modules;

use Hamista\Core\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder so the module shows on the Modules tab and other code can check
 * `hamista_module_enabled( 'tickets' )` ahead of task D2, which adds the
 * `tickets` My Account endpoint, the admin screen and the reply flow.
 *
 * @since 1.0.0
 */
final class Tickets_Module extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'tickets';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title(): string {
		return __( 'Support Tickets', 'hamista-customer-dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Customer support tickets on My Account, with staff replies and attachments.', 'hamista-customer-dashboard' );
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
		// Implemented in task D2.
	}
}
