<?php
/**
 * The `licenses` settings tab (spec §4.5, §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the settings tab array consumed by `Settings_Registry::add_tab()`.
 *
 * The settings framework (Task 1.2) is built concurrently; Plugin::boot()
 * only calls tab() after confirming `add_tab()` exists, so every string
 * here is safe to translate (this only ever runs in wp-admin).
 *
 * @since 1.0.0
 */
final class Settings {

	/**
	 * The `licenses` tab definition.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function tab(): array {
		return [
			'id'       => 'licenses',
			'title'    => __( 'Licenses', 'hamista-license-manager' ),
			'icon'     => 'key',
			'group'    => 'platform',
			'option'   => 'hamista_licenses',
			'priority' => 40,
			'sections' => [
				[
					'id'     => 'keys',
					'title'  => __( 'Keys', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'key_prefix',
							'type'    => 'text',
							'label'   => __( 'Key prefix', 'hamista-license-manager' ),
							'default' => 'HMST',
						],
					],
				],
				[
					'id'     => 'activations',
					'title'  => __( 'Activations & domains', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'default_activation_limit',
							'type'    => 'number',
							'label'   => __( 'Default activation limit', 'hamista-license-manager' ),
							'description' => __( '0 = unlimited.', 'hamista-license-manager' ),
							'min'     => 0,
							'default' => 1,
						],
						[
							'id'      => 'default_validity_days',
							'type'    => 'number',
							'label'   => __( 'Default validity (days)', 'hamista-license-manager' ),
							'description' => __( '0 = lifetime.', 'hamista-license-manager' ),
							'min'     => 0,
							'default' => 365,
						],
						[
							'id'      => 'count_local_domains',
							'type'    => 'toggle',
							'label'   => __( 'Count local domains', 'hamista-license-manager' ),
							'description' => __( 'Local/staging domains normally activate for free.', 'hamista-license-manager' ),
							'default' => false,
						],
						[
							'id'      => 'local_patterns',
							'type'    => 'textarea',
							'label'   => __( 'Extra local domain patterns', 'hamista-license-manager' ),
							'description' => __( 'One fnmatch() pattern per line, e.g. *.preview.example.com.', 'hamista-license-manager' ),
							'default' => '',
						],
					],
				],
				[
					'id'     => 'woocommerce',
					'title'  => __( 'WooCommerce', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'issue_on',
							'type'    => 'select',
							'label'   => __( 'Issue licenses when the order reaches', 'hamista-license-manager' ),
							'options' => [
								'processing' => __( 'Processing', 'hamista-license-manager' ),
								'completed'  => __( 'Completed', 'hamista-license-manager' ),
							],
							'default' => 'processing',
						],
						[
							'id'      => 'revoke_on_refund',
							'type'    => 'toggle',
							'label'   => __( 'Revoke licenses on refund', 'hamista-license-manager' ),
							'default' => true,
						],
						[
							'id'      => 'revoke_on_cancel',
							'type'    => 'toggle',
							'label'   => __( 'Revoke licenses on cancellation', 'hamista-license-manager' ),
							'default' => true,
						],
						[
							'id'      => 'show_keys_in_emails',
							'type'    => 'toggle',
							'label'   => __( 'Show keys in order emails', 'hamista-license-manager' ),
							'default' => true,
						],
					],
				],
				[
					'id'     => 'api',
					'title'  => __( 'Update API', 'hamista-license-manager' ),
					'description' => __( 'The REST endpoints and response signing are implemented in a later release.', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'api_enabled',
							'type'    => 'toggle',
							'label'   => __( 'Enable the update API', 'hamista-license-manager' ),
							'default' => true,
						],
						[
							'id'      => 'api_rate_limit',
							'type'    => 'number',
							'label'   => __( 'Rate limit (requests per minute per IP)', 'hamista-license-manager' ),
							'min'     => 1,
							'default' => 60,
						],
						[
							'id'      => 'api_fail_limit',
							'type'    => 'number',
							'label'   => __( 'Failed key lookups before a stricter limit applies', 'hamista-license-manager' ),
							'min'     => 1,
							'default' => 10,
						],
						[
							'id'      => 'token_ttl_hours',
							'type'    => 'number',
							'label'   => __( 'Download token lifetime (hours)', 'hamista-license-manager' ),
							'min'     => 1,
							'default' => 24,
						],
						[
							'id'      => 'sign_responses',
							'type'    => 'toggle',
							'label'   => __( 'Sign REST responses (Ed25519)', 'hamista-license-manager' ),
							'default' => true,
						],
					],
				],
				[
					'id'     => 'downloads',
					'title'  => __( 'Downloads & storage', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'sendfile_mode',
							'type'    => 'select',
							'label'   => __( 'File delivery mode', 'hamista-license-manager' ),
							'options' => [
								'php'            => __( 'PHP (streamed)', 'hamista-license-manager' ),
								'x-sendfile'     => 'X-Sendfile',
								'x-accel-redirect' => 'X-Accel-Redirect',
							],
							'default' => 'php',
						],
						[
							'id'      => 'accel_prefix',
							'type'    => 'text',
							'label'   => __( 'X-Accel-Redirect internal path prefix', 'hamista-license-manager' ),
							'default' => '/hamista-protected/',
							'show_if' => [ 'field' => 'sendfile_mode', 'value' => 'x-accel-redirect' ],
						],
						[
							'id'      => 'allowed_extensions',
							'type'    => 'text',
							'label'   => __( 'Allowed release file extensions', 'hamista-license-manager' ),
							'description' => __( 'Comma-separated, e.g. zip.', 'hamista-license-manager' ),
							'default' => 'zip',
						],
						[
							'id'      => 'max_upload_mb',
							'type'    => 'number',
							'label'   => __( 'Maximum release upload size (MB)', 'hamista-license-manager' ),
							'min'     => 1,
							'default' => 100,
						],
						[
							'id'      => 'notify_customers_on_release',
							'type'    => 'toggle',
							'label'   => __( 'Notify customers when a new release is published', 'hamista-license-manager' ),
							'default' => true,
						],
					],
				],
				[
					'id'     => 'data',
					'title'  => __( 'Data', 'hamista-license-manager' ),
					'fields' => [
						[
							'id'      => 'delete_data',
							'type'    => 'toggle',
							'label'   => __( 'Delete all license data on uninstall', 'hamista-license-manager' ),
							'default' => false,
						],
					],
				],
			],
		];
	}
}
