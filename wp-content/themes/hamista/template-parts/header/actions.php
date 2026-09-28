<?php
/**
 * Header actions: search, colour mode, account, cart, call to action and the mobile menu button.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Options;

$hamista_account = hamista_account_link();
$hamista_cart    = hamista_cart_link();
$hamista_cta     = hamista_header_cta();

if ( Options::enabled( 'header_search' ) ) :
	?>
	<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon" data-hm-open="hm-search" aria-controls="hm-search" aria-expanded="false">
		<span class="hm-sr-only"><?php esc_html_e( 'Search', 'hamista' ); ?></span>
		<?php echo hamista_theme_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
	</button>
	<?php
endif;

hamista_color_mode_toggle();

if ( $hamista_account ) :
	?>
	<a class="hm-btn hm-btn--ghost hm-btn--icon hm-header__account" href="<?php echo esc_url( $hamista_account['url'] ); ?>">
		<span class="hm-sr-only"><?php echo esc_html( $hamista_account['label'] ); ?></span>
		<?php echo hamista_theme_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
	</a>
	<?php
endif;

if ( $hamista_cart ) :
	?>
	<a class="hm-btn hm-btn--ghost hm-btn--icon hm-header__cart" href="<?php echo esc_url( $hamista_cart['url'] ); ?>">
		<span class="hm-sr-only"><?php esc_html_e( 'Cart', 'hamista' ); ?></span>
		<?php echo hamista_theme_icon( 'shopping-cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
		<span class="hm-badge hm-badge--brand hm-header__count" data-hm-cart-count><?php echo $hamista_cart['count'] ? esc_html( number_format_i18n( $hamista_cart['count'] ) ) : ''; ?></span>
	</a>
	<?php
endif;

if ( $hamista_cta ) :
	?>
	<a class="hm-btn hm-btn--<?php echo esc_attr( $hamista_cta['style'] ); ?> hm-btn--sm hm-header__cta" href="<?php echo esc_url( $hamista_cta['url'] ); ?>"><?php echo esc_html( $hamista_cta['text'] ); ?></a>
	<?php
endif;
?>
<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-header__menu-btn" data-hm-open="hm-drawer" aria-controls="hm-drawer" aria-expanded="false">
	<span class="hm-sr-only"><?php esc_html_e( 'Menu', 'hamista' ); ?></span>
	<?php echo hamista_theme_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
</button>
