<?php
/**
 * The core license system module.
 *
 * @package Hamista\License
 */

namespace Hamista\License\Modules;

use Hamista\Core\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

/**
 * `licenses` module: license keys, activations, domain control and the
 * (LM2) update API. Independent of WooCommerce so the REST API can serve
 * licenses issued through other channels too.
 *
 * @since 1.0.0
 */
final class Licenses_Module extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function id(): string {
		return 'licenses';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function title(): string {
		return __( 'Licenses', 'hamista-license-manager' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function description(): string {
		return __( 'License keys, activations, domain control and the update API.', 'hamista-license-manager' );
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
	 * The REST API, the download endpoint and the admin list tables arrive
	 * in LM2/LM3; this module currently only marks the feature as present
	 * on the Modules tab.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
	}
}
