<?php
/**
 * Modules tab.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'modules',
	'title'    => __( 'Modules', 'hamista-core' ),
	'icon'     => 'puzzle',
	'group'    => 'platform',
	'option'   => 'hamista_modules',
	'priority' => 10,
	'sections' => [
		[
			'id'          => 'main',
			'title'       => __( 'Modules', 'hamista-core' ),
			'description' => __( 'Switch platform features on or off. A module that is off loads no code. A module whose requirements are not met cannot be enabled until they are.', 'hamista-core' ),
			'fields'      => [
				[ 'id' => 'modules', 'type' => 'modules' ],
			],
		],
	],
];
