<?php
/**
 * VikoStream — TMDB & Asian Drama Import Engine.
 * Supports: Movie, TV Show, and Asian Drama search, discovery, single & bulk import,
 * full season/episode mapping, cast extraction, poster sideloading, and DramaCool direct scraping.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VIKO_TMDB = 'https://api.themoviedb.org/3';

function viko_tmdb_genres() {
	return array(
		28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy', 80 => 'Crime',
		99 => 'Documentary', 18 => 'Drama', 10751 => 'Family', 14 => 'Fantasy', 36 => 'History',
		27 => 'Horror', 10402 => 'Music', 9648 => 'Mystery', 10749 => 'Romance', 878 => 'Sci-Fi',
		10770 => 'TV Movie', 53 => 'Thriller', 10752 => 'War', 37 => 'Western',
		10759 => 'Action', 10762 => 'Kids', 10763 => 'News', 10764 => 'Reality',
		10765 => 'Sci-Fi', 10766 => 'Melodrama', 10767 => 'Talk', 10768 => 'War',
	);
}

/* ------------------------------------------------------------------ */
/* TMDB client                                                         */
/* ------------------------------------------------------------------ */

function viko_tmdb( $path, $args = array() ) {
	$key = get_option( 'viko_tmdb_key' );
	if ( ! $key ) {
		return new WP_Error( 'viko_no_key', __( 'TMDB API key missing — tafadhali weka kwenye Settings.', 'vikostream' ) );
	}
	$url = VIKO_TMDB . $path . '?' . http_build_query(
		array_merge( array( 'api_key' => $key, 'language' => 'en-US' ), $args )
	);
	$res = wp_remote_get( $url, array( 'timeout' => 20, 'sslverify' => false ) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code !== 200 ) {
		return new WP_Error( 'viko_tmdb', isset( $body['status_message'] ) ? $body['status_message'] : 'TMDB error ' . $code );
	}
	return $body;
}

function viko_tmdb_img( $path, $size = 'w500' ) {
	return $path ? 'https://image.tmdb.org/t/p/' . $size . $path : '';
}

function viko_drama_countries() {
	$raw = get_option( 'viko_drama_countries', 'KR,CN,JP,TW,TH,ID,VN' );
	return array_filter( array_map( 'trim', explode( ',', strtoupper( $raw ) ) ) );
}

/* ------------------------------------------------------------------ */
/* result mapping                                                      */
/* ------------------------------------------------------------------ */

function viko_detect_type( $r ) {
	$media_type = $r['media_type'] ?? '';
	if ( 'movie' === $media_type ) {
		return 'movie';
	}
	$origin = array_map( 'strtoupper', (array) ( $r['origin_country'] ?? $r['production_countries_iso'] ?? array() ) );
	if ( array_intersect( $origin, viko_drama_countries() ) ) {
		return 'asian-drama';
	}
	return ( 'tv' === $media_type || ! empty( $r['first_air_date'] ) || ! empty( $r['name'] ) ) ? 'tvshow' : 'movie';
}

function viko_map_result( $r ) {
	$type     = viko_detect_type( $r );
	$is_movie = ( 'movie' === $type );

	return array(
		'tmdb'     => (int) ( $r['id'] ?? 0 ),
		'is_movie' => $is_movie,
		'type'     => $type,
		'title'    => $r['title'] ?? $r['name'] ?? '',
		'year'     => substr( $r['release_date'] ?? $r['first_air_date'] ?? '', 0, 4 ),
		'overview' => $r['overview'] ?? '',
		'poster'   => viko_tmdb_img( $r['poster_path'] ?? '' ),
		'backdrop' => viko_tmdb_img( $r['backdrop_path'] ?? '', 'w1280' ),
		'rating'   => round( (float) ( $r['vote_average'] ?? 0 ), 1 ),
		'genres'   => array_values( array_intersect_key( viko_tmdb_genres(), array_flip( $r['genre_ids'] ?? array() ) ) ),
		'origin'   => implode( ', ', (array) ( $r['origin_country'] ?? array() ) ),
	);
}

/* ------------------------------------------------------------------ */
/* searches                                                            */
/* ------------------------------------------------------------------ */

