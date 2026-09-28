<?php
/**
 * Locale-aware date and digit formatting.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

use Hamista\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Formats dates in the Solar Hijri calendar and digits in Persian when the
 * site is set up for it (spec §3 "Dates").
 *
 * - Jalali dates: `hamista_core` → `calendar` is 'jalali' (the default) and
 *   the locale is `fa_*`.
 * - Persian digits: `hamista_core` → `persian_digits` is on and the locale is `fa_*`.
 * - When the `jalali` module is registered and switched off, both are off.
 * - The `hamista_use_jalali` and `hamista_use_persian_digits` filters have the last word.
 *
 * @since 1.0.0
 */
final class Formatter {

	/**
	 * Formats a time like wp_date(), in the site timezone.
	 *
	 * Strings without a timezone are read as UTC (the storage convention).
	 *
	 * @since 1.0.0
	 *
	 * @param string                        $format date() format.
	 * @param int|string|\DateTimeInterface $time   Timestamp, date string or date object.
	 * @return string '' when the time cannot be parsed.
	 */
	public static function date( string $format, $time ): string {
		$timestamp = self::timestamp( $time );
		if ( null === $timestamp ) {
			return '';
		}
		$output = self::use_jalali()
			? Jalali::format( $format, $timestamp, wp_timezone() )
			: (string) wp_date( $format, $timestamp );
		return self::digits( $output );
	}

	/**
	 * Converts digits to Persian when enabled. HTML-safe: digits inside tags
	 * and character references (`&#036;`) are left alone, so it works on
	 * WooCommerce price HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text or HTML.
	 * @return string
	 */
	public static function digits( string $text ): string {
		if ( '' === $text || ! self::use_persian_digits() ) {
			return $text;
		}
		$parts = preg_split( '/(<[^>]*>|&#?[a-zA-Z0-9]+;)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $parts ) {
			return $text;
		}
		foreach ( $parts as $index => $part ) {
			if ( 0 === $index % 2 ) {
				$parts[ $index ] = Jalali::persian_digits( $part );
			}
		}
		return implode( '', $parts );
	}

	/**
	 * Whether dates use the Jalali calendar.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function use_jalali(): bool {
		$enabled = 'jalali' === Options::get( 'hamista_core', 'calendar' ) && self::is_persian_locale() && ! self::jalali_module_off();

		/**
		 * Filters whether hamista_date() uses the Jalali calendar.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Whether Jalali formatting is on.
		 */
		return (bool) apply_filters( 'hamista_use_jalali', $enabled );
	}

	/**
	 * Whether digits are converted to Persian.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function use_persian_digits(): bool {
		$enabled = (bool) Options::get( 'hamista_core', 'persian_digits' ) && self::is_persian_locale() && ! self::jalali_module_off();

		/**
		 * Filters whether dates and prices use Persian digits.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Whether Persian digits are on.
		 */
		return (bool) apply_filters( 'hamista_use_persian_digits', $enabled );
	}

	/**
	 * Whether the current locale is Persian (`fa`, `fa_IR`, `fa_AF`).
	 *
	 * @return bool
	 */
	private static function is_persian_locale(): bool {
		return 1 === preg_match( '/^fa(_|$)/', determine_locale() );
	}

	/**
	 * Whether the `jalali` module is registered and switched off.
	 *
	 * @return bool
	 */
	private static function jalali_module_off(): bool {
		$registry = Plugin::instance()->modules();
		return null !== $registry->get( 'jalali' ) && ! $registry->is_enabled( 'jalali' );
	}

	/**
	 * Normalises the accepted time inputs to a Unix timestamp.
	 *
	 * @param mixed $time Timestamp, date string or date object.
	 * @return int|null
	 */
	private static function timestamp( $time ): ?int {
		if ( $time instanceof \DateTimeInterface ) {
			return $time->getTimestamp();
		}
		if ( is_int( $time ) || is_float( $time ) ) {
			return (int) $time;
		}
		if ( ! is_string( $time ) || '' === trim( $time ) ) {
			return null;
		}
		if ( is_numeric( $time ) ) {
			return (int) $time;
		}
		try {
			return ( new \DateTimeImmutable( $time, new \DateTimeZone( 'UTC' ) ) )->getTimestamp();
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
