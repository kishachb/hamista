<?php
/**
 * The "License" product data tab and its variation fields (spec §7).
 *
 * @package Hamista\License
 */

namespace Hamista\License\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Adds `_hamista_lm_*` meta to WooCommerce products and, for the
 * activation limit and validity, to their variations.
 *
 * @since 1.0.0
 */
final class Product_Fields {

	/**
	 * Product types this feature can be used with (the classic WooCommerce ones).
	 */
	private const APPLICABLE_TYPES = [ 'simple', 'variable' ];

	/**
	 * Registers the WordPress/WooCommerce hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'render_panel' ] );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save' ] );

		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'render_variation_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation' ], 10, 2 );
	}

	/**
	 * Adds the "License" tab, shown for simple and variable products.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_tab( array $tabs ): array {
		$tabs['hamista_license'] = [
			'label'    => __( 'License', 'hamista-license-manager' ),
			'target'   => 'hamista_license_product_data',
			'class'    => [ 'show_if_simple', 'show_if_variable' ],
			'priority' => 65,
			'icon'     => 'dashicons dashicons-admin-network',
		];
		return $tabs;
	}

	/**
	 * Renders the tab's panel.
	 *
	 * @since 1.0.0
	 */
	public function render_panel(): void {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof \WC_Product || ! in_array( $product->get_type(), self::APPLICABLE_TYPES, true ) ) {
			return;
		}

		echo '<div id="hamista_license_product_data" class="panel woocommerce_options_panel">';
		wp_nonce_field( 'hamista_lm_save_product', 'hamista_lm_product_nonce' );

