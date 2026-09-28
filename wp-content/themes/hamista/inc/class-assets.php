<?php
/**
 * Front-end assets.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the theme's CSS and JS (spec §5, ruling R2):
 *
 * - `hamista-ui` is registered as an empty style handle on `wp_enqueue_scripts` priority 1, before
 *   core's priority-5 fallback, because main.css already carries the tokens and UI primitives.
 *   It depends on `hamista-main`, so plugin styles that depend on `hamista-ui` print after it.
 * - main.css / main.js, or their .min siblings unless SCRIPT_DEBUG; main.js is deferred in the
 *   footer with its config in `window.hamistaTheme`.
 * - enqueue_bundle() adds blog.css / shop.css / pages.css for the templates that need them.
 * - Preloads the primary font of the page direction (Vazirmatn in RTL, Inter in LTR).
 * - `<style id="hamista-custom-properties">`: only the colour / layout tokens whose saved values
 *   differ from the defaults, each validated as a hex colour or a number.
 * - Presentation presets on <html>: data-radius, data-spacing, data-font, data-font-latin,
 *   data-animations, data-card-style, data-button-style.
 *
 * @since 1.0.0
 */
final class Assets {

	/**
	 * Conditional stylesheets that templates may request.
	 */
	private const BUNDLES = [ 'blog', 'shop', 'pages' ];

