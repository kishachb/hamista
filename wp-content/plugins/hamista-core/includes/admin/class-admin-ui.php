<?php
/**
 * Shared admin UI.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the `hamista-admin` assets on Hamista screens and prints the page header.
 *
 * A Hamista screen is one whose id contains `hamista` or whose post type
 * starts with `hamista_`. Its <body> gets the `hamista-admin` class, which
 * scopes the admin design tokens and components (admin.css).
 *
 * @since 1.0.0
 */
final class Admin_UI {

	/**
	 * Attaches the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 20 );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * Whether a screen belongs to Hamista.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Screen|null $screen Screen; defaults to the current one.
	 * @return bool
	 */
	public static function is_hamista_screen( ?\WP_Screen $screen = null ): bool {
		if ( null === $screen && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
		}
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}
		return str_contains( (string) $screen->id, 'hamista' ) || str_starts_with( (string) $screen->post_type, 'hamista_' );
	}

	/**
	 * Enqueues the admin assets on Hamista screens.
	 *
	 * @since 1.0.0
	 */
	public function enqueue(): void {
		if ( self::is_hamista_screen() ) {
			wp_enqueue_style( 'hamista-admin' );
			wp_enqueue_script( 'hamista-admin' );
		}
	}

	/**
	 * Adds `hamista-admin` to the body class on Hamista screens.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $classes Space-separated classes.
	 * @return string
	 */
	public function body_class( $classes ): string {
		$classes = (string) $classes;
		return self::is_hamista_screen() ? $classes . ' hamista-admin ' : $classes;
	}

	/**
	 * Prints the branded page header. Call it first inside `<div class="wrap">`.
	 *
	 * WordPress moves admin notices below the `wp-header-end` marker printed here.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title Page title (plain text).
	 * @param array  $args  {
	 *     Optional.
	 *
	 *     @type string $subtitle One line under the title (plain text).
	 *     @type array  $actions  List of { label, url, primary (bool) } buttons.
	 * }
	 */
	public static function header( string $title, array $args = [] ): void {
		$subtitle = isset( $args['subtitle'] ) ? (string) $args['subtitle'] : '';
		$actions  = isset( $args['actions'] ) && is_array( $args['actions'] ) ? $args['actions'] : [];
		?>
		<div class="hm-admin-header">
			<span class="hm-admin-header__mark" aria-hidden="true">
				<?php echo Admin_Menu::mark_svg( '#fff' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG, attribute escaped inside. ?>
			</span>
			<div class="hm-admin-header__text">
				<h1 class="hm-admin-header__title"><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $subtitle ) : ?>
					<p class="hm-admin-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( [] !== $actions ) : ?>
				<div class="hm-admin-header__actions">
					<?php
					foreach ( $actions as $action ) {
						if ( ! is_array( $action ) || empty( $action['label'] ) || empty( $action['url'] ) ) {
							continue;
						}
						printf(
							'<a class="%s" href="%s">%s</a>',
							esc_attr( empty( $action['primary'] ) ? 'button' : 'button button-primary' ),
							esc_url( (string) $action['url'] ),
							esc_html( (string) $action['label'] )
						);
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<hr class="wp-header-end">
		<?php
	}
}