		woocommerce_wp_checkbox(
			[
				'id'          => '_hamista_lm_enabled',
				'label'       => __( 'Sells a license', 'hamista-license-manager' ),
				'description' => __( 'Issue a Hamista license key when this product is purchased.', 'hamista-license-manager' ),
				'value'       => $product->get_meta( '_hamista_lm_enabled', true ) === 'yes' ? 'yes' : 'no',
			]
		);
		woocommerce_wp_text_input(
			[
				'id'                => '_hamista_lm_activation_limit',
				'label'             => __( 'Activation limit', 'hamista-license-manager' ),
				'description'       => __( 'Leave empty to use the default from Licenses settings. 0 = unlimited.', 'hamista-license-manager' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
				'value'             => $product->get_meta( '_hamista_lm_activation_limit', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'                => '_hamista_lm_validity_days',
				'label'             => __( 'Validity (days)', 'hamista-license-manager' ),
				'description'       => __( 'Leave empty to use the default from Licenses settings. 0 = lifetime.', 'hamista-license-manager' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
				'value'             => $product->get_meta( '_hamista_lm_validity_days', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_hamista_lm_slug',
				'label'       => __( 'Slug', 'hamista-license-manager' ),
				'description' => __( 'Identifies this product to the client SDK, e.g. in update checks.', 'hamista-license-manager' ),
				'value'       => $product->get_meta( '_hamista_lm_slug', true ),
			]
		);
		woocommerce_wp_select(
			[
				'id'      => '_hamista_lm_type',
				'label'   => __( 'Type', 'hamista-license-manager' ),
				'options' => self::types(),
				'value'   => $product->get_meta( '_hamista_lm_type', true ) ?: 'plugin',
			]
		);

		echo '</div>';
	}

	/**
	 * Saves the product-level fields.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	public function save( \WC_Product $product ): void {
		if ( ! isset( $_POST['hamista_lm_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hamista_lm_product_nonce'] ) ), 'hamista_lm_save_product' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}

		$product->update_meta_data( '_hamista_lm_enabled', isset( $_POST['_hamista_lm_enabled'] ) ? 'yes' : 'no' );
		$product->update_meta_data( '_hamista_lm_activation_limit', self::sanitize_int_or_empty( $_POST['_hamista_lm_activation_limit'] ?? '' ) );
		$product->update_meta_data( '_hamista_lm_validity_days', self::sanitize_int_or_empty( $_POST['_hamista_lm_validity_days'] ?? '' ) );
		$product->update_meta_data( '_hamista_lm_slug', isset( $_POST['_hamista_lm_slug'] ) ? sanitize_title( wp_unslash( $_POST['_hamista_lm_slug'] ) ) : '' );
		$product->update_meta_data( '_hamista_lm_type', self::sanitize_type( $_POST['_hamista_lm_type'] ?? '' ) );
	}

	/**
	 * Renders the per-variation activation limit and validity override.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $loop           Position in the variation loop.
	 * @param array    $variation_data Variation data (unused).
	 * @param \WP_Post $variation      Variation post.
	 */
	public function render_variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- WooCommerce hook signature.
		$product = wc_get_product( $variation->ID );
		if ( ! $product instanceof \WC_Product_Variation ) {
			return;
		}

		echo '<div class="hamista-lm-variation-fields show_if_variation_has_license" style="width:100%;">';
		woocommerce_wp_text_input(
			[
				'id'                => "_hamista_lm_activation_limit_{$loop}",
				'name'              => "_hamista_lm_variation_activation_limit[{$loop}]",
				'label'             => __( 'License activation limit', 'hamista-license-manager' ),
				'description'       => __( 'Leave empty to use the product default.', 'hamista-license-manager' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
				'value'             => $product->get_meta( '_hamista_lm_activation_limit', true ),
				'wrapper_class'     => 'form-row form-row-first',
			]
		);
		woocommerce_wp_text_input(
			[
				'id'                => "_hamista_lm_validity_days_{$loop}",
				'name'              => "_hamista_lm_variation_validity_days[{$loop}]",
				'label'             => __( 'License validity (days)', 'hamista-license-manager' ),
				'description'       => __( 'Leave empty to use the product default.', 'hamista-license-manager' ),
				'type'              => 'number',
				'custom_attributes' => [ 'min' => '0', 'step' => '1' ],
				'value'             => $product->get_meta( '_hamista_lm_validity_days', true ),
				'wrapper_class'     => 'form-row form-row-last',
			]
		);
		echo '</div>';
	}

	/**
	 * Saves a variation's override fields.
	 *
	 * WooCommerce's own AJAX controller (`WC_AJAX::save_variations()`)
	 * verifies the `save-variations` nonce and `edit_product` capability
	 * before this hook fires; the capability check here guards direct calls.
	 *
	 * @since 1.0.0
	 *
	 * @param int $variation_id Variation id.
	 * @param int $loop         Position in the variation loop.
	 */
	public function save_variation( int $variation_id, int $loop ): void {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		$product = wc_get_product( $variation_id );
		if ( ! $product instanceof \WC_Product_Variation ) {
			return;
		}

		$limit    = $_POST['_hamista_lm_variation_activation_limit'][ $loop ] ?? '';
		$validity = $_POST['_hamista_lm_variation_validity_days'][ $loop ] ?? '';

		$product->update_meta_data( '_hamista_lm_activation_limit', self::sanitize_int_or_empty( wp_unslash( $limit ) ) );
		$product->update_meta_data( '_hamista_lm_validity_days', self::sanitize_int_or_empty( wp_unslash( $validity ) ) );
		$product->save_meta_data();
	}

	/**
	 * `_hamista_lm_type` options.
	 *
	 * @return array<string, string>
	 */
	private static function types(): array {
		return [
			'plugin'   => __( 'Plugin', 'hamista-license-manager' ),
			'theme'    => __( 'Theme', 'hamista-license-manager' ),
			'software' => __( 'Software', 'hamista-license-manager' ),
		];
	}

	/**
	 * Sanitizes a number field that may be left empty (meaning "use the default").
	 *
	 * @param mixed $value Raw value.
	 * @return string '' or a non-negative integer string.
	 */
	private static function sanitize_int_or_empty( $value ): string {
		$value = trim( (string) $value );
		return '' === $value ? '' : (string) max( 0, (int) $value );
	}

	/**
	 * Sanitizes `_hamista_lm_type` against the known options.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function sanitize_type( $value ): string {
		$value = sanitize_key( (string) $value );
		return array_key_exists( $value, self::types() ) ? $value : 'plugin';
	}
}
