<?php
/**
 * VikoStream — blog fallback.
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
			<p class="vk-archive__kicker"><?php esc_html_e( 'Blog', 'vikostream' ); ?></p>
			<h1 class="vk-archive__title"><?php is_home() ? esc_html_e( 'Habari & Matoleo', 'vikostream' ) : single_post_title(); ?></h1>
		</header>
		<?php if ( have_posts() ) : ?>
			<div class="vk-grid vk-grid--archive">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="vk-card" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<span class="vk-card__media"><?php the_post_thumbnail( 'medium_large' ); ?></span>
						<?php endif; ?>
						<span class="vk-card__body">
							<span class="vk-card__title"><?php the_title(); ?></span>
							<span class="vk-card__meta"><span><?php echo esc_html( get_the_date() ); ?></span></span>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<nav class="vk-pagination"><?php echo wp_kses_post( paginate_links( array( 'prev_text' => '‹', 'next_text' => '›' ) ) ); ?></nav>
		<?php else : ?>
			<div class="vk-empty"><p><?php esc_html_e( 'Hakuna posts bado.', 'vikostream' ); ?></p></div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