function viko_search( $query, $page = 1 ) {
	$query = trim( $query );
	/* IMDB ID lookup */
	if ( preg_match( '/^tt\d{5,9}$/i', $query ) ) {
		$data = viko_tmdb( '/find/' . $query, array( 'external_source' => 'imdb_id' ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$item = null;
		foreach ( array( 'movie_results', 'tv_results' ) as $k ) {
			if ( ! empty( $data[ $k ][0] ) ) {
				$item               = $data[ $k ][0];
				$item['media_type'] = 'movie' === $k ? 'movie' : 'tv';
				break;
			}
		}
		if ( ! $item ) {
			return array( 'results' => array(), 'page' => 1, 'total_pages' => 1, 'total' => 0 );
		}
		return array( 'results' => array( viko_map_result( $item ) ), 'page' => 1, 'total_pages' => 1, 'total' => 1 );
	}

	$data = viko_tmdb( '/search/multi', array( 'query' => $query, 'include_adult' => 'false', 'page' => $page ) );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$results = array();
	foreach ( $data['results'] as $r ) {
		if ( ! in_array( $r['media_type'] ?? '', array( 'movie', 'tv' ), true ) ) {
			continue;
		}
		$results[] = viko_map_result( $r );
	}
	return array(
		'results'     => $results,
		'page'        => (int) $data['page'],
		'total_pages' => min( 10, (int) $data['total_pages'] ),
		'total'       => (int) $data['total_results'],
	);
}

function viko_discover( $type, $genre_id, $year, $page = 1 ) {
	$endpoint = 'movie' === $type ? '/discover/movie' : '/discover/tv';
	$args     = array( 'page' => $page, 'sort_by' => 'popularity.desc', 'include_adult' => 'false' );
	if ( $genre_id ) {
		$args['with_genres'] = (int) $genre_id;
	}
	if ( $year ) {
		$args[ 'movie' === $type ? 'primary_release_year' : 'first_air_date_year' ] = (int) $year;
	}
	if ( 'asian' === $type ) {
		$endpoint                    = '/discover/tv';
		$args['with_origin_country'] = implode( '|', viko_drama_countries() );
	}
	$data = viko_tmdb( $endpoint, $args );
	if ( is_wp_error( $data ) ) {
		return $data;
	}
	$results = array();
	foreach ( $data['results'] as $r ) {
		$r['media_type'] = ( 'tv' === $type || 'asian' === $type ) ? 'tv' : 'movie';
		$results[]       = viko_map_result( $r );
	}
	return array(
		'results'     => $results,
		'page'        => (int) $data['page'],
		'total_pages' => min( 10, (int) $data['total_pages'] ),
		'total'       => (int) $data['total_results'],
	);
}

/* ------------------------------------------------------------------ */
/* poster sideloading                                                  */
/* ------------------------------------------------------------------ */

function viko_sideload_poster( $url, $post_id ) {
	if ( ! $url ) {
		return false;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $url, 25 );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}
	$att_id = media_handle_sideload(
		array( 'tmp_name' => $tmp, 'name' => sanitize_title( get_the_title( $post_id ) ) . '-poster.jpg' ),
		$post_id
	);
	if ( is_wp_error( $att_id ) ) {
		@unlink( $tmp );
		return false;
	}
	set_post_thumbnail( $post_id, $att_id );
	return true;
}

/* ------------------------------------------------------------------ */
/* import item                                                         */
/* ------------------------------------------------------------------ */

function viko_import_item( $item ) {
	$item = wp_parse_args(
		$item,
		array(
			'tmdb'        => 0,
			'is_movie'    => null,
			'type'        => 'movie',
			'title'       => '',
			'year'        => '',
			'overview'    => '',
			'poster'      => '',
			'backdrop'    => '',
			'rating'      => 0,
			'genres'      => array(),
			'origin'      => '',
			'recommended' => false,
		)
	);

	if ( empty( $item['title'] ) ) {
		return new WP_Error( 'viko_bad_item', 'Missing title' );
	}

	// Auto resolve is_movie from type if null
	if ( $item['is_movie'] === null ) {
		$item['is_movie'] = ( $item['type'] === 'movie' );
	} else {
		// Strict boolean
		$item['is_movie'] = filter_var( $item['is_movie'], FILTER_VALIDATE_BOOLEAN );
	}

	if ( in_array( $item['type'], array( 'tvshow', 'asian-drama', 'tv' ), true ) ) {
		$item['is_movie'] = false;
	}

	/* duplicate check */
	if ( $item['tmdb'] ) {
		$dup = get_posts(
			array(
				'post_type'      => 'viko_title',
				'meta_key'       => '_viko_tmdb',
				'meta_value'     => (int) $item['tmdb'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $dup ) {
			return new WP_Error( 'viko_dup', __( 'Tayari ipo kwenye maktaba', 'vikostream' ), array( 'post_id' => $dup[0] ) );
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'viko_title',
			'post_title'   => sanitize_text_field( $item['title'] ),
			'post_content' => wp_kses_post( $item['overview'] ),
			'post_status'  => 'publish',
		)
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$final_type = $item['is_movie'] ? 'movie' : ( 'asian-drama' === $item['type'] ? 'asian-drama' : 'tvshow' );
	viko_assign_type( $post_id, $final_type );
	if ( ! empty( $item['genres'] ) ) {
		viko_assign_genres( $post_id, $item['genres'] );
	}

	/* Fetch detailed metadata from TMDB */
	$imdb           = '';
	$runtime        = '';
	$seasons_count  = 1;
	$total_episodes = 12;
	$trailer        = '';
	$seasons_map    = array();
	$cast           = array();

	if ( $item['tmdb'] ) {
		$endpoint = $item['is_movie'] ? ( '/movie/' . $item['tmdb'] ) : ( '/tv/' . $item['tmdb'] );
		$details  = viko_tmdb( $endpoint, array( 'append_to_response' => 'external_ids,videos,credits' ) );

		// Fallback check if endpoint was mismatched
		if ( is_wp_error( $details ) ) {
			$fallback_endpoint = ( ! $item['is_movie'] ) ? ( '/movie/' . $item['tmdb'] ) : ( '/tv/' . $item['tmdb'] );
			$details = viko_tmdb( $fallback_endpoint, array( 'append_to_response' => 'external_ids,videos,credits' ) );
			if ( ! is_wp_error( $details ) ) {
				$item['is_movie'] = ! $item['is_movie'];
				$final_type       = $item['is_movie'] ? 'movie' : 'tvshow';
				viko_assign_type( $post_id, $final_type );
			}
		}

		if ( ! is_wp_error( $details ) ) {
			$imdb    = $details['external_ids']['imdb_id'] ?? '';
			$runtime = $item['is_movie']
				? ( ( $details['runtime'] ?? '' ) ? ( $details['runtime'] . ' min' ) : '120 min' )
				: ( isset( $details['episode_run_time'][0] ) ? ( $details['episode_run_time'][0] . ' min' ) : '45 min' );

			if ( ! $item['is_movie'] ) {
				$seasons_count  = max( 1, (int) ( $details['number_of_seasons'] ?? 1 ) );
				$total_episodes = max( 1, (int) ( $details['number_of_episodes'] ?? 12 ) );

				/* Parse seasons array accurately */
				if ( ! empty( $details['seasons'] ) && is_array( $details['seasons'] ) ) {
					foreach ( $details['seasons'] as $s ) {
						$s_num = (int) ( $s['season_number'] ?? 0 );
						if ( $s_num > 0 ) {
							$seasons_map[] = array(
								's'    => $s_num,
								'e'    => max( 1, (int) ( $s['episode_count'] ?? 12 ) ),
								'name' => $s['name'] ?? "Season {$s_num}",
							);
						}
					}
				}

				// Default fallback for TV show / Asian drama seasons if map was empty
				if ( empty( $seasons_map ) ) {
					for ( $sn = 1; $sn <= $seasons_count; $sn++ ) {
						$seasons_map[] = array(
							's'    => $sn,
							'e'    => ( $sn === 1 ) ? $total_episodes : 12,
							'name' => "Season {$sn}",
						);
					}
				}
			}

			/* Top cast with profile photos */
			foreach ( array_slice( $details['credits']['cast'] ?? array(), 0, 12 ) as $c ) {
				$cast[] = array(
					'name'      => $c['name'] ?? '',
					'character' => $c['character'] ?? '',
					'img'       => viko_tmdb_img( $c['profile_path'] ?? '', 'w185' ),
				);
			}

			/* Trailer link */
			foreach ( $details['videos']['results'] ?? array() as $v ) {
				if ( 'YouTube' === ( $v['site'] ?? '' ) && in_array( $v['type'] ?? '', array( 'Trailer', 'Teaser' ), true ) ) {
					$trailer = 'https://www.youtube.com/watch?v=' . $v['key'];
					break;
				}
			}
		}
	}

	// Update meta keys
	update_post_meta( $post_id, '_viko_tmdb', (int) $item['tmdb'] );
	update_post_meta( $post_id, '_viko_imdb', sanitize_text_field( $imdb ) );
	update_post_meta( $post_id, '_viko_year', sanitize_text_field( $item['year'] ) );
	update_post_meta( $post_id, '_viko_rating', floatval( $item['rating'] ?: 8.5 ) );
	update_post_meta( $post_id, '_viko_runtime', sanitize_text_field( $runtime ) );
	update_post_meta( $post_id, '_viko_seasons', (int) $seasons_count );
	update_post_meta( $post_id, '_viko_total_episodes', (int) $total_episodes );
	update_post_meta( $post_id, '_viko_country', sanitize_text_field( $item['origin'] ) );
	update_post_meta( $post_id, '_viko_trailer', esc_url_raw( $trailer ) );
	update_post_meta( $post_id, '_viko_quality', get_option( 'viko_default_quality', '4K UHD' ) );
	update_post_meta( $post_id, '_viko_backdrop_url', esc_url_raw( $item['backdrop'] ) );
	update_post_meta( $post_id, '_viko_drama_slug', sanitize_title( $item['title'] ) );
	update_post_meta( $post_id, '_viko_recommended', ( $item['recommended'] || get_option( 'viko_autoreco' ) ) ? 1 : 0 );
	update_post_meta( $post_id, '_viko_imported_at', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_viko_seasons_map', $seasons_map );
	update_post_meta( $post_id, '_viko_cast', $cast );

	if ( ! viko_sideload_poster( $item['poster'], $post_id ) ) {
		update_post_meta( $post_id, '_viko_poster_url', esc_url_raw( $item['poster'] ) );
	}

	viko_log_import( $item['title'], true, $post_id );

	return array(
		'post_id'  => $post_id,
		'title'    => $item['title'],
		'type'     => $final_type,
		'seasons'  => count( $seasons_map ),
		'episodes' => $total_episodes,
		'url'      => get_edit_post_link( $post_id, 'raw' ),
		'view'     => get_permalink( $post_id ),
	);
}

/* ------------------------------------------------------------------ */
/* Re-sync single title data                                           */
/* ------------------------------------------------------------------ */

function viko_sync_title_data( $post_id ) {
	$tmdb = (int) viko_meta( $post_id, 'tmdb' );
	if ( ! $tmdb ) {
		return new WP_Error( 'viko_no_tmdb', __( 'Title haina TMDB ID — tafadhali weka TMDB ID au i-import upya.', 'vikostream' ) );
	}

	$is_movie = ( viko_type_of( $post_id ) === 'movie' );
	$details  = viko_tmdb( ( $is_movie ? '/movie/' : '/tv/' ) . $tmdb, array( 'append_to_response' => 'external_ids,videos,credits' ) );

	if ( is_wp_error( $details ) ) {
		$details  = viko_tmdb( ( $is_movie ? '/tv/' : '/movie/' ) . $tmdb, array( 'append_to_response' => 'external_ids,videos,credits' ) );
		$is_movie = ! $is_movie;
		if ( is_wp_error( $details ) ) {
			return $details;
		}
	}

	$type = $is_movie ? 'movie' : viko_detect_type( array( 'origin_country' => $details['origin_country'] ?? array() ) );
	viko_assign_type( $post_id, $type );

	$seasons_map    = array();
	$seasons_count  = 1;
	$total_episodes = 12;

	if ( ! $is_movie ) {
		$seasons_count  = max( 1, (int) ( $details['number_of_seasons'] ?? 1 ) );
		$total_episodes = max( 1, (int) ( $details['number_of_episodes'] ?? 12 ) );

		foreach ( $details['seasons'] ?? array() as $s ) {
			$s_num = (int) ( $s['season_number'] ?? 0 );
			if ( $s_num > 0 ) {
				$seasons_map[] = array(
					's'    => $s_num,
					'e'    => max( 1, (int) ( $s['episode_count'] ?? 1 ) ),
					'name' => $s['name'] ?? "Season {$s_num}",
				);
			}
		}

		if ( empty( $seasons_map ) ) {
			for ( $sn = 1; $sn <= $seasons_count; $sn++ ) {
				$seasons_map[] = array(
					's'    => $sn,
					'e'    => ( $sn === 1 ) ? $total_episodes : 12,
					'name' => "Season {$sn}",
				);
			}
		}
	}

	$cast = array();
	foreach ( array_slice( $details['credits']['cast'] ?? array(), 0, 12 ) as $c ) {
		$cast[] = array(
			'name'      => $c['name'] ?? '',
			'character' => $c['character'] ?? '',
			'img'       => viko_tmdb_img( $c['profile_path'] ?? '', 'w185' ),
		);
	}

	$trailer = '';
	foreach ( $details['videos']['results'] ?? array() as $v ) {
		if ( 'YouTube' === ( $v['site'] ?? '' ) && in_array( $v['type'] ?? '', array( 'Trailer', 'Teaser' ), true ) ) {
			$trailer = 'https://www.youtube.com/watch?v=' . $v['key'];
			break;
		}
	}

	update_post_meta( $post_id, '_viko_imdb', $details['external_ids']['imdb_id'] ?? viko_meta( $post_id, 'imdb' ) );
	update_post_meta( $post_id, '_viko_seasons', (int) $seasons_count );
	update_post_meta( $post_id, '_viko_total_episodes', (int) $total_episodes );
	update_post_meta( $post_id, '_viko_seasons_map', $seasons_map );
	update_post_meta( $post_id, '_viko_cast', $cast );
	if ( $trailer ) {
		update_post_meta( $post_id, '_viko_trailer', $trailer );
	}
	update_post_meta( $post_id, '_viko_synced_at', current_time( 'mysql' ) );

	return array(
		'seasons'  => count( $seasons_map ),
		'episodes' => $total_episodes,
		'cast'     => count( $cast ),
		'type'     => $type,
	);
}

/* ------------------------------------------------------------------ */
/* Direct Asian Drama (DramaCool) Scraper                             */
/* ------------------------------------------------------------------ */

function viko_import_dramacool_url( $url ) {
	$url = esc_url_raw( trim( $url ) );
	if ( ! $url ) {
		return new WP_Error( 'invalid_url', 'Invalid DramaCool URL provided.' );
	}

	$response = wp_remote_get( $url, array(
		'timeout'    => 20,
		'sslverify'  => false,
		'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
	));

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$html = wp_remote_retrieve_body( $response );
	if ( empty( $html ) ) {
		return new WP_Error( 'empty_body', 'Failed to retrieve Drama page contents.' );
	}

	// Extract Title
	$title = '';
	if ( preg_match( '/<h1[^>]*>([^<]+)<\/h1>/i', $html, $m ) ) {
		$title = html_entity_decode( trim( $m[1] ), ENT_QUOTES, 'UTF-8' );
	}

	if ( empty( $title ) ) {
		return new WP_Error( 'parse_error', 'Could not parse Drama title from page.' );
	}

	// Extract Poster
	$poster = '';
	if ( preg_match( '/<div class="img"[^>]*>[\s\S]*?<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m ) ) {
		$poster = $m[1];
	}

	// Extract Synopsis
	$synopsis = '';
	if ( preg_match( '/<div class="info"[^>]*>([\s\S]*?)<\/div>/i', $html, $m ) ) {
		$synopsis = strip_tags( $m[1], '<p><br><strong><b>' );
	}

	// Count Episodes
	$ep_matches = array();
	preg_match_all( '/href=["\'][^"\']+-episode-(\d+)[^"\']*["\']/i', $html, $ep_matches );
	$max_ep = 12;
	if ( ! empty( $ep_matches[1] ) ) {
		$max_ep = max( array_map( 'intval', $ep_matches[1] ) );
	}

	// Build Drama Item
	$item = array(
		'tmdb'        => 0,
		'is_movie'    => false,
		'type'        => 'asian-drama',
		'title'       => $title,
		'year'        => date( 'Y' ),
		'overview'    => $synopsis,
		'poster'      => $poster,
		'backdrop'    => $poster,
		'rating'      => 8.8,
		'genres'      => array( 'Asian Drama', 'Romance', 'Drama' ),
		'origin'      => 'South Korea',
		'recommended' => true,
	);

	$res = viko_import_item( $item );
	if ( is_wp_error( $res ) ) {
		return $res;
	}

	$post_id = $res['post_id'];
	$seasons_map = array(
		array( 's' => 1, 'e' => max( 1, $max_ep ), 'name' => 'Season 1' )
	);
	update_post_meta( $post_id, '_viko_seasons', 1 );
	update_post_meta( $post_id, '_viko_total_episodes', $max_ep );
	update_post_meta( $post_id, '_viko_seasons_map', $seasons_map );

	return array(
		'post_id'   => $post_id,
		'title'     => $title,
		'total_eps' => $max_ep,
		'view'      => get_permalink( $post_id ),
	);
}

/* ------------------------------------------------------------------ */
/* log                                                                 */
/* ------------------------------------------------------------------ */

function viko_log_import( $title, $ok, $post_id = 0 ) {
	$log   = get_option( 'viko_import_log', array() );
	$log[] = array(
		'title'   => $title,
		'ok'      => (bool) $ok,
		'post_id' => $post_id,
		'time'    => current_time( 'mysql' ),
	);
	update_option( 'viko_import_log', array_slice( $log, -80 ) );
}

/* ------------------------------------------------------------------ */
/* admin menu + AJAX                                                   */
/* ------------------------------------------------------------------ */

function viko_import_menu() {
	add_submenu_page(
		'edit.php?post_type=viko_title',
		__( 'Viko Import & Catalog Scraper', 'vikostream' ),
		__( '⇩ Import Titles', 'vikostream' ),
		'manage_options',
		'viko-import',
		function () {
			require VIKO_DIR . '/admin/views/import.php';
		}
	);
}
add_action( 'admin_menu', 'viko_import_menu' );

function viko_admin_scripts( $hook ) {
	if ( false === strpos( $hook, 'viko' ) && 'viko_title' !== get_post_type() ) {
		return;
	}
	wp_enqueue_style( 'viko-admin', VIKO_URI . '/assets/css/admin.css', array(), VIKO_VERSION );
	wp_enqueue_script( 'viko-admin', VIKO_URI . '/assets/js/admin.js', array(), VIKO_VERSION, true );
	wp_localize_script(
		'viko-admin',
		'VIKO_ADMIN',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'viko_admin' ),
			'i18n'  => array(
				'importing' => __( 'Ina-import…', 'vikostream' ),
				'imported'  => __( 'Imeingizwa ✓', 'vikostream' ),
				'exists'    => __( 'Tayari ipo', 'vikostream' ),
				'error'     => __( 'Hitilafu', 'vikostream' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'viko_admin_scripts' );

function viko_ajax_search() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$mode  = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'search';
	$page  = max( 1, (int) ( $_POST['page'] ?? 1 ) );

	if ( 'discover' === $mode ) {
		$data = viko_discover(
			sanitize_key( $_POST['type'] ?? 'movie' ),
			(int) ( $_POST['genre'] ?? 0 ),
			(int) ( $_POST['year'] ?? 0 ),
			$page
		);
	} else {
		$data = viko_search( sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), $page );
	}

	if ( is_wp_error( $data ) ) {
		wp_send_json_error( array( 'msg' => $data->get_error_message() ) );
	}

	/* mark duplicates */
	foreach ( $data['results'] as &$r ) {
		$r['exists'] = false;
		if ( $r['tmdb'] ) {
			$dup = get_posts(
				array(
					'post_type'      => 'viko_title',
					'meta_key'       => '_viko_tmdb',
					'meta_value'     => $r['tmdb'],
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			$r['exists'] = ! empty( $dup );
		}
	}
	unset( $r );

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_viko_search', 'viko_ajax_search' );

function viko_ajax_import() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$item = isset( $_POST['item'] ) ? json_decode( stripslashes( $_POST['item'] ), true ) : null;
	if ( ! is_array( $item ) ) {
		wp_send_json_error( array( 'msg' => 'bad item' ) );
	}
	$item['recommended'] = ! empty( $_POST['recommended'] );
	$res = viko_import_item( $item );
	if ( is_wp_error( $res ) ) {
		$extra = $res->get_error_data();
		wp_send_json_error(
			array(
				'msg'     => $res->get_error_message(),
				'code'    => $res->get_error_code(),
				'post_id' => is_array( $extra ) ? ( $extra['post_id'] ?? 0 ) : 0,
			)
		);
	}
	wp_send_json_success( $res );
}
add_action( 'wp_ajax_viko_import', 'viko_ajax_import' );

/* bulk: sequential list of items */
function viko_ajax_bulk() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$items = isset( $_POST['items'] ) ? json_decode( stripslashes( $_POST['items'] ), true ) : array();
	$reco  = ! empty( $_POST['recommended'] );
	$out   = array( 'ok' => 0, 'fail' => 0, 'dup' => 0 );
	foreach ( (array) $items as $item ) {
		if ( ! is_array( $item ) ) {
			$out['fail']++;
			continue;
		}
		$item['recommended'] = $reco;
		$res = viko_import_item( $item );
		if ( is_wp_error( $res ) ) {
			'viko_dup' === $res->get_error_code() ? $out['dup']++ : $out['fail']++;
		} else {
			$out['ok']++;
		}
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_viko_bulk', 'viko_ajax_bulk' );

/* bulk from pasted list of titles / IMDB IDs */
function viko_ajax_bulk_resolve() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['lines'] ?? '' ) ) ) ) );
	$out   = array( 'ok' => 0, 'fail' => 0, 'dup' => 0, 'missing' => array() );
	foreach ( array_slice( $lines, 0, 40 ) as $line ) {
		$found = viko_search( $line, 1 );
		if ( is_wp_error( $found ) || empty( $found['results'][0] ) ) {
			$out['fail']++;
			$out['missing'][] = $line;
			continue;
		}
		$item                = $found['results'][0];
		$item['recommended'] = ! empty( $_POST['recommended'] );
		$res                 = viko_import_item( $item );
		if ( is_wp_error( $res ) ) {
			'viko_dup' === $res->get_error_code() ? $out['dup']++ : $out['fail']++;
		} else {
			$out['ok']++;
		}
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_viko_bulk_resolve', 'viko_ajax_bulk_resolve' );

/* Direct DramaCool URL scraper AJAX */
function viko_ajax_import_dramacool() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$url = isset( $_POST['url'] ) ? esc_url_raw( trim( $_POST['url'] ) ) : '';
	if ( ! $url ) {
		wp_send_json_error( array( 'msg' => 'Tafadhali weka URL sahihi ya DramaCool.' ) );
	}
	$res = viko_import_dramacool_url( $url );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'msg' => $res->get_error_message() ) );
	}
	wp_send_json_success( $res );
}
add_action( 'wp_ajax_viko_import_dramacool', 'viko_ajax_import_dramacool' );

function viko_ajax_log() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	if ( isset( $_POST['clear'] ) ) {
		delete_option( 'viko_import_log' );
	}
	wp_send_json_success( array( 'log' => array_reverse( get_option( 'viko_import_log', array() ) ) ) );
}
add_action( 'wp_ajax_viko_log', 'viko_ajax_log' );

