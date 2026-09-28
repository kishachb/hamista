<?php
/**
 * System Status report.
 *
 * @package Hamista\Core
 */

namespace Hamista\Core\Settings;

use Hamista\Core\Support\Private_Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the System Status tab's read-only report (spec §4.5's System Status bullet).
 *
 * @since 1.0.0
 */
final class System_Status {

	/**
	 * Minimum versions, per spec §3.
	 */
	private const REQUIREMENTS = [
		'php'         => '8.1',
		'wp'          => '6.5',
		'woocommerce' => '8.0',
		'elementor'   => '3.20',
	];

	/**
	 * The report's HTML. Already escaped/safe — used as an `html` field's `content`.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function render(): string {
		$html  = '<table class="hm-table hm-table--status"><tbody>';
		$html .= self::version_row( __( 'PHP', 'hamista-core' ), PHP_VERSION, self::REQUIREMENTS['php'] );
		$html .= self::version_row( __( 'WordPress', 'hamista-core' ), get_bloginfo( 'version' ), self::REQUIREMENTS['wp'] );
		$html .= self::plugin_version_row( __( 'WooCommerce', 'hamista-core' ), defined( 'WC_VERSION' ) ? WC_VERSION : null, self::REQUIREMENTS['woocommerce'] );
		$html .= self::plugin_version_row( __( 'Elementor', 'hamista-core' ), defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null, self::REQUIREMENTS['elementor'] );
		$html .= self::plugin_version_row( __( 'Rank Math', 'hamista-core' ), defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : null, null );
		$html .= '</tbody></table>';

		$permalink = get_option( 'permalink_structure' );
		$html     .= '<table class="hm-table hm-table--status"><tbody>';
		$html     .= self::info_row( __( 'Permalink structure', 'hamista-core' ), '' !== $permalink ? $permalink : __( 'Plain (not recommended)', 'hamista-core' ) );
		$html     .= self::status_row( __( 'HTTPS', 'hamista-core' ), is_ssl(), false );
		$html     .= self::status_row( __( 'WP_DEBUG', 'hamista-core' ), defined( 'WP_DEBUG' ) && WP_DEBUG, true );
		$html     .= self::info_row( __( 'WP-Cron', 'hamista-core' ), ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? __( 'Disabled (server cron expected)', 'hamista-core' ) : __( 'WordPress pseudo-cron', 'hamista-core' ) );
		$html     .= self::info_row( __( 'Memory limit', 'hamista-core' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : (string) ini_get( 'memory_limit' ) );
		$html     .= self::info_row( __( 'Upload max size', 'hamista-core' ), size_format( (int) wp_max_upload_size() ) );
		$html     .= '</tbody></table>';

		$html .= '<h3 class="hm-settings__status-title">' . esc_html__( 'Active Hamista packages', 'hamista-core' ) . '</h3>';
		$html .= self::packages_html();

		$html .= '<h3 class="hm-settings__status-title">' . esc_html__( 'Private storage protection', 'hamista-core' ) . '</h3>';
		$html .= self::private_storage_html();

		return $html;
	}

	/**
	 * A version row, badged OK/needs-X against a minimum.
	 */
	private static function version_row( string $label, string $current, string $required ): string {
		$ok = version_compare( $current, $required, '>=' );
		/* translators: %s: minimum version, e.g. "8.1". */
		return '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( $current ) . '</td><td>' . self::badge( $ok, sprintf( __( 'needs %s+', 'hamista-core' ), $required ) ) . '</td></tr>';
	}

	/**
	 * A plugin's row: "Not active" when absent, "Active" when there is no minimum, else badged.
	 */
	private static function plugin_version_row( string $label, ?string $current, ?string $required ): string {
		if ( null === $current ) {
			return '<tr><td>' . esc_html( $label ) . '</td><td>—</td><td><span class="hm-badge hm-badge--neutral">' . esc_html__( 'Not active', 'hamista-core' ) . '</span></td></tr>';
		}
		if ( null === $required ) {
			return '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( $current ) . '</td><td><span class="hm-badge hm-badge--success">' . esc_html__( 'Active', 'hamista-core' ) . '</span></td></tr>';
		}
		$ok = version_compare( $current, $required, '>=' );
		/* translators: %s: minimum version. */
		return '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( $current ) . '</td><td>' . self::badge( $ok, sprintf( __( 'needs %s+', 'hamista-core' ), $required ) ) . '</td></tr>';
	}

	/**
	 * OK/failure badge.
	 */
	private static function badge( bool $ok, string $fail_label ): string {
		return $ok
			? '<span class="hm-badge hm-badge--success">' . esc_html__( 'OK', 'hamista-core' ) . '</span>'
			: '<span class="hm-badge hm-badge--danger">' . esc_html( $fail_label ) . '</span>';
	}

