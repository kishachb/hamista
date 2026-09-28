<?php
/**
 * Unit tests for Hamista\License\Support\Domain (spec §7).
 *
 * @package Hamista\License\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/class-domain.php';

use Hamista\License\Support\Domain;

// -- normalize() -------------------------------------------------------

function test_normalize_a_plain_domain() {
	assert_same( 'example.com', Domain::normalize( 'example.com' ) );
}

function test_normalize_lowercases() {
	assert_same( 'example.com', Domain::normalize( 'ExAmple.COM' ) );
}

function test_normalize_strips_the_http_scheme() {
	assert_same( 'example.com', Domain::normalize( 'http://example.com' ) );
}

function test_normalize_strips_the_https_scheme() {
	assert_same( 'example.com', Domain::normalize( 'https://example.com' ) );
}

function test_normalize_strips_userinfo() {
	assert_same( 'example.com', Domain::normalize( 'https://user:pass@example.com' ) );
}

function test_normalize_strips_a_port() {
	assert_same( 'example.com', Domain::normalize( 'example.com:8080' ) );
}

function test_normalize_strips_scheme_and_port_together() {
	assert_same( 'example.com', Domain::normalize( 'https://example.com:443' ) );
}

function test_normalize_strips_a_path() {
	assert_same( 'example.com', Domain::normalize( 'example.com/some/path' ) );
}

function test_normalize_strips_a_query_string() {
	assert_same( 'example.com', Domain::normalize( 'example.com?foo=bar' ) );
}

function test_normalize_strips_a_fragment() {
	assert_same( 'example.com', Domain::normalize( 'example.com#section' ) );
}

function test_normalize_strips_path_query_and_fragment_together() {
	assert_same( 'example.com', Domain::normalize( 'https://example.com/a/b?x=1#y' ) );
}

function test_normalize_strips_a_trailing_dot() {
	assert_same( 'example.com', Domain::normalize( 'example.com.' ) );
}

function test_normalize_strips_a_leading_www() {
	assert_same( 'example.com', Domain::normalize( 'www.example.com' ) );
}

function test_normalize_strips_www_only_as_the_leading_label() {
	assert_same( 'www2.example.com', Domain::normalize( 'www2.example.com' ) );
}

function test_normalize_keeps_other_subdomains() {
	assert_same( 'shop.example.com', Domain::normalize( 'shop.example.com' ) );
}

function test_normalize_a_full_url_with_www_and_path() {
	assert_same( 'example.com', Domain::normalize( 'https://www.example.com/wp-admin/' ) );
}

function test_normalize_converts_idn_to_ascii() {
	$result = Domain::normalize( 'تست.ir' );
	assert_true( '' !== $result, 'a valid IDN should normalize to a non-empty punycode string' );
	assert_true( str_starts_with( $result, 'xn--' ) || 1 === preg_match( '/\.xn--/', $result ), $result );
}

function test_normalize_idn_with_scheme_and_www() {
	$result = Domain::normalize( 'https://www.تست.ir/' );
	assert_true( str_ends_with( $result, '.ir' ), $result );
	assert_false( str_contains( $result, 'www' ), $result );
}

function test_normalize_an_ipv4_address() {
	assert_same( '203.0.113.5', Domain::normalize( '203.0.113.5' ) );
}

function test_normalize_an_ipv4_address_with_port() {
	assert_same( '203.0.113.5', Domain::normalize( '203.0.113.5:8080' ) );
}

function test_normalize_localhost() {
	assert_same( 'localhost', Domain::normalize( 'localhost' ) );
}

function test_normalize_localhost_with_port() {
	assert_same( 'localhost', Domain::normalize( 'localhost:3000' ) );
}

function test_normalize_a_staging_subdomain() {
	assert_same( 'staging.example.com', Domain::normalize( 'https://staging.example.com' ) );
}

function test_normalize_rejects_a_label_over_63_characters() {
	$label = str_repeat( 'a', 64 );
	assert_same( '', Domain::normalize( $label . '.com' ) );
}

function test_normalize_accepts_a_label_at_63_characters() {
	$label = str_repeat( 'a', 63 );
	assert_same( $label . '.com', Domain::normalize( $label . '.com' ) );
}

function test_normalize_rejects_a_leading_hyphen_label() {
	assert_same( '', Domain::normalize( '-example.com' ) );
}

function test_normalize_rejects_a_trailing_hyphen_label() {
	assert_same( '', Domain::normalize( 'example-.com' ) );
}

function test_normalize_accepts_internal_hyphens() {
	assert_same( 'my-shop.example.com', Domain::normalize( 'my-shop.example.com' ) );
}

function test_normalize_rejects_an_empty_string() {
	assert_same( '', Domain::normalize( '' ) );
}

function test_normalize_rejects_an_empty_label() {
	assert_same( '', Domain::normalize( 'example..com' ) );
}

function test_normalize_rejects_invalid_characters() {
	assert_same( '', Domain::normalize( 'exa mple.com' ) );
	assert_same( '', Domain::normalize( 'exam_ple.com' ) );
}

function test_normalize_rejects_a_bare_tld_with_no_label() {
	assert_same( '', Domain::normalize( '.com' ) );
}

// -- is_local() ----------------------------------------------------------

function test_is_local_localhost() {
	assert_true( Domain::is_local( 'localhost' ) );
}

function test_is_local_loopback_ipv4() {
	assert_true( Domain::is_local( '127.0.0.1' ) );
	assert_true( Domain::is_local( '127.5.5.5' ), '127.0.0.0/8' );
}

function test_is_local_loopback_ipv6() {
	assert_true( Domain::is_local( '::1' ) );
}

function test_is_local_private_ipv4_ranges() {
	assert_true( Domain::is_local( '10.0.0.5' ) );
	assert_true( Domain::is_local( '192.168.1.1' ) );
	assert_true( Domain::is_local( '172.16.0.1' ) );
}

function test_is_local_a_public_ip_is_not_local() {
	assert_false( Domain::is_local( '8.8.8.8' ) );
}

function test_is_local_reserved_tlds() {
	assert_true( Domain::is_local( 'myapp.local' ) );
	assert_true( Domain::is_local( 'myapp.localhost' ) );
	assert_true( Domain::is_local( 'myapp.test' ) );
	assert_true( Domain::is_local( 'myapp.invalid' ) );
	assert_true( Domain::is_local( 'myapp.example' ) );
}

function test_is_local_staging_subdomains() {
	assert_true( Domain::is_local( 'staging.example.com' ) );
	assert_true( Domain::is_local( 'stage.example.com' ) );
	assert_true( Domain::is_local( 'dev.example.com' ) );
	assert_true( Domain::is_local( 'test.example.com' ) );
	assert_true( Domain::is_local( 'local.example.com' ) );
}

function test_is_local_a_real_domain_is_not_local() {
	assert_false( Domain::is_local( 'example.com' ) );
	assert_false( Domain::is_local( 'shop.example.com' ) );
}

function test_is_local_extra_patterns() {
	assert_true( Domain::is_local( 'preview.example.com', [ 'preview.*' ] ) );
	assert_false( Domain::is_local( 'preview.example.com', [ 'other.*' ] ) );
}

function test_is_local_non_matching_first_label_lookalike() {
	// "testing.example.com" should NOT match the "test" first-label rule.
	assert_false( Domain::is_local( 'testing.example.com' ) );
}
