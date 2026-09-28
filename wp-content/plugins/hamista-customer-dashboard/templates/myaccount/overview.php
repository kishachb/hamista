<?php
/**
 * My Account overview (replaces WooCommerce's myaccount/dashboard.php).
 *
 * Theme-overridable at {theme}/hamista-customer-dashboard/myaccount/overview.php.
 *
 * @package Hamista\Dashboard
 */

use Hamista\Dashboard\Support\Overview;

defined( 'ABSPATH' ) || exit;

$hm_user = isset( $current_user ) && $current_user instanceof WP_User ? $current_user : wp_get_current_user();
$hm_user_id = $hm_user->ID;

$hm_welcome        = Overview::welcome( $hm_user );
$hm_quick_actions  = Overview::quick_actions();
$hm_stats          = Overview::stat_cards( $hm_user_id );
$hm_updates        = (bool) hamista_get_option( 'hamista_dashboard', 'show_updates_card', true ) ? Overview::updates( $hm_user_id ) : [];
$hm_orders         = (bool) hamista_get_option( 'hamista_dashboard', 'show_recent_orders', true ) ? Overview::recent_orders( $hm_user_id, 3 ) : [];
$hm_notifications  = (bool) hamista_get_option( 'hamista_dashboard', 'show_recent_notifications', true ) ? Overview::recent_notifications( $hm_user_id, 5 ) : [];
$hm_panels         = Overview::panels( $hm_user_id );

