<?php
/**
 * Public API of hamista-core (spec §4.3 and §14).
 *
 * Every function is a thin wrapper around a class, so behaviour lives in one
 * place. Consumers guard calls with function_exists(), because the theme and
 * the satellites must degrade gracefully when core is inactive.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Core\Admin\Admin_UI;
use Hamista\Core\Plugin;
use Hamista\Core\Support\Flash;
use Hamista\Core\Support\Formatter;
use Hamista\Core\Support\Icons;
use Hamista\Core\Support\Mailer;
use Hamista\Core\Support\Multilingual;
use Hamista\Core\Support\Notifier;
use Hamista\Core\Support\Options;
use Hamista\Core\Support\Private_Storage;
use Hamista\Core\Support\Rate_Limiter;
use Hamista\Core\Support\Request;
use Hamista\Core\Support\Template_Loader;
use Hamista\Core\Support\Transient_Store;

if ( ! function_exists( 'hamista_core' ) ) {
	/**
	 * The core plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	function hamista_core(): Plugin {
		return Plugin::instance();
	}
}

if ( ! function_exists( 'hamista_register_option_defaults' ) ) {
	/**
	 * Registers untranslated defaults for keys of an array option.
	 *
	 * Call it at load time (e.g. `plugins_loaded`, `after_setup_theme`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $option   Option name, e.g. 'hamista_theme'.
	 * @param array  $defaults key => default.
	 */
	function hamista_register_option_defaults( string $option, array $defaults ): void {
		Options::register_defaults( $option, $defaults );
	}
}

if ( ! function_exists( 'hamista_get_option' ) ) {
	/**
	 * Reads a key of an array option: saved value, else registered default, else `$default`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option  Option name.
	 * @param string $key     Key.
	 * @param mixed  $default Fallback when the key is neither saved nor registered.
	 * @return mixed
	 */
	function hamista_get_option( string $option, string $key, mixed $default = null ): mixed { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- public API name from the spec.
		return Options::get( $option, $key, $default );
	}
}

if ( ! function_exists( 'hamista_module_enabled' ) ) {
	/**
	 * Whether a module is registered, switched on and has its requirements met.
	 *
	 * Modules register on `init` priority 1; before that this returns false.
	 *
	 * @since 1.0.0
	 *
	 * @param string $module_id Module id.
	 * @return bool
	 */
	function hamista_module_enabled( string $module_id ): bool {
		return Plugin::instance()->modules()->is_active( $module_id );
	}
}

if ( ! function_exists( 'hamista_icon' ) ) {
	/**
	 * Inline SVG icon (escaped, safe to print).
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Icon name, e.g. 'check' or 'brand-instagram'.
	 * @param array  $attrs Attributes; `title` makes the icon non-decorative.
	 * @return string '' for unknown names.
	 */
	function hamista_icon( string $name, array $attrs = [] ): string {
		return Icons::get( $name, $attrs );
	}
}

if ( ! function_exists( 'hamista_render' ) ) {
	/**
	 * Component HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component Component name, e.g. 'hero'.
	 * @param array  $args      Component arguments.
	 * @return string '' when the component does not exist.
	 */
	function hamista_render( string $component, array $args = [] ): string {
		return Plugin::instance()->components()->render( $component, $args );
	}
}

if ( ! function_exists( 'hamista_date' ) ) {
	/**
	 * Formats a date like wp_date(), in the Jalali calendar when enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param string                        $format date() format.
	 * @param int|string|\DateTimeInterface $time   Timestamp, UTC date string or date object.
	 * @return string
	 */
	function hamista_date( string $format, int|string|\DateTimeInterface $time ): string {
		return Formatter::date( $format, $time );
	}
}

if ( ! function_exists( 'hamista_price_digits' ) ) {
	/**
	 * Converts digits to Persian when enabled. Safe on price HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text or HTML.
	 * @return string
	 */
	function hamista_price_digits( string $text ): string {
		return Formatter::digits( $text );
	}
}

if ( ! function_exists( 'hamista_client_ip' ) ) {
	/**
	 * The visitor's IP address, honouring the "IP header" setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function hamista_client_ip(): string {
		return Request::client_ip();
	}
}

if ( ! function_exists( 'hamista_rate_limit' ) ) {
	/**
	 * Counts a hit against a fixed-window rate limit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket         Bucket, e.g. 'contact:' . hamista_client_ip().
	 * @param int    $limit          Hits allowed per window.
	 * @param int    $window_seconds Window length.
	 * @return bool True = allowed.
	 */
	function hamista_rate_limit( string $bucket, int $limit, int $window_seconds ): bool {
		return ( new Rate_Limiter( new Transient_Store() ) )->hit( $bucket, $limit, $window_seconds );
	}
}

