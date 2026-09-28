<?php
/**
 * Unit tests for Hamista\Dashboard\Support\Mobile.
 *
 * @package Hamista\Dashboard\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-mobile.php';

use Hamista\Dashboard\Support\Mobile;

function test_mobile_accepts_the_canonical_09_format() {
	assert_same( '09121234567', Mobile::normalize( '09121234567' ) );
	assert_true( Mobile::is_valid( '09121234567' ) );
}

function test_mobile_normalizes_the_plus98_format() {
	assert_same( '09121234567', Mobile::normalize( '+989121234567' ) );
}

function test_mobile_normalizes_the_0098_format() {
	assert_same( '09121234567', Mobile::normalize( '00989121234567' ) );
}

function test_mobile_normalizes_the_bare_98_format() {
	assert_same( '09121234567', Mobile::normalize( '989121234567' ) );
}

function test_mobile_normalizes_the_bare_9_format() {
	assert_same( '09121234567', Mobile::normalize( '9121234567' ) );
}

function test_mobile_normalizes_persian_digits() {
	assert_same( '09121234567', Mobile::normalize( '۰۹۱۲۱۲۳۴۵۶۷' ) );
}

function test_mobile_normalizes_arabic_indic_digits() {
	assert_same( '09121234567', Mobile::normalize( '٠٩١٢١٢٣٤٥٦٧' ) );
}

function test_mobile_normalizes_a_plus98_number_with_persian_digits() {
	assert_same( '09121234567', Mobile::normalize( '+۹۸۹۱۲۱۲۳۴۵۶۷' ) );
}

function test_mobile_strips_spaces_and_dashes() {
	assert_same( '09121234567', Mobile::normalize( '0912-123 4567' ) );
}

function test_mobile_rejects_a_short_number() {
	assert_same( '', Mobile::normalize( '12345' ) );
}

function test_mobile_rejects_a_landline_number() {
	assert_same( '', Mobile::normalize( '02112345678' ) );
}

function test_mobile_rejects_an_empty_string() {
	assert_same( '', Mobile::normalize( '' ) );
}

function test_mobile_rejects_a_too_long_number() {
	assert_same( '', Mobile::normalize( '091212345678' ) );
}

function test_mobile_rejects_letters() {
	assert_same( '', Mobile::normalize( 'not-a-phone' ) );
}

function test_mobile_is_valid_rejects_an_unnormalised_value() {
	assert_false( Mobile::is_valid( '+989121234567' ) );
	assert_false( Mobile::is_valid( '9121234567' ) );
}
