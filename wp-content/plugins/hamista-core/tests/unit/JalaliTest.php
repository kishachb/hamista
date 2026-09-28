<?php
/**
 * Unit tests for Hamista\Core\Support\Jalali.
 *
 * Expected values are hand-derived from known Nowruz (1 Farvardin) dates:
 * 1399 → 2020-03-20, 1400 → 2021-03-21, 1402 → 2023-03-21, 1403 → 2024-03-20,
 * 1404 → 2025-03-21, 1405 → 2026-03-21. A 366-day gap between two Nowruz dates
 * marks a leap year (1399, 1403); a 365-day gap marks a common year (1402, 1404).
 *
 * @package Hamista\Core\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-jalali.php';

use Hamista\Core\Support\Jalali;

/**
 * Builds a UTC timestamp.
 *
 * @param string $datetime 'Y-m-d H:i' in UTC.
 * @return int
 */
function hm_jalali_test_utc( string $datetime ): int {
	return ( new DateTimeImmutable( $datetime, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
}

function test_nowruz_1404_is_march_21_2025() {
	assert_same( [ 1404, 1, 1 ], Jalali::to_jalali( 2025, 3, 21 ) );
}

function test_nowruz_1403_is_march_20_2024() {
	assert_same( [ 1403, 1, 1 ], Jalali::to_jalali( 2024, 3, 20 ) );
}

function test_nowruz_1402_is_march_21_2023() {
	assert_same( [ 1402, 1, 1 ], Jalali::to_jalali( 2023, 3, 21 ) );
}

function test_nowruz_1405_is_march_21_2026() {
	assert_same( [ 1405, 1, 1 ], Jalali::to_jalali( 2026, 3, 21 ) );
}

function test_september_28_2026_is_mehr_6_1405() {
	// 191 days after Nowruz 1405: six 31-day months (186 days) + 5 → 6 Mehr.
	assert_same( [ 1405, 7, 6 ], Jalali::to_jalali( 2026, 9, 28 ) );
}

function test_last_day_of_a_year_is_the_day_before_nowruz() {
	assert_same( [ 1403, 12, 30 ], Jalali::to_jalali( 2025, 3, 20 ) );
	assert_same( [ 1402, 12, 29 ], Jalali::to_jalali( 2024, 3, 19 ) );
}

function test_to_gregorian_converts_known_dates() {
	assert_same( [ 2025, 3, 21 ], Jalali::to_gregorian( 1404, 1, 1 ) );
	assert_same( [ 2026, 9, 28 ], Jalali::to_gregorian( 1405, 7, 6 ) );
	assert_same( [ 2025, 3, 20 ], Jalali::to_gregorian( 1403, 12, 30 ) );
	assert_same( [ 2024, 3, 19 ], Jalali::to_gregorian( 1402, 12, 29 ) );
}

function test_is_leap_matches_nowruz_gaps() {
	assert_true( Jalali::is_leap( 1399 ) );
	assert_true( Jalali::is_leap( 1403 ) );
	assert_false( Jalali::is_leap( 1402 ) );
	assert_false( Jalali::is_leap( 1404 ) );
}

function test_month_length_follows_the_calendar_rules() {
	assert_same( 31, Jalali::month_length( 1404, 1 ) );
	assert_same( 31, Jalali::month_length( 1404, 6 ) );
	assert_same( 30, Jalali::month_length( 1404, 7 ) );
	assert_same( 30, Jalali::month_length( 1404, 11 ) );
	assert_same( 30, Jalali::month_length( 1403, 12 ), 'Esfand of a leap year' );
	assert_same( 29, Jalali::month_length( 1402, 12 ), 'Esfand of a common year' );
}

function test_month_length_rejects_months_outside_1_to_12() {
	assert_throws( InvalidArgumentException::class, static fn() => Jalali::month_length( 1404, 13 ) );
	assert_throws( InvalidArgumentException::class, static fn() => Jalali::month_length( 1404, 0 ) );
}

function test_is_valid_rejects_impossible_dates() {
	assert_true( Jalali::is_valid( 1403, 12, 30 ) );
	assert_false( Jalali::is_valid( 1402, 12, 30 ), 'Esfand 30 only exists in leap years' );
	assert_false( Jalali::is_valid( 1404, 7, 31 ) );
	assert_false( Jalali::is_valid( 1404, 13, 1 ) );
	assert_false( Jalali::is_valid( 1404, 0, 1 ) );
	assert_false( Jalali::is_valid( 1404, 1, 0 ) );
}

function test_out_of_range_years_throw() {
	assert_throws( InvalidArgumentException::class, static fn() => Jalali::to_gregorian( 3500, 1, 1 ) );
	assert_throws( InvalidArgumentException::class, static fn() => Jalali::to_jalali( 4200, 1, 1 ) );
}

function test_round_trip_over_1000_random_dates_between_1950_and_2100() {
	mt_srand( 20260928 );
	$min = hm_jalali_test_utc( '1950-01-01 00:00' );
	$max = hm_jalali_test_utc( '2100-12-31 00:00' );
	for ( $i = 0; $i < 1000; $i++ ) {
		$day              = gmdate( 'Y-n-j', mt_rand( $min, $max ) );
		[ $gy, $gm, $gd ] = array_map( 'intval', explode( '-', $day ) );
		[ $jy, $jm, $jd ] = Jalali::to_jalali( $gy, $gm, $gd );
		assert_true( Jalali::is_valid( $jy, $jm, $jd ), "{$day} → {$jy}-{$jm}-{$jd} must be a valid Jalali date" );
		assert_same( [ $gy, $gm, $gd ], Jalali::to_gregorian( $jy, $jm, $jd ), "round trip of {$day}" );
	}
	mt_srand();
}

function test_consecutive_days_stay_consecutive() {
	// Walks 2 years day by day: each Jalali date is the successor of the previous one.
	$utc  = new DateTimeZone( 'UTC' );
	$day  = new DateTimeImmutable( '2024-01-01', $utc );
	$prev = Jalali::to_jalali( 2023, 12, 31 );
	for ( $i = 0; $i < 731; $i++ ) {
		$current          = Jalali::to_jalali( (int) $day->format( 'Y' ), (int) $day->format( 'n' ), (int) $day->format( 'j' ) );
		[ $py, $pm, $pd ] = $prev;
		if ( $pd < Jalali::month_length( $py, $pm ) ) {
			$expected = [ $py, $pm, $pd + 1 ];
		} elseif ( $pm < 12 ) {
			$expected = [ $py, $pm + 1, 1 ];
		} else {
			$expected = [ $py + 1, 1, 1 ];
		}
		assert_same( $expected, $current, $day->format( 'Y-m-d' ) );
		$prev = $current;
		$day  = $day->modify( '+1 day' );
	}
}

function test_format_numeric_date() {
	assert_same( '1405/07/06', Jalali::format( 'Y/m/d', hm_jalali_test_utc( '2026-09-28 12:00' ), new DateTimeZone( 'UTC' ) ) );
}

function test_format_day_month_name_and_year() {
	assert_same( '6 مهر 1405', Jalali::format( 'j F Y', hm_jalali_test_utc( '2026-09-28 12:00' ), new DateTimeZone( 'UTC' ) ) );
}

function test_format_weekday_names_start_on_sunday() {
	$utc = new DateTimeZone( 'UTC' );
	// 2026-09-28 is a Monday; 2026-09-26 a Saturday; 2026-09-27 a Sunday.
	assert_same( 'دوشنبه', Jalali::format( 'l', hm_jalali_test_utc( '2026-09-28 12:00' ), $utc ) );
	assert_same( 'شنبه', Jalali::format( 'l', hm_jalali_test_utc( '2026-09-26 12:00' ), $utc ) );
	assert_same( 'یکشنبه', Jalali::format( 'l', hm_jalali_test_utc( '2026-09-27 12:00' ), $utc ) );
	assert_same( 'د', Jalali::format( 'D', hm_jalali_test_utc( '2026-09-28 12:00' ), $utc ) );
	assert_same( '1 1', Jalali::format( 'w N', hm_jalali_test_utc( '2026-09-28 12:00' ), $utc ) );
}

function test_format_uses_the_given_timezone() {
	// 21:00 UTC on 27 Sep is 00:30 on 28 Sep in Tehran (UTC+03:30).
	$timestamp = hm_jalali_test_utc( '2026-09-27 21:00' );
	assert_same( '1405/07/05 21:00', Jalali::format( 'Y/m/d H:i', $timestamp, new DateTimeZone( 'UTC' ) ) );
	assert_same( '1405/07/06 00:30', Jalali::format( 'Y/m/d H:i', $timestamp, new DateTimeZone( 'Asia/Tehran' ) ) );
}

function test_format_month_tokens() {
	$utc = new DateTimeZone( 'UTC' );
	// 2025-03-10 is 20 Esfand 1403, the last month of a leap year.
	$esfand = hm_jalali_test_utc( '2025-03-10 08:00' );
	assert_same( '12 12 30 1 03', Jalali::format( 'n m t L y', $esfand, $utc ) );
	assert_same( 'اسفند', Jalali::format( 'F', $esfand, $utc ) );
	assert_same( 'فروردین', Jalali::format( 'F', hm_jalali_test_utc( '2025-03-21 08:00' ), $utc ) );
	assert_same( '0 L', Jalali::format( 'L \\L', hm_jalali_test_utc( '2025-06-01 08:00' ), $utc ), 'escaped L is literal' );
}

function test_format_day_of_year_and_ordinal_suffix() {
	$utc = new DateTimeZone( 'UTC' );
	// 6 Mehr: 186 days in the first six months + 5 → zero-based day 191.
	assert_same( '191', Jalali::format( 'z', hm_jalali_test_utc( '2026-09-28 12:00' ), $utc ) );
	assert_same( '6 مهر', Jalali::format( 'jS F', hm_jalali_test_utc( '2026-09-28 12:00' ), $utc ), 'no English ordinal suffix' );
}

function test_format_meridiem_and_time_tokens() {
	$utc = new DateTimeZone( 'UTC' );
	assert_same( 'ق.ظ', Jalali::format( 'a', hm_jalali_test_utc( '2026-09-28 09:00' ), $utc ) );
	assert_same( 'ب.ظ', Jalali::format( 'A', hm_jalali_test_utc( '2026-09-28 15:00' ), $utc ) );
	assert_same( '3:05 03:05:00 15 3', Jalali::format( 'g:i h:i:s G g', hm_jalali_test_utc( '2026-09-28 15:05' ), $utc ) );
}

function test_format_escapes_and_delegated_tokens() {
	$utc       = new DateTimeZone( 'UTC' );
	$timestamp = hm_jalali_test_utc( '2026-09-28 12:00' );
	assert_same( 'Y 1405', Jalali::format( '\\Y Y', $timestamp, $utc ) );
	assert_same( '\\1405', Jalali::format( '\\\\Y', $timestamp, $utc ) );
	assert_same( (string) $timestamp, Jalali::format( 'U', $timestamp, $utc ) );
	assert_same( 'Asia/Tehran +03:30', Jalali::format( 'e P', $timestamp, new DateTimeZone( 'Asia/Tehran' ) ) );
	assert_same( '۶ مهر', Jalali::format( '۶ F', $timestamp, $utc ), 'multibyte literals pass through' );
}

function test_persian_digits_converts_latin_digits() {
	assert_same( '۱۴۰۵/۰۷/۰۶', Jalali::persian_digits( '1405/07/06' ) );
	assert_same( 'abc', Jalali::persian_digits( 'abc' ) );
}

function test_persian_digits_normalises_arabic_indic_digits() {
	// Arabic-Indic ٤٥٦ look different from Persian ۴۵۶.
	assert_same( '۱۲۳۴۵۶', Jalali::persian_digits( '١٢٣٤٥٦' ) );
}

function test_latin_digits_converts_persian_and_arabic_indic_digits() {
	assert_same( '1405/07/06', Jalali::latin_digits( '۱۴۰۵/۰۷/۰۶' ) );
	assert_same( '0123456789', Jalali::latin_digits( '٠١٢٣٤٥٦٧٨٩' ) );
	assert_same( '0123456789', Jalali::latin_digits( '۰۱۲۳۴۵۶۷۸۹' ) );
}
