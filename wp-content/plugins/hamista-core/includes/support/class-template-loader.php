<?php
/**
 * Theme-overridable templates.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Finds and renders package templates (spec §14 "Templates").
 *
 * Lookup order for `locate( 'hamista-core', 'emails/base.php', $dir )`:
 * 1. `{child-theme}/hamista-core/emails/base.php`
 * 2. `{parent-theme}/hamista-core/emails/base.php`
 * 3. `{$dir}/emails/base.php`
 *
 * Templates receive their data as `$args` (never extract()) and must escape
 * everything they print.
 *
 * @since 1.0.0
 */
final class Template_Loader {

	/**
	 * Path of the template to use.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Package slug, which is also the theme override directory.
	 * @param string $template    Relative template path ending in `.php`, e.g. 'emails/base.php'.
	 * @param string $default_dir The package's templates directory.
	 * @return string Absolute path, or '' when not found or the name is unsafe.
	 */
	public static function locate( string $plugin_slug, string $template, string $default_dir ): string {
		$template = ltrim( str_replace( '\\', '/', $template ), '/' );
		if ( ! self::is_safe( $template ) ) {
			return '';
		}

		$plugin_slug = sanitize_key( $plugin_slug );
		$candidates  = [];
		if ( '' !== $plugin_slug ) {
			$candidates[] = trailingslashit( get_stylesheet_directory() ) . $plugin_slug . '/' . $template;
			$candidates[] = trailingslashit( get_template_directory() ) . $plugin_slug . '/' . $template;
		}
		if ( '' !== $default_dir ) {
			$candidates[] = trailingslashit( $default_dir ) . $template;
		}

		$found = '';
		foreach ( array_unique( $candidates ) as $candidate ) {
			if ( is_readable( $candidate ) ) {
				$found = $candidate;
				break;
			}
		}

		/**
		 * Filters the located template path.
		 *
		 * @since 1.0.0
		 *
		 * @param string $found       Absolute path, or ''.
		 * @param string $plugin_slug Package slug.
		 * @param string $template    Relative template path.
		 * @param string $default_dir Package templates directory.
		 */
		return (string) apply_filters( 'hamista_locate_template', $found, $plugin_slug, $template, $default_dir );
	}

	/**
	 * Prints a template. Does nothing when it cannot be found.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Package slug.
	 * @param string $template    Relative template path.
	 * @param array  $args        Data, available as `$args` in the template.
	 * @param string $default_dir The package's templates directory.
	 */
	public static function get( string $plugin_slug, string $template, array $args = [], string $default_dir = '' ): void {
		$file = self::locate( $plugin_slug, $template, $default_dir );
		if ( '' !== $file ) {
			self::include_file( $file, $args );
		}
	}

	/**
	 * Returns a template's output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Package slug.
	 * @param string $template    Relative template path.
	 * @param array  $args        Data, available as `$args` in the template.
	 * @param string $default_dir The package's templates directory.
	 * @return string '' when the template cannot be found.
	 */
	public static function render( string $plugin_slug, string $template, array $args = [], string $default_dir = '' ): string {
		$file = self::locate( $plugin_slug, $template, $default_dir );
		return '' === $file ? '' : self::render_file( $file, $args );
	}

	/**
	 * Returns the output of an already located template file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Absolute path from locate().
	 * @param array  $args Data, available as `$args` in the template.
	 * @return string
	 */
	public static function render_file( string $file, array $args = [] ): string {
		ob_start();
		try {
			self::include_file( $file, $args );
		} finally {
			$html = (string) ob_get_clean();
		}
		return $html;
	}

	/**
	 * Includes a template with only `$args` in scope.
	 *
	 * @param string $hamista_template_file Absolute path.
	 * @param array  $args                  Template data.
	 */
	private static function include_file( string $hamista_template_file, array $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $args is used by the template.
		include $hamista_template_file;
	}

	/**
	 * Rejects traversal, absolute paths, stream wrappers and non-PHP names.
	 *
	 * @param string $template Normalised relative path.
	 * @return bool
	 */
	private static function is_safe( string $template ): bool {
		return '' !== $template
			&& str_ends_with( $template, '.php' )
			&& 0 === validate_file( $template )
			&& 1 !== preg_match( '#(^|/)\.\.?(/|$)|[\x00:]#', $template );
	}
}
