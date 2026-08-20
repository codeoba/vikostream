<?php
/**
 * VikoStream — content block (row scroll or grid).
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block = isset( $args['block'] ) ? $args['block'] : array();
$style = $block['style'] ?? 'row';
$q     = new WP_Query( viko_block_query( $block ) );

if ( ! $q->have_posts() ) {
	return;
}

$all_link = '';
$rule     = $block['rule'] ?? '';
if ( strpos( $rule, 'type:' ) === 0 ) {
	$term = get_term_by( 'slug', substr( $rule, 5 ), 'viko_type' );
	if ( $term ) {
		$all_link = get_term_link( $term );
	}
} elseif ( strpos( $rule, 'genre:' ) === 0 ) {
	$term = get_term_by( 'slug', substr( $rule, 6 ), 'viko_genre' );
	if ( $term ) {
		$all_link = get_term_link( $term );
	}
} elseif ( 'recommended' === $rule ) {
	$all_link = get_post_type_archive_link( 'viko_title' ) . '?sort=rating';
}
?>
<section class="vk-block" aria-label="<?php echo esc_attr( $block['title'] ?? '' ); ?>">
	<div class="vk-container">
		<header class="vk-sec-head">
			<h2 class="vk-sec-title"><span class="vk-sec-rule"></span><?php echo esc_html( $block['title'] ?? '' ); ?></h2>
			<?php if ( 'row' === $style ) : ?>
				<div class="vk-sec-tools">
					<button class="vk-sec-arrow vk-sec-arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Scroll back', 'vikostream' ); ?>">‹</button>
					<button class="vk-sec-arrow vk-sec-arrow--next" type="button" aria-label="<?php esc_attr_e( 'Scroll forward', 'vikostream' ); ?>">›</button>
					<?php if ( $all_link && ! is_wp_error( $all_link ) ) : ?>
						<a class="vk-sec-all" href="<?php echo esc_url( $all_link ); ?>"><?php esc_html_e( 'Ona zote →', 'vikostream' ); ?></a>
					<?php endif; ?>
				</div>
			<?php elseif ( $all_link && ! is_wp_error( $all_link ) ) : ?>
				<a class="vk-sec-all" href="<?php echo esc_url( $all_link ); ?>"><?php esc_html_e( 'Ona zote →', 'vikostream' ); ?></a>
			<?php endif; ?>
		</header>

		<?php if ( 'row' === $style ) : ?>
			<div class="vk-row">
				<?php while ( $q->have_posts() ) : $q->the_post(); get_template_part( 'template-parts/card' ); endwhile; ?>
			</div>
		<?php else : ?>
			<div class="vk-grid">
				<?php while ( $q->have_posts() ) : $q->the_post(); get_template_part( 'template-parts/card' ); endwhile; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php wp_reset_postdata(); ?>
