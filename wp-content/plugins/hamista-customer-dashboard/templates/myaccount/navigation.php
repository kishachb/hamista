<?php
/**
 * Grouped My Account navigation (replaces WooCommerce's myaccount/navigation.php).
 *
 * Theme-overridable at {theme}/hamista-customer-dashboard/myaccount/navigation.php.
 *
 * @package Hamista\Dashboard
 */

use Hamista\Dashboard\Support\Navigation;

defined( 'ABSPATH' ) || exit;

$hm_groups = Navigation::groups();
$hm_logout = Navigation::logout_item();

do_action( 'woocommerce_before_account_navigation' );
?>
<nav class="hm-account-nav" aria-label="<?php esc_attr_e( 'Account pages', 'hamista-customer-dashboard' ); ?>" data-hm-account-nav>
	<?php foreach ( $hm_groups as $hm_group => $hm_items ) : ?>
		<?php if ( [] === $hm_items ) : ?>
			<?php continue; ?>
		<?php endif; ?>
		<div class="hm-account-nav__group">
			<p class="hm-account-nav__group-title"><?php echo esc_html( Navigation::group_label( $hm_group ) ); ?></p>
			<ul class="hm-account-nav__list">
				<?php foreach ( $hm_items as $hm_item ) : ?>
					<li class="hm-account-nav__item<?php echo $hm_item['current'] ? ' is-current' : ''; ?>">
						<a class="hm-account-nav__link" href="<?php echo esc_url( $hm_item['url'] ); ?>"<?php echo $hm_item['current'] ? ' aria-current="page"' : ''; ?>>
							<?php if ( '' !== $hm_item['icon'] && function_exists( 'hamista_icon' ) ) : ?>
								<?php echo hamista_icon( $hm_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hamista_icon() returns escaped, safe SVG. ?>
							<?php endif; ?>
							<span class="hm-account-nav__label"><?php echo esc_html( $hm_item['label'] ); ?></span>
							<?php if ( $hm_item['badge'] > 0 ) : ?>
								<span class="hm-badge hm-badge--brand hm-account-nav__badge" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: unread count. */ _n( '%d unread', '%d unread', $hm_item['badge'], 'hamista-customer-dashboard' ), $hm_item['badge'] ) ); ?>">
									<?php echo esc_html( (string) $hm_item['badge'] ); ?>
								</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>

	<?php if ( null !== $hm_logout ) : ?>
		<div class="hm-account-nav__group hm-account-nav__group--logout">
			<a class="hm-account-nav__link hm-account-nav__logout" href="<?php echo esc_url( $hm_logout['url'] ); ?>">
				<?php if ( function_exists( 'hamista_icon' ) ) : ?>
					<?php echo hamista_icon( 'log-out' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hamista_icon() returns escaped, safe SVG. ?>
				<?php endif; ?>
				<span class="hm-account-nav__label"><?php echo esc_html( $hm_logout['label'] ); ?></span>
			</a>
		</div>
	<?php endif; ?>
</nav>
<?php
do_action( 'woocommerce_after_account_navigation' );
