<?php
/**
 * Integrations tab (option `hamista_core`).
 *
 * @package Hamista\Core
 */

use Hamista\Core\Support\Request;

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'integrations',
	'title'    => __( 'Integrations', 'hamista-core' ),
	'icon'     => 'plug',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 40,
	'sections' => [
		[
			'id'     => 'ip',
			'title'  => __( 'Visitor IP', 'hamista-core' ),
			'fields' => [
				[
					'id'          => 'ip_header',
					'type'        => 'select',
					'label'       => __( 'Trusted IP header', 'hamista-core' ),
					'description' => __( 'Only change this behind a trusted reverse proxy or CDN — visitors can send any header themselves.', 'hamista-core' ),
					'options'     => static fn(): array => array_combine( Request::IP_HEADERS, Request::IP_HEADERS ),
				],
			],
		],
		[
			'id'     => 'email',
			'title'  => __( 'Outgoing email', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'mail_from_name', 'type' => 'text', 'label' => __( 'From name', 'hamista-core' ) ],
				[ 'id' => 'mail_from_email', 'type' => 'email', 'label' => __( 'From email', 'hamista-core' ) ],
				[ 'id' => 'email_logo', 'type' => 'image', 'label' => __( 'Email logo', 'hamista-core' ) ],
				[ 'id' => 'email_footer', 'type' => 'textarea', 'label' => __( 'Email footer text', 'hamista-core' ), 'rows' => 2 ],
			],
		],
	],
];