do_action( 'woocommerce_before_account_navigation' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- parity keeper, no-op without the navigation template.
?>
<div class="hm-dashboard-overview">

	<div class="hm-dashboard-welcome hm-card">
		<div class="hm-dashboard-welcome__avatar"><?php echo wp_kses( $hm_welcome['avatar'], [ 'img' => [ 'src' => true, 'srcset' => true, 'class' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true, 'decoding' => true ] ] ); ?></div>
		<div class="hm-dashboard-welcome__body">
			<h2 class="hm-dashboard-welcome__name">
				<?php
				printf(
					/* translators: %s: customer display name. */
					esc_html__( 'Hi, %s', 'hamista-customer-dashboard' ),
					esc_html( $hm_welcome['name'] )
				);
				?>
			</h2>
			<?php if ( '' !== $hm_welcome['member_since'] ) : ?>
				<p class="hm-dashboard-welcome__meta">
					<?php
					printf(
						/* translators: %s: a formatted date. */
						esc_html__( 'Member since %s', 'hamista-customer-dashboard' ),
						esc_html( $hm_welcome['member_since'] )
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( '' !== $hm_welcome['welcome_text'] ) : ?>
				<p class="hm-dashboard-welcome__text"><?php echo wp_kses_post( $hm_welcome['welcome_text'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( [] !== $hm_quick_actions ) : ?>
			<div class="hm-dashboard-welcome__actions">
				<?php foreach ( $hm_quick_actions as $hm_action ) : ?>
					<a class="hm-btn hm-btn--secondary hm-btn--sm" href="<?php echo esc_url( $hm_action['url'] ); ?>">
						<?php if ( '' !== $hm_action['icon'] ) : ?>
							<?php echo hamista_icon( $hm_action['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?>
						<?php endif; ?>
						<?php echo esc_html( $hm_action['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( [] !== $hm_updates ) : ?>
		<div class="hm-alert hm-alert--info hm-dashboard-updates">
			<?php echo hamista_icon( 'refresh-cw' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?>
			<div>
				<p><strong><?php esc_html_e( 'Updates available', 'hamista-customer-dashboard' ); ?></strong></p>
				<ul class="hm-dashboard-updates__list">
					<?php foreach ( $hm_updates as $hm_update ) : ?>
						<li>
							<?php
							printf(
								/* translators: 1: product name, 2: current version, 3: new version. */
								esc_html__( '%1$s: %2$s → %3$s', 'hamista-customer-dashboard' ),
								esc_html( (string) ( $hm_update['product_name'] ?? '' ) ),
								esc_html( (string) ( $hm_update['current_version'] ?? '' ) ),
								esc_html( (string) ( $hm_update['new_version'] ?? '' ) )
							);
							?>
							<?php if ( ! empty( $hm_update['url'] ) ) : ?>
								&mdash; <a href="<?php echo esc_url( $hm_update['url'] ); ?>"><?php esc_html_e( 'View', 'hamista-customer-dashboard' ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( [] !== $hm_stats ) : ?>
		<div class="hm-grid hm-grid--4 hm-dashboard-stats">
			<?php foreach ( $hm_stats as $hm_stat ) : ?>
				<?php $hm_tag = '' !== $hm_stat['link'] ? 'a' : 'div'; ?>
				<<?php echo esc_html( $hm_tag ); ?> class="hm-stat" <?php echo '' !== $hm_stat['link'] ? 'href="' . esc_url( $hm_stat['link'] ) . '"' : ''; ?>>
					<span class="hm-stat__icon"><?php echo hamista_icon( '' !== $hm_stat['icon'] ? $hm_stat['icon'] : 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?></span>
					<span class="hm-stat__value"><?php echo esc_html( $hm_stat['value'] ); ?></span>
					<span class="hm-stat__label"><?php echo esc_html( $hm_stat['label'] ); ?></span>
				</<?php echo esc_html( $hm_tag ); ?>>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="hm-grid hm-grid--2 hm-dashboard-panels">

		<div class="hm-card hm-dashboard-orders">
			<div class="hm-card__body">
				<h3 class="hm-card__title"><?php esc_html_e( 'Recent orders', 'hamista-customer-dashboard' ); ?></h3>
				<?php if ( [] !== $hm_orders ) : ?>
					<div class="hm-table-wrap">
						<table class="hm-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Order', 'hamista-customer-dashboard' ); ?></th>
									<th><?php esc_html_e( 'Date', 'hamista-customer-dashboard' ); ?></th>
									<th><?php esc_html_e( 'Status', 'hamista-customer-dashboard' ); ?></th>
									<th><?php esc_html_e( 'Total', 'hamista-customer-dashboard' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $hm_orders as $hm_order ) : ?>
									<tr>
										<td data-label="<?php esc_attr_e( 'Order', 'hamista-customer-dashboard' ); ?>">
											<a href="<?php echo esc_url( $hm_order->get_view_order_url() ); ?>">#<?php echo esc_html( $hm_order->get_order_number() ); ?></a>
										</td>
										<td data-label="<?php esc_attr_e( 'Date', 'hamista-customer-dashboard' ); ?>"><?php echo esc_html( wc_format_datetime( $hm_order->get_date_created() ) ); ?></td>
										<td data-label="<?php esc_attr_e( 'Status', 'hamista-customer-dashboard' ); ?>">
											<span class="hm-badge hm-badge--<?php echo esc_attr( Overview::status_badge( $hm_order->get_status() ) ); ?>"><?php echo esc_html( wc_get_order_status_name( $hm_order->get_status() ) ); ?></span>
										</td>
										<td data-label="<?php esc_attr_e( 'Total', 'hamista-customer-dashboard' ); ?>"><?php echo wp_kses_post( $hm_order->get_formatted_order_total() ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="hm-empty">
						<span class="hm-empty__icon"><?php echo hamista_icon( 'shopping-bag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?></span>
						<p class="hm-empty__title"><?php esc_html_e( 'No orders yet', 'hamista-customer-dashboard' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="hm-card hm-dashboard-notifications">
			<div class="hm-card__body">
				<h3 class="hm-card__title"><?php esc_html_e( 'Recent notifications', 'hamista-customer-dashboard' ); ?></h3>
				<?php if ( [] !== $hm_notifications ) : ?>
					<ul class="hm-dashboard-notifications__list">
						<?php foreach ( $hm_notifications as $hm_notification ) : ?>
							<li class="hm-dashboard-notifications__item<?php echo empty( $hm_notification['is_read'] ) ? ' is-unread' : ''; ?>">
								<span class="hm-dashboard-notifications__title"><?php echo esc_html( (string) $hm_notification['title'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( function_exists( 'hamista_dashboard_notifications_url' ) ) : ?>
						<a class="hm-btn hm-btn--link hm-btn--sm" href="<?php echo esc_url( hamista_dashboard_notifications_url() ); ?>"><?php esc_html_e( 'View all notifications', 'hamista-customer-dashboard' ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					<div class="hm-empty">
						<span class="hm-empty__icon"><?php echo hamista_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?></span>
						<p class="hm-empty__title"><?php esc_html_e( 'No notifications yet', 'hamista-customer-dashboard' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

	</div>

	<?php foreach ( $hm_panels as $hm_panel ) : ?>
		<div class="hm-card hm-dashboard-panel"<?php echo ! empty( $hm_panel['id'] ) ? ' id="hm-panel-' . esc_attr( (string) $hm_panel['id'] ) . '"' : ''; ?>>
			<div class="hm-card__body">
				<?php if ( ! empty( $hm_panel['title'] ) ) : ?>
					<h3 class="hm-card__title"><?php echo esc_html( (string) $hm_panel['title'] ); ?></h3>
				<?php endif; ?>
				<?php echo wp_kses_post( (string) ( $hm_panel['html'] ?? '' ) ); ?>
			</div>
		</div>
	<?php endforeach; ?>

</div>
