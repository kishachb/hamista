<?php
/**
 * Unit tests for Hamista\Core\Support\Rate_Limiter (fixed-window counter).
 *
 * Time is injected through a clock callable, so window expiry is tested
 * without sleeping.
 *
 * @package Hamista\Core\Tests
 */

defined( 'HAMISTA_TESTS' ) || exit;

require_once dirname( __DIR__, 2 ) . '/includes/support/interface-rate-limit-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/support/class-array-store.php';
require_once dirname( __DIR__, 2 ) . '/includes/support/class-rate-limiter.php';

use Hamista\Core\Support\Array_Store;
use Hamista\Core\Support\Rate_Limit_Store;
use Hamista\Core\Support\Rate_Limiter;

/**
 * A store that records every write, to observe keys and TTLs.
 */
final class Hm_Rate_Limit_Spy_Store implements Rate_Limit_Store {

	/**
	 * Recorded writes: list of [ key, value, ttl ].
	 *
	 * @var array<int, array{0:string,1:array,2:int}>
	 */
	public array $writes = [];

	/**
	 * Current values by key.
	 *
	 * @var array<string, array>
	 */
	private array $values = [];

	public function get( string $key ): ?array {
		return $this->values[ $key ] ?? null;
	}

	public function set( string $key, array $value, int $ttl ): void {
		$this->writes[]       = [ $key, $value, $ttl ];
		$this->values[ $key ] = $value;
	}

	public function delete( string $key ): void {
		unset( $this->values[ $key ] );
	}
}

/**
 * Builds a limiter with an in-memory store and a controllable clock.
 *
 * @param int                   $now   Clock value, by reference.
 * @param Rate_Limit_Store|null $store Optional store.
 * @return Rate_Limiter
 */
function hm_rate_limiter_test_make( int &$now, ?Rate_Limit_Store $store = null ): Rate_Limiter {
	$clock = static function () use ( &$now ): int {
		return $now;
	};
	return new Rate_Limiter( $store ?? new Array_Store( $clock ), $clock );
}

function test_rate_limiter_allows_hits_up_to_the_limit_then_blocks() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	assert_true( $limiter->hit( 'login:198.51.100.7', 3, 60 ) );
	assert_true( $limiter->hit( 'login:198.51.100.7', 3, 60 ) );
	assert_true( $limiter->hit( 'login:198.51.100.7', 3, 60 ) );
	assert_false( $limiter->hit( 'login:198.51.100.7', 3, 60 ), 'fourth hit in the window' );
	assert_false( $limiter->hit( 'login:198.51.100.7', 3, 60 ), 'stays blocked' );
}

function test_rate_limiter_remaining_counts_down_to_zero() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	assert_same( 2, $limiter->remaining( 'api', 2 ) );
	$limiter->hit( 'api', 2, 60 );
	assert_same( 1, $limiter->remaining( 'api', 2 ) );
	$limiter->hit( 'api', 2, 60 );
	$limiter->hit( 'api', 2, 60 );
	assert_same( 0, $limiter->remaining( 'api', 2 ) );
}

function test_rate_limiter_allows_again_once_the_window_has_passed() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	$limiter->hit( 'form', 1, 60 );
	assert_false( $limiter->hit( 'form', 1, 60 ) );
	$now = 1059;
	assert_false( $limiter->hit( 'form', 1, 60 ), 'one second before the window ends' );
	$now = 1060;
	assert_true( $limiter->hit( 'form', 1, 60 ), 'a new window starts' );
	assert_same( 0, $limiter->remaining( 'form', 1 ) );
}

function test_rate_limiter_window_is_fixed_from_the_first_hit() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	$limiter->hit( 'fixed', 2, 60 );
	$now = 1050;
	$limiter->hit( 'fixed', 2, 60 );
	$now = 1060;
	// A sliding window would still count the hit at 1050; a fixed window has reset.
	assert_true( $limiter->hit( 'fixed', 2, 60 ) );
	assert_true( $limiter->hit( 'fixed', 2, 60 ) );
	assert_false( $limiter->hit( 'fixed', 2, 60 ) );
}

function test_rate_limiter_buckets_are_independent() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	assert_true( $limiter->hit( 'login:203.0.113.1', 1, 60 ) );
	assert_false( $limiter->hit( 'login:203.0.113.1', 1, 60 ) );
	assert_true( $limiter->hit( 'login:203.0.113.2', 1, 60 ) );
}

function test_rate_limiter_reset_clears_the_bucket() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	$limiter->hit( 'reset-me', 1, 60 );
	assert_false( $limiter->hit( 'reset-me', 1, 60 ) );
	$limiter->reset( 'reset-me' );
	assert_same( 1, $limiter->remaining( 'reset-me', 1 ) );
	assert_true( $limiter->hit( 'reset-me', 1, 60 ) );
}

function test_rate_limiter_zero_limit_always_blocks() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	assert_false( $limiter->hit( 'closed', 0, 60 ) );
	assert_same( 0, $limiter->remaining( 'closed', 0 ) );
}

function test_rate_limiter_retry_after_reports_seconds_left_in_the_window() {
	$now     = 1000;
	$limiter = hm_rate_limiter_test_make( $now );
	assert_same( 0, $limiter->retry_after( 'retry' ), 'no window yet' );
	$limiter->hit( 'retry', 1, 60 );
	$now = 1015;
	assert_same( 45, $limiter->retry_after( 'retry' ) );
	$now = 1060;
	assert_same( 0, $limiter->retry_after( 'retry' ), 'window over' );
}

function test_rate_limiter_hashes_bucket_keys() {
	$now     = 1000;
	$store   = new Hm_Rate_Limit_Spy_Store();
	$limiter = hm_rate_limiter_test_make( $now, $store );
	$limiter->hit( 'login:198.51.100.7', 5, 60 );
	$limiter->hit( 'LOGIN:198.51.100.7', 5, 60 );
	[ $first_key ]  = $store->writes[0];
	[ $second_key ] = $store->writes[1];
	assert_same( 1, preg_match( '/^hamista_rl_[a-f0-9]{40}$/', $first_key ), 'key shape: ' . $first_key );
	assert_false( str_contains( $first_key, '198.51.100.7' ), 'raw bucket must not leak into the key' );
	assert_false( $first_key === $second_key, 'bucket names are case-sensitive' );
}

function test_rate_limiter_store_ttl_ends_with_the_window() {
	$now     = 1000;
	$store   = new Hm_Rate_Limit_Spy_Store();
	$limiter = hm_rate_limiter_test_make( $now, $store );
	$limiter->hit( 'ttl', 5, 60 );
	$now = 1030;
	$limiter->hit( 'ttl', 5, 60 );
	assert_same( 60, $store->writes[0][2] );
	assert_same( 30, $store->writes[1][2] );
}

function test_array_store_expires_entries_after_their_ttl() {
	$now   = 500;
	$store = new Array_Store(
		static function () use ( &$now ): int {
			return $now;
		}
	);
	$store->set( 'k', [ 'count' => 1 ], 10 );
	$now = 509;
	assert_same( [ 'count' => 1 ], $store->get( 'k' ) );
	$now = 510;
	assert_same( null, $store->get( 'k' ) );
	$store->set( 'k', [ 'count' => 2 ], 10 );
	$store->delete( 'k' );
	assert_same( null, $store->get( 'k' ) );
}
