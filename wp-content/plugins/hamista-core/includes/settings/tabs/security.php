<?php
/**
 * Security tab (option `hamista_core`).
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'security',
	'title'    => __( 'Security', 'hamista-core' ),
	'icon'     => 'shield',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 20,
	'sections' => [
		[
			'id'     => 'hardening',
			'title'  => __( 'Hardening', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'sec_headers', 'type' => 'toggle', 'label' => __( 'Send security headers', 'hamista-core' ), 'description' => __( 'X-Content-Type-Options, Referrer-Policy and a conservative Permissions-Policy.', 'hamista-core' ) ],
				[ 'id' => 'sec_disable_xmlrpc', 'type' => 'toggle', 'label' => __( 'Disable XML-RPC', 'hamista-core' ) ],
				[ 'id' => 'sec_block_user_enum', 'type' => 'toggle', 'label' => __( 'Block user enumeration', 'hamista-core' ), 'description' => __( 'Blocks ?author=N probing.', 'hamista-core' ) ],
				[ 'id' => 'sec_generic_login_errors', 'type' => 'toggle', 'label' => __( 'Generic login error messages', 'hamista-core' ), 'description' => __( 'Does not reveal whether a username exists.', 'hamista-core' ) ],
				[ 'id' => 'sec_hide_version', 'type' => 'toggle', 'label' => __( 'Hide the WordPress version', 'hamista-core' ) ],
				[ 'id' => 'sec_disable_file_editor', 'type' => 'toggle', 'label' => __( 'Disable the theme/plugin file editor', 'hamista-core' ) ],
			],
		],
		[
			'id'     => 'login',
			'title'  => __( 'Login rate limiting', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'sec_login_rate_limit', 'type' => 'toggle', 'label' => __( 'Limit login attempts', 'hamista-core' ) ],
				[ 'id' => 'sec_login_max_attempts', 'type' => 'number', 'label' => __( 'Maximum attempts', 'hamista-core' ), 'min' => 1, 'max' => 50, 'step' => 1, 'show_if' => [ 'field' => 'sec_login_rate_limit', 'value' => true ] ],
				[ 'id' => 'sec_login_lockout_minutes', 'type' => 'number', 'label' => __( 'Lockout (minutes)', 'hamista-core' ), 'min' => 1, 'max' => 1440, 'step' => 1, 'show_if' => [ 'field' => 'sec_login_rate_limit', 'value' => true ] ],
			],
		],
	],
];
