<?php
/**
 * VikoStream — hero slider block.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block = isset( $args['block'] ) ? $args['block'] : array();
$q     = new WP_Query( viko_block_query( $block ) );

if ( ! $q->have_posts() ) {
	return;
}
?>
<section class="vk-slider" aria-label="<?php esc_attr_e( 'Featured titles', 'vikostream' ); ?>" data-autoplay="6000">
	<div class="vk-slider__viewport">
		<?php
		$idx = 0;
		while ( $q->have_posts() ) :
			$q->the_post();
			$pid      = get_the_ID();
			$backdrop = viko_backdrop_url( $pid );
			$idx++;
			?>
			<article class="vk-slide<?php echo 1 === $idx ? ' vk-slide--active' : ''; ?>" style="background-image:url('<?php echo esc_url( $backdrop ); ?>')">
				<div class="vk-slide__shade" aria-hidden="true"></div>
				<div class="vk-container vk-slide__content">
					<p class="vk-slide__kicker">
						<span class="vk-slide__badge"><?php echo esc_html( viko_type_label( viko_type_of( $pid ) ) ); ?></span>
						<?php if ( viko_meta( $pid, 'year' ) ) : ?><span><?php echo esc_html( viko_meta( $pid, 'year' ) ); ?></span><?php endif; ?>
						<?php if ( viko_meta( $pid, 'rating' ) ) : ?><span class="vk-slide__rating">★ <?php echo esc_html( viko_meta( $pid, 'rating' ) ); ?></span><?php endif; ?>
						<?php if ( viko_meta( $pid, 'runtime' ) ) : ?><span><?php echo esc_html( viko_meta( $pid, 'runtime' ) ); ?> min</span><?php endif; ?>
					</p>
					<h2 class="vk-slide__title"><?php the_title(); ?></h2>
					<p class="vk-slide__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt() ? get_the_excerpt() : get_the_content(), 26 ) ); ?></p>
					<div class="vk-slide__cta">
						<a class="vk-btn vk-btn--gold" href="<?php the_permalink(); ?>">
							<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>
							<?php esc_html_e( 'Tazama Sasa', 'vikostream' ); ?>
						</a>
						<a class="vk-btn vk-btn--ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Maelezo', 'vikostream' ); ?></a>
					</div>
				</div>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<button class="vk-slider__arrow vk-slider__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous', 'vikostream' ); ?>">‹</button>
	<button class="vk-slider__arrow vk-slider__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Next', 'vikostream' ); ?>">›</button>

	<div class="vk-slider__dots" role="tablist" aria-label="<?php esc_attr_e( 'Slides', 'vikostream' ); ?>">
		<?php for ( $i = 0; $i < $idx; $i++ ) : ?>
			<button class="vk-slider__dot<?php echo 0 === $i ? ' vk-slider__dot--active' : ''; ?>" type="button" role="tab" aria-label="Slide <?php echo esc_attr( $i + 1 ); ?>"></button>
		<?php endfor; ?>
	</div>
</section>
