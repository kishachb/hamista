<?php
/**
 * Settings section for core's Performance tab, stored in `hamista_theme`.
 *
 * Added with `$settings->add_section( 'performance', $section )`; the section-level `option`
 * overrides the tab's option (settings registry, Task 1.2).
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

$hamista_toggle = static function ( string $id, string $label, string $description ): array {
	return [
		'id'          => $id,
		'type'        => 'toggle',
		'label'       => $label,
		'description' => $description,
	];
};

return [
	'id'          => 'theme-performance',
	'title'       => __( 'Theme', 'hamista' ),
	'description' => __( 'Front-end optimisations applied by the Hamista theme.', 'hamista' ),
	'option'      => 'hamista_theme',
	'fields'      => [
		$hamista_toggle( 'perf_defer_js', __( 'Defer theme scripts', 'hamista' ), __( 'Theme scripts load with defer and never block rendering.', 'hamista' ) ),
		$hamista_toggle( 'perf_wc_assets_non_shop', __( 'WooCommerce assets only on shop pages', 'hamista' ), __( 'Drops WooCommerce scripts, order attribution and block styles on other pages.', 'hamista' ) ),
		$hamista_toggle( 'perf_cart_fragments', __( 'Lightweight cart count', 'hamista' ), __( 'Replaces WooCommerce cart fragments with a small, cookie-gated request.', 'hamista' ) ),
		$hamista_toggle( 'perf_block_styles', __( 'Block styles only when used', 'hamista' ), __( 'Drops the block library and global styles on views without blocks.', 'hamista' ) ),
		$hamista_toggle( 'perf_hero_preload', __( 'Preload the hero image', 'hamista' ), __( 'Preloads the home hero image with high fetch priority.', 'hamista' ) ),
	],
];
