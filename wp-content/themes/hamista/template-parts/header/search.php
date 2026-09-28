<?php
/**
 * Search panel: drops from the top of the viewport; focus moves to the field when it opens.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="hm-overlay hm-search" id="hm-search" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'hamista' ); ?>">
	<div class="hm-overlay__backdrop" data-hm-close></div>
	<div class="hm-overlay__panel">
		<div class="hm-container">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="hm-sr-only" for="hm-search-field"><?php esc_html_e( 'Search for:', 'hamista' ); ?></label>
				<input class="hm-input" id="hm-search-field" type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e( 'Search products, services and articles…', 'hamista' ); ?>" data-hm-autofocus>
				<button class="hm-btn hm-btn--primary" type="submit"><?php esc_html_e( 'Search', 'hamista' ); ?></button>
				<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon" data-hm-close>
					<span class="hm-sr-only"><?php esc_html_e( 'Close search', 'hamista' ); ?></span>
					<?php echo hamista_theme_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
				</button>
			</form>
		</div>
	</div>
</div>
