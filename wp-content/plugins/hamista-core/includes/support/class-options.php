<?php
/**
 * Array options with registered defaults.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Reads keys of array options (`hamista_core`, `hamista_theme`, …).
 *
 * Lookup order: saved value → registered default → the caller's default.
 * Each option array is read once per request and the copy is dropped when
 * the option is added, updated or deleted, or the site is switched.
 *
 * Packages register cheap, untranslated defaults at load time through
 * hamista_register_option_defaults().
 *
 * @since 1.0.0
 */
final class Options {

	/**
	 * Registered defaults: option => [ key => value ].
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $defaults = [];

	/**
	 * Saved arrays read in this request: option => array.
	 *
	 * @var array<string, array>
	 */
	private static array $cache = [];

	/**
	 * Options whose change hooks are attached.
	 *
	 * @var array<string, true>
	 */
	private static array $watched = [];

	/**
	 * Registers defaults for keys of an array option. Later calls merge over earlier ones.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option   Option name.
	 * @param array  $defaults key => default value (untranslated).
	 */
	public static function register_defaults( string $option, array $defaults ): void {
		self::$defaults[ $option ] = array_merge( self::$defaults[ $option ] ?? [], $defaults );
	}

	/**
	 * Registered defaults of an option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option Option name.
	 * @return array<string, mixed>
	 */
	public static function defaults( string $option ): array {
		return self::$defaults[ $option ] ?? [];
	}

	/**
	 * Reads one key: saved value, else registered default, else `$fallback`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option   Option name.
	 * @param string $key      Key inside the option array.
	 * @param mixed  $fallback Returned when the key is neither saved nor registered.
	 * @return mixed
	 */
	public static function get( string $option, string $key, $fallback = null ) {
		$saved = self::saved( $option );
		if ( array_key_exists( $key, $saved ) ) {
			return $saved[ $key ];
		}
		if ( isset( self::$defaults[ $option ] ) && array_key_exists( $key, self::$defaults[ $option ] ) ) {
			return self::$defaults[ $option ][ $key ];
		}
		return $fallback;
	}

	/**
	 * The whole option: registered defaults with the saved values on top.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option Option name.
	 * @return array<string, mixed>
	 */
	public static function all( string $option ): array {
		return array_merge( self::defaults( $option ), self::saved( $option ) );
	}

	/**
	 * Drops the per-request copy of one option, or of all options.
	 *
	 * Hooked to `switch_blog`; the option hooks use their own closures.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $option Option name, or anything else (such as the blog id
	 *                      that `switch_blog` passes) to drop every copy.
	 */
	public static function flush( $option = null ): void {
		if ( is_string( $option ) && '' !== $option ) {
			unset( self::$cache[ $option ] );
			return;
		}
		self::$cache = [];
	}

	/**
	 * The saved array of an option, read once per request.
	 *
	 * @param string $option Option name.
	 * @return array
	 */
	private static function saved( string $option ): array {
		if ( ! array_key_exists( $option, self::$cache ) ) {
			$value                  = get_option( $option, [] );
			self::$cache[ $option ] = is_array( $value ) ? $value : [];
			self::watch( $option );
		}
		return self::$cache[ $option ];
	}

	/**
	 * Drops the cached copy whenever the option changes.
	 *
	 * @param string $option Option name.
	 */
	private static function watch( string $option ): void {
		if ( [] === self::$watched ) {
			add_action( 'switch_blog', [ self::class, 'flush' ] );
		}
		if ( isset( self::$watched[ $option ] ) ) {
			return;
		}
		self::$watched[ $option ] = true;

		// The three hooks pass different arguments, so bind the name here.
		$forget = static function () use ( $option ): void {
			unset( self::$cache[ $option ] );
		};
		add_action( "add_option_{$option}", $forget );
		add_action( "update_option_{$option}", $forget );
		add_action( "delete_option_{$option}", $forget );
	}
}
