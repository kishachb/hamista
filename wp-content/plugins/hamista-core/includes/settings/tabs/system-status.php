<?php
/**
 * System Status tab.
 *
 * @package Hamista\Core
 */

use Hamista\Core\Settings\System_Status;

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'system-status',
	'title'    => __( 'System Status', 'hamista-core' ),
	'icon'     => 'activity',
	'group'    => 'advanced',
	'option'   => '',
	'priority' => 70,
	'sections' => [
		[
			'id'     => 'main',
			'title'  => __( 'System Status', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'status', 'type' => 'html', 'content' => [ System_Status::class, 'render' ] ],
			],
		],
	],
];
