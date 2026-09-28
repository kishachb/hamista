<?php
/**
 * Sidebar: the Blog or Shop widget area when that layout has a sidebar (Settings → Blog / Shop;
 * both default to none). Also keeps get_sidebar() from falling back to the deprecated
 * theme-compat sidebar (WooCommerce calls get_sidebar( 'shop' )).
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Options;

$hamista_shop = function_exists( 'is_woocommerce' ) && is_woocommerce();
$hamista_area = $hamista_shop ? 'shop' : 'blog';

if ( 'none' === Options::choice( $hamista_area . '_sidebar', [ 'none', 'start', 'end' ] ) || ! is_active_sidebar( $hamista_area ) ) {
	return;
}
?>
<aside class="hm-sidebar" aria-label="<?php echo esc_attr( $hamista_shop ? __( 'Shop sidebar', 'hamista' ) : __( 'Blog sidebar', 'hamista' ) ); ?>">
	<?php dynamic_sidebar( $hamista_area ); ?>
</aside>
