<?php
/**
 * Fallback template: the post loop as a card grid with pagination.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_search() ) {
	$hamista_eyebrow = __( 'Search', 'hamista' );
	/* translators: %s: search query. */
	$hamista_title = sprintf( __( 'Results for “%s”', 'hamista' ), get_search_query( false ) );
} elseif ( is_archive() ) {
	$hamista_eyebrow = __( 'Archive', 'hamista' );
	$hamista_title   = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_home() && ! is_front_page() ) {
	$hamista_eyebrow = __( 'Journal', 'hamista' );
	$hamista_title   = single_post_title( '', false );
} else {
	$hamista_eyebrow = __( 'Journal', 'hamista' );
	$hamista_title   = __( 'Latest articles', 'hamista' );
}
$hamista_description = is_archive() ? wp_strip_all_tags( get_the_archive_description() ) : '';
?>
<div class="hm-section">
	<div class="hm-container">
		<header class="hm-section-head">
			<p class="hm-section-head__eyebrow"><?php echo esc_html( $hamista_eyebrow ); ?></p>
			<h1 class="hm-section-head__title"><?php echo esc_html( $hamista_title ); ?></h1>
			<?php if ( '' !== $hamista_description ) : ?>
				<p class="hm-section-head__subtitle"><?php echo esc_html( $hamista_description ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="hm-grid hm-grid--3">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/content/card' );
				}
				?>
			</div>
			<?php
			the_posts_pagination(
				[
					'class'              => 'hm-pagination',
					'mid_size'           => 1,
					'prev_text'          => hamista_theme_icon( 'arrow-left' ) . '<span class="hm-sr-only">' . esc_html__( 'Previous page', 'hamista' ) . '</span>',
					'next_text'          => '<span class="hm-sr-only">' . esc_html__( 'Next page', 'hamista' ) . '</span>' . hamista_theme_icon( 'arrow-right' ),
					'before_page_number' => '<span class="hm-sr-only">' . esc_html__( 'Page', 'hamista' ) . ' </span>',
				]
			);
			?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/none' ); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
