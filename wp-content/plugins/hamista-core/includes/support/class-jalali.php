<?php
/**
 * Solar Hijri (Jalali) calendar: conversion, leap years and formatting.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Pure-PHP Jalali calendar with a date()-compatible formatter.
 *
 * It has no WordPress dependency, so it is unit tested in plain PHP.
 *
 * Conversion uses Kazimierz M. Borkowski's algorithm as implemented by
 * jalaali-js (https://github.com/jalaali/jalaali-js, MIT licence,
 * © Behrang Noruzi Niya). It is valid for Jalali years -61 to 3177; other
 * years throw an \InvalidArgumentException.
 *
 * Dates are `[ year, month, day ]` lists of integers.
 *
 * @since 1.0.0
 */
final class Jalali {

	/**
	 * Jalali years that start a new leap-cycle segment (Borkowski).
	 */
	private const BREAKS = [ -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178 ];

	/**
	 * Month names; index 0 is Farvardin (month 1), index 11 is Esfand (month 12).
	 */
	private const MONTHS = [ 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' ];

	/**
	 * Weekday names indexed like PHP's `w` token (0 = Sunday).
	 */
	private const WEEKDAYS = [ 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه' ];

	/**
	 * One-letter weekday abbreviations (as used on Persian calendars), 0 = Sunday.
	 */
	private const WEEKDAY_INITIALS = [ 'ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش' ];

	/**
	 * Digits 0-9 in Latin, Persian (U+06F0…) and Arabic-Indic (U+0660…) forms.
	 */
	private const LATIN_DIGITS   = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
	private const PERSIAN_DIGITS = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
	private const ARABIC_DIGITS  = [ '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ];

	/**
	 * Converts a Gregorian date to Jalali.
	 *
	 * @since 1.0.0
	 *
	 * @param int $gy Gregorian year.
	 * @param int $gm Gregorian month (1-12).
	 * @param int $gd Gregorian day.
	 * @return int[] `[ jy, jm, jd ]`.
	 * @throws \InvalidArgumentException When the year is outside the supported range.
	 */
	public static function to_jalali( int $gy, int $gm, int $gd ): array {
		return self::d2j( self::g2d( $gy, $gm, $gd ) );
	}

	/**
	 * Converts a Jalali date to Gregorian.
	 *
	 * @since 1.0.0
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month (1-12).
	 * @param int $jd Jalali day.
	 * @return int[] `[ gy, gm, gd ]`.
	 * @throws \InvalidArgumentException When the year is outside the supported range.
	 */
	public static function to_gregorian( int $jy, int $jm, int $jd ): array {
		return self::d2g( self::j2d( $jy, $jm, $jd ) );
	}

	/**
	 * Whether a Jalali year is a leap year (Esfand has 30 days).
	 *
	 * @since 1.0.0
	 *
	 * @param int $jy Jalali year.
	 * @return bool
	 * @throws \InvalidArgumentException When the year is outside the supported range.
	 */
	public static function is_leap( int $jy ): bool {
		return 0 === self::jal_cal( $jy )['leap'];
	}

	/**
	 * Number of days in a Jalali month.
	 *
	 * @since 1.0.0
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month (1-12).
	 * @return int 29, 30 or 31.
	 * @throws \InvalidArgumentException When the month is not 1-12 or the year is out of range.
	 */
	public static function month_length( int $jy, int $jm ): int {
		if ( $jm < 1 || $jm > 12 ) {
			throw new \InvalidArgumentException( sprintf( 'Jalali month %d is not between 1 and 12.', $jm ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- integer.
		}
		if ( $jm <= 6 ) {
			return 31;
		}
		if ( $jm <= 11 ) {
			return 30;
		}
		return self::is_leap( $jy ) ? 30 : 29;
	}

	/**
	 * Whether a Jalali date exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month.
	 * @param int $jd Jalali day.
	 * @return bool
	 */
	public static function is_valid( int $jy, int $jm, int $jd ): bool {
		return $jy >= self::BREAKS[0] && $jy < self::BREAKS[ count( self::BREAKS ) - 1 ]
			&& $jm >= 1 && $jm <= 12
			&& $jd >= 1 && $jd <= self::month_length( $jy, $jm );
	}

	/**
	 * Formats a timestamp as a Jalali date, like PHP's date().
	 *
	 * Calendar tokens are Jalali: d D j l N w z F M m n t L Y y, plus
	 * a/A (ق.ظ / ب.ظ) and S (empty: Persian has no ordinal suffix). Time
	 * tokens (g G h H i s U …) and any other token are delegated to
	 * DateTime::format(). A backslash escapes the next character.
	 *
	 * @since 1.0.0
	 *
	 * @param string             $format    date() format string.
	 * @param int                $timestamp Unix timestamp.
	 * @param \DateTimeZone|null $tz        Output timezone. Defaults to PHP's default timezone.
	 * @return string
	 */
	public static function format( string $format, int $timestamp, ?\DateTimeZone $tz = null ): string {
		$date = ( new \DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $tz ?? new \DateTimeZone( date_default_timezone_get() ) );

		[ $jy, $jm, $jd ] = self::to_jalali( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );
		$weekday          = (int) $date->format( 'w' );

		$output = '';
		$length = strlen( $format );
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $format[ $i ];
			switch ( $char ) {
				case '\\':
					++$i;
					$output .= $i < $length ? $format[ $i ] : '';
					break;
				case 'd':
					$output .= str_pad( (string) $jd, 2, '0', STR_PAD_LEFT );
					break;
				case 'D':
					$output .= self::WEEKDAY_INITIALS[ $weekday ];
					break;
				case 'j':
					$output .= $jd;
					break;
				case 'l':
					$output .= self::WEEKDAYS[ $weekday ];
					break;
				case 'w':
					$output .= $weekday;
					break;
				case 'z':
					$output .= ( $jm <= 7 ? ( $jm - 1 ) * 31 : 186 + ( $jm - 7 ) * 30 ) + $jd - 1;
					break;
				case 'S':
					break;
				case 'F':
				case 'M':
					// Persian has no conventional month abbreviations.
					$output .= self::MONTHS[ $jm - 1 ];
					break;
				case 'm':
					$output .= str_pad( (string) $jm, 2, '0', STR_PAD_LEFT );
					break;
				case 'n':
					$output .= $jm;
					break;
				case 't':
					$output .= self::month_length( $jy, $jm );
					break;
				case 'L':
					$output .= self::is_leap( $jy ) ? '1' : '0';
					break;
				case 'Y':
					$output .= $jy;
					break;
				case 'y':
					$output .= str_pad( (string) ( $jy % 100 ), 2, '0', STR_PAD_LEFT );
					break;
				case 'a':
				case 'A':
					$output .= (int) $date->format( 'G' ) < 12 ? 'ق.ظ' : 'ب.ظ';
					break;
				default:
					$is_token = ( $char >= 'a' && $char <= 'z' ) || ( $char >= 'A' && $char <= 'Z' );
					$output  .= $is_token ? $date->format( $char ) : $char;
			}
		}
		return $output;
	}

	/**
	 * Converts Latin and Arabic-Indic digits to Persian digits.
	 *
	 * @since 1.0.0
	 *
	 * @param string $s Plain text (not HTML: digits inside markup would change too).
	 * @return string
	 */
	public static function persian_digits( string $s ): string {
		return str_replace( [ ...self::LATIN_DIGITS, ...self::ARABIC_DIGITS ], [ ...self::PERSIAN_DIGITS, ...self::PERSIAN_DIGITS ], $s );
	}

	/**
	 * Converts Persian and Arabic-Indic digits to Latin digits.
	 *
	 * @since 1.0.0
	 *
	 * @param string $s Text.
	 * @return string
	 */
	public static function latin_digits( string $s ): string {
		return str_replace( [ ...self::PERSIAN_DIGITS, ...self::ARABIC_DIGITS ], [ ...self::LATIN_DIGITS, ...self::LATIN_DIGITS ], $s );
	}

	/**
	 * Borkowski: leap status, Gregorian year and March day of Nowruz for a Jalali year.
	 *
	 * @param int $jy Jalali year.
	 * @return array{leap:int,gy:int,march:int} `leap` is 0 for a leap year.
	 * @throws \InvalidArgumentException When the year is outside the supported range.
	 */
	private static function jal_cal( int $jy ): array {
		$breaks = self::BREAKS;
		$count  = count( $breaks );
		if ( $jy < $breaks[0] || $jy >= $breaks[ $count - 1 ] ) {
			throw new \InvalidArgumentException( sprintf( 'Jalali year %d is outside the supported range.', $jy ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- integer.
		}

		$gy     = $jy + 621;
		$leap_j = -14;
		$jp     = $breaks[0];
		$jump   = 0;

		// Find the limiting years for the Jalali year.
		for ( $i = 1; $i < $count; $i++ ) {
			$jm   = $breaks[ $i ];
			$jump = $jm - $jp;
			if ( $jy < $jm ) {
				break;
			}
			$leap_j += intdiv( $jump, 33 ) * 8 + intdiv( $jump % 33, 4 );
			$jp      = $jm;
		}
		$n = $jy - $jp;

		// Leap years from AD 621 to the start of this Jalali year.
		$leap_j += intdiv( $n, 33 ) * 8 + intdiv( ( $n % 33 ) + 3, 4 );
		if ( 4 === $jump % 33 && 4 === $jump - $n ) {
			++$leap_j;
		}

		// The same in the Gregorian calendar, up to year $gy.
		$leap_g = intdiv( $gy, 4 ) - intdiv( ( intdiv( $gy, 100 ) + 1 ) * 3, 4 ) - 150;

		// Years since the last leap year.
		if ( $jump - $n < 6 ) {
			$n = $n - $jump + intdiv( $jump + 4, 33 ) * 33;
		}
		$leap = ( ( ( $n + 1 ) % 33 ) - 1 ) % 4;
		if ( -1 === $leap ) {
			$leap = 4;
		}

		return [
			'leap'  => $leap,
			'gy'    => $gy,
			'march' => 20 + $leap_j - $leap_g,
		];
	}

	/**
	 * Jalali date to Julian Day Number.
	 *
	 * @param int $jy Year.
	 * @param int $jm Month.
	 * @param int $jd Day.
	 * @return int
	 */
	private static function j2d( int $jy, int $jm, int $jd ): int {
		$r = self::jal_cal( $jy );
		return self::g2d( $r['gy'], 3, $r['march'] ) + ( $jm - 1 ) * 31 - intdiv( $jm, 7 ) * ( $jm - 7 ) + $jd - 1;
	}

	/**
	 * Julian Day Number to Jalali date.
	 *
	 * @param int $jdn Julian Day Number.
	 * @return int[] `[ jy, jm, jd ]`.
	 */
	private static function d2j( int $jdn ): array {
		$gy    = self::d2g( $jdn )[0];
		$jy    = $gy - 621;
		$r     = self::jal_cal( $jy );
		$jdn1f = self::g2d( $gy, 3, $r['march'] );

		// Days since 1 Farvardin.
		$k = $jdn - $jdn1f;
		if ( $k >= 0 ) {
			if ( $k <= 185 ) {
				// The first six months have 31 days.
				return [ $jy, 1 + intdiv( $k, 31 ), ( $k % 31 ) + 1 ];
			}
			$k -= 186;
		} else {
			// The day belongs to the previous Jalali year.
			--$jy;
			$k += 179;
			if ( 1 === $r['leap'] ) {
				++$k;
			}
		}
		return [ $jy, 7 + intdiv( $k, 30 ), ( $k % 30 ) + 1 ];
	}

	/**
	 * Gregorian date to Julian Day Number.
	 *
	 * @param int $gy Year.
	 * @param int $gm Month.
	 * @param int $gd Day.
	 * @return int
	 */
	private static function g2d( int $gy, int $gm, int $gd ): int {
		$d = intdiv( ( $gy + intdiv( $gm - 8, 6 ) + 100100 ) * 1461, 4 )
			+ intdiv( 153 * ( ( $gm + 9 ) % 12 ) + 2, 5 )
			+ $gd - 34840408;
		return $d - intdiv( intdiv( $gy + 100100 + intdiv( $gm - 8, 6 ), 100 ) * 3, 4 ) + 752;
	}

	/**
	 * Julian Day Number to Gregorian date.
	 *
	 * @param int $jdn Julian Day Number.
	 * @return int[] `[ gy, gm, gd ]`.
	 */
	private static function d2g( int $jdn ): array {
		$j  = 4 * $jdn + 139361631;
		$j += intdiv( intdiv( 4 * $jdn + 183187720, 146097 ) * 3, 4 ) * 4 - 3908;
		$i  = intdiv( $j % 1461, 4 ) * 5 + 308;
		$gm = ( intdiv( $i, 153 ) % 12 ) + 1;
		return [ intdiv( $j, 1461 ) - 100100 + intdiv( 8 - $gm, 6 ), $gm, intdiv( $i % 153, 5 ) + 1 ];
	}
}