	/**
	 * On/Off row. `$warn_when_on` flips which state is "good" (e.g. WP_DEBUG).
	 */
	private static function status_row( string $label, bool $on, bool $warn_when_on ): string {
		$good  = $warn_when_on ? ! $on : $on;
		$text  = $on ? __( 'On', 'hamista-core' ) : __( 'Off', 'hamista-core' );
		return '<tr><td>' . esc_html( $label ) . '</td><td colspan="2"><span class="hm-badge hm-badge--' . ( $good ? 'success' : 'warning' ) . '">' . esc_html( $text ) . '</span></td></tr>';
	}

	/**
	 * A plain info row.
	 */
	private static function info_row( string $label, string $value ): string {
		return '<tr><td>' . esc_html( $label ) . '</td><td colspan="2">' . esc_html( $value ) . '</td></tr>';
	}

	/**
	 * Active Hamista packages and their versions.
	 */
	private static function packages_html(): string {
		$rows   = '<tr><td>Hamista Core</td><td>' . esc_html( defined( 'HAMISTA_CORE_VERSION' ) ? HAMISTA_CORE_VERSION : '' ) . '</td><td><span class="hm-badge hm-badge--success">' . esc_html__( 'Active', 'hamista-core' ) . '</span></td></tr>';
		$others = [
			'HAMISTA_LM_VERSION'         => 'Hamista License Manager',
			'HAMISTA_DASHBOARD_VERSION'  => 'Hamista Customer Dashboard',
			'HAMISTA_SO_VERSION'         => 'Hamista Service Orders',
		];
		foreach ( $others as $const => $label ) {
			if ( defined( $const ) ) {
				$rows .= '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( (string) constant( $const ) ) . '</td><td><span class="hm-badge hm-badge--success">' . esc_html__( 'Active', 'hamista-core' ) . '</span></td></tr>';
			}
		}
		if ( 'hamista' === get_template() && function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			$rows .= '<tr><td>Hamista theme</td><td>' . esc_html( $theme->get( 'Version' ) ) . '</td><td><span class="hm-badge hm-badge--success">' . esc_html__( 'Active', 'hamista-core' ) . '</span></td></tr>';
		}
		return '<table class="hm-table hm-table--status"><tbody>' . $rows . '</tbody></table>';
	}

	/**
	 * Private storage protection: a loopback HTTP probe against a temporary
	 * file, so nginx/`php -S` setups (where the guard files are no-ops) show
	 * a clear warning instead of silently passing.
	 */
	private static function private_storage_html(): string {
		if ( defined( 'HAMISTA_PRIVATE_STORAGE_PATH' ) && '' !== (string) HAMISTA_PRIVATE_STORAGE_PATH ) {
			return '<p class="hm-alert hm-alert--success">' . esc_html__( 'Private storage is configured outside the web root (HAMISTA_PRIVATE_STORAGE_PATH), so it is protected regardless of the web server.', 'hamista-core' ) . '</p>';
		}

		if ( ! Private_Storage::protect_directory( Private_Storage::base_path() ) ) {
			return '<p class="hm-alert hm-alert--warning">' . esc_html__( 'The private storage directory could not be created or is not writable.', 'hamista-core' ) . '</p>';
		}

		$upload_dir = wp_upload_dir( null, false );
		$base_url   = trailingslashit( (string) $upload_dir['baseurl'] ) . 'hamista-private';
		$name       = 'probe-' . wp_generate_password( 12, false, false ) . '.txt';
		$secret     = wp_generate_password( 24, false, false );
		$path       = Private_Storage::base_path() . '/' . $name;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- uploads dir; a self-test probe file.
		if ( false === @file_put_contents( $path, $secret ) ) {
			return '<p class="hm-alert hm-alert--warning">' . esc_html__( 'Could not write a probe file to test protection.', 'hamista-core' ) . '</p>';
		}

		$response = wp_remote_get( $base_url . '/' . $name, [ 'timeout' => 5, 'redirection' => 0, 'sslverify' => false ] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- cleaning up the probe file.
		@unlink( $path );

		if ( is_wp_error( $response ) ) {
			return '<p class="hm-alert hm-alert--success">' . esc_html__( 'Protected: the probe request could not connect.', 'hamista-core' ) . '</p>';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 403 === $code || 404 === $code || $body !== $secret ) {
			/* translators: %d: HTTP status code. */
			return '<p class="hm-alert hm-alert--success">' . esc_html( sprintf( __( 'Protected (HTTP %d).', 'hamista-core' ), $code ) ) . '</p>';
		}

		$snippet = "location ^~ /wp-content/uploads/hamista-private/ {\n    deny all;\n    return 404;\n}";
		return '<div class="hm-alert hm-alert--danger">'
			. '<p>' . esc_html__( 'Warning: the private storage directory is reachable over HTTP. On nginx the .htaccess guard has no effect. Add this to your server block:', 'hamista-core' ) . '</p>'
			. '<pre class="hm-code">' . esc_html( $snippet ) . '</pre>'
			. '<p>' . esc_html__( 'Or define HAMISTA_PRIVATE_STORAGE_PATH in wp-config.php to a directory outside the web root.', 'hamista-core' ) . '</p>'
			. '</div>';
	}
}
