<?php
/**
 * Settings tab: Footer (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'footer',
	'title'    => __( 'Footer', 'hamista' ),
	'icon'     => 'layers',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 60,
	'sections' => [
		[
			'id'          => 'columns',
			'title'       => __( 'Columns', 'hamista' ),
			'description' => __( 'The brand column, then the footer menus (Appearance → Menus) and the contact details.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'footer_columns',
					'type'        => 'select',
					'label'       => __( 'Number of columns', 'hamista' ),
					'description' => __( 'On wide screens. Phones always stack.', 'hamista' ),
					'options'     => [
						1 => number_format_i18n( 1 ),
						2 => number_format_i18n( 2 ),
						3 => number_format_i18n( 3 ),
						4 => number_format_i18n( 4 ),
					],
				],
				[
					'id'          => 'footer_show_logo',
					'type'        => 'toggle',
					'label'       => __( 'Show the logo', 'hamista' ),
					'description' => __( 'At the top of the brand column.', 'hamista' ),
				],
				[
					'id'          => 'footer_about',
					'type'        => 'textarea',
					'label'       => __( 'About text', 'hamista' ),
					'description' => __( 'A sentence or two under the logo. Leave empty for the default text.', 'hamista' ),
				],
				[
					'id'          => 'footer_menus',
					'type'        => 'toggle',
					'label'       => __( 'Footer menus', 'hamista' ),
					'description' => __( 'Shows the menus assigned to Footer column 1–3.', 'hamista' ),
				],
				[
					'id'          => 'footer_contact',
					'type'        => 'toggle',
					'label'       => __( 'Contact details', 'hamista' ),
					'description' => __( 'From the General tab.', 'hamista' ),
				],
				[
					'id'          => 'footer_social',
					'type'        => 'toggle',
					'label'       => __( 'Social links', 'hamista' ),
					'description' => __( 'From the General tab.', 'hamista' ),
				],
			],
		],
		[
			'id'          => 'bottom',
			'title'       => __( 'Trust badges and bottom bar', 'hamista' ),
			'description' => __( 'Badges, copyright and the bottom menu.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'footer_trust_badges',
					'type'        => 'code',
					'mode'        => 'html',
					'label'       => __( 'Trust badge code', 'hamista' ),
					'description' => __( 'Paste the e-Namad or Samandehi snippet. Saved only for users who may post unfiltered HTML.', 'hamista' ),
				],
				[
					'id'          => 'footer_copyright',
					'type'        => 'text',
					'label'       => __( 'Copyright text', 'hamista' ),
					'description' => __( 'Placeholders: {year} and {site}. Leave empty for "© {year} {site}. All rights reserved."', 'hamista' ),
				],
				[
					'id'          => 'footer_bottom_menu',
					'type'        => 'toggle',
					'label'       => __( 'Bottom menu', 'hamista' ),
					'description' => __( 'Shows the menu assigned to Footer bottom bar.', 'hamista' ),
				],
			],
		],
	],
];
