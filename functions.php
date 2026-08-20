<?php
/**
 * VikoStream — theme core.
 * CPT `viko_title`, taxonomies, enqueues, query filters, live search.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VIKO_VERSION', '1.1.1' );
define( 'VIKO_DIR', get_template_directory() );
define( 'VIKO_URI', get_template_directory_uri() );

require_once VIKO_DIR . '/inc/players.php';
require_once VIKO_DIR . '/inc/importer.php';
require_once VIKO_DIR . '/inc/metabox.php';
require_once VIKO_DIR . '/inc/settings.php';
require_once VIKO_DIR . '/inc/blocks-admin.php';

/* ------------------------------------------------------------------ */
/* theme setup                                                         */
/* ------------------------------------------------------------------ */

function viko_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption' ) );
	add_image_size( 'viko-poster', 400, 600, true );
	add_image_size( 'viko-backdrop', 1600, 900, true );
	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'vikostream' ),
			'footer'  => __( 'Footer menu', 'vikostream' ),
		)
	);
}
add_action( 'after_setup_theme', 'viko_setup' );

/* ------------------------------------------------------------------ */
/* post type + taxonomies                                              */
/* ------------------------------------------------------------------ */

function viko_register_post_type() {
	register_post_type(
		'viko_title',
		array(
			'labels'       => array(
				'name'          => __( 'Titles', 'vikostream' ),
				'singular_name' => __( 'Title', 'vikostream' ),
				'add_new_item'  => __( 'Add Movie / Show / Drama', 'vikostream' ),
				'search_items'  => __( 'Search titles', 'vikostream' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'watch' ),
			'menu_icon'    => 'dashicons-format-video',
			'menu_position'=> 5,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			/* Classic editor on purpose: the core checkbox boxes for the
			   type/genre taxonomies save reliably there (no REST round-trip). */
			'show_in_rest' => false,
		)
	);

	register_taxonomy(
		'viko_type',
		'viko_title',
		array(
			'labels'            => array(
				'name'          => __( 'Types', 'vikostream' ),
				'singular_name' => __( 'Type', 'vikostream' ),
			),
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'browse' ),
			'show_admin_column' => true,
			'show_in_rest'      => false,
			/* Suppress the core box: its empty tax_input would erase our type
			   choice on every save. Type is chosen in the VikoStream metabox. */
			'meta_box_cb'       => false,
		)
	);

	register_taxonomy(
		'viko_genre',
		'viko_title',
		array(
			'labels'       => array(
				'name'          => __( 'Genres', 'vikostream' ),
				'singular_name' => __( 'Genre', 'vikostream' ),
			),
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'genre' ),
			'show_admin_column' => true,
			'show_in_rest' => false,
			/* Same reason as viko_type — genres are edited in the VikoStream metabox. */
			'meta_box_cb'  => false,
		)
	);
}
add_action( 'init', 'viko_register_post_type' );

/* seed the three core types + common genres once */
function viko_seed_terms() {
	if ( get_option( 'viko_seeded' ) ) {
		return;
	}
	foreach ( array( 'movie', 'tvshow', 'asian-drama' ) as $slug ) {
		wp_insert_term( $slug, 'viko_type', array( 'slug' => $slug ) );
	}
	$genres = array( 'Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Documentary', 'Drama', 'Family', 'Fantasy', 'History', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Thriller', 'War', 'Melodrama', 'Historical' );
	foreach ( $genres as $g ) {
		wp_insert_term( $g, 'viko_genre', array( 'slug' => sanitize_title( $g ) ) );
	}
	update_option( 'viko_seeded', 1 );
}
add_action( 'init', 'viko_seed_terms', 20 );

function viko_flush_on_switch() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'viko_flush_on_switch' );

/* ------------------------------------------------------------------ */
/* front assets                                                        */
/* ------------------------------------------------------------------ */

