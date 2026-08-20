<?php
/**
 * VikoStream — title card.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
$poster  = viko_poster_url( $post_id );
$rating  = viko_meta( $post_id, 'rating' );
$year    = viko_meta( $post_id, 'year' );
$quality = viko_meta( $post_id, 'quality', 'HD' );
$type    = viko_type_of( $post_id );
?>
<a class="vk-card vk-reveal" href="<?php the_permalink( $post_id ); ?>">
	<span class="vk-card__media">
		<?php if ( $poster ) : ?>
			<img src="<?php echo esc_url( $poster ); ?>" alt="<?php the_title_attribute( array( 'post' => $post_id ) ); ?>" loading="lazy" decoding="async">
		<?php else : ?>
			<span class="vk-card__fallback"><span><?php echo esc_html( mb_substr( get_the_title( $post_id ), 0, 2 ) ); ?></span></span>
		<?php endif; ?>
		<span class="vk-card__overlay" aria-hidden="true">
			<span class="vk-card__play">
				<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>
			</span>
		</span>
		<?php if ( $quality ) : ?>
			<span class="vk-card__quality"><?php echo esc_html( $quality ); ?></span>
		<?php endif; ?>
		<?php if ( $rating ) : ?>
			<span class="vk-card__rating">★ <?php echo esc_html( $rating ); ?></span>
		<?php endif; ?>
	</span>
	<span class="vk-card__body">
		<span class="vk-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
		<span class="vk-card__meta">
			<?php if ( $year ) : ?><span><?php echo esc_html( $year ); ?></span><?php endif; ?>
			<span class="vk-card__type vk-card__type--<?php echo esc_attr( $type ); ?>"><?php echo esc_html( viko_type_label( $type ) ); ?></span>
		</span>
	</span>
</a>
