<?php
/**
 * Theme supports, menus, sidebars and image sizes.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Declares what the theme supports (spec §5).
 *
 * Translated labels (menus, sidebars) are registered on `init` or later, never before
 * (spec §14); the text domain itself loads on `after_setup_theme`.
 *
 * @since 1.0.0
 */
final class Setup {

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'content_width' ], 0 );
		add_action( 'after_setup_theme', [ $this, 'setup' ] );
		add_action( 'init', [ $this, 'register_menus' ], 5 );
		add_action( 'widgets_init', [ $this, 'register_sidebars' ] );
	}

	/**
	 * Sets `$content_width` to the narrow reading column.
	 *
	 * @since 1.0.0
	 */
	public function content_width(): void {
		/**
		 * Filters the theme's content width in pixels.
		 *
		 * @since 1.0.0
		 *
		 * @param int $width Default 760.
		 */
		$GLOBALS['content_width'] = (int) apply_filters( 'hamista_content_width', 760 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress's own content width global.
	}

	/**
	 * Theme supports, image sizes and the text domain.
	 *
	 * @since 1.0.0
	 */
	public function setup(): void {
		load_theme_textdomain( 'hamista', HAMISTA_THEME_DIR . 'languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'custom-logo',
			[
				'height'      => 96,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			]
		);
		add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'hamista-templates' );

		// WooCommerce: gallery zoom, lightbox and slider stay off (no extra scripts; spec §5).
		add_theme_support(
			'woocommerce',
			[
				'thumbnail_image_width' => 480,
				'single_image_width'    => 960,
			]
		);

		add_image_size( 'hamista-card', 720, 450, true );
		add_image_size( 'hamista-hero', 1440, 1080 );
		add_image_size( 'hamista-portfolio', 960, 720, true );
		add_image_size( 'hamista-square', 480, 480, true );
	}

	/**
	 * Menu locations.
	 *
	 * @since 1.0.0
	 */
	public function register_menus(): void {
		register_nav_menus(
			[
				'primary'       => __( 'Primary menu', 'hamista' ),
				'mobile'        => __( 'Mobile menu', 'hamista' ),
				'footer-1'      => __( 'Footer column 1', 'hamista' ),
				'footer-2'      => __( 'Footer column 2', 'hamista' ),
				'footer-3'      => __( 'Footer column 3', 'hamista' ),
				'footer-bottom' => __( 'Footer bottom bar', 'hamista' ),
				'account'       => __( 'Account menu', 'hamista' ),
			]
		);
	}

	/**
	 * Widget areas.
	 *
	 * @since 1.0.0
	 */
	public function register_sidebars(): void {
		$areas = [
			'blog' => __( 'Blog sidebar', 'hamista' ),
			'shop' => __( 'Shop sidebar', 'hamista' ),
		];
		for ( $column = 1; $column <= 4; $column++ ) {
			/* translators: %d: footer column number (1–4). */
			$areas[ 'footer-' . $column ] = sprintf( __( 'Footer column %d', 'hamista' ), $column );
		}

		foreach ( $areas as $id => $name ) {
			register_sidebar(
				[
					'id'            => $id,
					'name'          => $name,
					'before_widget' => '<section id="%1$s" class="hm-widget %2$s">',
					'after_widget'  => '</section>',
					'before_title'  => '<h2 class="hm-widget__title">',
					'after_title'   => '</h2>',
				]
			);
		}
	}
}