	/**
	 * Colour options → token, grouped by the palette they belong to.
	 */
	private const COLOR_TOKENS = [
		'root' => [
			'color_brand_orange' => '--hm-color-brand-orange',
			'color_brand_red'    => '--hm-color-brand-red',
			'color_brand_purple' => '--hm-color-brand-purple',
			'color_action_start' => '--hm-color-action-start',
			'color_action_mid'   => '--hm-color-action-mid',
			'color_action_end'   => '--hm-color-action-end',
			'color_link_light'   => '--hm-color-link',
			'light_bg'           => '--hm-color-bg',
			'light_surface'      => '--hm-color-surface',
			'light_text'         => '--hm-color-text',
		],
		'dark' => [
			'color_link_dark' => '--hm-color-link',
			'dark_bg'         => '--hm-color-bg',
			'dark_surface'    => '--hm-color-surface',
			'dark_text'       => '--hm-color-text',
		],
	];

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_handles' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head', [ $this, 'preload_font' ], 2 );
		add_action( 'wp_head', [ $this, 'print_custom_properties' ], 9 );
		add_action( 'wp_head', [ $this, 'print_noscript' ], 99 );
		add_filter( 'language_attributes', [ $this, 'html_attributes' ], 11 );
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	}

	/**
	 * Registers the style and script handles. Hooked to `wp_enqueue_scripts` priority 1.
	 *
	 * @since 1.0.0
	 */
	public function register_handles(): void {
		wp_register_style( 'hamista-main', $this->url( 'css/main.css' ), [], $this->version( 'css/main.css' ) );
		// Empty on purpose (R2): main.css holds the primitives; core must not load its fallback.
		wp_register_style( 'hamista-ui', false, [ 'hamista-main' ], HAMISTA_THEME_VERSION );
		wp_register_script(
			'hamista-main',
			$this->url( 'js/main.js' ),
			[],
			$this->version( 'js/main.js' ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
	}

	/**
	 * Enqueues main.css and main.js with its config. Hooked to `wp_enqueue_scripts`.
	 *
	 * @since 1.0.0
	 */
	public function enqueue(): void {
		wp_enqueue_style( 'hamista-main' );
		wp_enqueue_script( 'hamista-main' );
		wp_add_inline_script( 'hamista-main', 'window.hamistaTheme=' . wp_json_encode( $this->script_config() ) . ';', 'before' );
	}

	/**
	 * Enqueues a conditional stylesheet (blog, shop or pages) when its file exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bundle Bundle name.
	 * @return bool Whether it was enqueued.
	 */
	public function enqueue_bundle( string $bundle ): bool {
		$bundle   = sanitize_key( $bundle );
		$relative = 'css/' . $bundle . '.css';
		if ( ! in_array( $bundle, self::BUNDLES, true ) || ! is_readable( HAMISTA_THEME_DIR . 'assets/' . $relative ) ) {
			return false;
		}
		wp_enqueue_style( 'hamista-' . $bundle, $this->url( $relative ), [ 'hamista-main' ], $this->version( $relative ) );
		return true;
	}

	/**
	 * Public URL of an asset under assets/, preferring the .min sibling unless SCRIPT_DEBUG.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative Path under assets/, e.g. `css/main.css`.
	 * @return string
	 */
	public function url( string $relative ): string {
		if ( ! self::debug() ) {
			$min = (string) preg_replace( '/\.(css|js)$/', '.min.$1', $relative );
			if ( is_readable( HAMISTA_THEME_DIR . 'assets/' . $min ) ) {
				$relative = $min;
			}
		}
		return HAMISTA_THEME_URI . 'assets/' . $relative;
	}

	/**
	 * Prints the font preload for the page direction. Hooked to `wp_head` priority 2.
	 *
	 * @since 1.0.0
	 */
	public function preload_font(): void {
		$file = $this->preload_file();
		if ( '' === $file ) {
			return;
		}
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( HAMISTA_THEME_URI . 'assets/fonts/' . $file )
		);
	}

	/**
	 * The font file to preload, or '' (preload off, or a system font for this direction).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function preload_file(): string {
		if ( ! Options::enabled( 'font_preload' ) ) {
			return '';
		}
		if ( is_rtl() ) {
			return 'vazirmatn' === Options::choice( 'font_family', [ 'vazirmatn', 'system' ] ) ? 'vazirmatn-nl-var.woff2' : '';
		}
		return 'inter' === Options::choice( 'font_family_latin', [ 'inter', 'system' ] ) ? 'inter-latin-var.woff2' : '';
	}

	/**
	 * Prints the custom-properties style. Hooked to `wp_head` priority 9 (after the stylesheets).
	 *
	 * @since 1.0.0
	 */
	public function print_custom_properties(): void {
		$css = $this->custom_properties_css();
		if ( '' !== $css ) {
			// Every value was validated as a hex colour or a number in custom_properties_css().
			echo '<style id="hamista-custom-properties">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * CSS for the tokens whose saved values differ from the defaults; '' when none do.
	 *
	 * Tokens derived from a changed colour are recomputed too (hover shades, the soft gradient,
	 * the glass tint), because CSS cannot derive them from a hex token on the targeted browsers.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function custom_properties_css(): string {
		$defaults = Options::defaults();
		$root     = [];
		$dark     = [];
		$changed  = [];

		foreach ( self::COLOR_TOKENS as $palette => $tokens ) {
			foreach ( $tokens as $key => $token ) {
				$value = self::hex( Options::text( $key ) );
				if ( null === $value || strtolower( (string) $defaults[ $key ] ) === $value ) {
					continue;
				}
				$changed[ $key ] = $value;
				if ( 'dark' === $palette ) {
					$dark[ $token ] = $value;
				} else {
					$root[ $token ] = $value;
				}
			}
		}

		$color = static function ( string $key ) use ( $changed, $defaults ): string {
			return $changed[ $key ] ?? strtolower( (string) $defaults[ $key ] );
		};

		if ( isset( $changed['color_action_start'] ) ) {
			$root['--hm-color-primary-hover'] = self::mix( $changed['color_action_start'], '#000000', .2 );
		}
		if ( isset( $changed['color_link_light'] ) ) {
			$root['--hm-color-link-hover'] = self::mix( $changed['color_link_light'], '#000000', .22 );
		}
		if ( isset( $changed['color_link_dark'] ) ) {
			$dark['--hm-color-link-hover'] = self::mix( $changed['color_link_dark'], '#ffffff', .3 );
		}
		if ( array_intersect_key( $changed, array_flip( [ 'color_brand_orange', 'color_brand_red', 'color_brand_purple' ] ) ) ) {
			$stops                      = [ $color( 'color_brand_orange' ), $color( 'color_brand_red' ), $color( 'color_brand_purple' ) ];
			$root['--hm-gradient-soft'] = self::soft_gradient( $stops, [ .1, .07, .1 ] );
			$dark['--hm-gradient-soft'] = self::soft_gradient( $stops, [ .14, .1, .16 ] );
		}
		if ( isset( $changed['light_bg'] ) ) {
			$root['--hm-glass-bg'] = self::rgba( $changed['light_bg'], .72 );
		}
		if ( isset( $changed['dark_bg'] ) ) {
			$dark['--hm-glass-bg'] = self::rgba( $changed['dark_bg'], .7 );
		}

		$width = Options::number( 'container_width', 1080, 1440 );
		if ( (int) $defaults['container_width'] !== $width ) {
			$root['--hm-container'] = $width . 'px';
		}
		$size = Options::number( 'font_size_base', 14, 20 );
		if ( (int) $defaults['font_size_base'] !== $size ) {
			$root['--hm-text-base'] = round( $size / 16, 4 ) . 'rem';
		}
		$weight = (int) Options::choice( 'heading_weight', [ '600', '700', '800', '900' ] );
		if ( (int) $defaults['heading_weight'] !== $weight ) {
			$root['--hm-heading-weight'] = (string) $weight;
		}

		$css = self::rule( ':root', $root );
		if ( $dark ) {
			$css .= self::rule( ':root[data-theme="dark"],.hm-scheme-dark', $dark );
		}
		return $css;
	}

	/**
	 * Adds the presentation presets to <html> on the front end. Hooked to `language_attributes`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $output Attributes so far.
	 * @return string
	 */
	public function html_attributes( $output ): string {
		$output = (string) $output;
		if ( ! Color_Mode::is_front_end() ) {
			return $output;
		}

		$attributes = [
			'data-radius'  => Options::choice( 'radius_scale', [ 'sharp', 'soft', 'round' ] ),
			'data-spacing' => Options::choice( 'section_spacing', [ 'compact', 'normal', 'relaxed' ] ),
		];
		if ( 'system' === Options::choice( 'font_family', [ 'vazirmatn', 'system' ] ) ) {
			$attributes['data-font'] = 'system';
		}
		if ( 'system' === Options::choice( 'font_family_latin', [ 'inter', 'system' ] ) ) {
			$attributes['data-font-latin'] = 'system';
		}
		if ( ! Options::enabled( 'animations' ) ) {
			$attributes['data-animations'] = 'off';
		}
		$card = Options::choice( 'card_style', [ 'elevated', 'bordered', 'glass' ] );
		if ( 'elevated' !== $card ) {
			$attributes['data-card-style'] = $card;
		}
		$button = Options::choice( 'button_style', [ 'gradient', 'solid', 'dark' ] );
		if ( 'gradient' !== $button ) {
			$attributes['data-button-style'] = $button;
		}

		foreach ( $attributes as $name => $value ) {
			$output .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
		}
		return $output;
	}

	/**
	 * Without JavaScript the drawer and search panel cannot open: show the primary menu inline
	 * and hide the controls that need scripts. Hooked to `wp_head` priority 99.
	 *
	 * @since 1.0.0
	 */
	public function print_noscript(): void {
		echo '<noscript><style>.hm-header .hm-container{flex-wrap:wrap}.hm-header .hm-nav{display:flex;flex-basis:100%;order:9;overflow-x:auto}.hm-header .hm-nav__list{display:flex;gap:1rem}.hm-header .hm-nav li{border:0}[data-hm-open],.hm-nav__toggle{display:none}</style></noscript>' . "\n";
	}

	/**
	 * Runtime config for main.js.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function script_config(): array {
		return [
			'colorMode' => [
				'default'  => Color_Mode::default_mode(),
				'remember' => Color_Mode::remembers(),
			],
			'i18n'      => [
				/* translators: 1: current colour mode (Light, Dark or System), 2: the mode the button switches to. */
				'toggle'  => __( 'Color mode: %1$s. Switch to %2$s.', 'hamista' ),
				/* translators: %s: the colour mode now in use (Light, Dark or System). */
				'changed' => __( 'Color mode: %s', 'hamista' ),
				'modes'   => Color_Mode::names(),
			],
		];
	}

	/**
	 * Whether SCRIPT_DEBUG is on.
	 *
	 * @return bool
	 */
	private static function debug(): bool {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Cache-busting version: the theme version, or the file time under SCRIPT_DEBUG.
	 *
	 * @param string $relative Path under assets/.
	 * @return string
	 */
	private function version( string $relative ): string {
		if ( self::debug() ) {
			$time = @filemtime( HAMISTA_THEME_DIR . 'assets/' . $relative ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file just falls back to the version.
			if ( $time ) {
				return (string) $time;
			}
		}
		return HAMISTA_THEME_VERSION;
	}

	/**
	 * A validated, lower-cased 6-digit hex colour, or null.
	 *
	 * @param string $value Raw value.
	 * @return string|null
	 */
	private static function hex( string $value ): ?string {
		$value = strtolower( trim( $value ) );
		if ( ! preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $value ) ) {
			return null;
		}
		if ( 4 === strlen( $value ) ) {
			$value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
		}
		return $value;
	}

	/**
	 * [ r, g, b ] of a validated hex colour.
	 *
	 * @param string $hex #rrggbb.
	 * @return int[]
	 */
	private static function channels( string $hex ): array {
		return [ (int) hexdec( substr( $hex, 1, 2 ) ), (int) hexdec( substr( $hex, 3, 2 ) ), (int) hexdec( substr( $hex, 5, 2 ) ) ];
	}

	/**
	 * Mixes a colour towards another (0 = first colour, 1 = second).
	 *
	 * @param string $hex    #rrggbb.
	 * @param string $toward #rrggbb.
	 * @param float  $amount 0–1.
	 * @return string #rrggbb
	 */
	private static function mix( string $hex, string $toward, float $amount ): string {
		$a = self::channels( $hex );
		$b = self::channels( $toward );
		$c = '#';
		foreach ( $a as $index => $channel ) {
			$c .= sprintf( '%02x', (int) round( $channel + ( $b[ $index ] - $channel ) * $amount ) );
		}
		return $c;
	}

	/**
	 * The rgba() notation of a hex colour.
	 *
	 * @param string $hex   #rrggbb.
	 * @param float  $alpha 0–1.
	 * @return string
	 */
	private static function rgba( string $hex, float $alpha ): string {
		return sprintf( 'rgba(%s,%s)', implode( ',', self::channels( $hex ) ), rtrim( rtrim( number_format( $alpha, 2, '.', '' ), '0' ), '.' ) );
	}

	/**
	 * The soft (tint) gradient from three brand stops.
	 *
	 * @param string[] $stops  Three #rrggbb colours.
	 * @param float[]  $alphas Their opacities.
	 * @return string
	 */
	private static function soft_gradient( array $stops, array $alphas ): string {
		return sprintf(
			'linear-gradient(var(--hm-gradient-angle),%s,%s 50%%,%s)',
			self::rgba( $stops[0], $alphas[0] ),
			self::rgba( $stops[1], $alphas[1] ),
			self::rgba( $stops[2], $alphas[2] )
		);
	}

	/**
	 * One CSS rule from token => value pairs; '' when there are none.
	 *
	 * @param string                $selector Selector.
	 * @param array<string, string> $tokens   Declarations.
	 * @return string
	 */
	private static function rule( string $selector, array $tokens ): string {
		if ( ! $tokens ) {
			return '';
		}
		$declarations = [];
		foreach ( $tokens as $token => $value ) {
			$declarations[] = $token . ':' . $value;
		}
		return $selector . '{' . implode( ';', $declarations ) . '}';
	}
}
