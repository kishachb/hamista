<?php
/**
 * Option lists for the settings tab schemas.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Builds `options` arrays for select / radio / choice / multicheck / sortable fields.
 *
 * Tab schemas are built only on the settings screen (spec §14), so the queries here never run on
 * the front end.
 *
 * @since 1.0.0
 */
final class Choices {

	/**
	 * Social networks for the social links repeater. Brand names are not translated.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function social_networks(): array {
		return [
			'instagram' => 'Instagram',
			'telegram'  => 'Telegram',
			'whatsapp'  => 'WhatsApp',
			'linkedin'  => 'LinkedIn',
			'x'         => 'X',
			'youtube'   => 'YouTube',
			'aparat'    => 'Aparat',
			'github'    => 'GitHub',
			'dribbble'  => 'Dribbble',
			'behance'   => 'Behance',
			'pinterest' => 'Pinterest',
			'facebook'  => 'Facebook',
		];
	}

	/**
	 * Home page sections, in their default order.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function home_sections(): array {
		return [
			'hero'         => __( 'Hero', 'hamista' ),
			'logos'        => __( 'Client logos', 'hamista' ),
			'services'     => __( 'Services', 'hamista' ),
			'categories'   => __( 'Product categories', 'hamista' ),
			'products'     => __( 'Products', 'hamista' ),
			'stats'        => __( 'Stats', 'hamista' ),
			'process'      => __( 'Process', 'hamista' ),
			'pricing'      => __( 'Pricing', 'hamista' ),
			'portfolio'    => __( 'Portfolio', 'hamista' ),
			'testimonials' => __( 'Testimonials', 'hamista' ),
			'faq'          => __( 'FAQ', 'hamista' ),
			'posts'        => __( 'Latest posts', 'hamista' ),
			'cta'          => __( 'Call to action', 'hamista' ),
		];
	}

	/**
	 * Terms of a taxonomy, keyed by slug, after an optional "any" entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $any      Label of the empty choice; '' leaves it out.
	 * @return array<string, string>
	 */
	public static function terms( string $taxonomy, string $any = '' ): array {
		$choices = '' === $any ? [] : [ '' => $any ];
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $choices;
		}
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
			]
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$choices[ $term->slug ] = $term->name;
			}
		}
		return $choices;
	}

	/**
	 * Published posts of a type, keyed by ID, after a "none" entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type.
	 * @param string $none      Label of the empty choice.
	 * @return array<int|string, string>
	 */
	public static function posts( string $post_type, string $none ): array {
		$choices = [ 0 => $none ];
		if ( ! post_type_exists( $post_type ) ) {
			return $choices;
		}
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			]
		);
		foreach ( $posts as $post ) {
			$choices[ $post->ID ] = get_the_title( $post );
		}
		return $choices;
	}

	/**
	 * Sidebar positions: none, start or end.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function sidebar_positions(): array {
		return [
			'none'  => __( 'No sidebar', 'hamista' ),
			'start' => __( 'Start side (right in RTL)', 'hamista' ),
			'end'   => __( 'End side (left in RTL)', 'hamista' ),
		];
	}

	/**
	 * Column counts 2–4.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public static function columns(): array {
		$choices = [];
		foreach ( [ 2, 3, 4 ] as $count ) {
			$choices[ $count ] = number_format_i18n( $count );
		}
		return $choices;
	}
}
