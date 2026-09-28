<?php
/**
 * Class autoloader for the Hamista\Theme namespace.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Maps classes to WordPress-style file names (spec §14):
 * `Hamista\Theme\Settings\Choices` → `inc/settings/class-choices.php`.
 *
 * - Namespace segments after the prefix become lower-case directories, `_` → `-`.
 * - The short class name is lower-cased with `_` → `-`.
 * - Candidates, in order: `class-{slug}.php`, `interface-{slug}.php`, `trait-{slug}.php`.
 *
 * @since 1.0.0
 */
final class Autoloader {

	/**
	 * Registers the autoloader for one namespace prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix   Namespace prefix, e.g. `Hamista\Theme\`.
	 * @param string $base_dir Directory that holds the prefix's classes.
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$prefix   = trim( $prefix, '\\' ) . '\\';
		$base_dir = rtrim( $base_dir, '/\\' ) . '/';

		spl_autoload_register(
			static function ( string $class_name ) use ( $prefix, $base_dir ): void {
				$file = self::file_for( $class_name, $prefix, $base_dir );
				if ( '' !== $file ) {
					require_once $file;
				}
			}
		);
	}

	/**
	 * Resolves the file that holds a class, or '' when none exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Fully qualified class name.
	 * @param string $prefix     Namespace prefix with a trailing backslash.
	 * @param string $base_dir   Base directory with a trailing slash.
	 * @return string
	 */
	public static function file_for( string $class_name, string $prefix, string $base_dir ): string {
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return '';
		}

		$segments = explode( '\\', substr( $class_name, strlen( $prefix ) ) );
		$slug     = self::slug( (string) array_pop( $segments ) );
		$dir      = '';
		foreach ( $segments as $segment ) {
			$dir .= self::slug( $segment ) . '/';
		}

		foreach ( [ 'class', 'interface', 'trait' ] as $type ) {
			$file = $base_dir . $dir . $type . '-' . $slug . '.php';
			if ( is_readable( $file ) ) {
				return $file;
			}
		}
		return '';
	}

	/**
	 * `Color_Mode` → `color-mode`.
	 *
	 * @param string $name Class or namespace segment.
	 * @return string
	 */
	private static function slug( string $name ): string {
		return strtolower( str_replace( '_', '-', $name ) );
	}
}