/* ------------------------------------------------------------------ */
/* sync (single title) + repair (all titles)                           */
/* ------------------------------------------------------------------ */

function viko_ajax_sync_eps() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}
	$post_id = (int) ( $_POST['post_id'] ?? 0 );
	if ( ! $post_id ) {
		wp_send_json_error( array( 'msg' => 'bad id' ) );
	}
	$res = viko_sync_title_data( $post_id );
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'msg' => $res->get_error_message() ) );
	}
	wp_send_json_success( $res );
}
add_action( 'wp_ajax_viko_sync_eps', 'viko_ajax_sync_eps' );

function viko_ajax_repair() {
	check_ajax_referer( 'viko_admin', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'msg' => 'no permission' ) );
	}

	$posts = get_posts(
		array(
			'post_type'      => 'viko_title',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$out        = array( 'total' => count( $posts ), 'types' => 0, 'synced' => 0, 'failed' => 0 );
	$tmdb_calls = 0;

	foreach ( $posts as $pid ) {
		$slug = get_post_meta( $pid, '_viko_type_slug', true );
		if ( $slug ) {
			viko_assign_type( $pid, $slug );
		}

		if ( viko_meta( $pid, 'tmdb' ) && $tmdb_calls < 150 ) {
			$res = viko_sync_title_data( $pid );
			$tmdb_calls += 2;
			if ( is_wp_error( $res ) ) {
				$out['failed']++;
			} else {
				$out['types']++;
				$out['synced']++;
			}
		}
	}

	update_option( 'viko_last_repair', current_time( 'mysql' ) );
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_viko_repair', 'viko_ajax_repair' );
