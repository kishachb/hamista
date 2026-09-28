<?php
/**
 * Settings tab: Colors (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

$hamista_color = static function ( string $id, string $label, string $description ): array {
	return [
		'id'          => $id,
		'type'        => 'color',
		'label'       => $label,
		'description' => $description,
	];
};

return [
	'id'       => 'colors',
	'title'    => __( 'Colors', 'hamista' ),
	'icon'     => 'palette',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 20,
	'sections' => [
		[
			'id'          => 'brand',
			'title'       => __( 'Brand gradient', 'hamista' ),
			'description' => __( 'The vivid orange → red → purple gradient. Decorative only (accents, borders, glows): never put text on it.', 'hamista' ),
			'fields'      => [
				$hamista_color( 'color_brand_orange', __( 'Brand orange', 'hamista' ), __( 'First stop of the brand gradient.', 'hamista' ) ),
				$hamista_color( 'color_brand_red', __( 'Brand red', 'hamista' ), __( 'Middle stop of the brand gradient.', 'hamista' ) ),
				$hamista_color( 'color_brand_purple', __( 'Brand purple', 'hamista' ), __( 'Last stop of the brand gradient.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'action',
			'title'       => __( 'Action gradient', 'hamista' ),
			'description' => __( 'Primary buttons, badges and highlights with white text. Keep every stop at a 4.5:1 contrast ratio or more against white.', 'hamista' ),
			'fields'      => [
				$hamista_color( 'color_action_start', __( 'Action start', 'hamista' ), __( 'Also the solid primary colour.', 'hamista' ) ),
				$hamista_color( 'color_action_mid', __( 'Action middle', 'hamista' ), __( 'Middle stop of the action gradient.', 'hamista' ) ),
				$hamista_color( 'color_action_end', __( 'Action end', 'hamista' ), __( 'Last stop of the action gradient.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'links',
			'title'       => __( 'Links', 'hamista' ),
			'description' => __( 'Link colours need 4.5:1 contrast against their background.', 'hamista' ),
			'fields'      => [
				$hamista_color( 'color_link_light', __( 'Links in light mode', 'hamista' ), __( 'The hover colour is derived automatically.', 'hamista' ) ),
				$hamista_color( 'color_link_dark', __( 'Links in dark mode', 'hamista' ), __( 'The hover colour is derived automatically.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'light',
			'title'       => __( 'Light palette', 'hamista' ),
			'description' => __( 'Clean white by default.', 'hamista' ),
			'fields'      => [
				$hamista_color( 'light_bg', __( 'Background', 'hamista' ), __( 'Page background; also the browser theme colour.', 'hamista' ) ),
				$hamista_color( 'light_surface', __( 'Surface', 'hamista' ), __( 'Muted sections and panels.', 'hamista' ) ),
				$hamista_color( 'light_text', __( 'Text', 'hamista' ), __( 'Body text and headings.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'dark',
			'title'       => __( 'Dark palette', 'hamista' ),
			'description' => __( 'Luxury black by default. Also used by the footer.', 'hamista' ),
			'fields'      => [
				$hamista_color( 'dark_bg', __( 'Background', 'hamista' ), __( 'Page background in dark mode; also the browser theme colour.', 'hamista' ) ),
				$hamista_color( 'dark_surface', __( 'Surface', 'hamista' ), __( 'Muted sections and panels in dark mode.', 'hamista' ) ),
				$hamista_color( 'dark_text', __( 'Text', 'hamista' ), __( 'Body text and headings in dark mode.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'mode',
			'title'       => __( 'Colour mode', 'hamista' ),
			'description' => __( 'Light is the default. Visitors can switch with the toggle in the header.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'color_mode_default',
					'type'        => 'radio',
					'label'       => __( 'Default mode', 'hamista' ),
					'description' => __( 'System follows the visitor\'s device setting.', 'hamista' ),
					'options'     => [
						'light'  => __( 'Light', 'hamista' ),
						'dark'   => __( 'Dark', 'hamista' ),
						'system' => __( 'System', 'hamista' ),
					],
				],
				[
					'id'          => 'color_mode_toggle',
					'type'        => 'toggle',
					'label'       => __( 'Show the colour-mode toggle', 'hamista' ),
					'description' => __( 'A header button that cycles light, dark and system.', 'hamista' ),
				],
				[
					'id'          => 'color_mode_remember',
					'type'        => 'toggle',
					'label'       => __( 'Remember the visitor\'s choice', 'hamista' ),
					'description' => __( 'Stores the choice in the browser (localStorage), not on the server.', 'hamista' ),
					'show_if'     => [
						'field' => 'color_mode_toggle',
						'value' => true,
					],
				],
			],
		],
	],
];
