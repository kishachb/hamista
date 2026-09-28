<?php
/**
 * SEO tab (option `hamista_core`).
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'seo',
	'title'    => __( 'SEO', 'hamista-core' ),
	'icon'     => 'search',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 30,
	'sections' => [
		[
			'id'     => 'general',
			'title'  => __( 'Schema & sharing', 'hamista-core' ),
			'fields' => [
				[
					'id'      => 'seo_detected',
					'type'    => 'html',
					'content' => static function (): string {
						$found = [];
						if ( defined( 'RANK_MATH_VERSION' ) ) {
							$found[] = 'Rank Math';
						}
						if ( defined( 'WPSEO_VERSION' ) ) {
							$found[] = 'Yoast SEO';
						}
						if ( defined( 'AIOSEO_VERSION' ) ) {
							$found[] = 'All in One SEO';
						}
						if ( defined( 'SEOPRESS_VERSION' ) ) {
							$found[] = 'SEOPress';
						}
						if ( [] === $found ) {
							return '<p class="hm-alert hm-alert--info">' . esc_html__( 'No other SEO plugin was detected.', 'hamista-core' ) . '</p>';
						}
						return '<p class="hm-alert hm-alert--warning">' . esc_html(
							sprintf(
								/* translators: %s: comma-separated plugin names. */
								__( 'Detected SEO plugin(s): %s. They may already output schema and Open Graph markup; enabling both here can duplicate it.', 'hamista-core' ),
								implode( ', ', $found )
							)
						) . '</p>';
					},
				],
				[ 'id' => 'seo_schema', 'type' => 'toggle', 'label' => __( 'Organization / WebSite schema', 'hamista-core' ) ],
				[
					'id'      => 'seo_org_type',
					'type'    => 'select',
					'label'   => __( 'Organization type', 'hamista-core' ),
					'options' => [
						'Organization'  => __( 'Organization', 'hamista-core' ),
						'LocalBusiness' => __( 'Local business', 'hamista-core' ),
						'OnlineStore'   => __( 'Online store', 'hamista-core' ),
					],
					'show_if' => [ 'field' => 'seo_schema', 'value' => true ],
				],
				[ 'id' => 'seo_org_name', 'type' => 'text', 'label' => __( 'Organization name', 'hamista-core' ), 'show_if' => [ 'field' => 'seo_schema', 'value' => true ] ],
				[ 'id' => 'seo_org_logo', 'type' => 'image', 'label' => __( 'Organization logo', 'hamista-core' ), 'show_if' => [ 'field' => 'seo_schema', 'value' => true ] ],
				[ 'id' => 'seo_open_graph', 'type' => 'toggle', 'label' => __( 'Open Graph & Twitter card tags', 'hamista-core' ) ],
				[ 'id' => 'seo_breadcrumbs_schema', 'type' => 'toggle', 'label' => __( 'BreadcrumbList schema', 'hamista-core' ) ],
			],
		],
	],
];
