<?php
/**
 * Extra profile fields on WooCommerce's edit-account form.
 *
 * @package Hamista\Dashboard
 */

namespace Hamista\Dashboard\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Mobile phone (Iranian format), company and website, each switchable through
 * the `hamista_dashboard` settings. Validation runs on WooCommerce's own
 * edit-account form (its own nonce already protects the request).
 *
 * @since 1.0.0
 */
final class Profile_Fields {

	/**
	 * User meta keys.
	 */
	public const META_MOBILE  = 'hamista_mobile';
	public const META_COMPANY = 'hamista_company';
	public const META_WEBSITE = 'hamista_website';

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public static function register(): void {
		add_action( 'woocommerce_edit_account_form', [ self::class, 'render_fields' ] );
		add_action( 'woocommerce_save_account_details_errors', [ self::class, 'validate' ] );
		add_action( 'woocommerce_save_account_details', [ self::class, 'save' ] );
	}

	/**
	 * Prints the extra fields.
	 *
	 * @since 1.0.0
	 */
	public static function render_fields(): void {
		$user_id = get_current_user_id();

		if ( self::mobile_enabled() ) {
			$required = self::mobile_required();
			?>
			<div class="hm-field">
				<label class="hm-field__label" for="hamista_mobile">
					<?php esc_html_e( 'Mobile phone', 'hamista-customer-dashboard' ); ?>
					<?php if ( $required ) : ?><span class="hm-required">*</span><?php endif; ?>
				</label>
				<input type="tel" class="hm-input" name="hamista_mobile" id="hamista_mobile" inputmode="numeric" autocomplete="tel"
					placeholder="0912xxxxxxx" value="<?php echo esc_attr( get_user_meta( $user_id, self::META_MOBILE, true ) ); ?>" <?php echo $required ? 'required' : ''; ?> />
				<span class="hm-field__help"><?php esc_html_e( 'Iranian mobile number, e.g. 0912xxxxxxx.', 'hamista-customer-dashboard' ); ?></span>
			</div>
			<?php
		}

		if ( self::company_enabled() ) {
			?>
			<div class="hm-field">
				<label class="hm-field__label" for="hamista_company"><?php esc_html_e( 'Company', 'hamista-customer-dashboard' ); ?></label>
				<input type="text" class="hm-input" name="hamista_company" id="hamista_company"
					value="<?php echo esc_attr( get_user_meta( $user_id, self::META_COMPANY, true ) ); ?>" />
			</div>
			<?php
		}

		if ( self::website_enabled() ) {
			?>
			<div class="hm-field">
				<label class="hm-field__label" for="hamista_website"><?php esc_html_e( 'Website', 'hamista-customer-dashboard' ); ?></label>
				<input type="url" class="hm-input" name="hamista_website" id="hamista_website" placeholder="https://"
					value="<?php echo esc_attr( get_user_meta( $user_id, self::META_WEBSITE, true ) ); ?>" />
			</div>
			<?php
		}
	}

	/**
	 * Validates the posted values. Hooked to `woocommerce_save_account_details_errors`.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Error $errors Error bag.
	 */
	public static function validate( \WP_Error $errors ): void {
		if ( self::mobile_enabled() ) {
			$raw = isset( $_POST['hamista_mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['hamista_mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own form nonce covers this request.
			if ( '' === $raw ) {
				if ( self::mobile_required() ) {
					$errors->add( 'hamista_mobile_required', __( 'Please enter your mobile phone number.', 'hamista-customer-dashboard' ) );
				}
			} elseif ( '' === Mobile::normalize( $raw ) ) {
				$errors->add( 'hamista_mobile_invalid', __( 'Please enter a valid Iranian mobile number, e.g. 0912xxxxxxx.', 'hamista-customer-dashboard' ) );
			}
		}

		if ( self::website_enabled() ) {
			$raw = isset( $_POST['hamista_website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['hamista_website'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own form nonce covers this request.
			if ( '' !== $raw && '' === esc_url_raw( $raw ) ) {
				$errors->add( 'hamista_website_invalid', __( 'Please enter a valid website address.', 'hamista-customer-dashboard' ) );
			}
		}
	}

	/**
	 * Saves the posted values. Hooked to `woocommerce_save_account_details`.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 */
	public static function save( int $user_id ): void {
		if ( self::mobile_enabled() ) {
			$raw = isset( $_POST['hamista_mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['hamista_mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own form nonce covers this request.
			update_user_meta( $user_id, self::META_MOBILE, Mobile::normalize( $raw ) );
		}

		if ( self::company_enabled() ) {
			$raw = isset( $_POST['hamista_company'] ) ? sanitize_text_field( wp_unslash( $_POST['hamista_company'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own form nonce covers this request.
			update_user_meta( $user_id, self::META_COMPANY, $raw );
		}

		if ( self::website_enabled() ) {
			$raw = isset( $_POST['hamista_website'] ) ? esc_url_raw( trim( wp_unslash( $_POST['hamista_website'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own form nonce covers this request.
			update_user_meta( $user_id, self::META_WEBSITE, $raw );
		}
	}

	/**
	 * Whether the mobile field is switched on.
	 *
	 * @return bool
	 */
	private static function mobile_enabled(): bool {
		return (bool) hamista_get_option( 'hamista_dashboard', 'profile_mobile', true );
	}

	/**
	 * Whether the mobile field is required.
	 *
	 * @return bool
	 */
	private static function mobile_required(): bool {
		return (bool) hamista_get_option( 'hamista_dashboard', 'profile_mobile_required', false );
	}

	/**
	 * Whether the company field is switched on.
	 *
	 * @return bool
	 */
	private static function company_enabled(): bool {
		return (bool) hamista_get_option( 'hamista_dashboard', 'profile_company', true );
	}

	/**
	 * Whether the website field is switched on.
	 *
	 * @return bool
	 */
	private static function website_enabled(): bool {
		return (bool) hamista_get_option( 'hamista_dashboard', 'profile_website', true );
	}
}
