<?php
/**
 * Transient-backed rate-limit store.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Rate_Limit_Store on WordPress transients (the object cache when one is installed).
 *
 * @since 1.0.0
 */
final class Transient_Store implements Rate_Limit_Store {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 * @return array|null
	 */
	public function get( string $key ): ?array {
		$value = get_transient( $key );
		return is_array( $value ) ? $value : null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Storage key.
	 * @param array  $value Record.
	 * @param int    $ttl   Seconds until expiry.
	 */
	public function set( string $key, array $value, int $ttl ): void {
		set_transient( $key, $value, max( 1, $ttl ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 */
	public function delete( string $key ): void {
		delete_transient( $key );
	}
}
