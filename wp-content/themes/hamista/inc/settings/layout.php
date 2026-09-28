<?php
/**
 * Settings tab: Layout (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'layout',
	'title'    => __( 'Layout', 'hamista' ),
	'icon'     => 'layout-grid',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 40,
	'sections' => [
		[
			'id'          => 'structure',
			'title'       => __( 'Structure', 'hamista' ),
			'description' => __( 'Page width and vertical rhythm.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'container_width',
					'type'        => 'number',
					'label'       => __( 'Content width (px)', 'hamista' ),
					'description' => __( 'Maximum width of the page content.', 'hamista' ),
					'min'         => 1080,
					'max'         => 1440,
					'step'        => 10,
				],
				[
					'id'          => 'section_spacing',
					'type'        => 'select',
					'label'       => __( 'Section spacing', 'hamista' ),
					'description' => __( 'Space above and below page sections; it scales with the screen.', 'hamista' ),
					'options'     => [
						'compact' => __( 'Compact', 'hamista' ),
						'normal'  => __( 'Normal', 'hamista' ),
						'relaxed' => __( 'Relaxed', 'hamista' ),
					],
				],
			],
		],
		[
			'id'          => 'style',
			'title'       => __( 'Style', 'hamista' ),
			'description' => __( 'Shape of every card, button and field across the site and the HAMISTA plugins.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'radius_scale',
					'type'        => 'choice',
					'label'       => __( 'Corner radius', 'hamista' ),
					'description' => __( 'Sharp, soft or round corners.', 'hamista' ),
					'options'     => [
						'sharp' => __( 'Sharp', 'hamista' ),
						'soft'  => __( 'Soft', 'hamista' ),
						'round' => __( 'Round', 'hamista' ),
					],
				],
				[
					'id'          => 'card_style',
					'type'        => 'choice',
					'label'       => __( 'Card style', 'hamista' ),
					'description' => __( 'Elevated (soft shadow), bordered (flat) or glass (translucent).', 'hamista' ),
					'options'     => [
						'elevated' => __( 'Elevated', 'hamista' ),
						'bordered' => __( 'Bordered', 'hamista' ),
						'glass'    => __( 'Glass', 'hamista' ),
					],
				],
				[
					'id'          => 'button_style',
					'type'        => 'choice',
					'label'       => __( 'Primary button style', 'hamista' ),
					'description' => __( 'Gradient, solid colour, or dark (inverted in dark mode).', 'hamista' ),
					'options'     => [
						'gradient' => __( 'Gradient', 'hamista' ),
						'solid'    => __( 'Solid', 'hamista' ),
						'dark'     => __( 'Dark', 'hamista' ),
					],
				],
			],
		],
		[
			'id'          => 'motion',
			'title'       => __( 'Motion', 'hamista' ),
			'description' => __( 'Animations are always off for visitors who ask their device for reduced motion.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'animations',
					'type'        => 'toggle',
					'label'       => __( 'Reveal animations', 'hamista' ),
					'description' => __( 'Sections fade in as they scroll into view.', 'hamista' ),
				],
				[
					'id'          => 'back_to_top',
					'type'        => 'toggle',
					'label'       => __( 'Back-to-top button', 'hamista' ),
					'description' => __( 'Appears after one screen of scrolling.', 'hamista' ),
				],
			],
		],
	],
];
