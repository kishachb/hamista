<?php
/**
 * Template tags. Each is pluggable (a child theme can define it first).
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Icons;
use Hamista\Theme\Options;
use Hamista\Theme\Theme;

if ( ! function_exists( 'hamista_option' ) ) {
	/**
	 * A `hamista_theme` value (saved, else the theme default).
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	function hamista_option( string $key ): mixed {
		return Options::get( $key );
	}
}

if ( ! function_exists( 'hamista_theme_icon' ) ) {
	/**
	 * Inline SVG icon: core's `hamista_icon()` when available, else the theme's own set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Icon name; `brand-{slug}` for brand marks.
	 * @param array  $attrs Optional `title`, `class`, `size`.
	 * @return string Escaped SVG markup.
	 */
	function hamista_theme_icon( string $name, array $attrs = [] ): string {
		return Icons::get( $name, $attrs );
	}
}

if ( ! function_exists( 'hamista_get_site_logo' ) ) {
	/**
	 * Logo markup: the light logo (a <picture> with the mobile logo as its small-screen source), the
	 * dark logo shown only in dark mode through CSS, both at the configured widths; or the
	 * gradient wordmark when no logo is set.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function hamista_get_site_logo(): string {
		$name = get_bloginfo( 'name', 'display' );
		$logo = absint( Options::get( 'logo' ) );
		if ( ! $logo ) {
			$logo = absint( get_theme_mod( 'custom_logo' ) );
		}
		$dark   = absint( Options::get( 'logo_dark' ) );
		$mobile = absint( Options::get( 'logo_mobile' ) );

		$classes = [ 'hm-logo' ];
		$style   = '';
		$inner   = '';

		if ( $logo && wp_attachment_is_image( $logo ) ) {
			$image_attrs = [
				'alt'      => $name,
				'loading'  => 'eager',
				'decoding' => 'async',
			];
			$inner       = wp_get_attachment_image( $logo, 'full', false, $image_attrs + [ 'class' => 'hm-logo__light' ] );
			$mobile_url  = $mobile ? wp_get_attachment_image_url( $mobile, 'full' ) : '';
			if ( $mobile_url ) {
				$inner = '<picture><source media="(max-width: 39.99em)" srcset="' . esc_url( $mobile_url ) . '">' . $inner . '</picture>';
			}
			if ( $dark && wp_attachment_is_image( $dark ) ) {
				$classes[] = 'hm-logo--has-dark';
				$inner    .= wp_get_attachment_image( $dark, 'full', false, $image_attrs + [ 'class' => 'hm-logo__dark' ] );
			}
			$style = sprintf(
				'--hm-logo-w:%dpx;--hm-logo-w-mobile:%dpx',
				Options::number( 'logo_width', 40, 400 ),
				Options::number( 'logo_width_mobile', 40, 300 )
			);
		} else {
			$text  = Options::text( 'brand_text' );
			$latin = '' === $text;
			$inner = sprintf(
				'<span class="hm-wordmark hm-gradient-text"%s>%s</span>',
				$latin ? ' lang="en" dir="ltr"' : '',
				esc_html( $latin ? 'HAMISTA' : $text )
			);
		}

		$tagline = get_bloginfo( 'description', 'display' );
		if ( $tagline && Options::enabled( 'show_tagline' ) ) {
			$inner .= '<small>' . esc_html( $tagline ) . '</small>';
		}

		return sprintf(
			'<a class="%1$s" href="%2$s" rel="home"%3$s>%4$s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( home_url( '/' ) ),
			'' === $style ? '' : ' style="' . esc_attr( $style ) . '"',
			$inner
		);
	}
}

if ( ! function_exists( 'hamista_header_cta' ) ) {
	/**
	 * The header call-to-action, or null when it is off or has nowhere to lead.
	 *
	 * An empty URL falls back to the services archive (HAMISTA Core); an empty text to
	 * "Start a project".
	 *
	 * @since 1.0.0
	 *
	 * @return array{text:string,url:string,style:string}|null
	 */
	function hamista_header_cta(): ?array {
		if ( ! Options::enabled( 'header_cta' ) ) {
			return null;
		}
		$url = Options::text( 'header_cta_url' );
		if ( '' === $url && post_type_exists( 'hamista_service' ) ) {
			$url = (string) get_post_type_archive_link( 'hamista_service' );
		}
		if ( '' === $url ) {
			return null;
		}
		return [
			'text'  => Options::text_or( 'header_cta_text', __( 'Start a project', 'hamista' ) ),
			'url'   => $url,
			'style' => Options::choice( 'header_cta_style', [ 'primary', 'dark', 'secondary' ] ),
		];
	}
}

if ( ! function_exists( 'hamista_account_link' ) ) {
	/**
	 * The account link: My Account with WooCommerce, the profile screen, or the login form.
	 *
	 * @since 1.0.0
	 *
	 * @return array{url:string,label:string}|null Null when switched off.
	 */
	function hamista_account_link(): ?array {
		if ( ! Options::enabled( 'header_account' ) ) {
			return null;
		}
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$url = (string) wc_get_page_permalink( 'myaccount' );
		} elseif ( is_user_logged_in() ) {
			$url = get_edit_profile_url();
		} else {
			$url = wp_login_url();
		}
		return [
			'url'   => $url,
			'label' => is_user_logged_in() ? __( 'My account', 'hamista' ) : __( 'Log in', 'hamista' ),
		];
	}
}

if ( ! function_exists( 'hamista_cart_link' ) ) {
	/**
	 * The cart link and item count, or null without WooCommerce or when switched off.
	 *
	 * @since 1.0.0
	 *
	 * @return array{url:string,count:int}|null
	 */
	function hamista_cart_link(): ?array {
		if ( ! Options::enabled( 'header_cart' ) || ! function_exists( 'wc_get_cart_url' ) ) {
			return null;
		}
		$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
		return [
			'url'   => wc_get_cart_url(),
			'count' => $cart ? (int) $cart->get_cart_contents_count() : 0,
		];
	}
}

if ( ! function_exists( 'hamista_site_logo' ) ) {
	/**
	 * Prints the logo (see hamista_get_site_logo()).
	 *
	 * @since 1.0.0
	 */
	function hamista_site_logo(): void {
		echo hamista_get_site_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}
}

if ( ! function_exists( 'hamista_color_mode_toggle' ) ) {
	/**
	 * Prints the three-state colour-mode toggle (nothing when it is switched off).
	 *
	 * @since 1.0.0
	 */
	function hamista_color_mode_toggle(): void {
		echo Theme::instance()->color_mode()->toggle_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}
}
