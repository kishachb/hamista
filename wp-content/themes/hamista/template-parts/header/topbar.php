<?php
/**
 * Announcement bar. A dismissed bar stays hidden until its text or link changes: the key is a
 * hash of both, checked by an inline script placed before the bar so it never flashes.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Options;

$hamista_text = Options::text( 'topbar_text' );
if ( '' === $hamista_text ) {
	return;
}

$hamista_link_text   = Options::text( 'topbar_link_text' );
$hamista_link_url    = Options::text( 'topbar_link_url' );
$hamista_dismissible = Options::enabled( 'topbar_dismissible' );
$hamista_key         = substr( md5( $hamista_text . '|' . $hamista_link_text . '|' . $hamista_link_url ), 0, 12 );

if ( $hamista_dismissible ) {
	wp_print_inline_script_tag(
		'try{localStorage.getItem("hamista-topbar")===' . wp_json_encode( $hamista_key ) . '&&document.documentElement.setAttribute("data-topbar","off")}catch(e){}'
	);
}
?>
<div class="hm-topbar" data-hm-topbar="<?php echo esc_attr( $hamista_key ); ?>">
	<div class="hm-container">
		<p>
			<?php echo esc_html( $hamista_text ); ?>
			<?php if ( '' !== $hamista_link_text && '' !== $hamista_link_url ) : ?>
				<a href="<?php echo esc_url( $hamista_link_url ); ?>"><?php echo esc_html( $hamista_link_text ); ?> <?php echo hamista_theme_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?></a>
			<?php endif; ?>
		</p>
		<?php if ( $hamista_dismissible ) : ?>
			<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-btn--sm hm-topbar__close" data-hm-topbar-close>
				<span class="hm-sr-only"><?php esc_html_e( 'Dismiss announcement', 'hamista' ); ?></span>
				<?php echo hamista_theme_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
			</button>
		<?php endif; ?>
	</div>
</div>
