<?php
/**
 * Asset URL and registration helper.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a package's styles and scripts with min/debug switching.
 *
 * Paths are relative to the package root, e.g. 'assets/js/ui.js'. The
 * `.min` sibling is used when SCRIPT_DEBUG is off and the file exists.
 * Every package can create its own instance:
 * `new Assets( HAMISTA_LM_URL, HAMISTA_LM_PATH, HAMISTA_LM_VERSION )`.
 *
 * @since 1.0.0
 */
final class Assets {

	/**
	 * Package URL with a trailing slash.
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Package path with a trailing slash.
	 *
	 * @var string
	 */
	private string $base_path;

	/**
	 * Version used for cache busting.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_url  Package root URL.
	 * @param string $base_path Package root directory.
	 * @param string $version   Package version.
	 */
	public function __construct( string $base_url, string $base_path, string $version ) {
		$this->base_url  = trailingslashit( $base_url );
		$this->base_path = trailingslashit( $base_path );
		$this->version   = $version;
	}

	/**
	 * URL of an asset, preferring the minified sibling outside SCRIPT_DEBUG.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative Path relative to the package root.
	 * @return string
	 */
	public function url( string $relative ): string {
		return $this->base_url . $this->resolve( $relative );
	}

	/**
	 * Absolute path of the file url() points to.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative Path relative to the package root.
	 * @return string
	 */
	public function path( string $relative ): string {
		return $this->base_path . $this->resolve( $relative );
	}

	/**
	 * Version string used for cache busting.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function version(): string {
		return $this->version;
	}

	/**
	 * Registers a stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $handle   Handle.
	 * @param string   $relative Path relative to the package root.
	 * @param string[] $deps     Dependencies.
	 * @param string   $media    Media query.
	 * @return bool Whether the style was registered.
	 */
	public function register_style( string $handle, string $relative, array $deps = [], string $media = 'all' ): bool {
		return wp_register_style( $handle, $this->url( $relative ), $deps, $this->version, $media );
	}

	/**
	 * Registers a script, deferred in the footer unless `$args` says otherwise.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $handle   Handle.
	 * @param string   $relative Path relative to the package root.
	 * @param string[] $deps     Dependencies.
	 * @param array    $args     wp_register_script() args: `strategy` (default 'defer'), `in_footer` (default true).
	 * @return bool Whether the script was registered.
	 */
	public function register_script( string $handle, string $relative, array $deps = [], array $args = [] ): bool {
		$args = array_merge(
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			],
			$args
		);
		return wp_register_script( $handle, $this->url( $relative ), $deps, $this->version, $args );
	}

	/**
	 * Picks the `.min` sibling when appropriate.
	 *
	 * @param string $relative Path relative to the package root.
	 * @return string
	 */
	private function resolve( string $relative ): string {
		$relative = ltrim( $relative, '/' );
		if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || str_contains( $relative, '.min.' ) ) {
			return $relative;
		}
		$minified = (string) preg_replace( '/\.(css|js)$/', '.min.$1', $relative );
		return $minified !== $relative && is_readable( $this->base_path . $minified ) ? $minified : $relative;
	}
}
