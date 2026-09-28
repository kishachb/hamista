<?php
/**
 * Menus: accessible submenu toggles and the fallback page list.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the header and drawer menus (`.hm-nav`).
 *
 * Every item with children in the `primary` and `mobile` locations gets a disclosure button right
 * after its link (`<button class="hm-nav__toggle" aria-expanded="false">`). main.js toggles it for
 * keyboard, touch and click; on desktop the submenu also opens on hover.
 *
 * @since 1.0.0
 */
final class Navigation {

	/**
	 * Locations whose parent items get a submenu toggle.
	 */
	private const TOGGLE_LOCATIONS = [ 'primary', 'mobile' ];

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_filter( 'walker_nav_menu_start_el', [ $this, 'submenu_toggle' ], 10, 4 );
	}

	/**
	 * Prints a menu location as an `.hm-nav__list`, or the page-list fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location Theme location.
	 * @param int    $depth    Maximum depth.
	 */
	public static function menu( string $location, int $depth = 3 ): void {
		wp_nav_menu(
			[
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'hm-nav__list',
				'depth'          => $depth,
				'fallback_cb'    => [ self::class, 'fallback' ],
			]
		);
	}

	/**
	 * Appends the submenu toggle to parent items. Hooked to `walker_nav_menu_start_el`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item_output Item HTML.
	 * @param mixed $item        Menu item.
	 * @param mixed $depth       Depth.
	 * @param mixed $args        wp_nav_menu() arguments.
	 * @return string
	 */
	public function submenu_toggle( $item_output, $item, $depth, $args ): string {
		$item_output = (string) $item_output;
		$location    = is_object( $args ) && isset( $args->theme_location ) ? (string) $args->theme_location : '';
		if ( ! in_array( $location, self::TOGGLE_LOCATIONS, true ) || ! is_object( $item ) ) {
			return $item_output;
		}
		$classes = isset( $item->classes ) ? (array) $item->classes : [];
		if ( ! in_array( 'menu-item-has-children', $classes, true ) ) {
			return $item_output;
		}
		// A child beyond the menu depth never renders, so the toggle would control nothing.
		$max_depth = isset( $args->depth ) ? (int) $args->depth : 0;
		if ( $max_depth > 0 && (int) $depth + 1 >= $max_depth ) {
			return $item_output;
		}

		$title = wp_strip_all_tags( isset( $item->title ) ? (string) $item->title : '' );
		return $item_output . sprintf(
			'<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-nav__toggle" aria-expanded="false"><span class="hm-sr-only">%s</span>%s</button>',
			/* translators: %s: menu item title. */
			esc_html( sprintf( __( '%s submenu', 'hamista' ), $title ) ),
			Icons::get( 'chevron-down' )
		);
	}

	/**
	 * Without an assigned menu: the first top-level pages, in menu order.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args wp_nav_menu() arguments.
	 * @return string
	 */
	public static function fallback( $args = [] ): string {
		$args  = (array) $args;
		$pages = wp_list_pages(
			[
				'title_li'    => '',
				'depth'       => 1,
				'number'      => 6,
				'sort_column' => 'menu_order,post_title',
				'echo'        => false,
			]
		);
		$html  = $pages ? '<ul class="' . esc_attr( $args['menu_class'] ?? 'hm-nav__list' ) . '">' . $pages . '</ul>' : '';

		if ( ! isset( $args['echo'] ) || $args['echo'] ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_list_pages() output.
		}
		return $html;
	}
}