if ( ! function_exists( 'hamista_notify' ) ) {
	/**
	 * Announces a notification for a user (`hamista_notify` action).
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    `type`, `title`, `message`, `link`.
	 */
	function hamista_notify( int $user_id, array $args ): void {
		Notifier::notify( $user_id, $args );
	}
}

if ( ! function_exists( 'hamista_mail' ) ) {
	/**
	 * Sends a branded HTML email.
	 *
	 * @since 1.0.0
	 *
	 * @param string|string[] $to      Recipient(s).
	 * @param string          $subject Subject.
	 * @param array           $args    `heading`, `body` (HTML), `button` { text, url }, `footer`, `user_id`.
	 * @return bool
	 */
	function hamista_mail( string|array $to, string $subject, array $args ): bool {
		return ( new Mailer() )->send( $to, $subject, $args );
	}
}

if ( ! function_exists( 'hamista_register_account_endpoint' ) ) {
	/**
	 * Adds a My Account endpoint (spec §4.4).
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   Endpoint slug.
	 * @param array  $args `title`, `icon`, `group`, `position`, `callback`, `badge`, `overview`, `menu`.
	 */
	function hamista_register_account_endpoint( string $id, array $args ): void {
		Plugin::instance()->account_endpoints()->register( $id, $args );
	}
}

if ( ! function_exists( 'hamista_account_endpoints' ) ) {
	/**
	 * The registered My Account endpoints, ordered by position.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	function hamista_account_endpoints(): array {
		return Plugin::instance()->account_endpoints()->all();
	}
}

if ( ! function_exists( 'hamista_private_storage' ) ) {
	/**
	 * Private file storage for a bucket.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bucket Bucket slug (`[a-z0-9-]`).
	 * @return Private_Storage
	 */
	function hamista_private_storage( string $bucket ): Private_Storage {
		return new Private_Storage( $bucket );
	}
}

if ( ! function_exists( 'hamista_translate_string' ) ) {
	/**
	 * Translates a registered string through WPML or Polylang.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Original value.
	 * @param string $name    String name.
	 * @param string $context WPML domain / Polylang group.
	 * @return string
	 */
	function hamista_translate_string( string $value, string $name, string $context = 'hamista' ): string {
		return Multilingual::translate_string( $value, $name, $context );
	}
}

if ( ! function_exists( 'hamista_language_switcher' ) ) {
	/**
	 * Language switcher markup, or '' when no multilingual plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args `class`, `display`, `show_current`, `label`.
	 * @return string
	 */
	function hamista_language_switcher( array $args = [] ): string {
		return Multilingual::switcher( $args );
	}
}

if ( ! function_exists( 'hamista_locate_template' ) ) {
	/**
	 * Finds a template: child theme, then parent theme, then the package.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Package slug (the theme override directory).
	 * @param string $template    Relative path, e.g. 'emails/base.php'.
	 * @param string $default_dir The package's templates directory.
	 * @return string Absolute path, or ''.
	 */
	function hamista_locate_template( string $plugin_slug, string $template, string $default_dir ): string {
		return Template_Loader::locate( $plugin_slug, $template, $default_dir );
	}
}

if ( ! function_exists( 'hamista_get_template' ) ) {
	/**
	 * Prints a template with `$args` in scope.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Package slug.
	 * @param string $template    Relative path.
	 * @param array  $args        Template data.
	 * @param string $default_dir The package's templates directory.
	 */
	function hamista_get_template( string $plugin_slug, string $template, array $args, string $default_dir ): void {
		Template_Loader::get( $plugin_slug, $template, $args, $default_dir );
	}
}

if ( ! function_exists( 'hamista_flash' ) ) {
	/**
	 * Queues a one-time message for the next page view (before any output).
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @param string $type    success | error | warning | info.
	 */
	function hamista_flash( string $message, string $type = 'success' ): void {
		Flash::add( $message, $type );
	}
}

if ( ! function_exists( 'hamista_flash_messages' ) ) {
	/**
	 * Returns and clears the queued messages. Escape them when printing.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{message:string,type:string}>
	 */
	function hamista_flash_messages(): array {
		return Flash::pull();
	}
}

if ( ! function_exists( 'hamista_admin_header' ) ) {
	/**
	 * Prints the branded header that starts every Hamista admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $title Page title.
	 * @param array  $args  `subtitle`, `actions` (list of { label, url, primary }).
	 */
	function hamista_admin_header( string $title, array $args = [] ): void {
		Admin_UI::header( $title, $args );
	}
}
