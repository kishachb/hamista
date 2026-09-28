<?php
/**
 * Domain normalisation and local-domain detection (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Turns a URL, host or IP into a comparable domain, and decides whether a
 * domain should be treated as "local" (a development copy that should not
 * count against an activation limit).
 *
 * Pure PHP: no WordPress functions, so it loads under HAMISTA_TESTS.
 *
 * @since 1.0.0
 */
final class Domain {

	/**
	 * TLDs that are always local (RFC 2606 plus the common `.local`).
	 */
	private const LOCAL_TLDS = [ 'local', 'localhost', 'test', 'invalid', 'example' ];

	/**
	 * First (left-most) labels that mark a subdomain as local.
	 */
	private const LOCAL_FIRST_LABELS = [ 'staging', 'stage', 'dev', 'test', 'local' ];

	/**
	 * Normalises a URL, host or `host:port` into a bare, lower-case domain
	 * or IP address.
	 *
	 * Strips the scheme, userinfo, port, path, query, fragment and a
	 * trailing dot; strips a single leading `www.` label; lower-cases;
	 * converts an IDN host to ASCII (punycode).
	 *
	 * @since 1.0.0
	 *
	 * @param string $url_or_host A URL, bare host name, or IP (with an
	 *                             optional scheme and/or port).
	 * @return string The normalised domain/IP, or '' when it is not valid.
	 */
	public static function normalize( string $url_or_host ): string {
		$value = trim( $url_or_host );
		if ( '' === $value ) {
			return '';
		}

		// Add a scheme when missing so parse_url() reliably finds the host,
		// even for input like "example.com:8080" (which parse_url() would
		// otherwise treat as scheme "example.com").
		if ( ! preg_match( '#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $value ) ) {
			$value = '//' . ltrim( $value, '/' );
		}

		$host = parse_url( $value, PHP_URL_HOST ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure class, no WordPress available.
		$host = is_string( $host ) ? $host : '';
		if ( '' === $host ) {
			return '';
		}

		$host = strtolower( rtrim( $host, '.' ) );

		// Strip IPv6 brackets, e.g. "[::1]" from parse_url().
		if ( str_starts_with( $host, '[' ) && str_ends_with( $host, ']' ) ) {
			$host = substr( $host, 1, -1 );
		}

		if ( self::is_ip( $host ) ) {
			return $host;
		}

		if ( 'localhost' === $host ) {
			return 'localhost';
		}

		if ( str_starts_with( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		$ascii = self::to_ascii( $host );
		if ( '' === $ascii || ! self::is_valid_hostname( $ascii ) ) {
			return '';
		}

		return $ascii;
	}

	/**
	 * Whether a normalised domain should be treated as local (a development
	 * copy that need not count against an activation limit).
	 *
	 * @since 1.0.0
	 *
	 * @param string   $domain         Already-normalised domain (see normalize()).
	 * @param string[] $extra_patterns Extra fnmatch() patterns, e.g. `[ '*.preview.example.com' ]`.
	 * @return bool
	 */
	public static function is_local( string $domain, array $extra_patterns = [] ): bool {
		if ( '' === $domain ) {
			return false;
		}

		if ( 'localhost' === $domain ) {
			return true;
		}

		if ( self::is_ip( $domain ) && self::is_reserved_ip( $domain ) ) {
			return true;
		}

		$labels = explode( '.', $domain );
		$tld    = end( $labels );
		if ( in_array( $tld, self::LOCAL_TLDS, true ) ) {
			return true;
		}

		if ( count( $labels ) > 1 && in_array( $labels[0], self::LOCAL_FIRST_LABELS, true ) ) {
			return true;
		}

		foreach ( $extra_patterns as $pattern ) {
			if ( is_string( $pattern ) && '' !== $pattern && fnmatch( $pattern, $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a string is an IPv4 or IPv6 address.
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	private static function is_ip( string $value ): bool {
		return false !== filter_var( $value, FILTER_VALIDATE_IP );
	}

	/**
	 * Whether an IP is loopback, private or otherwise reserved (not
	 * globally routable).
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private static function is_reserved_ip( string $ip ): bool {
		if ( '::1' === $ip || str_starts_with( $ip, '127.' ) ) {
			return true;
		}
		return false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Converts an IDN host to ASCII (punycode) when the intl extension is
	 * available; otherwise passes plain-ASCII hosts through unchanged and
	 * rejects hosts with non-ASCII characters.
	 *
	 * @param string $host Lower-case host.
	 * @return string ASCII host, or '' when conversion failed.
	 */
	private static function to_ascii( string $host ): string {
		if ( 1 === preg_match( '/^[\x00-\x7F]*$/', $host ) ) {
			return $host;
		}
		if ( ! function_exists( 'idn_to_ascii' ) ) {
			return '';
		}
		$flag   = defined( 'IDNA_NONTRANSITIONAL_TO_ASCII' ) ? IDNA_NONTRANSITIONAL_TO_ASCII : 0;
		$result = idn_to_ascii( $host, $flag, INTL_IDNA_VARIANT_UTS46 );
		return is_string( $result ) ? $result : '';
	}

	/**
	 * Validates an ASCII host as a sequence of DNS labels.
	 *
	 * Each label: 1-63 characters of `[a-z0-9-]`, no leading or trailing hyphen.
	 *
	 * @param string $host ASCII host.
	 * @return bool
	 */
	private static function is_valid_hostname( string $host ): bool {
		if ( '' === $host || strlen( $host ) > 253 ) {
			return false;
		}
		$labels = explode( '.', $host );
		if ( count( $labels ) < 2 ) {
			// A bare single label (no dot) is not a valid public domain,
			// except the "localhost" special case handled by normalize().
			return false;
		}
		foreach ( $labels as $label ) {
			if ( 1 !== preg_match( '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $label ) ) {
				return false;
			}
		}
		return true;
	}
}
