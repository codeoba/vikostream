<?php
/**
 * VikoStream — site footer.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="vk-footer">
	<div class="vk-container vk-footer__grid">
		<div>
			<a class="vk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg viewBox="0 0 36 36" width="30" height="30" aria-hidden="true">
					<rect x="1.5" y="1.5" width="33" height="33" fill="none" stroke="#f5c518" stroke-width="2.5"/>
					<path d="M13 11v14l13-7z" fill="#f5c518"/>
					<path d="M1.5 1.5h6M1.5 1.5v6M34.5 34.5h-6M34.5 34.5v-6" stroke="#e63946" stroke-width="2.5"/>
				</svg>
				<span class="vk-logo__text">VIKO<em>STREAM</em></span>
			</a>
			<p class="vk-footer__about">
				<?php esc_html_e( 'Movies, TV shows na Asian dramas — popote, wakati wowote. Powered by VikoStream theme.', 'vikostream' ); ?>
			</p>
		</div>
		<div class="vk-footer__col">
			<h3><?php esc_html_e( 'Browse', 'vikostream' ); ?></h3>
			<ul>
				<?php
				foreach ( array( 'movie', 'tvshow', 'asian-drama' ) as $t ) {
					$link = get_term_link( $t, 'viko_type' );
					if ( ! is_wp_error( $link ) ) {
						echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( viko_type_label( $t ) ) . '</a></li>';
					}
				}
				?>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'viko_title' ) ); ?>"><?php esc_html_e( 'A–Z Index', 'vikostream' ); ?></a></li>
			</ul>
		</div>
		<div class="vk-footer__col">
			<h3><?php esc_html_e( 'Top Genres', 'vikostream' ); ?></h3>
			<ul>
				<?php
				$fg = get_terms( array( 'taxonomy' => 'viko_genre', 'hide_empty' => true, 'number' => 6 ) );
				if ( $fg && ! is_wp_error( $fg ) ) {
					foreach ( $fg as $g ) {
						echo '<li><a href="' . esc_url( get_term_link( $g ) ) . '">' . esc_html( $g->name ) . '</a></li>';
					}
				}
				?>
			</ul>
		</div>
		<div class="vk-footer__col">
			<h3><?php esc_html_e( 'Info', 'vikostream' ); ?></h3>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'vikostream' ); ?></a></li>
				<?php
				wp_list_pages( array( 'title_li' => '', 'depth' => 1, 'number' => 4 ) );
				?>
			</ul>
		</div>
	</div>
	<div class="vk-container vk-footer__bar">
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> — VikoStream v<?php echo esc_html( VIKO_VERSION ); ?></p>
		<p class="vk-footer__legal"><?php esc_html_e( 'VikoStream haitunzi video yoyote — players ni embeds za third-party.', 'vikostream' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
