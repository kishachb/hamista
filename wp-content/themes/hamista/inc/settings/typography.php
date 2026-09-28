<?php
/**
 * Settings tab: Typography (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'typography',
	'title'    => __( 'Typography', 'hamista' ),
	'icon'     => 'pen-tool',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 30,
	'sections' => [
		[
			'id'          => 'fonts',
			'title'       => __( 'Fonts', 'hamista' ),
			'description' => __( 'Both fonts are self-hosted variable fonts; nothing loads from Google Fonts or a CDN. Persian digits in prices and dates are set in HAMISTA Core (Jalali module).', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'font_family',
					'type'        => 'select',
					'label'       => __( 'Persian font', 'hamista' ),
					'description' => __( 'Vazirmatn (about 48 KB, Arabic script only) or the device\'s own font.', 'hamista' ),
					'options'     => [
						'vazirmatn' => __( 'Vazirmatn', 'hamista' ),
						'system'    => __( 'System font', 'hamista' ),
					],
				],
				[
					'id'          => 'font_family_latin',
					'type'        => 'select',
					'label'       => __( 'Latin font', 'hamista' ),
					'description' => __( 'Inter (about 48 KB, Latin only) for English text, or the device\'s own font.', 'hamista' ),
					'options'     => [
						'inter'  => __( 'Inter', 'hamista' ),
						'system' => __( 'System font', 'hamista' ),
					],
				],
				[
					'id'          => 'font_size_base',
					'type'        => 'number',
					'label'       => __( 'Body text size (px)', 'hamista' ),
					'description' => __( 'Headings keep their fluid scale.', 'hamista' ),
					'min'         => 14,
					'max'         => 20,
					'step'        => 1,
				],
				[
					'id'          => 'heading_weight',
					'type'        => 'select',
					'label'       => __( 'Heading weight', 'hamista' ),
					'description' => __( 'Weight of large headings.', 'hamista' ),
					'options'     => [
						600 => __( 'Semibold (600)', 'hamista' ),
						700 => __( 'Bold (700)', 'hamista' ),
						800 => __( 'Extra bold (800)', 'hamista' ),
						900 => __( 'Black (900)', 'hamista' ),
					],
				],
				[
					'id'          => 'font_preload',
					'type'        => 'toggle',
					'label'       => __( 'Preload the primary font', 'hamista' ),
					'description' => __( 'Preloads Vazirmatn on Persian (RTL) pages and Inter on English pages, so text renders sooner.', 'hamista' ),
				],
			],
		],
	],
];
