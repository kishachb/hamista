<?php
/**
 * Request information.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Client IP detection that honours the "IP header" setting.
 *
 * Only trust a forwarding header when the site really sits behind a proxy
 * that sets it (Cloudflare, ArvanCloud, a load balancer): visitors can send
 * any header themselves.
 *
 * @since 1.0.0
 */
final class Request {

	/**
	 * `$_SERVER` keys the `ip_header` setting may choose.
	 */
	public const IP_HEADERS = [ 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'HTTP_AR_REAL_IP', 'HTTP_X_REAL_IP' ];

	/**
	 * The visitor's IP address.
	 *
	 * Reads the configured header (`hamista_core` → `ip_header`, default
	 * REMOTE_ADDR), takes the first valid IP of a comma-separated list, and
	 * falls back to REMOTE_ADDR, then to '0.0.0.0'.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function client_ip(): string {
		$header = (string) Options::get( 'hamista_core', 'ip_header', 'REMOTE_ADDR' );
		if ( ! in_array( $header, self::IP_HEADERS, true ) ) {
			$header = 'REMOTE_ADDR';
		}

		$ip = self::first_valid_ip( self::server( $header ) );
		if ( '' === $ip && 'REMOTE_ADDR' !== $header ) {
			$ip = self::first_valid_ip( self::server( 'REMOTE_ADDR' ) );
		}
		$ip = '' === $ip ? '0.0.0.0' : $ip;

		/**
		 * Filters the detected client IP.
		 *
		 * @since 1.0.0
		 *
		 * @param string $ip     Detected IP address.
		 * @param string $header The `$_SERVER` key that was read.
		 */
		return (string) apply_filters( 'hamista_client_ip', $ip, $header );
	}

	/**
	 * First valid IPv4/IPv6 address in a comma-separated list. Ports and
	 * IPv6 brackets (`1.2.3.4:80`, `[::1]:443`) are stripped.
	 *
	 * @since 1.0.0
	 *
	 * @param string $header_value Header value.
	 * @return string '' when none is valid.
	 */
	public static function first_valid_ip( string $header_value ): string {
		foreach ( explode( ',', $header_value ) as $candidate ) {
			$candidate = trim( $candidate );
			if ( 1 === preg_match( '/^\[([0-9a-f:.]+)\](?::\d+)?$/i', $candidate, $match ) ) {
				$candidate = $match[1];
			} elseif ( 1 === preg_match( '/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $candidate, $match ) ) {
				$candidate = $match[1];
			}
			if ( false !== filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				return $candidate;
			}
		}
		return '';
	}

	/**
	 * A sanitized `$_SERVER` value.
	 *
	 * @param string $key One of IP_HEADERS.
	 * @return string
	 */
	private static function server( string $key ): string {
		return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : '';
	}
}
