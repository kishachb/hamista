<?php
/**
 * Storage contract for the rate limiter.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Key/value storage with expiry, used by Rate_Limiter.
 *
 * @since 1.0.0
 */
interface Rate_Limit_Store {

	/**
	 * Reads a record.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key (already hashed by the limiter).
	 * @return array|null The record, or null when missing or expired.
	 */
	public function get( string $key ): ?array;

	/**
	 * Writes a record that expires after `$ttl` seconds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Storage key.
	 * @param array  $value Record.
	 * @param int    $ttl   Seconds until the record expires (at least 1).
	 */
	public function set( string $key, array $value, int $ttl ): void;

	/**
	 * Deletes a record.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 */
	public function delete( string $key ): void;
}
