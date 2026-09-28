<?php
/**
 * Component renderer.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Components;

use Hamista\Core\Support\Template_Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Renders `templates/components/{component}.php` (spec §4.7).
 *
 * A theme overrides a component at `{theme}/hamista-core/components/{component}.php`.
 * Rendering enqueues the `hamista-c-{component}` style and script when they
 * are registered.
 *
 * @since 1.0.0
 */
final class Renderer {

	/**
	 * Returns a component's HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component Component name; reduced to `[a-z0-9-]`.
	 * @param array  $args      Component arguments, available as `$args` in the template.
	 * @return string '' when the component has no template.
	 */
	public function render( string $component, array $args = [] ): string {
		$component = (string) preg_replace( '/[^a-z0-9-]/', '', strtolower( $component ) );
		if ( '' === $component ) {
			return '';
		}

		$template = Template_Loader::locate( 'hamista-core', 'components/' . $component . '.php', HAMISTA_CORE_PATH . 'templates/' );
		if ( '' === $template ) {
			return '';
		}

		/**
		 * Filters a component's arguments before rendering.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args      Arguments.
		 * @param string $component Component name.
		 */
		$args = (array) apply_filters( 'hamista_component_args', $args, $component );

		/**
		 * Filters a component's HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html      Rendered HTML.
		 * @param string $component Component name.
		 * @param array  $args      Arguments used.
		 */
		$html = (string) apply_filters( 'hamista_component_html', Template_Loader::render_file( $template, $args ), $component, $args );

		if ( '' !== trim( $html ) ) {
			$handle = 'hamista-c-' . $component;
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_enqueue_script( $handle );
			}
		}

		return $html;
	}
}
