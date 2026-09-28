<?php
/**
 * WPCS-style PSR-4 autoloader.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core;

if ( ! defined( 'ABSPATH' ) && ! defined( 'HAMISTA_TESTS' ) ) {
	exit;
}

/**
 * Maps namespaced classes to WordPress-style file names (spec §14).
 *
 * - Namespace segments after the prefix become lower-case directories,
 *   with `_` turned into `-`.
 * - The class short name is lower-cased with `_` turned into `-`.
 * - Candidate files, in order: `class-{slug}.php`, `interface-{slug}.php`,
 *   `trait-{slug}.php`.
 *
 * Example: `Hamista\Core\Modules\Abstract_Module` →
 * `includes/modules/class-abstract-module.php`.
 *
 * @since 1.0.0
 */
final class Autoloader {

	/**
	 * Registered base directories by lower-cased namespace prefix.
	 *
	 * @var array<string, string>
	 */
	private static array $prefixes = [];

	/**
	 * Whether load() is registered with spl_autoload_register().
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Registers a namespace prefix and its base directory.
	 *
	 * Calling it again for the same prefix replaces the directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix   Namespace prefix, e.g. 'Hamista\Core\'.
	 * @param string $base_dir Directory that holds the prefix's root namespace.
	 */
	public static function register( string $prefix, string $base_dir ): void {
		self::$prefixes[ strtolower( self::normalize_prefix( $prefix ) ) ] = self::normalize_dir( $base_dir );

		if ( ! self::$registered ) {
			spl_autoload_register( [ self::class, 'load' ] );
			self::$registered = true;
		}
	}

	/**
	 * Loads a class, interface or trait from a registered prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Fully qualified name, without a leading backslash.
	 */
	public static function load( string $class_name ): void {
		foreach ( self::$prefixes as $prefix => $base_dir ) {
			foreach ( self::candidates( $class_name, $prefix, $base_dir ) as $file ) {
				if ( is_readable( $file ) ) {
					require $file;
					return;
				}
			}
		}
	}

	/**
	 * Candidate file paths for a class, most likely first.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Fully qualified name.
	 * @param string $prefix     Namespace prefix (trailing backslash optional; case-insensitive).
	 * @param string $base_dir   Base directory (trailing slash optional).
	 * @return string[] Empty when the class is outside the prefix or its name is not a plain identifier.
	 */
	public static function candidates( string $class_name, string $prefix, string $base_dir ): array {
		$class_name = ltrim( $class_name, '\\' );
		$prefix     = self::normalize_prefix( $prefix );

		if ( 0 !== strncasecmp( $class_name, $prefix, strlen( $prefix ) ) ) {
			return [];
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		if ( '' === $relative || ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $relative ) ) {
			return [];
		}

		$segments = explode( '\\', strtolower( str_replace( '_', '-', $relative ) ) );
		$slug     = array_pop( $segments );
		$dir      = self::normalize_dir( $base_dir ) . ( [] === $segments ? '' : implode( '/', $segments ) . '/' );

		return [
			$dir . 'class-' . $slug . '.php',
			$dir . 'interface-' . $slug . '.php',
			$dir . 'trait-' . $slug . '.php',
		];
	}

	/**
	 * Ensures a prefix ends with exactly one backslash.
	 *
	 * @param string $prefix Namespace prefix.
	 * @return string
	 */
	private static function normalize_prefix( string $prefix ): string {
		return trim( $prefix, '\\' ) . '\\';
	}

	/**
	 * Ensures a directory ends with exactly one slash.
	 *
	 * @param string $dir Directory.
	 * @return string
	 */
	private static function normalize_dir( string $dir ): string {
		return rtrim( $dir, '/\\' ) . '/';
	}
}
