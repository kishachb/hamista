<?php
/**
 * Settings tab: Shop (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Settings\Choices;

$hamista_toggle = static function ( string $id, string $label, string $description ): array {
	return [
		'id'          => $id,
		'type'        => 'toggle',
		'label'       => $label,
		'description' => $description,
	];
};

return [
	'id'       => 'shop',
	'title'    => __( 'Shop', 'hamista' ),
	'icon'     => 'shopping-bag',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 100,
	'sections' => [
		[
			'id'          => 'catalog',
			'title'       => __( 'Catalogue', 'hamista' ),
			'description' => __( 'The shop page and product categories (WooCommerce).', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'shop_columns',
					'type'        => 'select',
					'label'       => __( 'Columns', 'hamista' ),
					'description' => __( 'Products per row on wide screens.', 'hamista' ),
					'options'     => Choices::columns(),
				],
				[
					'id'          => 'shop_per_page',
					'type'        => 'number',
					'label'       => __( 'Products per page', 'hamista' ),
					'description' => __( 'Before pagination.', 'hamista' ),
					'min'         => 4,
					'max'         => 48,
					'step'        => 1,
				],
				[
					'id'          => 'shop_sidebar',
					'type'        => 'radio',
					'label'       => __( 'Sidebar', 'hamista' ),
					'description' => __( 'Uses the Shop sidebar widget area.', 'hamista' ),
					'options'     => Choices::sidebar_positions(),
				],
			],
		],
		[
			'id'          => 'cards',
			'title'       => __( 'Product cards', 'hamista' ),
			'description' => __( 'Details on each product card.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'product_card_rating', __( 'Rating', 'hamista' ), __( 'Star rating, when the product has reviews.', 'hamista' ) ),
				$hamista_toggle( 'product_card_version', __( 'Version badge', 'hamista' ), __( 'Current version of plugins, themes and software.', 'hamista' ) ),
				$hamista_toggle( 'product_card_sales', __( 'Sales count', 'hamista' ), __( 'Number of sales.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'product',
			'title'       => __( 'Product page', 'hamista' ),
			'description' => __( 'The single product page.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'product_sticky_cart', __( 'Sticky add-to-cart bar', 'hamista' ), __( 'Appears once the main button scrolls out of view.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'checkout',
			'title'       => __( 'Checkout', 'hamista' ),
			'description' => __( 'Classic checkout adjustments.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'checkout_simplified_virtual', __( 'Simplified checkout for virtual carts', 'hamista' ), __( 'Drops address fields when every item is virtual or downloadable.', 'hamista' ) ),
				$hamista_toggle( 'checkout_phone_required', __( 'Phone number required', 'hamista' ), __( 'Makes the billing phone mandatory.', 'hamista' ) ),
				$hamista_toggle( 'checkout_hide_company', __( 'Hide the company field', 'hamista' ), __( 'Removes the billing company field.', 'hamista' ) ),
				$hamista_toggle( 'checkout_order_notes', __( 'Order notes', 'hamista' ), __( 'Keeps the order notes field.', 'hamista' ) ),
			],
		],
	],
];
