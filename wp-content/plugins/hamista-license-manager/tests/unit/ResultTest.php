<?php
/**
 * Unit tests for Hamista\License\Support\Result (spec §7).
 *
 * @package Hamista\License\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-result.php';

use Hamista\License\Support\Result;

/**
 * ok() builds a successful result with the given code, message and data.
 */
function test_ok_builds_a_successful_result() {
	$result = Result::ok( 'activated', 'The license was activated.', [ 'license' => [ 'status' => 'active' ] ] );

	assert_true( $result->success );
	assert_same( 'activated', $result->code );
	assert_same( 'The license was activated.', $result->message );
	assert_same( [ 'license' => [ 'status' => 'active' ] ], $result->data );
}

/**
 * ok() defaults data to an empty array.
 */
function test_ok_defaults_data_to_empty_array() {
	$result = Result::ok( 'valid', 'OK' );
	assert_same( [], $result->data );
}

/**
 * error() builds a failed result with an HTTP status, defaulting to 400.
 */
function test_error_builds_a_failed_result_with_default_http_status() {
	$result = Result::error( 'invalid_domain', 'The domain is invalid.' );

	assert_false( $result->success );
	assert_same( 'invalid_domain', $result->code );
	assert_same( 'The domain is invalid.', $result->message );
	assert_same( 400, $result->http_status() );
}

/**
 * error() accepts an explicit HTTP status.
 */
function test_error_accepts_an_explicit_http_status() {
	$result = Result::error( 'license_not_found', 'No such license.', 404 );
	assert_same( 404, $result->http_status() );
}

/**
 * http_status() on a successful result is always 200, regardless of what an
 * error() call elsewhere might have used.
 */
function test_http_status_on_success_is_200() {
	$result = Result::ok( 'valid', 'OK' );
	assert_same( 200, $result->http_status() );
}

/**
 * to_array() exposes success, code, message and data as a plain array,
 * suitable for a REST response body.
 */
function test_to_array_exposes_the_rest_shape() {
	$result = Result::ok( 'activated', 'Activated.', [ 'license' => [ 'status' => 'active' ] ] );
	assert_same(
		[
			'success' => true,
			'code'    => 'activated',
			'message' => 'Activated.',
			'data'    => [ 'license' => [ 'status' => 'active' ] ],
		],
		$result->to_array()
	);

	$error = Result::error( 'rate_limited', 'Too many requests.', 429 );
	assert_same(
		[
			'success' => false,
			'code'    => 'rate_limited',
			'message' => 'Too many requests.',
			'data'    => [],
		],
		$error->to_array()
	);
}
