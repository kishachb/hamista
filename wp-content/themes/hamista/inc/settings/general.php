<?php
/**
 * Settings tab: General / Brand (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'general',
	'title'    => __( 'General', 'hamista' ),
	'icon'     => 'settings',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 10,
	'sections' => [
		[
			'id'          => 'brand',
			'title'       => __( 'Brand', 'hamista' ),
			'description' => __( 'Logos for light mode, dark mode and phones. Without a logo the header shows a typographic wordmark.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'logo',
					'type'        => 'image',
					'label'       => __( 'Logo', 'hamista' ),
					'description' => __( 'Shown in light mode. SVG or a transparent PNG works best.', 'hamista' ),
				],
				[
					'id'          => 'logo_dark',
					'type'        => 'image',
					'label'       => __( 'Dark mode logo', 'hamista' ),
					'description' => __( 'Replaces the logo in dark mode and in the (always dark) footer. Leave empty to keep the main logo.', 'hamista' ),
				],
				[
					'id'          => 'logo_mobile',
					'type'        => 'image',
					'label'       => __( 'Mobile logo', 'hamista' ),
					'description' => __( 'A compact mark for screens narrower than 640px. Leave empty to scale the main logo.', 'hamista' ),
				],
				[
					'id'          => 'logo_width',
					'type'        => 'number',
					'label'       => __( 'Logo width (px)', 'hamista' ),
					'description' => __( 'Display width on tablets and desktops.', 'hamista' ),
					'min'         => 40,
					'max'         => 400,
					'step'        => 1,
				],
				[
					'id'          => 'logo_width_mobile',
					'type'        => 'number',
					'label'       => __( 'Logo width on phones (px)', 'hamista' ),
					'description' => __( 'Display width below 640px.', 'hamista' ),
					'min'         => 40,
					'max'         => 300,
					'step'        => 1,
				],
				[
					'id'          => 'brand_text',
					'type'        => 'text',
					'label'       => __( 'Wordmark text', 'hamista' ),
					'description' => __( 'Text of the gradient wordmark used when no logo is set. Leave empty for "HAMISTA".', 'hamista' ),
				],
				[
					'id'          => 'show_tagline',
					'type'        => 'toggle',
					'label'       => __( 'Show the tagline', 'hamista' ),
					'description' => __( 'Shows the site tagline (Settings → General) next to the logo on larger screens.', 'hamista' ),
				],
			],
		],
		[
			'id'          => 'contact',
			'title'       => __( 'Contact details', 'hamista' ),
			'description' => __( 'Used in the footer, the contact page and structured data.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'contact_phone',
					'type'        => 'tel',
					'label'       => __( 'Phone', 'hamista' ),
					'description' => __( 'Shown as a tap-to-call link.', 'hamista' ),
				],
				[
					'id'          => 'contact_email',
					'type'        => 'email',
					'label'       => __( 'Email', 'hamista' ),
					'description' => __( 'Public contact address.', 'hamista' ),
				],
				[
					'id'          => 'contact_address',
					'type'        => 'textarea',
					'label'       => __( 'Address', 'hamista' ),
					'description' => __( 'Postal or office address.', 'hamista' ),
				],
				[
					'id'          => 'contact_hours',
					'type'        => 'text',
					'label'       => __( 'Working hours', 'hamista' ),
					'description' => __( 'For example: Saturday to Wednesday, 9:00–17:00.', 'hamista' ),
				],
				[
					'id'          => 'contact_map_embed',
					'type'        => 'code',
					'mode'        => 'html',
					'label'       => __( 'Map embed code', 'hamista' ),
					'description' => __( 'An iframe from Neshan, Balad or another map service. Saved only for users who may post unfiltered HTML.', 'hamista' ),
				],
			],
		],
		[
			'id'          => 'social',
			'title'       => __( 'Social links', 'hamista' ),
			'description' => __( 'Shown as icons in the footer.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'social_links',
					'type'        => 'repeater',
					'label'       => __( 'Profiles', 'hamista' ),
					'description' => __( 'Add, remove and reorder your profiles.', 'hamista' ),
					'fields'      => [
						[
							'id'          => 'network',
							'type'        => 'select',
							'label'       => __( 'Network', 'hamista' ),
							'description' => __( 'Decides the icon.', 'hamista' ),
							'options'     => \Hamista\Theme\Settings\Choices::social_networks(),
						],
						[
							'id'          => 'url',
							'type'        => 'url',
							'label'       => __( 'Profile URL', 'hamista' ),
							'description' => __( 'The full address, starting with https://.', 'hamista' ),
						],
					],
				],
			],
		],
	],
];
