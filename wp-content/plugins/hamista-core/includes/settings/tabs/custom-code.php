<?php
/**
 * Custom Code tab (option `hamista_core`).
 *
 * HTML fields are read-only for accounts without `unfiltered_html`; output
 * on the frontend is wired in register-core-tabs.php.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

$hamista_code_readonly = ! current_user_can( 'unfiltered_html' );

return [
	'id'       => 'custom-code',
	'title'    => __( 'Custom Code', 'hamista-core' ),
	'icon'     => 'code',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 50,
	'sections' => [
		[
			'id'     => 'html',
			'title'  => __( 'HTML snippets', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'code_head', 'type' => 'code', 'mode' => 'html', 'label' => __( 'Before </head>', 'hamista-core' ), 'readonly' => $hamista_code_readonly, 'rows' => 6 ],
				[ 'id' => 'code_body_open', 'type' => 'code', 'mode' => 'html', 'label' => __( 'After <body>', 'hamista-core' ), 'readonly' => $hamista_code_readonly, 'rows' => 4 ],
				[ 'id' => 'code_footer', 'type' => 'code', 'mode' => 'html', 'label' => __( 'Before </body>', 'hamista-core' ), 'readonly' => $hamista_code_readonly, 'rows' => 6 ],
			],
		],
		[
			'id'     => 'css',
			'title'  => __( 'Custom CSS', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'code_css', 'type' => 'code', 'mode' => 'css', 'label' => __( 'Custom CSS', 'hamista-core' ), 'description' => __( 'Printed after every theme stylesheet.', 'hamista-core' ), 'rows' => 10 ],
			],
		],
	],
];
