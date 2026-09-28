<?php
/**
 * Semver-ish version validation and comparison (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Validates and compares release/plugin version strings.
 *
 * Accepted shape: `\d+(\.\d+){0,3}` with an optional `-prerelease` suffix,
 * e.g. `1`, `1.2`, `1.2.3`, `1.2.3.4`, `1.2.3-beta.1`. This is looser than
 * strict semver (which requires exactly major.minor.patch) because plugin
 * and theme "Version:" headers commonly use two to four numeric segments.
 *
 * @since 1.0.0
 */
final class Version {

	/**
	 * Regex for a well-formed version string.
	 */
	private const PATTERN = '/^\d+(\.\d+){0,3}(-[0-9A-Za-z]+(\.[0-9A-Za-z]+)*)?$/';

	/**
	 * Whether a string is a well-formed version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $v Version string.
	 * @return bool
	 */
	public static function is_valid( string $v ): bool {
		return 1 === preg_match( self::PATTERN, $v );
	}

	/**
	 * Compares two versions, delegating to version_compare() once both are validated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a First version.
	 * @param string $b Second version.
	 * @return int -1, 0 or 1.
	 * @throws \InvalidArgumentException When either version is not well-formed.
	 */
	public static function compare( string $a, string $b ): int {
		if ( ! self::is_valid( $a ) || ! self::is_valid( $b ) ) {
			throw new \InvalidArgumentException( sprintf( 'Invalid version string: "%s" or "%s".', $a, $b ) );
		}
		return version_compare( $a, $b );
	}
}
