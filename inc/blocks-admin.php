<?php
/**
 * VikoStream — homepage block manager.
 * Option `viko_home_blocks`: ordered array of blocks rendered on the front page.
 * A block rule can be: recommended | type:<slug> | genre:<slug>
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function viko_default_blocks() {
	return array(
		array( 'id' => 'b1', 'enabled' => 1, 'style' => 'slider',   'title' => 'Featured',      'rule' => 'recommended',        'sort' => 'new', 'count' => 6 ),
		array( 'id' => 'b2', 'enabled' => 1, 'style' => 'alphabet', 'title' => 'Browse A–Z',    'rule' => '',                   'sort' => 'az',  'count' => 18 ),
		array( 'id' => 'b3', 'enabled' => 1, 'style' => 'row',      'title' => 'Recommended',   'rule' => 'recommended',        'sort' => 'new', 'count' => 12 ),
		array( 'id' => 'b4', 'enabled' => 1, 'style' => 'row',      'title' => 'Latest Movies', 'rule' => 'type:movie',         'sort' => 'new', 'count' => 12 ),
		array( 'id' => 'b5', 'enabled' => 1, 'style' => 'row',      'title' => 'TV Shows',      'rule' => 'type:tvshow',        'sort' => 'new', 'count' => 12 ),
		array( 'id' => 'b6', 'enabled' => 1, 'style' => 'row',      'title' => 'Asian Dramas',  'rule' => 'type:asian-drama',   'sort' => 'new', 'count' => 12 ),
	);
}

function viko_get_blocks() {
	$blocks = get_option( 'viko_home_blocks' );
	if ( ! is_array( $blocks ) || ! $blocks ) {
		$blocks = viko_default_blocks();
		update_option( 'viko_home_blocks', $blocks );
	}
	return $blocks;
}

/** Query args from a block rule. */
function viko_block_query( $block ) {
	$args = array(
		'post_type'      => 'viko_title',
		'posts_per_page' => max( 1, (int) ( $block['count'] ?? 12 ) ),
		'post_status'    => 'publish',
	);
	$rule = $block['rule'] ?? '';

	if ( 'recommended' === $rule ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array( 'key' => '_viko_recommended', 'value' => '1' ),
			array( 'key' => '_viko_rating', 'value' => '7.5', 'compare' => '>=', 'type' => 'DECIMAL' )
		);
	} elseif ( strpos( $rule, 'type:' ) === 0 ) {
		$slug = substr( $rule, 5 );
		if ( 'movie' === $slug ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'viko_type',
					'field'    => 'slug',
					'terms'    => array( 'movie', 'movies' ),
					'operator' => 'IN',
				)
			);
		} elseif ( 'tvshow' === $slug ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'viko_type',
					'field'    => 'slug',
					'terms'    => array( 'tvshow', 'tvshows', 'tv' ),
					'operator' => 'IN',
				)
			);
		} elseif ( 'asian-drama' === $slug ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'viko_type',
					'field'    => 'slug',
					'terms'    => array( 'asian-drama', 'asian_drama', 'drama', 'k-drama', 'c-drama' ),
					'operator' => 'IN',
				)
			);
		} else {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'viko_type',
					'field'    => 'slug',
					'terms'    => $slug,
				)
			);
		}
	} elseif ( strpos( $rule, 'genre:' ) === 0 ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'viko_genre',
				'field'    => 'slug',
				'terms'    => substr( $rule, 6 ),
			)
		);
	}

	switch ( $block['sort'] ?? 'new' ) {
		case 'rating':
			$args['meta_key'] = '_viko_rating';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		case 'az':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'random':
			$args['orderby'] = 'rand';
			break;
		case 'year':
			$args['meta_key'] = '_viko_year';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}
	return $args;
}

/* ------------------------------------------------------------------ */
/* admin page                                                          */
/* ------------------------------------------------------------------ */

function viko_blocks_menu() {
	add_submenu_page(
		'edit.php?post_type=viko_title',
		__( 'Homepage Blocks', 'vikostream' ),
		__( '▦ Homepage Blocks', 'vikostream' ),
		'manage_options',
		'viko-blocks',
		function () {
			require VIKO_DIR . '/admin/views/blocks.php';
		}
	);
}
add_action( 'admin_menu', 'viko_blocks_menu' );

