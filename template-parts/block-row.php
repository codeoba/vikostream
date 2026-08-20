<?php
/**
 * VikoStream — Content Block Template (Normal Grid Display).
 * Displays movies, TV shows, and Asian dramas in a responsive grid layout.
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
<section class="vk-block" aria-label="<?php echo esc_attr( $block['title'] ?? '' ); ?>" style="margin-bottom:45px;">
	<div class="vk-container">
		<header class="vk-sec-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:12px;">
			<h2 class="vk-sec-title" style="margin:0; font-size:1.35rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px;">
				<span class="vk-sec-rule" style="display:inline-block; width:4px; height:20px; background:var(--accent-cyan, #00d4ff); border-radius:2px;"></span>
				<?php echo esc_html( $block['title'] ?? '' ); ?>
			</h2>
			<?php if ( $all_link && ! is_wp_error( $all_link ) ) : ?>
				<a class="vk-sec-all" href="<?php echo esc_url( $all_link ); ?>" style="color:var(--accent-cyan, #00d4ff); font-size:0.85rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:all 0.2s;">
					<?php esc_html_e( 'Ona Zote →', 'vikostream' ); ?>
				</a>
			<?php endif; ?>
		</header>

		<!-- Standard Normal Grid of Movie/Show Cards -->
		<div class="vk-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:18px;">
			<?php 
			while ( $q->have_posts() ) : 
				$q->the_post(); 
				get_template_part( 'template-parts/card' ); 
			endwhile; 
			?>
		</div>
	</div>
</section>
<?php wp_reset_postdata(); ?>
