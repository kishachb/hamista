<?php
/**
 * Theme container.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Boots every theme component once and exposes the ones templates need.
 *
 * The theme works with its own defaults when HAMISTA Core is inactive; with core present it
 * also registers its option defaults and settings tabs (see Options).
 *
 * @since 1.0.0
 */
final class Theme {

	/**
	 * Singleton instance.
	 *
	 * @var Theme|null
	 */
	private static ?Theme $instance = null;

	/**
	 * Asset loader.
	 *
	 * @var Assets|null
	 */
	private ?Assets $assets = null;

	/**
	 * Colour-mode controller.
	 *
	 * @var Color_Mode|null
	 */
	private ?Color_Mode $color_mode = null;

	/**
	 * Returns the theme container.
	 *
	 * @since 1.0.0
	 *
	 * @return Theme
	 */
	public static function instance(): Theme {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers every component's hooks. Safe to call more than once.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {
		if ( null !== $this->assets ) {
			return;
		}

		( new Setup() )->register();
		( new Options() )->register();

		$this->assets = new Assets();
		$this->assets->register();

		$this->color_mode = new Color_Mode();
		$this->color_mode->register();

		( new Navigation() )->register();
		( new Styleguide() )->register();

		/**
		 * Fires after the theme registered its hooks.
		 *
		 * @since 1.0.0
		 *
		 * @param Theme $theme Theme container.
		 */
		do_action( 'hamista_theme_loaded', $this );
	}

	/**
	 * Asset loader (enqueue_bundle() for templates).
	 *
	 * @since 1.0.0
	 *
	 * @return Assets
	 */
	public function assets(): Assets {
		if ( null === $this->assets ) {
			$this->assets = new Assets();
		}
		return $this->assets;
	}

	/**
	 * Colour-mode controller (toggle_button() for templates).
	 *
	 * @since 1.0.0
	 *
	 * @return Color_Mode
	 */
	public function color_mode(): Color_Mode {
		if ( null === $this->color_mode ) {
			$this->color_mode = new Color_Mode();
		}
		return $this->color_mode;
	}
}
