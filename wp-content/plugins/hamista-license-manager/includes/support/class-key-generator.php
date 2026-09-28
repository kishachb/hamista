<?php
/**
 * License key generation, validation and normalisation (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Crockford base32 license keys: `{PREFIX}-XXXXX-XXXXX-XXXXX-XXXXX`.
 *
 * Four random groups of 5 characters from the Crockford alphabet give
 * 4 * 5 * 5 bits = 100 bits of entropy. `random_int()` is cryptographically
 * secure, so callers still retry on a (practically impossible) collision
 * against the unique `license_key` column.
 *
 * @since 1.0.0
 */
final class Key_Generator {

	/**
	 * Crockford base32 alphabet: no I, L, O or U, to avoid look-alikes.
	 */
	public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

	/**
	 * Number of random groups after the prefix.
	 */
	private const GROUPS = 4;

	/**
	 * Characters per group.
	 */
	private const GROUP_LENGTH = 5;

	/**
	 * Generates a license key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix Key prefix: 2-8 characters of `[A-Z0-9]`.
	 * @return string `{PREFIX}-XXXXX-XXXXX-XXXXX-XXXXX`.
	 * @throws \InvalidArgumentException When the prefix does not match `[A-Z0-9]{2,8}`.
	 */
	public static function generate( string $prefix = 'HMST' ): string {
		if ( 1 !== preg_match( '/^[A-Z0-9]{2,8}$/', $prefix ) ) {
			throw new \InvalidArgumentException( 'A license key prefix must be 2-8 characters of A-Z and 0-9.' );
		}

		$groups = [ $prefix ];
		for ( $g = 0; $g < self::GROUPS; $g++ ) {
			$group = '';
			for ( $i = 0; $i < self::GROUP_LENGTH; $i++ ) {
				$group .= self::ALPHABET[ random_int( 0, strlen( self::ALPHABET ) - 1 ) ];
			}
			$groups[] = $group;
		}
		return implode( '-', $groups );
	}

	/**
	 * Whether a string has the well-formed shape of a license key.
	 *
	 * Does not check whether the key exists; it only validates the shape
	 * against a 2-8 char `[A-Z0-9]` prefix and four 5-char Crockford groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Candidate key, already normalised (upper-case, no aliases).
	 * @return bool
	 */
	public static function is_well_formed( string $key ): bool {
		return 1 === preg_match( '/^[A-Z0-9]{2,8}(-[' . self::ALPHABET . ']{5}){4}$/', $key );
	}

	/**
	 * Normalises user input into a comparable key: trims, upper-cases, maps
	 * the Crockford aliases (O -> 0, I and L -> 1) and removes whitespace.
	 *
	 * Does not validate the shape; call is_well_formed() on the result.
	 *
	 * @since 1.0.0
	 *
	 * @param string $input Raw user input.
	 * @return string
	 */
	public static function normalize( string $input ): string {
		$value = strtoupper( trim( $input ) );
		$value = (string) preg_replace( '/\s+/', '', $value );
		return strtr( $value, [ 'O' => '0', 'I' => '1', 'L' => '1' ] );
	}
}
