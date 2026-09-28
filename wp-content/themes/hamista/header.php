<?php
/**
 * Site header: document head, skip link, optional announcement bar, the sticky header, the mobile
 * drawer and the search panel. Opens <main id="main">.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Navigation;
use Hamista\Theme\Options;

$hamista_header_classes = [ 'hm-header' ];
if ( Options::enabled( 'header_sticky' ) ) {
	$hamista_header_classes[] = 'is-sticky';
	if ( Options::enabled( 'header_glass' ) ) {
		$hamista_header_classes[] = 'is-glass';
	}
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'hamista' ); ?></a>
<?php
if ( Options::enabled( 'topbar' ) ) {
	get_template_part( 'template-parts/header/topbar' );
}
?>
<div class="hm-sentinel" data-hm-sentinel aria-hidden="true"></div>
<header class="<?php echo esc_attr( implode( ' ', $hamista_header_classes ) ); ?>" data-hm-header>
	<div class="hm-container">
		<?php hamista_site_logo(); ?>

		<nav class="hm-nav" aria-label="<?php esc_attr_e( 'Primary', 'hamista' ); ?>">
			<?php Navigation::menu( 'primary' ); ?>
		</nav>

		<div class="hm-header__actions">
			<?php get_template_part( 'template-parts/header/actions' ); ?>
		</div>
	</div>
</header>
<?php
get_template_part( 'template-parts/header/drawer' );
if ( Options::enabled( 'header_search' ) ) {
	get_template_part( 'template-parts/header/search' );
}
?>
<main id="main" tabindex="-1">
