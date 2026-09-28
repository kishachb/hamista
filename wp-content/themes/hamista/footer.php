<?php
/**
 * Site footer: the always-dark footer with the brand column, the footer menus and contact
 * details (1–4 columns), trust badges, the bottom bar and the back-to-top button. Closes <main>.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Options;

$hamista_columns = Options::number( 'footer_columns', 1, 4 );
$hamista_about   = Options::text_or( 'footer_about', __( 'A creative business studio for ambitious brands: plugins, themes, software and branding, designed and built with care.', 'hamista' ) );

$hamista_social   = [];
$hamista_networks = \Hamista\Theme\Settings\Choices::social_networks();
if ( Options::enabled( 'footer_social' ) ) {
	foreach ( (array) Options::get( 'social_links' ) as $hamista_link ) {
		$hamista_network = is_array( $hamista_link ) ? sanitize_key( (string) ( $hamista_link['network'] ?? '' ) ) : '';
		$hamista_url     = is_array( $hamista_link ) ? (string) ( $hamista_link['url'] ?? '' ) : '';
		if ( '' !== $hamista_network && '' !== $hamista_url ) {
			$hamista_social[ $hamista_network . '|' . $hamista_url ] = [ $hamista_network, $hamista_url ];
		}
	}
}

$hamista_contact = [];
if ( Options::enabled( 'footer_contact' ) ) {
	$hamista_phone = Options::text( 'contact_phone' );
	$hamista_email = sanitize_email( Options::text( 'contact_email' ) );
	if ( '' !== $hamista_phone ) {
		$hamista_contact[] = [ 'phone', '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $hamista_phone ) ) . '" dir="ltr">' . esc_html( $hamista_phone ) . '</a>' ];
	}
	if ( '' !== $hamista_email ) {
		$hamista_contact[] = [ 'mail', '<a href="mailto:' . esc_attr( antispambot( $hamista_email ) ) . '">' . esc_html( antispambot( $hamista_email ) ) . '</a>' ];
	}
	if ( '' !== Options::text( 'contact_address' ) ) {
		$hamista_contact[] = [ 'map-pin', nl2br( esc_html( Options::text( 'contact_address' ) ) ) ];
	}
	if ( '' !== Options::text( 'contact_hours' ) ) {
		$hamista_contact[] = [ 'clock', esc_html( Options::text( 'contact_hours' ) ) ];
	}
}

$hamista_year      = function_exists( 'hamista_date' ) ? hamista_date( 'Y', time() ) : wp_date( 'Y' );
$hamista_copyright = strtr(
	Options::text_or( 'footer_copyright', __( '© {year} {site}. All rights reserved.', 'hamista' ) ),
	[
		'{year}' => $hamista_year,
		'{site}' => get_bloginfo( 'name' ),
	]
);
?>
</main>

<footer class="hm-footer hm-scheme-dark">
	<div class="hm-container">
		<div class="hm-footer__grid hm-footer__grid--<?php echo (int) $hamista_columns; ?>">
			<div class="hm-footer__brand">
				<?php
				if ( Options::enabled( 'footer_show_logo' ) ) {
					hamista_site_logo();
				}
				?>
				<p><?php echo esc_html( $hamista_about ); ?></p>
				<?php if ( $hamista_social ) : ?>
					<ul class="hm-footer__social">
						<?php foreach ( $hamista_social as [ $hamista_network, $hamista_url ] ) : ?>
							<li>
								<a href="<?php echo esc_url( $hamista_url ); ?>" rel="me noopener">
									<?php
									$hamista_svg = hamista_theme_icon( 'brand-' . $hamista_network );
									echo '' !== $hamista_svg ? $hamista_svg : hamista_theme_icon( 'globe' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG.
									?>
									<span class="hm-sr-only"><?php echo esc_html( $hamista_networks[ $hamista_network ] ?? ucfirst( $hamista_network ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php
			if ( Options::enabled( 'footer_menus' ) ) :
				foreach ( [ 'footer-1', 'footer-2', 'footer-3' ] as $hamista_location ) :
					if ( ! has_nav_menu( $hamista_location ) ) {
						continue;
					}
					$hamista_menu_name = wp_get_nav_menu_name( $hamista_location );
					?>
					<nav aria-label="<?php echo esc_attr( $hamista_menu_name ); ?>">
						<h2 class="hm-footer__title"><?php echo esc_html( $hamista_menu_name ); ?></h2>
						<?php
						wp_nav_menu(
							[
								'theme_location' => $hamista_location,
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							]
						);
						?>
					</nav>
					<?php
				endforeach;
			endif;

			if ( $hamista_contact ) :
				?>
				<div>
					<h2 class="hm-footer__title"><?php esc_html_e( 'Contact', 'hamista' ); ?></h2>
					<ul class="hm-footer__contact">
						<?php foreach ( $hamista_contact as [ $hamista_icon, $hamista_html ] ) : ?>
							<li>
								<?php echo hamista_theme_icon( $hamista_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
								<span><?php echo $hamista_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<?php
		$hamista_badges = Options::text( 'footer_trust_badges' );
		if ( '' !== $hamista_badges ) :
			?>
			<div class="hm-footer__badges">
				<?php
				// Saved only by users with unfiltered_html (settings framework), like a Custom HTML widget.
				echo $hamista_badges; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		<?php endif; ?>

		<div class="hm-footer__bottom">
			<p><?php echo wp_kses_post( $hamista_copyright ); ?></p>
			<?php
			if ( Options::enabled( 'footer_bottom_menu' ) && has_nav_menu( 'footer-bottom' ) ) {
				wp_nav_menu(
					[
						'theme_location'       => 'footer-bottom',
						'container'            => 'nav',
						'container_aria_label' => wp_get_nav_menu_name( 'footer-bottom' ),
						'depth'                => 1,
						'fallback_cb'          => false,
					]
				);
			}
			?>
		</div>
	</div>
</footer>

<?php if ( Options::enabled( 'back_to_top' ) ) : ?>
	<button type="button" class="hm-btn hm-btn--secondary hm-btn--icon hm-to-top" data-hm-to-top>
		<span class="hm-sr-only"><?php esc_html_e( 'Back to top', 'hamista' ); ?></span>
		<?php echo hamista_theme_icon( 'arrow-up' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped SVG. ?>
	</button>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
