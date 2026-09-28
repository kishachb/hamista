<?php
/**
 * Settings tab: Blog (group `theme`, option `hamista_theme`). Schema: spec §4.5.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Settings\Choices;

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

return [
	'id'       => 'blog',
	'title'    => __( 'Blog', 'hamista' ),
	'icon'     => 'book-open',
	'group'    => 'theme',
	'option'   => 'hamista_theme',
	'priority' => 80,
	'sections' => [
		[
			'id'          => 'archive',
			'title'       => __( 'Archive layout', 'hamista' ),
			'description' => __( 'The blog index, categories, tags and search results.', 'hamista' ),
			'fields'      => [
				[
					'id'          => 'blog_layout',
					'type'        => 'choice',
					'label'       => __( 'Layout', 'hamista' ),
					'description' => __( 'Cards in a grid, or a list with the image beside the text.', 'hamista' ),
					'options'     => [
						'grid' => __( 'Grid', 'hamista' ),
						'list' => __( 'List', 'hamista' ),
					],
				],
				[
					'id'          => 'blog_columns',
					'type'        => 'select',
					'label'       => __( 'Grid columns', 'hamista' ),
					'description' => __( 'On wide screens.', 'hamista' ),
					'options'     => Choices::columns(),
					'show_if'     => [
						'field' => 'blog_layout',
						'value' => 'grid',
					],
				],
				[
					'id'          => 'blog_sidebar',
					'type'        => 'radio',
					'label'       => __( 'Sidebar', 'hamista' ),
					'description' => __( 'Uses the Blog sidebar widget area.', 'hamista' ),
					'options'     => Choices::sidebar_positions(),
				],
				[
					'id'          => 'blog_excerpt_length',
					'type'        => 'number',
					'label'       => __( 'Excerpt length (words)', 'hamista' ),
					'description' => __( 'Length of the summary on post cards.', 'hamista' ),
					'min'         => 10,
					'max'         => 80,
					'step'        => 1,
				],
			],
		],
		[
			'id'          => 'meta',
			'title'       => __( 'Post details', 'hamista' ),
			'description' => __( 'What appears with each post.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'blog_show_author', __( 'Author', 'hamista' ), __( 'Author name and avatar.', 'hamista' ) ),
				$hamista_toggle( 'blog_show_date', __( 'Date', 'hamista' ), __( 'Solar Hijri in Persian when the Jalali module is on.', 'hamista' ) ),
				$hamista_toggle( 'blog_show_reading_time', __( 'Reading time', 'hamista' ), __( 'Estimated minutes to read.', 'hamista' ) ),
				$hamista_toggle( 'blog_show_categories', __( 'Categories', 'hamista' ), __( 'Category badges.', 'hamista' ) ),
				$hamista_toggle( 'blog_show_tags', __( 'Tags', 'hamista' ), __( 'Tags under single posts.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'single',
			'title'       => __( 'Single post', 'hamista' ),
			'description' => __( 'The article page.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'blog_featured_image_single', __( 'Featured image', 'hamista' ), __( 'Shows the featured image above the article.', 'hamista' ) ),
				$hamista_toggle( 'blog_author_box', __( 'Author box', 'hamista' ), __( 'Biography after the article.', 'hamista' ) ),
				$hamista_toggle( 'blog_related_posts', __( 'Related posts', 'hamista' ), __( 'Posts from the same categories.', 'hamista' ) ),
				[
					'id'          => 'blog_related_count',
					'type'        => 'number',
					'label'       => __( 'Related posts count', 'hamista' ),
					'description' => __( 'How many related posts to show.', 'hamista' ),
					'min'         => 1,
					'max'         => 6,
					'step'        => 1,
					'show_if'     => [
						'field' => 'blog_related_posts',
						'value' => true,
					],
				],
				$hamista_toggle( 'blog_reading_progress', __( 'Reading progress bar', 'hamista' ), __( 'A thin gradient bar at the top of the article.', 'hamista' ) ),
				$hamista_toggle( 'blog_comments', __( 'Comments', 'hamista' ), __( 'Shows the comments area where comments are open.', 'hamista' ) ),
			],
		],
		[
			'id'          => 'share',
			'title'       => __( 'Sharing', 'hamista' ),
			'description' => __( 'Share buttons under the article; no third-party scripts are loaded.', 'hamista' ),
			'fields'      => [
				$hamista_toggle( 'blog_share_buttons', __( 'Share buttons', 'hamista' ), __( 'Plain links to each network.', 'hamista' ) ),
				[
					'id'          => 'blog_share_networks',
					'type'        => 'multicheck',
					'label'       => __( 'Networks', 'hamista' ),
					'description' => __( 'Which buttons to show.', 'hamista' ),
					'options'     => [
						'telegram' => 'Telegram',
						'whatsapp' => 'WhatsApp',
						'x'        => 'X',
						'linkedin' => 'LinkedIn',
						'copy'     => __( 'Copy link', 'hamista' ),
					],
					'show_if'     => [
						'field' => 'blog_share_buttons',
						'value' => true,
					],
				],
			],
		],
	],
];
