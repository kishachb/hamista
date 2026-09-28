<?php
/**
 * Performance tab (option `hamista_core`).
 *
 * Fields only — Task 1.4 implements the behaviour. The theme adds its own
 * section here (option `hamista_theme`) through `hamista_register_settings`.
 *
 * @package Hamista\Core
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'performance',
	'title'    => __( 'Performance', 'hamista-core' ),
	'icon'     => 'zap',
	'group'    => 'advanced',
	'option'   => 'hamista_core',
	'priority' => 10,
	'sections' => [
		[
			'id'          => 'assets',
			'title'       => __( 'Assets & scripts', 'hamista-core' ),
			'description' => __( 'These settings take effect once the performance module is active.', 'hamista-core' ),
			'fields'      => [
				[ 'id' => 'perf_disable_emojis', 'type' => 'toggle', 'label' => __( 'Disable the emoji script', 'hamista-core' ), 'description' => __( 'Removes the core emoji detection script and inline styles.', 'hamista-core' ) ],
				[ 'id' => 'perf_disable_embeds', 'type' => 'toggle', 'label' => __( 'Disable oEmbed discovery', 'hamista-core' ), 'description' => __( 'Stops WordPress from embedding, and being embedded by, other sites.', 'hamista-core' ) ],
				[ 'id' => 'perf_remove_jquery_migrate', 'type' => 'toggle', 'label' => __( 'Remove jQuery Migrate', 'hamista-core' ) ],
				[ 'id' => 'perf_dashicons_visitors', 'type' => 'toggle', 'label' => __( 'Remove Dashicons for visitors', 'hamista-core' ), 'description' => __( 'Dashicons still load for logged-in users.', 'hamista-core' ) ],
				[
					'id'      => 'perf_heartbeat',
					'type'    => 'select',
					'label'   => __( 'Heartbeat API', 'hamista-core' ),
					'options' => [
						'default'          => __( 'Default', 'hamista-core' ),
						'reduce'           => __( 'Reduce frequency', 'hamista-core' ),
						'disable_frontend' => __( 'Disable on the frontend', 'hamista-core' ),
					],
				],
				[
					'id'      => 'perf_speculation',
					'type'    => 'select',
					'label'   => __( 'Speculative page loading', 'hamista-core' ),
					'options' => [
						'auto'      => __( 'Auto', 'hamista-core' ),
						'prefetch'  => __( 'Prefetch', 'hamista-core' ),
						'prerender' => __( 'Prerender', 'hamista-core' ),
						'off'       => __( 'Off', 'hamista-core' ),
					],
				],
				[ 'id' => 'perf_dns_prefetch', 'type' => 'textarea', 'label' => __( 'DNS prefetch hosts', 'hamista-core' ), 'description' => __( 'One host per line, e.g. fonts.example.com.', 'hamista-core' ), 'rows' => 3 ],
				[ 'id' => 'perf_lazy_iframes', 'type' => 'toggle', 'label' => __( 'Lazy-load iframes', 'hamista-core' ) ],
			],
		],
		[
			'id'     => 'media',
			'title'  => __( 'Media', 'hamista-core' ),
			'fields' => [
				[ 'id' => 'perf_webp_uploads', 'type' => 'toggle', 'label' => __( 'Convert uploaded JPEG/PNG to WebP', 'hamista-core' ) ],
				[ 'id' => 'perf_big_image_threshold', 'type' => 'number', 'label' => __( 'Big image threshold (px)', 'hamista-core' ), 'min' => 0, 'max' => 10000, 'step' => 10, 'description' => __( 'Images wider or taller than this are scaled down on upload. 0 disables scaling.', 'hamista-core' ) ],
				[ 'id' => 'perf_local_avatars', 'type' => 'toggle', 'label' => __( 'Serve avatars locally', 'hamista-core' ), 'description' => __( 'Avoids requests to a remote Gravatar-style service.', 'hamista-core' ) ],
			],
		],
	],
];
