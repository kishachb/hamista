<?php
/**
 * Settings tab: Home (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * The front page renders the enabled sections in the saved order (Task 2.3). `home_sections`
 * is an ordered map of section id => enabled.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Settings\Choices;

$hamista_field = static function ( string $id, string $type, string $label, string $description, array $extra = [] ): array {
	return array_merge(
		[
			'id'          => $id,
			'type'        => $type,
			'label'       => $label,
			'description' => $description,
		],
		$extra
	);
};

$hamista_heading = static function ( string $prefix, bool $subtitle = true ) use ( $hamista_field ): array {
	$fields = [ $hamista_field( $prefix . '_title', 'text', __( 'Title', 'hamista' ), __( 'Leave empty for the default title.', 'hamista' ) ) ];
	if ( $subtitle ) {
		$fields[] = $hamista_field( $prefix . '_subtitle', 'textarea', __( 'Subtitle', 'hamista' ), __( 'One or two sentences under the title.', 'hamista' ) );
	}
	return $fields;
};

$hamista_count = static function ( string $id, int $max ) use ( $hamista_field ): array {
	return $hamista_field(
		$id,
		'number',
		__( 'Number of items', 'hamista' ),
		__( 'How many items to show.', 'hamista' ),
		[
			'min'  => 1,
			'max'  => $max,
			'step' => 1,
		]
	);
};

return [
	'id'       => 'home',
	'title'    => __( 'Home', 'hamista' ),
	'icon'     => 'house',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 70,
	'sections' => [
		[
			'id'          => 'sections',
			'title'       => __( 'Sections', 'hamista' ),
			'description' => __( 'Used when the front page is not built with Elementor.', 'hamista' ),
			'fields'      => [
				$hamista_field(
					'home_sections',
					'sortable',
					__( 'Sections and order', 'hamista' ),
					__( 'Drag to reorder; switch a section off to hide it.', 'hamista' ),
					[ 'options' => Choices::home_sections() ]
				),
			],
		],
		[
			'id'          => 'hero',
			'title'       => __( 'Hero', 'hamista' ),
			'description' => __( 'The first screen of the home page.', 'hamista' ),
			'fields'      => [
				$hamista_field( 'hero_badge', 'text', __( 'Badge', 'hamista' ), __( 'A short label above the title.', 'hamista' ) ),
				$hamista_field( 'hero_title', 'text', __( 'Title', 'hamista' ), __( 'The main headline.', 'hamista' ) ),
				$hamista_field( 'hero_highlight', 'text', __( 'Highlighted words', 'hamista' ), __( 'Words of the title painted with the brand gradient.', 'hamista' ) ),
				$hamista_field( 'hero_subtitle', 'textarea', __( 'Subtitle', 'hamista' ), __( 'One or two sentences under the headline.', 'hamista' ) ),
				$hamista_field( 'hero_primary_text', 'text', __( 'Primary button text', 'hamista' ), __( 'Leave empty to hide the button.', 'hamista' ) ),
				$hamista_field( 'hero_primary_url', 'url', __( 'Primary button link', 'hamista' ), __( 'Where the primary button leads.', 'hamista' ) ),
				$hamista_field( 'hero_secondary_text', 'text', __( 'Secondary button text', 'hamista' ), __( 'Leave empty to hide the button.', 'hamista' ) ),
				$hamista_field( 'hero_secondary_url', 'url', __( 'Secondary button link', 'hamista' ), __( 'Where the secondary button leads.', 'hamista' ) ),
				$hamista_field( 'hero_image', 'image', __( 'Image', 'hamista' ), __( 'Leave empty for the built-in illustration.', 'hamista' ) ),
				$hamista_field(
					'hero_layout',
					'choice',
					__( 'Layout', 'hamista' ),
					__( 'Text beside the visual, or centred.', 'hamista' ),
					[
						'options' => [
							'split'  => __( 'Split', 'hamista' ),
							'center' => __( 'Centred', 'hamista' ),
						],
					]
				),
				$hamista_field(
					'hero_stats',
					'repeater',
					__( 'Stats', 'hamista' ),
					__( 'Small figures under the buttons, e.g. "+120 projects".', 'hamista' ),
					[
						'fields' => [
							$hamista_field( 'value', 'text', __( 'Value', 'hamista' ), __( 'For example +120.', 'hamista' ) ),
							$hamista_field( 'label', 'text', __( 'Label', 'hamista' ), __( 'For example projects delivered.', 'hamista' ) ),
						],
					]
				),
			],
		],
		[
			'id'          => 'logos',
			'title'       => __( 'Client logos', 'hamista' ),
			'description' => __( 'A strip of client or partner logos.', 'hamista' ),
			'fields'      => [
				$hamista_field( 'logos_title', 'text', __( 'Title', 'hamista' ), __( 'Leave empty for the default title.', 'hamista' ) ),
				$hamista_field(
					'logos_items',
					'repeater',
					__( 'Logos', 'hamista' ),
					__( 'Add, remove and reorder logos.', 'hamista' ),
					[
						'fields' => [
							$hamista_field( 'image', 'image', __( 'Logo', 'hamista' ), __( 'A monochrome logo works best.', 'hamista' ) ),
							$hamista_field( 'name', 'text', __( 'Name', 'hamista' ), __( 'Used as the image\'s alternative text.', 'hamista' ) ),
							$hamista_field( 'url', 'url', __( 'Link', 'hamista' ), __( 'Optional.', 'hamista' ) ),
						],
					]
				),
			],
		],
		[
			'id'          => 'services',
			'title'       => __( 'Services', 'hamista' ),
			'description' => __( 'Branding and development services (HAMISTA Core).', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'services' ),
				[
					$hamista_count( 'services_count', 12 ),
					$hamista_field(
						'services_category',
						'select',
						__( 'Category', 'hamista' ),
						__( 'Show services from one category only.', 'hamista' ),
						[ 'options' => Choices::terms( 'hamista_service_cat', __( 'All categories', 'hamista' ) ) ]
					),
				]
			),
		],
		[
			'id'          => 'categories',
			'title'       => __( 'Product categories', 'hamista' ),
			'description' => __( 'Marketplace category cards (WooCommerce).', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'categories' ),
				[
					$hamista_field(
						'categories_terms',
						'multicheck',
						__( 'Categories', 'hamista' ),
						__( 'Leave all unchecked to show the top-level categories.', 'hamista' ),
						[ 'options' => Choices::terms( 'product_cat' ) ]
					),
				]
			),
		],
		[
			'id'          => 'products',
			'title'       => __( 'Products', 'hamista' ),
			'description' => __( 'Plugins, themes and software from the shop (WooCommerce).', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'products' ),
				[
					$hamista_count( 'products_count', 24 ),
					$hamista_field(
						'products_orderby',
						'select',
						__( 'Order', 'hamista' ),
						__( 'Which products come first.', 'hamista' ),
						[
							'options' => [
								'latest'  => __( 'Latest', 'hamista' ),
								'popular' => __( 'Best selling', 'hamista' ),
								'rating'  => __( 'Top rated', 'hamista' ),
								'price'   => __( 'Price', 'hamista' ),
							],
						]
					),
					$hamista_field(
						'products_category',
						'select',
						__( 'Category', 'hamista' ),
						__( 'Show products from one category only.', 'hamista' ),
						[ 'options' => Choices::terms( 'product_cat', __( 'All categories', 'hamista' ) ) ]
					),
				]
			),
		],
		[
			'id'          => 'stats',
			'title'       => __( 'Stats', 'hamista' ),
			'description' => __( 'Key figures in a row of stat cards.', 'hamista' ),
			'fields'      => [
				$hamista_field(
					'stats_items',
					'repeater',
					__( 'Figures', 'hamista' ),
					__( 'Add, remove and reorder figures.', 'hamista' ),
					[
						'fields' => [
							$hamista_field( 'icon', 'text', __( 'Icon', 'hamista' ), __( 'An icon name, e.g. rocket, users or award.', 'hamista' ) ),
							$hamista_field( 'value', 'text', __( 'Value', 'hamista' ), __( 'For example +350.', 'hamista' ) ),
							$hamista_field( 'label', 'text', __( 'Label', 'hamista' ), __( 'For example happy clients.', 'hamista' ) ),
						],
					]
				),
			],
		],
		[
			'id'          => 'process',
			'title'       => __( 'Process', 'hamista' ),
			'description' => __( 'How you work, step by step.', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'process' ),
				[
					$hamista_field(
						'process_steps',
						'repeater',
						__( 'Steps', 'hamista' ),
						__( 'Add, remove and reorder steps.', 'hamista' ),
						[
							'fields' => [
								$hamista_field( 'title', 'text', __( 'Title', 'hamista' ), __( 'Step name.', 'hamista' ) ),
								$hamista_field( 'text', 'textarea', __( 'Text', 'hamista' ), __( 'One or two sentences.', 'hamista' ) ),
								$hamista_field( 'icon', 'text', __( 'Icon', 'hamista' ), __( 'An icon name, e.g. lightbulb.', 'hamista' ) ),
							],
						]
					),
				]
			),
		],
		[
			'id'          => 'pricing',
			'title'       => __( 'Pricing', 'hamista' ),
			'description' => __( 'The plans of one service.', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'pricing' ),
				[
					$hamista_field(
						'pricing_service',
						'select',
						__( 'Service', 'hamista' ),
						__( 'Whose plans to show.', 'hamista' ),
						[ 'options' => Choices::posts( 'hamista_service', __( '— Select a service —', 'hamista' ) ) ]
					),
				]
			),
		],
		[
			'id'          => 'portfolio',
			'title'       => __( 'Portfolio', 'hamista' ),
			'description' => __( 'Recent projects.', 'hamista' ),
			'fields'      => array_merge( $hamista_heading( 'portfolio' ), [ $hamista_count( 'portfolio_count', 12 ) ] ),
		],
		[
			'id'          => 'testimonials',
			'title'       => __( 'Testimonials', 'hamista' ),
			'description' => __( 'What clients say.', 'hamista' ),
			'fields'      => array_merge( $hamista_heading( 'testimonials', false ), [ $hamista_count( 'testimonials_count', 12 ) ] ),
		],
		[
			'id'          => 'faq',
			'title'       => __( 'FAQ', 'hamista' ),
			'description' => __( 'Frequently asked questions (also added as FAQPage structured data).', 'hamista' ),
			'fields'      => array_merge(
				$hamista_heading( 'faq' ),
				[
					$hamista_field(
						'faq_group',
						'select',
						__( 'Group', 'hamista' ),
						__( 'Show questions from one group only.', 'hamista' ),
						[ 'options' => Choices::terms( 'hamista_faq_group', __( 'All groups', 'hamista' ) ) ]
					),
					$hamista_count( 'faq_count', 20 ),
				]
			),
		],
		[
			'id'          => 'posts',
			'title'       => __( 'Latest posts', 'hamista' ),
			'description' => __( 'Recent blog posts.', 'hamista' ),
			'fields'      => array_merge( $hamista_heading( 'posts', false ), [ $hamista_count( 'posts_count', 9 ) ] ),
		],
		[
			'id'          => 'cta',
			'title'       => __( 'Call to action', 'hamista' ),
			'description' => __( 'The closing band of the page.', 'hamista' ),
			'fields'      => [
				$hamista_field( 'cta_title', 'text', __( 'Title', 'hamista' ), __( 'Leave empty for the default title.', 'hamista' ) ),
				$hamista_field( 'cta_text', 'textarea', __( 'Text', 'hamista' ), __( 'One or two sentences.', 'hamista' ) ),
				$hamista_field( 'cta_button_text', 'text', __( 'Button text', 'hamista' ), __( 'Leave empty for the default label.', 'hamista' ) ),
				$hamista_field( 'cta_button_url', 'url', __( 'Button link', 'hamista' ), __( 'Where the button leads.', 'hamista' ) ),
			],
		],
	],
];