function viko_enqueue_front() {
	wp_enqueue_style( 'viko-fonts', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'viko-theme', VIKO_URI . '/assets/css/theme.css', array(), VIKO_VERSION );
	wp_enqueue_script( 'viko-theme', VIKO_URI . '/assets/js/theme.js', array(), VIKO_VERSION, true );
	wp_localize_script(
		'viko-theme',
		'VIKO',
		array(
			'ajax'   => admin_url( 'admin-ajax.php' ),
			'nonce'  => wp_create_nonce( 'viko_front' ),
			'home'   => home_url( '/' ),
			'i18n'   => array(
				'loading' => __( 'Loading…', 'vikostream' ),
				'empty'   => __( 'Hakuna matokeo kwa herufi hii.', 'vikostream' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'viko_enqueue_front' );

/* ------------------------------------------------------------------ */
/* helpers                                                             */
/* ------------------------------------------------------------------ */

function viko_meta( $post_id, $key, $default = '' ) {
	$v = get_post_meta( $post_id, '_viko_' . $key, true );
	return '' === $v || null === $v ? $default : $v;
}

function viko_type_of( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$terms   = wp_get_post_terms( $post_id, 'viko_type', array( 'fields' => 'slugs' ) );
	return ! is_wp_error( $terms ) && $terms ? $terms[0] : 'movie';
}

function viko_type_label( $slug ) {
	$map = array(
		'movie'       => __( 'Movie', 'vikostream' ),
		'tvshow'      => __( 'TV Show', 'vikostream' ),
		'asian-drama' => __( 'Asian Drama', 'vikostream' ),
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : ucfirst( $slug );
}

/**
 * Bulletproof term resolver: ALWAYS matches by slug, creates the term if
 * missing, returns the term ID. Prevents duplicate-term / slug mismatch bugs
 * (the reason type blocks stayed empty when term labels were renamed).
 */
function viko_ensure_term( $slug, $taxonomy ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) {
		return 0;
	}
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
	if ( is_wp_error( $created ) ) {
		$term = get_term_by( 'name', $slug, $taxonomy );
		return $term ? (int) $term->term_id : 0;
	}
	return (int) $created['term_id'];
}

/** Assign exactly one type term (by slug) to a title. */
function viko_assign_type( $post_id, $slug ) {
	$allowed = array( 'movie', 'tvshow', 'asian-drama' );
	$slug    = sanitize_title( $slug );
	if ( ! in_array( $slug, $allowed, true ) ) {
		return false;
	}
	$tid = viko_ensure_term( $slug, 'viko_type' );
	if ( ! $tid ) {
		return false;
	}
	wp_set_post_terms( $post_id, array( $tid ), 'viko_type', false );
	update_post_meta( $post_id, '_viko_type_slug', $slug );
	return true;
}

/** Assign genre terms (list of names or slugs) to a title. */
function viko_assign_genres( $post_id, $names ) {
	$ids = array();
	foreach ( (array) $names as $name ) {
		$name = trim( $name );
		if ( '' === $name ) {
			continue;
		}
		/* match existing by slug first, then by name, else create */
		$term = get_term_by( 'slug', sanitize_title( $name ), 'viko_genre' );
		if ( ! $term ) {
			$term = get_term_by( 'name', $name, 'viko_genre' );
		}
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		} else {
			$created = wp_insert_term( $name, 'viko_genre' );
			if ( ! is_wp_error( $created ) ) {
				$ids[] = (int) $created['term_id'];
			}
		}
	}
	wp_set_post_terms( $post_id, array_unique( $ids ), 'viko_genre', false );
}

function viko_poster_url( $post_id, $size = 'viko-poster' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
		if ( $img ) {
			return $img[0];
		}
	}
	$hot = viko_meta( $post_id, 'poster_url' );
	return $hot ? $hot : '';
}

function viko_backdrop_url( $post_id ) {
	$hot = viko_meta( $post_id, 'backdrop_url' );
	if ( $hot ) {
		return $hot;
	}
	return viko_poster_url( $post_id, 'viko-backdrop' );
}

/* ------------------------------------------------------------------ */
/* queries: A–Z filter + pagination + search                           */
/* ------------------------------------------------------------------ */

function viko_pre_get( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}

	/* A–Z query var (?az=A) */
	if ( isset( $_GET['az'] ) && '' !== $_GET['az'] ) { // phpcs:ignore
		$q->set( 'viko_az', sanitize_text_field( wp_unslash( $_GET['az'] ) ) ); // phpcs:ignore
	}

	if ( $q->is_post_type_archive( 'viko_title' ) || $q->is_tax( 'viko_type' ) || $q->is_tax( 'viko_genre' ) ) {
		$q->set( 'posts_per_page', 24 );

		/* archive-level type / genre / sort filters */
		if ( isset( $_GET['type'] ) && $_GET['type'] ) { // phpcs:ignore
			$q->set( 'viko_type', sanitize_title( wp_unslash( $_GET['type'] ) ) ); // phpcs:ignore
		}
		if ( isset( $_GET['genre'] ) && $_GET['genre'] ) { // phpcs:ignore
			$q->set( 'viko_genre', sanitize_title( wp_unslash( $_GET['genre'] ) ) ); // phpcs:ignore
		}
		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore
		switch ( $sort ) {
			case 'rating':
				$q->set( 'meta_key', '_viko_rating' );
				$q->set( 'orderby', 'meta_value_num' );
				$q->set( 'order', 'DESC' );
				break;
			case 'az':
				$q->set( 'orderby', 'title' );
				$q->set( 'order', 'ASC' );
				break;
			case 'year':
				$q->set( 'meta_key', '_viko_year' );
				$q->set( 'orderby', 'meta_value_num' );
				$q->set( 'order', 'DESC' );
				break;
			default:
				$q->set( 'orderby', 'date' );
				$q->set( 'order', 'DESC' );
		}
	}

	if ( $q->is_search() ) {
		$q->set( 'post_type', array( 'viko_title', 'post' ) );
	}
}
add_action( 'pre_get_posts', 'viko_pre_get' );

function viko_az_where( $where ) {
	global $wpdb;
	$az = get_query_var( 'viko_az' );
	if ( $az && preg_match( '/^[a-z0-9]$/i', $az ) ) {
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $az . '%' );
	}
	return $where;
}
add_filter( 'posts_where', 'viko_az_where' );

