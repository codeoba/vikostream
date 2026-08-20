<?php
/**
 * VikoStream — search results.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="vk-archive">
	<div class="vk-container">
		<header class="vk-archive__head">
			<p class="vk-archive__kicker"><?php esc_html_e( 'Search results', 'vikostream' ); ?></p>
			<h1 class="vk-archive__title">“<?php echo esc_html( get_search_query() ); ?>”</h1>
			<p class="vk-archive__count">
				<?php
				global $wp_query;
				/* translators: %d: count */
				printf( esc_html( _n( '%d title imepatikana', '%d titles zimepatikana', (int) $wp_query->found_posts, 'vikostream' ) ), (int) $wp_query->found_posts );
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="vk-grid vk-grid--archive">
				<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?>
			</div>
			<nav class="vk-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'vikostream' ); ?>">
				<?php echo wp_kses_post( paginate_links( array( 'prev_text' => '‹', 'next_text' => '›' ) ) ); ?>
			</nav>
		<?php else : ?>
			<div class="vk-empty"><p><?php esc_html_e( 'Hakuna kilichopatikana. Jaribu jina jingine au IMDb ID.', 'vikostream' ); ?></p></div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
