<?php
/**
 * WPML / Polylang bridge.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * String translation and language switcher data from WPML or Polylang.
 *
 * Without either plugin, strings are returned unchanged and the switcher is empty.
 *
 * @since 1.0.0
 */
final class Multilingual {

	/**
	 * Translates a string registered with WPML or Polylang.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Original value (e.g. a saved setting).
	 * @param string $name    String name used at registration.
	 * @param string $context WPML domain / Polylang group.
	 * @return string
	 */
	public static function translate_string( string $value, string $name, string $context = 'hamista' ): string {
		if ( '' === $value ) {
			return $value;
		}
		if ( has_filter( 'wpml_translate_single_string' ) ) {
			return (string) apply_filters( 'wpml_translate_single_string', $value, $context, $name ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's API hook.
		}
		if ( function_exists( 'pll__' ) ) {
			return (string) pll__( $value );
		}
		return $value;
	}

	/**
	 * Languages for a switcher.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{code:string,name:string,url:string,current:bool}>
	 */
	public static function switcher_items(): array {
		$items = [];

		if ( has_filter( 'wpml_active_languages' ) ) {
			$languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's API hook.
			foreach ( is_array( $languages ) ? $languages : [] as $language ) {
				if ( is_array( $language ) ) {
					$items[] = [
						'code'    => (string) ( $language['language_code'] ?? $language['code'] ?? '' ),
						'name'    => (string) ( $language['native_name'] ?? $language['translated_name'] ?? '' ),
						'url'     => (string) ( $language['url'] ?? '' ),
						'current' => ! empty( $language['active'] ),
					];
				}
			}
		} elseif ( function_exists( 'pll_the_languages' ) ) {
			$languages = pll_the_languages( [ 'raw' => 1 ] );
			foreach ( is_array( $languages ) ? $languages : [] as $language ) {
				if ( is_array( $language ) ) {
					$items[] = [
						'code'    => (string) ( $language['slug'] ?? '' ),
						'name'    => (string) ( $language['name'] ?? '' ),
						'url'     => (string) ( $language['url'] ?? '' ),
						'current' => ! empty( $language['current_lang'] ),
					];
				}
			}
		}

		return array_values(
			array_filter(
				$items,
				static fn( array $item ): bool => '' !== $item['code'] && '' !== $item['url']
			)
		);
	}

	/**
	 * Language switcher markup (`.hm-lang-switcher`).
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional.
	 *
	 *     @type string $class        Extra classes on the <nav>.
	 *     @type string $display      'name' (default) or 'code'.
	 *     @type bool   $show_current Whether to list the current language (default true).
	 *     @type string $label        Accessible name of the <nav>.
	 * }
	 * @return string '' when neither WPML nor Polylang is active.
	 */
	public static function switcher( array $args = [] ): string {
		$items = self::switcher_items();
		if ( [] === $items ) {
			return '';
		}
		$args = array_merge(
			[
				'class'        => '',
				'display'      => 'name',
				'show_current' => true,
				'label'        => __( 'Languages', 'hamista-core' ),
			],
			$args
		);

		$html = '';
		foreach ( $items as $item ) {
			if ( $item['current'] && ! $args['show_current'] ) {
				continue;
			}
			$text  = 'code' === $args['display'] || '' === $item['name'] ? strtoupper( $item['code'] ) : $item['name'];
			$html .= '<li' . Html::attrs( [ 'class' => [ 'hm-lang-switcher__item', $item['current'] ? 'is-current' : '' ] ] ) . '>'
				. '<a' . Html::attrs(
					[
						'class'        => 'hm-lang-switcher__link',
						'href'         => $item['url'],
						'hreflang'     => $item['code'],
						'lang'         => $item['code'],
						'aria-current' => $item['current'] ? 'true' : null,
					]
				) . '>' . esc_html( $text ) . '</a></li>';
		}

		return '<nav' . Html::attrs(
			[
				'class'      => [ 'hm-lang-switcher', (string) $args['class'] ],
				'aria-label' => (string) $args['label'],
			]
		) . '><ul class="hm-lang-switcher__list">' . $html . '</ul></nav>';
	}
}
