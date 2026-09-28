<?php
/**
 * Light / dark / system colour mode.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Colour mode without a flash of the wrong theme (spec §5):
 *
 * 1. The server renders `<html data-theme="light|dark" data-theme-mode="light|dark|system">` from
 *    `color_mode_default` (`system` renders as light until the head script resolves it).
 * 2. A blocking inline script, the first thing in `<head>` (wp_head priority 0, before any
 *    stylesheet), applies the visitor's stored choice (`localStorage['hamista-color-mode']`, only
 *    when the toggle and "remember" are both on) and resolves `system` with matchMedia. It also
 *    adds `hm-js` to <html>. Under 400 bytes.
 * 3. Two `meta[name=theme-color]` tags (one per `prefers-color-scheme`); main.js keeps them in
 *    step with an explicit choice.
 *
 * The toggle is a three-state button that cycles light → dark → system (see toggle_button()).
 *
 * @since 1.0.0
 */
final class Color_Mode {

	/**
	 * Modes in toggle order.
	 */
	public const MODES = [ 'light', 'dark', 'system' ];

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_filter( 'language_attributes', [ $this, 'html_attributes' ] );
		add_action( 'wp_head', [ $this, 'print_head_script' ], 0 );
		add_action( 'wp_head', [ $this, 'print_theme_color' ], 1 );
	}

	/**
	 * The site's default mode.
	 *
	 * @since 1.0.0
	 *
	 * @return string light, dark or system.
	 */
	public static function default_mode(): string {
		return Options::choice( 'color_mode_default', self::MODES );
	}

	/**
	 * Whether the visitor's choice is stored and restored.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function remembers(): bool {
		return Options::enabled( 'color_mode_toggle' ) && Options::enabled( 'color_mode_remember' );
	}

	/**
	 * Adds data-theme and data-theme-mode to <html> on the front end.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $output Attributes so far.
	 * @return string
	 */
	public function html_attributes( $output ): string {
		$output = (string) $output;
		if ( ! self::is_front_end() ) {
			return $output;
		}
		$mode = self::default_mode();
		return $output . sprintf( ' data-theme="%s" data-theme-mode="%s"', 'dark' === $mode ? 'dark' : 'light', esc_attr( $mode ) );
	}

	/**
	 * The minified head script.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function head_script(): string {
		$restore = self::remembers()
			? 'try{s=localStorage.getItem("hamista-color-mode")}catch(e){}if(/^(light|dark|system)$/.test(s))m=s;'
			: '';

		return '(function(d,m,s){d.classList.add("hm-js");' . $restore
			. 'd.setAttribute("data-theme-mode",m);'
			. 'd.setAttribute("data-theme",m=="system"?(matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light"):m)'
			. '})(document.documentElement,' . wp_json_encode( self::default_mode() ) . ')';
	}

	/**
	 * Prints the head script. Hooked to `wp_head` priority 0.
	 *
	 * @since 1.0.0
	 */
	public function print_head_script(): void {
		wp_print_inline_script_tag( $this->head_script(), [ 'id' => 'hamista-color-mode' ] );
	}

	/**
	 * Prints the theme-color metas. Hooked to `wp_head` priority 1.
	 *
	 * @since 1.0.0
	 */
	public function print_theme_color(): void {
		$colors = [
			'light' => sanitize_hex_color( Options::text( 'light_bg' ) ),
			'dark'  => sanitize_hex_color( Options::text( 'dark_bg' ) ),
		];
		foreach ( $colors as $scheme => $color ) {
			if ( ! $color ) {
				$color = 'light' === $scheme ? '#FFFFFF' : '#0B0B0F';
			}
			printf(
				'<meta name="theme-color" content="%1$s" media="(prefers-color-scheme: %2$s)">' . "\n",
				esc_attr( $color ),
				esc_attr( $scheme )
			);
		}
	}

	/**
	 * The header toggle: a three-state button that cycles light → dark → system. Returns '' when
	 * the toggle is switched off in settings.
	 *
	 * Three states rather than two, because "system" must stay reachable once a visitor has
	 * picked light or dark. The icon shows the current mode (set before first paint via
	 * data-theme-mode), and the accessible name states the current mode and the next one.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function toggle_button(): string {
		if ( ! Options::enabled( 'color_mode_toggle' ) ) {
			return '';
		}

		$names = self::names();
		$mode  = self::default_mode();
		$next  = self::MODES[ ( array_search( $mode, self::MODES, true ) + 1 ) % count( self::MODES ) ];
		/* translators: 1: current colour mode (Light, Dark or System), 2: the mode the button switches to. */
		$label = sprintf( __( 'Color mode: %1$s. Switch to %2$s.', 'hamista' ), $names[ $mode ], $names[ $next ] );

		$icons = '';
		foreach ( [
			'light'  => 'sun',
			'dark'   => 'moon',
			'system' => 'monitor',
		] as $icon_mode => $icon ) {
			$icons .= '<span data-hm-mode="' . esc_attr( $icon_mode ) . '">' . Icons::get( $icon ) . '</span>';
		}

		return sprintf(
			'<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-mode-toggle" data-hm-color-toggle aria-label="%1$s" title="%1$s">%2$s</button>',
			esc_attr( $label ),
			$icons
		);
	}

	/**
	 * Translated mode names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function names(): array {
		return [
			'light'  => __( 'Light', 'hamista' ),
			'dark'   => __( 'Dark', 'hamista' ),
			'system' => __( 'System', 'hamista' ),
		];
	}

	/**
	 * True on front-end page views (not wp-admin, not the login screen).
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_front_end(): bool {
		return ! is_admin() && ! ( function_exists( 'is_login' ) && is_login() );
	}
}
