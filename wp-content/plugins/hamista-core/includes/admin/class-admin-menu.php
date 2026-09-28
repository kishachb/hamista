<?php
/**
 * Top-level Hamista admin menu.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Admin;

use Hamista\Core\Installer;
use Hamista\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `hamista` top-level menu (admin_menu priority 9).
 *
 * The landing page comes from the `hamista_admin_overview_callback` filter
 * (the admin-dashboard module supplies the Overview); by default it is a
 * branded welcome screen. Satellites add submenus on admin_menu priority 20.
 *
 * @since 1.0.0
 */
final class Admin_Menu {

	/**
	 * Top-level menu slug.
	 */
	public const SLUG = 'hamista';

	/**
	 * Settings page slug (added by the settings framework).
	 */
	public const SETTINGS_SLUG = 'hamista-settings';

	/**
	 * Attaches the menu hook.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 9 );
	}

	/**
	 * Adds the menu and its Overview entry.
	 *
	 * @since 1.0.0
	 */
	public function add_menu(): void {
		/**
		 * Filters the callback that renders the Hamista landing page.
		 *
		 * @since 1.0.0
		 *
		 * @param callable $callback Prints the page.
		 */
		$callback = apply_filters( 'hamista_admin_overview_callback', [ $this, 'render_welcome' ] );
		if ( ! is_callable( $callback ) ) {
			$callback = [ $this, 'render_welcome' ];
		}

		add_menu_page(
			__( 'Hamista', 'hamista-core' ),
			__( 'Hamista', 'hamista-core' ),
			Installer::CAPABILITY,
			self::SLUG,
			$callback,
			self::icon_data_uri(),
			3
		);
		// Same slug as the parent, so the page above renders it; no second callback.
		add_submenu_page( self::SLUG, __( 'Overview', 'hamista-core' ), __( 'Overview', 'hamista-core' ), Installer::CAPABILITY, self::SLUG, '', 0 );
	}

	/**
	 * Default landing page: a branded welcome screen.
	 *
	 * @since 1.0.0
	 */
	public function render_welcome(): void {
		$can_configure = current_user_can( 'manage_options' );
		$registry      = Plugin::instance()->modules();
		$active        = count( array_filter( array_keys( $registry->all() ), [ $registry, 'is_booted' ] ) );

		/**
		 * Filters the documentation URL linked from the welcome screen.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url Documentation URL.
		 */
		$docs_url = (string) apply_filters( 'hamista_docs_url', 'https://hamista.ir/docs/' );

		$args = [
			'settings_url' => $can_configure ? admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) : '',
			'modules_url'  => $can_configure ? admin_url( 'admin.php?page=' . self::SETTINGS_SLUG . '&tab=modules' ) : '',
			'docs_url'     => $docs_url,
			'modules'      => count( $registry->all() ),
			'active'       => $active,
			'version'      => HAMISTA_CORE_VERSION,
		];
		include __DIR__ . '/views/welcome.php';
	}

	/**
	 * Menu icon: a white stylised "H" (the admin colour scheme recolours it).
	 *
	 * @since 1.0.0
	 *
	 * @return string data: URI.
	 */
	public static function icon_data_uri(): string {
		return 'data:image/svg+xml;base64,' . base64_encode( self::mark_svg( '#fff' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- data URI.
	}

	/**
	 * The Hamista "H" mark: two rounded stems joined by a rising bar.
	 *
	 * @since 1.0.0
	 *
	 * @param string $fill Fill colour or paint reference.
	 * @return string SVG markup (20×20).
	 */
	public static function mark_svg( string $fill ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="' . esc_attr( $fill ) . '" d="M5.25 2A1.75 1.75 0 0 0 3.5 3.75v12.5a1.75 1.75 0 0 0 3.5 0V12.9l6-2.4v5.75a1.75 1.75 0 0 0 3.5 0V3.75a1.75 1.75 0 0 0-3.5 0V7.1l-6 2.4V3.75A1.75 1.75 0 0 0 5.25 2z"/></svg>';
	}
}