/* ------------------------------------------------------------------ */
/* AJAX: live search suggest + alphabet grid                           */
/* ------------------------------------------------------------------ */

function viko_render_card( $post_id ) {
	ob_start();
	get_template_part( 'template-parts/card', null, array( 'post_id' => $post_id ) );
	return ob_get_clean();
}

function viko_ajax_suggest() {
	check_ajax_referer( 'viko_front', 'nonce' );
	$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	if ( strlen( $q ) < 2 ) {
		wp_send_json( array( 'items' => array() ) );
	}
	$found = new WP_Query(
		array(
			'post_type'      => 'viko_title',
			'posts_per_page' => 6,
			's'              => $q,
			'no_found_rows'  => true,
		)
	);
	$items = array();
	foreach ( $found->posts as $p ) {
		$items[] = array(
			'title'  => get_the_title( $p ),
			'url'    => get_permalink( $p ),
			'year'   => viko_meta( $p->ID, 'year' ),
			'type'   => viko_type_label( viko_type_of( $p->ID ) ),
			'poster' => viko_poster_url( $p->ID ),
		);
	}
	wp_send_json( array( 'items' => $items ) );
}
add_action( 'wp_ajax_viko_suggest', 'viko_ajax_suggest' );
add_action( 'wp_ajax_nopriv_viko_suggest', 'viko_ajax_suggest' );

function viko_ajax_alphabet() {
	check_ajax_referer( 'viko_front', 'nonce' );
	$letter = isset( $_GET['letter'] ) ? sanitize_text_field( wp_unslash( $_GET['letter'] ) ) : '';
	$type   = isset( $_GET['type'] ) ? sanitize_title( wp_unslash( $_GET['type'] ) ) : '';

	$args = array(
		'post_type'      => 'viko_title',
		'posts_per_page' => 18,
		'viko_az'        => $letter,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	if ( $type ) {
		$args['tax_query'] = array( // phpcs:ignore
			array(
				'taxonomy' => 'viko_type',
				'field'    => 'slug',
				'terms'    => $type,
			),
		);
	}
	$q = new WP_Query( $args );
	$html = '';
	foreach ( $q->posts as $p ) {
		$html .= viko_render_card( $p->ID );
	}
	wp_send_json( array( 'html' => $html, 'count' => (int) $q->found_posts ) );
}
add_action( 'wp_ajax_viko_alphabet', 'viko_ajax_alphabet' );
add_action( 'wp_ajax_nopriv_viko_alphabet', 'viko_ajax_alphabet' );

/* ------------------------------------------------------------------ */
/* admin niceties                                                      */
/* ------------------------------------------------------------------ */

function viko_admin_columns( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['viko_type'] = __( 'Type', 'vikostream' );
			$new['viko_year'] = __( 'Year', 'vikostream' );
			$new['viko_imdb'] = __( 'IMDb', 'vikostream' );
		}
	}
	$new['viko_reco'] = '★';
	return $new;
}
add_filter( 'manage_viko_title_posts_columns', 'viko_admin_columns' );

function viko_admin_column_data( $col, $post_id ) {
	switch ( $col ) {
		case 'viko_type':
			echo esc_html( viko_type_label( viko_type_of( $post_id ) ) );
			break;
		case 'viko_year':
			echo esc_html( viko_meta( $post_id, 'year' ) );
			break;
		case 'viko_imdb':
			$imdb = viko_meta( $post_id, 'imdb' );
			echo $imdb ? '<a href="https://www.imdb.com/title/' . esc_attr( $imdb ) . '/" target="_blank" rel="noreferrer">' . esc_html( $imdb ) . '</a>' : '—';
			break;
		case 'viko_reco':
			echo viko_meta( $post_id, 'recommended' ) ? '<span style="color:#f5c518">★</span>' : '—';
			break;
	}
}
add_action( 'manage_viko_title_posts_custom_column', 'viko_admin_column_data', 10, 2 );

/* missing API key notice */
function viko_admin_notice() {
	if ( get_option( 'viko_tmdb_key' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'viko_title' !== $screen->post_type ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>VikoStream:</strong> ' . esc_html__( 'Weka TMDB API key ili import tool ifanye kazi —', 'vikostream' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=viko-settings' ) ) . '">' . esc_html__( 'Settings → API Key', 'vikostream' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'viko_admin_notice' );
