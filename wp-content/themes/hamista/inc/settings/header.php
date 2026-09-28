<?php
/**
 * Settings tab: Header (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

$hamista_toggle = static function ( string $id, string $label, string $description, array $extra = [] ): array {
	return array_merge(
		[
			'id'          => $id,
			'type'        => 'toggle',
			'label'       => $label,
			'description' => $description,
		],
		$extra
	);
};

$hamista_if_cta    = [
	'show_if' => [
		'field' => 'header_cta',
		'value' => true,
	],
];
$hamista_if_topbar = [
	'show_if' => [
		'field' => 'topbar',
		'value' => true,
	],
];

return [
	'id'       => 'header',
	'title'    => __( 'Header', 'hamista' ),
	'icon'     => 'layout-template',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 50,
	'sections' => [
		[
			'id'          => 'behaviour',
			'title'       => __( 'Behaviour', 'hamista' ),
			'description' => __( 'How the header sits on the page.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'header_sticky', __( 'Sticky header', 'hamista' ), __( 'The header stays at the top while the page scrolls.', 'hamista' ) ),
				$hamista_toggle(
					'header_glass',
					__( 'Glass effect', 'hamista' ),
					__( 'A translucent, blurred background once the page scrolls.', 'hamista' ),
					[
						'show_if' => [
							'field' => 'header_sticky',
							'value' => true,
						],
					]
				),
				$hamista_toggle( 'header_transparent_home', __( 'Transparent on the home page', 'hamista' ), __( 'The header overlays the hero until the page scrolls.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'elements',
			'title'       => __( 'Elements', 'hamista' ),
			'description' => __( 'Every header element can be switched off.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'header_search', __( 'Search', 'hamista' ), __( 'A search button that opens a search panel.', 'hamista' ) ),
				$hamista_toggle( 'header_cart', __( 'Cart', 'hamista' ), __( 'Cart link with the item count (WooCommerce).', 'hamista' ) ),
				$hamista_toggle( 'header_account', __( 'Account', 'hamista' ), __( 'Link to My Account, or to log in.', 'hamista' ) ),
				$hamista_toggle( 'header_notifications', __( 'Notifications', 'hamista' ), __( 'A bell with the unread count for logged-in customers (HAMISTA Customer Dashboard).', 'hamista' ) ),
				$hamista_toggle( 'header_lang_switcher', __( 'Language switcher', 'hamista' ), __( 'Shown only when WPML or Polylang is active.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'cta',
			'title'       => __( 'Call-to-action button', 'hamista' ),
			'description' => __( 'The button at the end of the header.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'header_cta', __( 'Show the button', 'hamista' ), __( 'Hidden on phones, where it moves into the menu.', 'hamista' ) ),
				array_merge(
					[
						'id'          => 'header_cta_text',
						'type'        => 'text',
						'label'       => __( 'Button text', 'hamista' ),
						'description' => __( 'Leave empty for "Start a project".', 'hamista' ),
					],
					$hamista_if_cta
				),
				array_merge(
					[
						'id'          => 'header_cta_url',
						'type'        => 'url',
						'label'       => __( 'Button link', 'hamista' ),
						'description' => __( 'Leave empty to link to the services archive (HAMISTA Core).', 'hamista' ),
					],
					$hamista_if_cta
				),
				array_merge(
					[
						'id'          => 'header_cta_style',
						'type'        => 'select',
						'label'       => __( 'Button style', 'hamista' ),
						'description' => __( 'Primary uses the action gradient.', 'hamista' ),
						'options'     => [
							'primary'   => __( 'Primary', 'hamista' ),
							'dark'      => __( 'Dark', 'hamista' ),
							'secondary' => __( 'Secondary', 'hamista' ),
						],
					],
					$hamista_if_cta
				),
			],
		],
		[
			'id'          => 'topbar',
			'title'       => __( 'Announcement bar', 'hamista' ),
			'description' => __( 'A slim bar above the header for offers and news.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'topbar', __( 'Show the announcement bar', 'hamista' ), __( 'Shown on every page while it has text.', 'hamista' ) ),
				array_merge(
					[
						'id'          => 'topbar_text',
						'type'        => 'text',
						'label'       => __( 'Text', 'hamista' ),
						'description' => __( 'One short sentence.', 'hamista' ),
					],
					$hamista_if_topbar
				),
				array_merge(
					[
						'id'          => 'topbar_link_text',
						'type'        => 'text',
						'label'       => __( 'Link text', 'hamista' ),
						'description' => __( 'Optional.', 'hamista' ),
					],
					$hamista_if_topbar
				),
				array_merge(
					[
						'id'          => 'topbar_link_url',
						'type'        => 'url',
						'label'       => __( 'Link URL', 'hamista' ),
						'description' => __( 'Optional.', 'hamista' ),
					],
					$hamista_if_topbar
				),
				$hamista_toggle( 'topbar_dismissible', __( 'Visitors can close it', 'hamista' ), __( 'A closed bar stays closed until its text changes.', 'hamista' ), $hamista_if_topbar ),
			],
		],
	],
];
