<?php
/**
 * Mobile drawer: an off-canvas, focus-trapped panel from the inline-end side (main.js makes the
 * rest of the page inert while it is open). Uses the Mobile menu, else the Primary menu.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Navigation;

$hamista_account = hamista_account_link();
$hamista_cta     = hamista_header_cta();
?>
<div class="hm-overlay hm-drawer" id="hm-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Site menu', 'hamista' ); ?>">
	<div class="hm-overlay__backdrop" data-hm-close></div>
	<div class="hm-overlay__panel">
		<div class="hm-drawer__head">
			<?php hamista_site_logo(); ?>
			<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon" data-hm-close>
				<span class="hm-sr-only"><?php esc_html_e( 'Close menu', 'hamista' ); ?></span>
				<?php echo hamista_theme_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
			</button>
		</div>

		<nav class="hm-nav" aria-label="<?php esc_attr_e( 'Mobile', 'hamista' ); ?>">
			<?php Navigation::menu( has_nav_menu( 'mobile' ) ? 'mobile' : 'primary', 2 ); ?>
		</nav>

		<?php if ( $hamista_cta || $hamista_account ) : ?>
			<div class="hm-stack">
				<?php if ( $hamista_cta ) : ?>
					<a class="hm-btn hm-btn--primary hm-btn--block" href="<?php echo esc_url( $hamista_cta['url'] ); ?>"><?php echo esc_html( $hamista_cta['text'] ); ?></a>
				<?php endif; ?>
				<?php if ( $hamista_account ) : ?>
					<a class="hm-btn hm-btn--secondary hm-btn--block" href="<?php echo esc_url( $hamista_account['url'] ); ?>">
						<?php echo hamista_theme_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
						<?php echo esc_html( $hamista_account['label'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
