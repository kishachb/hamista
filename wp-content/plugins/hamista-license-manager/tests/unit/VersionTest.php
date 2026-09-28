<?php
/**
 * Unit tests for Hamista\License\Support\Version (spec §7).
 *
 * @package Hamista\License\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-version.php';

use Hamista\License\Support\Version;

/**
 * is_valid() accepts semver-ish strings: 1 to 4 numeric segments, optional
 * `-prerelease` suffix.
 */
function test_is_valid_accepts_semver_ish_strings() {
	assert_true( Version::is_valid( '1' ) );
	assert_true( Version::is_valid( '1.2' ) );
	assert_true( Version::is_valid( '1.2.3' ) );
	assert_true( Version::is_valid( '1.2.3.4' ) );
	assert_true( Version::is_valid( '1.2.3-beta' ) );
	assert_true( Version::is_valid( '1.2.3-beta.1' ) );
	assert_true( Version::is_valid( '2.0.0-rc1' ) );
	assert_true( Version::is_valid( '0.0.1' ) );
}

/**
 * is_valid() rejects malformed strings.
 */
function test_is_valid_rejects_malformed_strings() {
	assert_false( Version::is_valid( '' ) );
	assert_false( Version::is_valid( 'v1.2.3' ), 'leading v is not accepted' );
	assert_false( Version::is_valid( '1.2.3.4.5' ), 'too many segments' );
	assert_false( Version::is_valid( '1..2' ) );
	assert_false( Version::is_valid( '1.2.' ) );
	assert_false( Version::is_valid( '.1.2' ) );
	assert_false( Version::is_valid( '1.2.3-' ), 'empty prerelease' );
	assert_false( Version::is_valid( 'abc' ) );
	assert_false( Version::is_valid( '1.a.3' ) );
}

/**
 * compare() delegates to version_compare() for valid versions.
 */
function test_compare_delegates_to_version_compare_for_valid_versions() {
	assert_same( -1, Version::compare( '1.0.0', '1.0.1' ) );
	assert_same( 1, Version::compare( '1.2.0', '1.1.9' ) );
	assert_same( version_compare( '1.2', '1.2.0' ), Version::compare( '1.2', '1.2.0' ), 'mirrors version_compare(), including its "missing segment sorts lower" behaviour' );
	assert_same( -1, Version::compare( '1.0.0-beta', '1.0.0' ), 'a prerelease sorts before the release' );
}

/**
 * compare() throws on an invalid version string, so callers never silently
 * mis-order releases.
 */
function test_compare_throws_on_an_invalid_version() {
	assert_throws( \InvalidArgumentException::class, static fn() => Version::compare( 'not-a-version', '1.0.0' ) );
	assert_throws( \InvalidArgumentException::class, static fn() => Version::compare( '1.0.0', 'v2' ) );
}
