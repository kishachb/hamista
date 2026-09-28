<?php
/**
 * Iranian mobile number normalisation and validation.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Pure-PHP helper: no WordPress dependency, unit tested in plain PHP.
 *
 * normalize() accepts `09XXXXXXXXX`, `+989XXXXXXXXX`, `00989XXXXXXXXX`,
 * `989XXXXXXXXX` or `9XXXXXXXXX`, with Persian or Arabic-Indic digits, and
 * always returns `09XXXXXXXXX` or '' when the input cannot be normalised.
 *
 * @since 1.0.0
 */
final class Mobile {

	/**
	 * The final, valid shape.
	 */
	private const PATTERN = '/^09\d{9}$/';

	/**
	 * Normalises a raw mobile number to `09XXXXXXXXX`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw User input.
	 * @return string `09XXXXXXXXX`, or '' when it cannot be normalised.
	 */
	public static function normalize( string $raw ): string {
		$digits = self::latin_digits( $raw );
		$digits = (string) preg_replace( '/[^0-9+]/', '', $digits );

		if ( str_starts_with( $digits, '+98' ) ) {
			$digits = '0' . substr( $digits, 3 );
		} elseif ( str_starts_with( $digits, '0098' ) ) {
			$digits = '0' . substr( $digits, 4 );
		} elseif ( 1 === preg_match( '/^98\d{10}$/', $digits ) ) {
			$digits = '0' . substr( $digits, 2 );
		} elseif ( 1 === preg_match( '/^9\d{9}$/', $digits ) ) {
			$digits = '0' . $digits;
		}

		return self::is_valid( $digits ) ? $digits : '';
	}

	/**
	 * Whether a (already normalised) value matches `^09\d{9}$`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Value to check.
	 * @return bool
	 */
	public static function is_valid( string $value ): bool {
		return 1 === preg_match( self::PATTERN, $value );
	}

	/**
	 * Converts Persian and Arabic-Indic digits to Latin digits.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function latin_digits( string $text ): string {
		static $map = null;
		if ( null === $map ) {
			$persian = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
			$arabic  = [ '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ];
			$latin   = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
			$map     = array_combine( array_merge( $persian, $arabic ), array_merge( $latin, $latin ) );
		}
		return strtr( $text, $map );
	}
}
