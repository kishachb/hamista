<?php
/**
 * In-memory rate-limit store.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Rate_Limit_Store that lives for one PHP request.
 *
 * Used by unit tests, and usable for limits that only matter within a
 * single request.
 *
 * @since 1.0.0
 */
final class Array_Store implements Rate_Limit_Store {

	/**
	 * Records by key: `[ 'value' => array, 'expires' => int ]`.
	 *
	 * @var array<string, array{value:array,expires:int}>
	 */
	private array $records = [];

	/**
	 * Returns the current Unix time.
	 *
	 * @var callable
	 */
	private $clock;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param callable|null $clock Optional. Returns the current Unix time. Defaults to time().
	 */
	public function __construct( ?callable $clock = null ) {
		$this->clock = $clock ?? 'time';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 * @return array|null
	 */
	public function get( string $key ): ?array {
		if ( ! isset( $this->records[ $key ] ) ) {
			return null;
		}
		if ( $this->records[ $key ]['expires'] <= (int) ( $this->clock )() ) {
			unset( $this->records[ $key ] );
			return null;
		}
		return $this->records[ $key ]['value'];
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
		$this->records[ $key ] = [
			'value'   => $value,
			'expires' => (int) ( $this->clock )() + max( 1, $ttl ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 */
	public function delete( string $key ): void {
		unset( $this->records[ $key ] );
	}
}
