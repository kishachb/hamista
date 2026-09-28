<?php
/**
 * HTML helpers.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute builder and kses rules.
 *
 * @since 1.0.0
 */
final class Html {

	/**
	 * Attributes escaped as URLs.
	 */
	private const URL_ATTRIBUTES = [ 'href', 'src', 'action', 'formaction', 'poster', 'cite', 'xlink:href' ];

	/**
	 * Builds an escaped attribute string, each attribute preceded by a space.
	 *
	 * - `true` prints a bare attribute (`disabled`); `false` and `null` omit it.
	 * - A `class` array is joined with spaces (empty entries dropped).
	 * - Other arrays and objects are JSON-encoded (for `data-*` config).
	 * - A numeric key prints its string value as a bare attribute.
	 * - URL attributes (href, src, action, …) go through esc_url(); the rest through esc_attr().
	 *
	 * Usage: `'<a' . Html::attrs( [ 'href' => $url, 'class' => [ 'hm-btn', $extra ] ] ) . '>'`.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attrs name => value.
	 * @return string
	 */
	public static function attrs( array $attrs ): string {
		$html = '';
		foreach ( $attrs as $name => $value ) {
			if ( is_int( $name ) ) {
				$name  = is_string( $value ) ? $value : '';
				$value = true;
			}
			$name = (string) preg_replace( '/[^a-zA-Z0-9_:.-]/', '', (string) $name );
			if ( '' === $name || null === $value || false === $value ) {
				continue;
			}
			if ( true === $value ) {
				$html .= ' ' . $name;
				continue;
			}
			if ( 'class' === $name && is_array( $value ) ) {
				$value = implode( ' ', array_unique( array_filter( array_map( 'trim', array_map( 'strval', $value ) ) ) ) );
			} elseif ( is_array( $value ) || is_object( $value ) ) {
				$value = (string) wp_json_encode( $value );
			}
			$value   = (string) $value;
			$escaped = in_array( strtolower( $name ), self::URL_ATTRIBUTES, true ) ? esc_url( $value ) : esc_attr( $value );
			$html   .= ' ' . $name . '="' . $escaped . '"';
		}
		return $html;
	}

	/**
	 * Allowed tags and attributes for inline SVG, for wp_kses().
	 *
	 * Usage: `echo wp_kses( hamista_icon( 'check' ), Html::svg_kses() );`.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function svg_kses(): array {
		$shape = array_fill_keys(
			[ 'class', 'id', 'fill', 'fill-rule', 'fill-opacity', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'opacity', 'transform' ],
			true
		);

		return [
			'svg'            => $shape + array_fill_keys( [ 'xmlns', 'width', 'height', 'viewbox', 'role', 'aria-hidden', 'aria-label', 'aria-labelledby', 'focusable', 'preserveaspectratio' ], true ),
			'g'              => $shape,
			'path'           => $shape + [ 'd' => true ],
			'circle'         => $shape + array_fill_keys( [ 'cx', 'cy', 'r' ], true ),
			'ellipse'        => $shape + array_fill_keys( [ 'cx', 'cy', 'rx', 'ry' ], true ),
			'line'           => $shape + array_fill_keys( [ 'x1', 'y1', 'x2', 'y2' ], true ),
			'polyline'       => $shape + [ 'points' => true ],
			'polygon'        => $shape + [ 'points' => true ],
			'rect'           => $shape + array_fill_keys( [ 'x', 'y', 'width', 'height', 'rx', 'ry' ], true ),
			'title'          => [ 'id' => true ],
			'desc'           => [ 'id' => true ],
			'defs'           => [],
			'lineargradient' => array_fill_keys( [ 'id', 'x1', 'y1', 'x2', 'y2', 'gradientunits', 'gradienttransform' ], true ),
			'radialgradient' => array_fill_keys( [ 'id', 'cx', 'cy', 'r', 'fx', 'fy', 'gradientunits', 'gradienttransform' ], true ),
			'stop'           => array_fill_keys( [ 'offset', 'stop-color', 'stop-opacity' ], true ),
		];
	}
}
