<?php
/**
 * Settings tab: Pages (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'pages',
	'title'    => __( 'Pages', 'hamista' ),
	'icon'     => 'file',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 90,
	'sections' => [
		[
			'id'          => 'title',
			'title'       => __( 'Title bar', 'hamista' ),
			'description' => __( 'The band with the page title at the top of pages and archives.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'page_title_bar',
					'type'        => 'toggle',
					'label'       => __( 'Show the title bar', 'hamista' ),
					'description' => __( 'Elementor canvas and full-width templates never show it.', 'hamista' ),
				],
				[
					'id'          => 'page_title_style',
					'type'        => 'choice',
					'label'       => __( 'Title bar style', 'hamista' ),
					'description' => __( 'Background of the title bar.', 'hamista' ),
					'options'     => [
						'gradient' => __( 'Soft gradient', 'hamista' ),
						'surface'  => __( 'Muted surface', 'hamista' ),
						'plain'    => __( 'Plain', 'hamista' ),
					],
					'show_if'     => [
						'field' => 'page_title_bar',
						'value' => true,
					],
				],
			],
		],
		[
			'id'          => 'breadcrumbs',
			'title'       => __( 'Breadcrumbs', 'hamista' ),
			'description' => __( 'Rank Math breadcrumbs are used when available, otherwise the theme\'s own.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'breadcrumbs',
					'type'        => 'toggle',
					'label'       => __( 'Show breadcrumbs', 'hamista' ),
					'description' => __( 'In the title bar.', 'hamista' ),
				],
				[
					'id'          => 'breadcrumbs_on_home',
					'type'        => 'toggle',
					'label'       => __( 'Also on the home page', 'hamista' ),
					'description' => __( 'Usually not needed.', 'hamista' ),
					'show_if'     => [
						'field' => 'breadcrumbs',
						'value' => true,
					],
				],
			],
		],
		[
			'id'          => 'error',
			'title'       => __( 'Page not found (404)', 'hamista' ),
			'description' => __( 'Leave a field empty to use the default text.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'error_404_title',
					'type'        => 'text',
					'label'       => __( 'Title', 'hamista' ),
					'description' => __( 'Headline of the 404 page.', 'hamista' ),
				],
				[
					'id'          => 'error_404_text',
					'type'        => 'textarea',
					'label'       => __( 'Text', 'hamista' ),
					'description' => __( 'A short, helpful explanation.', 'hamista' ),
				],
				[
					'id'          => 'error_404_button_text',
					'type'        => 'text',
					'label'       => __( 'Button text', 'hamista' ),
					'description' => __( 'The button leads to the home page.', 'hamista' ),
				],
			],
		],
	],
];
