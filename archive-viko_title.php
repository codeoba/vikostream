<?php
/**
 * VikoStream — titles archive / type / genre templates.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_type  = isset( $_GET['type'] ) ? sanitize_title( wp_unslash( $_GET['type'] ) ) : '';
$current_genre = isset( $_GET['genre'] ) ? sanitize_title( wp_unslash( $_GET['genre'] ) ) : '';
$current_sort  = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : '';
$current_az    = get_query_var( 'viko_az' );

if ( is_tax( 'viko_type' ) ) {
	$current_type = get_queried_object()->slug;
}
if ( is_tax( 'viko_genre' ) ) {
	$current_genre = get_queried_object()->slug;
}

$heading = is_tax() ? single_term_title( '', false ) : __( 'Titles Zote', 'vikostream' );
?>
<section class="vk-archive">
	<div class="vk-container">
		<header class="vk-archive__head">
			<p class="vk-archive__kicker"><?php esc_html_e( 'Library', 'vikostream' ); ?></p>
			<h1 class="vk-archive__title"><?php echo esc_html( $heading ); ?></h1>
		</header>

		<nav class="vk-alpha__bar vk-alpha__bar--archive" aria-label="<?php esc_attr_e( 'Alphabet', 'vikostream' ); ?>">
			<a class="vk-alpha__letter <?php echo $current_az ? '' : 'vk-alpha__letter--active'; ?>" href="<?php echo esc_url( remove_query_arg( 'az' ) ); ?>"><?php esc_html_e( 'ZOTE', 'vikostream' ); ?></a>
			<?php foreach ( range( 'A', 'Z' ) as $L ) : ?>
				<a class="vk-alpha__letter <?php echo $current_az === $L ? 'vk-alpha__letter--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'az', $L ) ); ?>"><?php echo esc_html( $L ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form class="vk-filters" method="get" action="">
			<select name="type" aria-label="<?php esc_attr_e( 'Type', 'vikostream' ); ?>">
				<option value=""><?php esc_html_e( 'Aina zote', 'vikostream' ); ?></option>
				<?php foreach ( array( 'movie', 'tvshow', 'asian-drama' ) as $t ) : ?>
					<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $current_type, $t ); ?>><?php echo esc_html( viko_type_label( $t ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="genre" aria-label="<?php esc_attr_e( 'Genre', 'vikostream' ); ?>">
				<option value=""><?php esc_html_e( 'Genres zote', 'vikostream' ); ?></option>
				<?php
				$genres = get_terms( array( 'taxonomy' => 'viko_genre', 'hide_empty' => false ) );
				if ( $genres && ! is_wp_error( $genres ) ) {
					foreach ( $genres as $g ) {
						echo '<option value="' . esc_attr( $g->slug ) . '" ' . selected( $current_genre, $g->slug, false ) . '>' . esc_html( $g->name ) . '</option>';
					}
				}
				?>
			</select>
			<select name="sort" aria-label="<?php esc_attr_e( 'Sort', 'vikostream' ); ?>">
				<option value="" <?php selected( $current_sort, '' ); ?>><?php esc_html_e( 'Latest', 'vikostream' ); ?></option>
				<option value="rating" <?php selected( $current_sort, 'rating' ); ?>><?php esc_html_e( 'Top rated', 'vikostream' ); ?></option>
				<option value="year" <?php selected( $current_sort, 'year' ); ?>><?php esc_html_e( 'Newest year', 'vikostream' ); ?></option>
				<option value="az" <?php selected( $current_sort, 'az' ); ?>><?php esc_html_e( 'A → Z', 'vikostream' ); ?></option>
			</select>
			<button type="submit" class="vk-btn vk-btn--gold vk-btn--sm"><?php esc_html_e( 'Chuja', 'vikostream' ); ?></button>
		</form>

		<?php if ( have_posts() ) : ?>
			<div class="vk-grid vk-grid--archive">
				<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?>
			</div>
			<nav class="vk-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'vikostream' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'prev_text' => '‹',
							'next_text' => '›',
							'add_args'  => array_filter(
								array(
									'type'  => $current_type,
									'genre' => $current_genre,
									'sort'  => $current_sort,
									'az'    => $current_az,
								)
							),
						)
					)
				);
				?>
			</nav>
		<?php else : ?>
			<div class="vk-empty">
				<p><?php esc_html_e( 'Hakuna titles hapa bado.', 'vikostream' ); ?></p>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="vk-btn vk-btn--gold" href="<?php echo esc_url( admin_url( 'edit.php?post_type=viko_title&page=viko-import' ) ); ?>"><?php esc_html_e( '⇩ Import sasa', 'vikostream' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
