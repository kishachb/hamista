<?php
/**
 * General Data tab (option `hamista_core`).
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'data',
	'title'    => __( 'General Data', 'hamista-core' ),
	'icon'     => 'database',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 80,
	'sections' => [
		[
			'id'     => 'uninstall',
			'title'  => __( 'Data on uninstall', 'hamista-core' ),
			'fields' => [
				[
					'id'          => 'delete_data',
					'type'        => 'toggle',
					'label'       => __( 'Delete all Hamista data when the plugin is uninstalled', 'hamista-core' ),
					'description' => '<strong>' . esc_html__( 'Danger:', 'hamista-core' ) . '</strong> ' . esc_html__( 'removes every Hamista option and, on satellite plugins, their database tables. This cannot be undone.', 'hamista-core' ),
				],
			],
		],
	],
];
