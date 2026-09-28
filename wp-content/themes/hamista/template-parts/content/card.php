<?php
/**
 * Post card for loops: the whole card is one link (the stretched title link).
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Options;

$hamista_category = Options::enabled( 'blog_show_categories' ) ? get_the_category() : [];
$hamista_category = $hamista_category ? $hamista_category[0] : null;
$hamista_words    = Options::number( 'blog_excerpt_length', 10, 80 );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'hm-card hm-card--hover hm-reveal' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="hm-card__media">
			<?php
			the_post_thumbnail(
				'hamista-card',
				[
					'alt'     => '',
					'loading' => 'lazy',
				]
			);
			?>
		</div>
	<?php endif; ?>

	<div class="hm-card__body">
		<?php if ( $hamista_category || Options::enabled( 'blog_show_date' ) ) : ?>
			<div class="hm-cluster">
				<?php if ( $hamista_category ) : ?>
					<a class="hm-badge hm-badge--neutral" href="<?php echo esc_url( get_category_link( $hamista_category ) ); ?>"><?php echo esc_html( $hamista_category->name ); ?></a>
				<?php endif; ?>
				<?php if ( Options::enabled( 'blog_show_date' ) ) : ?>
					<small class="hm-card__text"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></small>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<h2 class="hm-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<?php if ( has_excerpt() || get_the_content() ) : ?>
			<p class="hm-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $hamista_words ) ); ?></p>
		<?php endif; ?>
	</div>
</article>
