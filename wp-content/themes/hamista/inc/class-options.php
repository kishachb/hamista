<?php
/**
 * The theme's option (`hamista_theme`): defaults, reads and settings registration.
 *
 * @package Hamista\Theme
 */

namespace Hamista\Theme;

defined( 'ABSPATH' ) || exit;

/**
 * Reads `hamista_theme` merged over the theme's own defaults, so the theme works without
 * HAMISTA Core (spec §14). With core present, the same defaults are registered with
 * `hamista_register_option_defaults()` and the settings tabs are added on
 * `hamista_register_settings`.
 *
 * Defaults are cheap and untranslated. Text fields default to '' and templates fall back to
 * translated strings when a value is empty.
 *
 * @since 1.0.0
 */
final class Options {

	/**
	 * Option name.
	 */
	public const OPTION = 'hamista_theme';

	/**
	 * Settings tab files in inc/settings/, in navigation order (group `theme`).
	 */
	private const TABS = [ 'general', 'colors', 'typography', 'layout', 'header', 'footer', 'home', 'blog', 'pages', 'shop' ];

	/**
	 * Merged values for this request.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $cache = null;

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_action( 'after_setup_theme', [ $this, 'register_defaults' ] );
		add_action( 'hamista_register_settings', [ $this, 'register_settings' ] );
		foreach ( [ 'add_option_', 'update_option_', 'delete_option_' ] as $hook ) {
			add_action( $hook . self::OPTION, [ self::class, 'flush' ] );
		}
	}

	/**
	 * Every `hamista_theme` key with its default value.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			// General.
			'logo'                        => 0,
			'logo_dark'                   => 0,
			'logo_mobile'                 => 0,
			'logo_width'                  => 132,
			'logo_width_mobile'           => 108,
			'brand_text'                  => '',
			'show_tagline'                => false,
			'contact_phone'               => '',
			'contact_email'               => '',
			'contact_address'             => '',
			'contact_hours'               => '',
			'contact_map_embed'           => '',
			'social_links'                => [],

			// Colors.
			'color_brand_orange'          => '#FF7A1A',
			'color_brand_red'             => '#FF3B5C',
			'color_brand_purple'          => '#8B5CF6',
			'color_action_start'          => '#C2410C',
			'color_action_mid'            => '#BE123C',
			'color_action_end'            => '#7C3AED',
			'color_link_light'            => '#C2410C',
			'color_link_dark'             => '#FF8A3D',
			'light_bg'                    => '#FFFFFF',
			'light_surface'               => '#F7F7F8',
			'light_text'                  => '#16161D',
			'dark_bg'                     => '#0B0B0F',
			'dark_surface'                => '#14141B',
			'dark_text'                   => '#F4F4F6',
			'color_mode_default'          => 'light',
			'color_mode_toggle'           => true,
			'color_mode_remember'         => true,

			// Typography.
			'font_family'                 => 'vazirmatn',
			'font_family_latin'           => 'inter',
			'font_size_base'              => 16,
			'heading_weight'              => 800,
			'font_preload'                => true,

			// Layout.
			'container_width'             => 1240,
			'radius_scale'                => 'soft',
			'card_style'                  => 'elevated',
			'button_style'                => 'gradient',
			'animations'                  => true,
			'section_spacing'             => 'normal',
			'back_to_top'                 => true,

			// Header.
			'header_sticky'               => true,
			'header_glass'                => true,
			'header_transparent_home'     => false,
			'header_search'               => true,
			'header_cart'                 => true,
			'header_account'              => true,
			'header_notifications'        => true,
			'header_lang_switcher'        => true,
			'header_cta'                  => true,
			'header_cta_text'             => '',
			'header_cta_url'              => '',
			'header_cta_style'            => 'primary',
			'topbar'                      => false,
			'topbar_text'                 => '',
			'topbar_link_text'            => '',
			'topbar_link_url'             => '',
			'topbar_dismissible'          => true,

			// Footer.
			'footer_columns'              => 4,
			'footer_about'                => '',
			'footer_show_logo'            => true,
			'footer_menus'                => true,
			'footer_contact'              => true,
			'footer_social'               => true,
			'footer_trust_badges'         => '',
			'footer_copyright'            => '',
			'footer_bottom_menu'          => true,

			// Home: section order and state, then per-section content.
			'home_sections'               => [
				'hero'         => true,
				'logos'        => false,
				'services'     => true,
				'categories'   => true,
				'products'     => true,
				'stats'        => true,
				'process'      => true,
				'pricing'      => false,
				'portfolio'    => true,
				'testimonials' => true,
				'faq'          => true,
				'posts'        => true,
				'cta'          => true,
			],
			'hero_badge'                  => '',
			'hero_title'                  => '',
			'hero_highlight'              => '',
			'hero_subtitle'               => '',
			'hero_primary_text'           => '',
			'hero_primary_url'            => '',
			'hero_secondary_text'         => '',
			'hero_secondary_url'          => '',
			'hero_image'                  => 0,
			'hero_layout'                 => 'split',
			'hero_stats'                  => [],
			'logos_title'                 => '',
			'logos_items'                 => [],
			'services_title'              => '',
			'services_subtitle'           => '',
			'services_count'              => 6,
			'services_category'           => '',
			'categories_title'            => '',
			'categories_subtitle'         => '',
			'categories_terms'            => [],
			'products_title'              => '',
			'products_subtitle'           => '',
			'products_count'              => 8,
			'products_orderby'            => 'latest',
			'products_category'           => '',
			'stats_items'                 => [],
			'process_title'               => '',
			'process_subtitle'            => '',
			'process_steps'               => [],
			'pricing_title'               => '',
			'pricing_subtitle'            => '',
			'pricing_service'             => 0,
			'portfolio_title'             => '',
			'portfolio_subtitle'          => '',
			'portfolio_count'             => 6,
			'testimonials_title'          => '',
			'testimonials_count'          => 6,
			'faq_title'                   => '',
			'faq_subtitle'                => '',
			'faq_group'                   => '',
			'faq_count'                   => 6,
			'posts_title'                 => '',
			'posts_count'                 => 3,
			'cta_title'                   => '',
			'cta_text'                    => '',
			'cta_button_text'             => '',
			'cta_button_url'              => '',

			// Blog.
			'blog_layout'                 => 'grid',
			'blog_columns'                => 3,
			'blog_sidebar'                => 'none',
			'blog_excerpt_length'         => 24,
			'blog_show_author'            => true,
			'blog_show_date'              => true,
			'blog_show_reading_time'      => true,
			'blog_show_categories'        => true,
			'blog_show_tags'              => true,
			'blog_featured_image_single'  => true,
			'blog_author_box'             => true,
			'blog_related_posts'          => true,
			'blog_related_count'          => 3,
			'blog_share_buttons'          => true,
			'blog_share_networks'         => [ 'telegram', 'whatsapp', 'x', 'linkedin', 'copy' ],
			'blog_reading_progress'       => true,
			'blog_comments'               => true,

			// Pages.
			'page_title_bar'              => true,
			'page_title_style'            => 'gradient',
			'breadcrumbs'                 => true,
			'breadcrumbs_on_home'         => false,
			'error_404_title'             => '',
			'error_404_text'              => '',
			'error_404_button_text'       => '',

			// Shop.
			'shop_columns'                => 3,
			'shop_per_page'               => 12,
			'shop_sidebar'                => 'none',
			'product_card_rating'         => true,
			'product_card_version'        => true,
			'product_card_sales'          => false,
			'product_sticky_cart'         => true,
			'checkout_simplified_virtual' => true,
			'checkout_phone_required'     => true,
			'checkout_hide_company'       => true,
			'checkout_order_notes'        => true,

			// Performance (a section of core's Performance tab, stored here).
			'perf_defer_js'               => true,
			'perf_wc_assets_non_shop'     => true,
			'perf_cart_fragments'         => true,
			'perf_block_styles'           => true,
			'perf_hero_preload'           => true,
		];
	}

	/**
	 * Saved values merged over the defaults, read once per request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, [] );
			self::$cache = array_merge( self::defaults(), is_array( $saved ) ? $saved : [] );
		}
		return self::$cache;
	}

	/**
	 * One value: saved, else the default; null for an unknown key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get( string $key ): mixed {
		return self::all()[ $key ] ?? null;
	}

	/**
	 * A value as a string ('' for arrays and objects), trimmed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 * @return string
	 */
	public static function text( string $key ): string {
		$value = self::get( $key );
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * A text value, or the fallback when it is empty (templates pass a translated default).
	 *
	 * @since 1.0.0
	 *
	 * @param string $key      Option key.
	 * @param string $fallback Text used when the saved value is empty.
	 * @return string
	 */
	public static function text_or( string $key, string $fallback ): string {
		$value = self::text( $key );
		return '' === $value ? $fallback : $value;
	}

	/**
	 * A value as a boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public static function enabled( string $key ): bool {
		return (bool) self::get( $key );
	}

	/**
	 * A choice value, or the default when the saved value is not one of the allowed choices.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $key     Option key.
	 * @param string[] $allowed Allowed values.
	 * @return string
	 */
	public static function choice( string $key, array $allowed ): string {
		$value = self::text( $key );
		return in_array( $value, $allowed, true ) ? $value : (string) ( self::defaults()[ $key ] ?? reset( $allowed ) );
	}

	/**
	 * An integer clamped to a range; the default when the saved value is not numeric.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 * @param int    $min Minimum.
	 * @param int    $max Maximum.
	 * @return int
	 */
	public static function number( string $key, int $min, int $max ): int {
		$value = self::get( $key );
		if ( ! is_numeric( $value ) ) {
			$value = self::defaults()[ $key ] ?? $min;
		}
		return max( $min, min( $max, (int) $value ) );
	}

	/**
	 * Drops the per-request cache. Hooked to changes of the option.
	 *
	 * @since 1.0.0
	 */
	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Registers the defaults with HAMISTA Core, when it is active. Hooked to `after_setup_theme`.
	 *
	 * @since 1.0.0
	 */
	public function register_defaults(): void {
		if ( function_exists( 'hamista_register_option_defaults' ) ) {
			hamista_register_option_defaults( self::OPTION, self::defaults() );
		}
	}

	/**
	 * Adds the theme's settings tabs and its Performance section to core's settings registry.
	 * Hooked to `hamista_register_settings`; the registry API may be absent (guarded).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $settings Core's Settings_Registry.
	 */
	public function register_settings( $settings ): void {
		if ( ! is_object( $settings ) ) {
			return;
		}

		if ( method_exists( $settings, 'add_tab' ) ) {
			foreach ( self::TABS as $tab ) {
				$schema = require HAMISTA_THEME_DIR . 'inc/settings/' . $tab . '.php';
				if ( is_array( $schema ) ) {
					$settings->add_tab( $schema );
				}
			}
		}

		if ( method_exists( $settings, 'add_section' ) ) {
			$section = require HAMISTA_THEME_DIR . 'inc/settings/performance.php';
			if ( is_array( $section ) ) {
				$settings->add_section( 'performance', $section );
			}
		}
	}
}
