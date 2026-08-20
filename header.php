<?php
/**
 * VikoStream — site header.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$genres_top = get_terms( array( 'taxonomy' => 'viko_genre', 'hide_empty' => true, 'number' => 14 ) );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#0e1420">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="vk-skip" href="#main"><?php esc_html_e( 'Skip to content', 'vikostream' ); ?></a>

<header id="vk-header" class="vk-header">
	<div class="vk-container vk-header__inner">
		<a class="vk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg viewBox="0 0 36 36" width="34" height="34" aria-hidden="true">
				<rect x="1.5" y="1.5" width="33" height="33" fill="none" stroke="#f5c518" stroke-width="2.5"/>
				<path d="M13 11v14l13-7z" fill="#f5c518"/>
				<path d="M1.5 1.5h6M1.5 1.5v6M34.5 34.5h-6M34.5 34.5v-6" stroke="#e63946" stroke-width="2.5"/>
			</svg>
			<span class="vk-logo__text">VIKO<em>STREAM</em></span>
		</a>

		<nav class="vk-nav" aria-label="<?php esc_attr_e( 'Primary', 'vikostream' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'vk-nav__menu', 'fallback_cb' => false ) );
			} else {
				$type_links = array(
					home_url( '/' )                      => __( 'Home', 'vikostream' ),
					get_term_link( 'movie', 'viko_type' )       => __( 'Movies', 'vikostream' ),
					get_term_link( 'tvshow', 'viko_type' )      => __( 'TV Shows', 'vikostream' ),
					get_term_link( 'asian-drama', 'viko_type' ) => __( 'Asian Drama', 'vikostream' ),
				);
				echo '<ul class="vk-nav__menu">';
				foreach ( $type_links as $url => $label ) {
					if ( is_wp_error( $url ) ) {
						continue;
					}
					echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
				}
				if ( $genres_top && ! is_wp_error( $genres_top ) ) {
					echo '<li class="vk-nav__drop"><a href="#">' . esc_html__( 'Genres', 'vikostream' ) . ' ▾</a><ul class="vk-drop">';
					foreach ( $genres_top as $g ) {
						echo '<li><a href="' . esc_url( get_term_link( $g ) ) . '">' . esc_html( $g->name ) . '</a></li>';
					}
					echo '</ul></li>';
				}
				echo '<li><a href="' . esc_url( get_post_type_archive_link( 'viko_title' ) ) . '">' . esc_html__( 'A–Z', 'vikostream' ) . '</a></li>';
				echo '</ul>';
			}
			?>
		</nav>

		<div class="vk-header__tools">
			<form role="search" method="get" class="vk-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" id="vk-search-input" name="s" placeholder="<?php esc_attr_e( 'Tafuta movie, drama…', 'vikostream' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off">
				<button type="submit" aria-label="<?php esc_attr_e( 'Search', 'vikostream' ); ?>">
					<svg viewBox="0 0 24 24" width="17" height="17" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.8-3.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>
				<div id="vk-suggest" class="vk-suggest" hidden></div>
			</form>
			<button id="vk-burger" class="vk-burger" type="button" aria-label="<?php esc_attr_e( 'Menu', 'vikostream' ); ?>" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>

	<div class="vk-ticker" aria-hidden="true">
		<div class="vk-ticker__track" id="vk-ticker"></div>
	</div>
</header>

<div id="vk-mobile" class="vk-mobile" aria-hidden="true">
	<nav aria-label="<?php esc_attr_e( 'Mobile', 'vikostream' ); ?>">
		<?php
		$mtypes = array( 'movie', 'tvshow', 'asian-drama' );
		echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'vikostream' ) . '</a>';
		foreach ( $mtypes as $mt ) {
			$link = get_term_link( $mt, 'viko_type' );
			if ( ! is_wp_error( $link ) ) {
				echo '<a href="' . esc_url( $link ) . '">' . esc_html( viko_type_label( $mt ) ) . '</a>';
			}
		}
		?>
	</nav>
</div>

<main id="main">