function viko_blocks_handle() {
	if ( ! isset( $_POST['viko_blocks_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['viko_blocks_nonce'] ), 'viko_blocks' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$blocks = viko_get_blocks();
	$action = sanitize_key( $_POST['viko_action'] ?? '' );

	if ( 'add' === $action ) {
		$blocks[] = array(
			'id'      => 'b' . substr( md5( uniqid( '', true ) ), 0, 6 ),
			'enabled' => 1,
			'style'   => 'row',
			'title'   => __( 'New Block', 'vikostream' ),
			'rule'    => 'type:movie',
			'sort'    => 'new',
			'count'   => 12,
		);
	} elseif ( 'update' === $action && isset( $_POST['blocks'] ) && is_array( $_POST['blocks'] ) ) {
		$clean = array();
		foreach ( wp_unslash( $_POST['blocks'] ) as $b ) {
			$clean[] = array(
				'id'      => sanitize_key( $b['id'] ),
				'enabled' => isset( $b['enabled'] ) ? 1 : 0,
				'style'   => in_array( $b['style'] ?? '', array( 'slider', 'alphabet', 'row', 'grid' ), true ) ? $b['style'] : 'row',
				'title'   => sanitize_text_field( $b['title'] ?? '' ),
				'rule'    => sanitize_text_field( $b['rule'] ?? '' ),
				'sort'    => sanitize_key( $b['sort'] ?? 'new' ),
				'count'   => max( 1, min( 30, (int) ( $b['count'] ?? 12 ) ) ),
			);
		}
		$blocks = $clean;
	} elseif ( 'move' === $action ) {
		$idx  = (int) ( $_POST['viko_idx'] ?? 0 );
		$dir  = (int) ( $_POST['viko_dir'] ?? 0 );
		$to   = $idx + $dir;
		if ( isset( $blocks[ $idx ], $blocks[ $to ] ) ) {
			$tmp            = $blocks[ $idx ];
			$blocks[ $idx ] = $blocks[ $to ];
			$blocks[ $to ]  = $tmp;
			$blocks         = array_values( $blocks );
		}
	} elseif ( 'delete' === $action ) {
		$idx = (int) ( $_POST['viko_idx'] ?? -1 );
		if ( isset( $blocks[ $idx ] ) ) {
			array_splice( $blocks, $idx, 1 );
		}
	} elseif ( 'reset' === $action ) {
		$blocks = viko_default_blocks();
	}

	update_option( 'viko_home_blocks', $blocks );

	wp_safe_redirect( admin_url( 'edit.php?post_type=viko_title&page=viko-blocks&saved=1' ) );
	exit;
}
add_action( 'admin_init', 'viko_blocks_handle' );

/* dropdown options shared with the view */
function viko_rule_options( $selected = '' ) {
	$html  = '<option value="" ' . selected( $selected, '', false ) . '>— ' . esc_html__( 'Latest (zote)', 'vikostream' ) . ' —</option>';
	$html .= '<option value="recommended" ' . selected( $selected, 'recommended', false ) . '>★ ' . esc_html__( 'Recommended', 'vikostream' ) . '</option>';
	$html .= '<optgroup label="' . esc_attr__( 'Type', 'vikostream' ) . '">';
	foreach ( get_terms( array( 'taxonomy' => 'viko_type', 'hide_empty' => false ) ) as $t ) {
		$val  = 'type:' . $t->slug;
		$html .= '<option value="' . esc_attr( $val ) . '" ' . selected( $selected, $val, false ) . '>' . esc_html( $t->name ) . '</option>';
	}
	$html .= '</optgroup><optgroup label="' . esc_attr__( 'Genre', 'vikostream' ) . '">';
	foreach ( get_terms( array( 'taxonomy' => 'viko_genre', 'hide_empty' => false ) ) as $t ) {
		$val  = 'genre:' . $t->slug;
		$html .= '<option value="' . esc_attr( $val ) . '" ' . selected( $selected, $val, false ) . '>' . esc_html( $t->name ) . '</option>';
	}
	$html .= '</optgroup>';
	return $html;
}

function viko_sort_options( $selected = 'new' ) {
	$opts = array(
		'new'    => __( 'Latest first', 'vikostream' ),
		'rating' => __( 'Top rated', 'vikostream' ),
		'year'   => __( 'Newest year', 'vikostream' ),
		'az'     => __( 'A → Z', 'vikostream' ),
		'random' => __( 'Random', 'vikostream' ),
	);
	$html = '';
	foreach ( $opts as $v => $l ) {
		$html .= '<option value="' . esc_attr( $v ) . '" ' . selected( $selected, $v, false ) . '>' . esc_html( $l ) . '</option>';
	}
	return $html;
}
