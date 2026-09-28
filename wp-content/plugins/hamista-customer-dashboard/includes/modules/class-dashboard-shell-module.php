<?php
/**
 * Module: dashboard-shell.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Modules;

use Hamista\Core\Modules\Abstract_Module;
use Hamista\Core\Support\Assets;
use Hamista\Dashboard\Support\Profile_Fields;

defined( 'ABSPATH' ) || exit;

/**
 * The grouped account navigation, the Overview screen, profile fields and the
 * plugin's own CSS/JS.
 *
 * @since 1.0.0
 */
final class Dashboard_Shell_Module extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'dashboard-shell';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title(): string {
		return __( 'Dashboard Shell', 'hamista-customer-dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'The grouped My Account navigation, the overview screen and the extra profile fields.', 'hamista-customer-dashboard' );
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
		add_filter( 'woocommerce_locate_template', [ $this, 'locate_template' ], 20, 2 );

		Profile_Fields::register();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Swaps `myaccount/navigation.php` and `myaccount/dashboard.php` for the
	 * plugin's own (theme-overridable) templates.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $template      Located template path.
	 * @param mixed $template_name WooCommerce template name.
	 * @return mixed
	 */
	public function locate_template( $template, $template_name ) {
		$map = [
			'myaccount/navigation.php' => 'myaccount/navigation.php',
			'myaccount/dashboard.php'  => 'myaccount/overview.php',
		];
		if ( ! is_string( $template_name ) || ! isset( $map[ $template_name ] ) || ! function_exists( 'hamista_locate_template' ) ) {
			return $template;
		}

		$found = hamista_locate_template( 'hamista-customer-dashboard', $map[ $template_name ], HAMISTA_DASHBOARD_PATH . 'templates' );
		return '' !== $found ? $found : $template;
	}

	/**
	 * Enqueues the plugin's CSS/JS, only on the My Account page. Hooked to
	 * `wp_enqueue_scripts` (registered on `wp_enqueue_scripts:5` by core).
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}

		$assets = new Assets( HAMISTA_DASHBOARD_URL, HAMISTA_DASHBOARD_PATH, HAMISTA_DASHBOARD_VERSION );
		$assets->register_style( 'hamista-dashboard', 'assets/css/dashboard.css', [ 'hamista-ui' ] );
		wp_enqueue_style( 'hamista-dashboard' );

		$assets->register_script( 'hamista-dashboard', 'assets/js/dashboard.js', [ 'hamista-ui' ] );
		wp_add_inline_script(
			'hamista-dashboard',
			'window.hamistaDashboard = ' . wp_json_encode(
				[
					'i18n' => [
						'markingRead' => __( 'Marking as read…', 'hamista-customer-dashboard' ),
					],
				]
			) . ';',
			'before'
		);
		wp_enqueue_script( 'hamista-dashboard' );
	}
}
