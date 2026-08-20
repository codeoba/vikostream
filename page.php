<?php
/**
 * VikoStream — static page.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<article class="vk-page">
		<div class="vk-container">
			<h1 class="vk-archive__title"><?php the_title(); ?></h1>
			<div class="vk-page__content"><?php the_content(); ?></div>
		</div>
	</article>
	<?php
endwhile;
get_footer();
