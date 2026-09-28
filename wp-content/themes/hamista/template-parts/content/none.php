<?php
/**
 * Empty state for loops without posts (and searches without results).
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

$hamista_search = is_search();
?>
<div class="hm-empty">
	<span class="hm-empty__icon"><?php echo hamista_theme_icon( $hamista_search ? 'search' : 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?></span>
	<h2 class="hm-empty__title">
		<?php echo esc_html( $hamista_search ? __( 'Nothing matched your search', 'hamista' ) : __( 'Nothing published yet', 'hamista' ) ); ?>
	</h2>
	<p class="hm-empty__text">
		<?php echo esc_html( $hamista_search ? __( 'Try other words, or browse the latest articles.', 'hamista' ) : __( 'New articles are on their way. Check back soon.', 'hamista' ) ); ?>
	</p>
	<a class="hm-btn hm-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the home page', 'hamista' ); ?></a>
</div>
