<?php
/**
 * Fixed-window rate limiter.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Fixed-window counter: at most `$limit` hits per bucket in each window.
 *
 * The window starts at the first hit and ends `$window` seconds later.
 * Bucket names (which often contain IP addresses or user IDs) are hashed
 * before they reach the store.
 *
 * The read-then-write update is not atomic, so under heavy concurrency a
 * few extra hits can slip through. That is acceptable for abuse throttling
 * (login, forms, public APIs), not for quota accounting.
 *
 * Usage: `hamista_rate_limit( 'login:' . hamista_client_ip(), 5, 300 )`.
 *
 * @since 1.0.0
 */
final class Rate_Limiter {

	/**
	 * Prefix of every storage key.
	 */
	private const KEY_PREFIX = 'hamista_rl_';

	/**
	 * Where counters are kept.
	 *
	 * @var Rate_Limit_Store
	 */
	private Rate_Limit_Store $store;

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
	 * @param Rate_Limit_Store $store Counter storage.
	 * @param callable|null    $clock Optional. Returns the current Unix time. Defaults to time().
	 */
	public function __construct( Rate_Limit_Store $store, ?callable $clock = null ) {
		$this->store = $store;
		$this->clock = $clock ?? 'time';
	}

	/**
	 * Records a hit if the bucket is under its limit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket name, e.g. 'login:203.0.113.5'.
	 * @param int    $limit  Hits allowed per window. 0 or less blocks everything.
	 * @param int    $window Window length in seconds (at least 1).
	 * @return bool True when the hit is allowed, false when the limit is reached.
	 */
	public function hit( string $bucket, int $limit, int $window ): bool {
		if ( $limit < 1 ) {
			return false;
		}

		$now    = $this->now();
		$key    = $this->key( $bucket );
		$record = $this->active_record( $key, $now ) ?? [
			'count'    => 0,
			'reset_at' => $now + max( 1, $window ),
		];

		if ( $record['count'] >= $limit ) {
			return false;
		}

		++$record['count'];
		$this->store->set( $key, $record, $record['reset_at'] - $now );
		return true;
	}

	/**
	 * Hits left in the current window.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket name.
	 * @param int    $limit  The limit used with hit().
	 * @return int
	 */
	public function remaining( string $bucket, int $limit ): int {
		$record = $this->active_record( $this->key( $bucket ), $this->now() );
		return max( 0, $limit - ( null === $record ? 0 : $record['count'] ) );
	}

	/**
	 * Seconds until the current window ends, e.g. for a Retry-After header.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket name.
	 * @return int 0 when the bucket has no active window.
	 */
	public function retry_after( string $bucket ): int {
		$now    = $this->now();
		$record = $this->active_record( $this->key( $bucket ), $now );
		return null === $record ? 0 : $record['reset_at'] - $now;
	}

	/**
	 * Clears a bucket, e.g. after a successful login.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket name.
	 */
	public function reset( string $bucket ): void {
		$this->store->delete( $this->key( $bucket ) );
	}

	/**
	 * Reads the record of a window that has not ended yet.
	 *
	 * @param string $key Storage key.
	 * @param int    $now Current time.
	 * @return array{count:int,reset_at:int}|null
	 */
	private function active_record( string $key, int $now ): ?array {
		$record = $this->store->get( $key );
		if ( ! isset( $record['count'], $record['reset_at'] ) ) {
			return null;
		}
		$record = [
			'count'    => (int) $record['count'],
			'reset_at' => (int) $record['reset_at'],
		];
		return $record['reset_at'] > $now ? $record : null;
	}

	/**
	 * Hashes a bucket name into a storage key (fits transient name limits).
	 *
	 * @param string $bucket Bucket name.
	 * @return string
	 */
	private function key( string $bucket ): string {
		return self::KEY_PREFIX . substr( hash( 'sha256', $bucket ), 0, 40 );
	}

	/**
	 * Current Unix time from the clock.
	 *
	 * @return int
	 */
	private function now(): int {
		return (int) ( $this->clock )();
	}
}
