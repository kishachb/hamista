<?php
/**
 * The `notifications` My Account endpoint: paginated list + mark all as read.
 *
 * Theme-overridable at {theme}/hamista-customer-dashboard/myaccount/notifications.php.
 *
 * @package Hamista\Dashboard
 *
 * @var array<int, array<string, mixed>> $args ['items'], ['page'], ['pages'].
 */

use Hamista\Dashboard\Support\Notification_Link;
use Hamista\Dashboard\Support\Notifications_Endpoint;

defined( 'ABSPATH' ) || exit;

$hm_items = $args['items'];
$hm_page  = (int) $args['page'];
$hm_pages = (int) $args['pages'];
?>
<div class="hm-dashboard-notifications-page">

	<?php if ( [] !== $hm_items ) : ?>
		<form class="hm-dashboard-notifications-page__mark-all" method="post" data-hm-busy>
			<input type="hidden" name="hamista_action" value="notifications_mark_all_read" />
			<?php wp_nonce_field( Notifications_Endpoint::MARK_ALL_NONCE, '_hamista_nonce' ); ?>
			<button type="submit" class="hm-btn hm-btn--secondary hm-btn--sm">
				<?php echo hamista_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?>
				<?php esc_html_e( 'Mark all as read', 'hamista-customer-dashboard' ); ?>
			</button>
		</form>
	<?php endif; ?>

	<?php if ( [] === $hm_items ) : ?>
		<div class="hm-empty">
			<span class="hm-empty__icon"><?php echo hamista_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped, safe SVG. ?></span>
			<p class="hm-empty__title"><?php esc_html_e( 'No notifications yet', 'hamista-customer-dashboard' ); ?></p>
			<p class="hm-empty__text"><?php esc_html_e( 'When something needs your attention, it will show up here.', 'hamista-customer-dashboard' ); ?></p>
		</div>
	<?php else : ?>
		<ul class="hm-dashboard-notifications-page__list">
			<?php foreach ( $hm_items as $hm_item ) : ?>
				<?php
				$hm_id     = (int) $hm_item['id'];
				$hm_unread = empty( $hm_item['is_read'] );
				$hm_url    = '' !== (string) $hm_item['link'] ? Notification_Link::url( $hm_id ) : '';
				?>
				<li class="hm-dashboard-notifications-page__item<?php echo $hm_unread ? ' is-unread' : ''; ?>">
					<?php if ( '' !== $hm_url ) : ?><a class="hm-dashboard-notifications-page__link" href="<?php echo esc_url( $hm_url ); ?>"><?php endif; ?>
						<span class="hm-dashboard-notifications-page__title"><?php echo esc_html( (string) $hm_item['title'] ); ?></span>
						<?php if ( '' !== (string) $hm_item['message'] ) : ?>
							<span class="hm-dashboard-notifications-page__message"><?php echo wp_kses( (string) $hm_item['message'], [ 'a' => [ 'href' => true ], 'strong' => [], 'em' => [], 'b' => [], 'br' => [], 'code' => [] ] ); ?></span>
						<?php endif; ?>
						<time class="hm-dashboard-notifications-page__date" datetime="<?php echo esc_attr( (string) $hm_item['created_at'] ); ?>">
							<?php echo esc_html( function_exists( 'hamista_date' ) ? hamista_date( 'j F Y، H:i', (string) $hm_item['created_at'] ) : (string) $hm_item['created_at'] ); ?>
						</time>
					<?php if ( '' !== $hm_url ) : ?></a><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $hm_pages > 1 ) : ?>
			<nav class="hm-pagination" aria-label="<?php esc_attr_e( 'Notifications pages', 'hamista-customer-dashboard' ); ?>">
				<?php for ( $hm_p = 1; $hm_p <= $hm_pages; $hm_p++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'notif-page', $hm_p ) ); ?>"<?php echo $hm_p === $hm_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $hm_p ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

</div>
