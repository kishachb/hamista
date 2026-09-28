<?php
/**
 * Developer style guide at ?hamista-styleguide=1.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Renders template-parts/dev/styleguide.php (every §4.2 primitive and the token swatches, in both
 * directions) for users who can edit theme options; everyone else gets a 404. Used for visual QA.
 *
 * @since 1.0.0
 */
final class Styleguide {

	/**
	 * Query argument.
	 */
	public const QUERY_ARG = 'hamista-styleguide';

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_render' ], 5 );
	}

	/**
	 * Whether the current request asks for the style guide.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function requested(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only developer view, capability-checked.
		return isset( $_GET[ self::QUERY_ARG ] ) && '1' === sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG ] ) );
	}

	/**
	 * Renders the style guide, or turns the request into a 404. Hooked to `template_redirect`.
	 *
	 * @since 1.0.0
	 */
	public function maybe_render(): void {
		if ( ! self::requested() ) {
			return;
		}

		nocache_headers();

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter(
			'document_title_parts',
			static function ( $parts ) {
				$parts          = (array) $parts;
				$parts['title'] = __( 'Style guide', 'hamista' );
				return $parts;
			}
		);

		get_header();
		get_template_part( 'template-parts/dev/styleguide' );
		get_footer();
		exit;
	}
}
