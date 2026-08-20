<?php
/**
 * VikoStream — Embed Player Engine.
 * Builds server tabs from configurable provider templates.
 * Server 1 Priority: VidSrc ME (https://vidsrc.me/embed/movie/{imdb} & https://vidsrc.me/embed/tv/{imdb}/{season}/{episode})
 * Placeholders: {imdb} {tmdb} {season} {episode} {slug}
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default verified player providers per content group.
 */
function viko_default_providers() {
	return array(
		'movie'       => array(
			array( 'id' => 'vidsrcme',  'label' => 'Server 1 (VidSrc ME)',    'url' => 'https://vidsrc.me/embed/movie/{imdb}',                    'enabled' => 1, 'order' => 1 ),
			array( 'id' => 'autoembed', 'label' => 'Server 2 (AutoEmbed CC)', 'url' => 'https://player.autoembed.cc/embed/movie/{tmdb}',          'enabled' => 1, 'order' => 2 ),
			array( 'id' => 'vidsrcto',  'label' => 'Server 3 (VidSrc PRO)',   'url' => 'https://vidsrc.to/embed/movie/{tmdb}',                    'enabled' => 1, 'order' => 3 ),
			array( 'id' => 'multi',     'label' => 'Server 4 (SuperEmbed)',   'url' => 'https://multiembed.mov/?video_id={imdb}&tmdb=1',          'enabled' => 1, 'order' => 4 ),
			array( 'id' => 'vsembed',   'label' => 'Server 5 (VSEmbed)',      'url' => 'https://vsembed.ru/embed/movie/{imdb}',                   'enabled' => 1, 'order' => 5 ),
			array( 'id' => 'vidlink',   'label' => 'Server 6 (VidLink)',      'url' => 'https://vidlink.pro/movie/{tmdb}?primaryColor=00d4ff',     'enabled' => 1, 'order' => 6 ),
		),
		'tv'          => array(
			array( 'id' => 'vidsrcme',  'label' => 'Server 1 (VidSrc ME)',    'url' => 'https://vidsrc.me/embed/tv/{imdb}/{season}/{episode}',                   'enabled' => 1, 'order' => 1 ),
			array( 'id' => 'autoembed', 'label' => 'Server 2 (AutoEmbed CC)', 'url' => 'https://player.autoembed.cc/embed/tv/{tmdb}/{season}/{episode}',         'enabled' => 1, 'order' => 2 ),
			array( 'id' => 'vidsrcto',  'label' => 'Server 3 (VidSrc PRO)',   'url' => 'https://vidsrc.to/embed/tv/{tmdb}/{season}/{episode}',                   'enabled' => 1, 'order' => 3 ),
			array( 'id' => 'multi',     'label' => 'Server 4 (SuperEmbed)',   'url' => 'https://multiembed.mov/?video_id={imdb}&tmdb=1&s={season}&e={episode}', 'enabled' => 1, 'order' => 4 ),
			array( 'id' => 'vsembed',   'label' => 'Server 5 (VSEmbed)',      'url' => 'https://vsembed.ru/embed/tv/{imdb}/{season}-{episode}',                  'enabled' => 1, 'order' => 5 ),
		),
		'asian-drama' => array(
			array( 'id' => 'vidsrcme',  'label' => 'Server 1 (VidSrc ME)',    'url' => 'https://vidsrc.me/embed/tv/{imdb}/{season}/{episode}',                   'enabled' => 1, 'order' => 1 ),
			array( 'id' => 'dramacool', 'label' => 'Server 2 (DramaCool)',    'url' => 'https://embtaku.pro/streaming.php?id={slug}-episode-{episode}',          'enabled' => 1, 'order' => 2 ),
			array( 'id' => 'autoembed', 'label' => 'Server 3 (AutoEmbed CC)', 'url' => 'https://player.autoembed.cc/embed/tv/{tmdb}/{season}/{episode}',         'enabled' => 1, 'order' => 3 ),
			array( 'id' => 'vidsrcto',  'label' => 'Server 4 (VidSrc PRO)',   'url' => 'https://vidsrc.to/embed/tv/{tmdb}/{season}/{episode}',                   'enabled' => 1, 'order' => 4 ),
			array( 'id' => 'multi',     'label' => 'Server 5 (SuperEmbed)',   'url' => 'https://multiembed.mov/?video_id={imdb}&tmdb=1&s={season}&e={episode}', 'enabled' => 1, 'order' => 5 ),
			array( 'id' => 'kissasian', 'label' => 'Server 6 (KissAsian)',    'url' => 'https://kissasian.video/drama/{slug}/episode-{episode}',                 'enabled' => 1, 'order' => 6 ),
		),
	);
}

/**
 * Get active, ordered provider list for a group.
 */
function viko_get_providers( $group ) {
	$defaults = viko_default_providers()[ $group ] ?? array();
	$saved    = get_option( 'viko_server_list_' . $group, null );

	if ( ! is_array( $saved ) || empty( $saved ) ) {
		// Try legacy fallback
		$enabled_ids = get_option( 'viko_providers_' . $group, null );
		if ( is_array( $enabled_ids ) && ! empty( $enabled_ids ) ) {
			$list = array();
			foreach ( $defaults as $idx => $def ) {
				$def['enabled'] = in_array( $def['id'], $enabled_ids, true ) ? 1 : 0;
				$def['order']   = $idx + 1;
				$list[]         = $def;
			}
			return $list;
		}
		return $defaults;
	}

	return $saved;
}

/**
 * Build player tabs for a title.
 * Manual override (metabox `_viko_players`, lines: Label|URL) wins if provided.
 *
 * @return array [ [ 'label' =>, 'url' =>, 'auto' => bool ] ]
 */
function viko_build_players( $post_id ) {
	$manual = viko_meta( $post_id, 'players' );
	if ( $manual ) {
		$tabs = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $manual ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( strpos( $line, '|' ) !== false ) {
				list( $label, $url ) = array_map( 'trim', explode( '|', $line, 2 ) );
			} else {
				$label = 'Server ' . ( count( $tabs ) + 1 );
				$url   = $line;
			}
			if ( $url ) {
				$tabs[] = array( 'label' => $label, 'url' => esc_url_raw( $url ), 'auto' => false );
			}
		}
		if ( ! empty( $tabs ) ) {
			return $tabs;
		}
	}

	$type  = viko_type_of( $post_id );
	$group = 'asian-drama' === $type ? 'asian-drama' : ( 'tvshow' === $type ? 'tv' : 'movie' );

	$imdb = viko_meta( $post_id, 'imdb' );
	$tmdb = viko_meta( $post_id, 'tmdb' );
	$slug = viko_meta( $post_id, 'drama_slug' );
	$slug = $slug ? $slug : sanitize_title( get_the_title( $post_id ) );

	// Clean IDs
	$clean_imdb = trim( $imdb );
	$clean_tmdb = preg_replace( '/[^0-9]/', '', (string) $tmdb );

	// Fallback ID if missing
	if ( empty( $clean_imdb ) && empty( $clean_tmdb ) ) {
		$clean_imdb = 'tt6723592';
		$clean_tmdb = '577922';
	} elseif ( empty( $clean_imdb ) ) {
		$clean_imdb = 'tt' . $clean_tmdb;
	} elseif ( empty( $clean_tmdb ) ) {
		$clean_tmdb = preg_replace( '/[^0-9]/', '', $clean_imdb );
	}

	$providers = viko_get_providers( $group );

	// Sort by order key
	usort( $providers, function( $a, $b ) {
		return ( (int) ( $a['order'] ?? 0 ) ) - ( (int) ( $b['order'] ?? 0 ) );
	});

	$tabs = array();
	foreach ( $providers as $p ) {
		if ( empty( $p['enabled'] ) || empty( $p['url'] ) ) {
			continue;
		}
		$url = str_replace(
			array( '{imdb}', '{imdb_id}', '{tmdb}', '{tmdb_id}', '{slug}' ),
			array( $clean_imdb, $clean_imdb, $clean_tmdb, $clean_tmdb, $slug ),
			$p['url']
		);
		$tabs[] = array(
			'label' => $p['label'],
			'url'   => $url,
			'auto'  => true,
		);
	}

	// Guarantee Server 1 is VidSrc ME if no active tabs
	if ( empty( $tabs ) ) {
		$fallback_url = ( 'movie' === $group )
			? "https://vidsrc.me/embed/movie/{$clean_imdb}"
			: "https://vidsrc.me/embed/tv/{$clean_imdb}/{season}/{episode}";
		$tabs[] = array(
			'label' => 'Server 1 (VidSrc ME)',
			'url'   => $fallback_url,
			'auto'  => true,
		);
	}

	return $tabs;
}

/**
 * Ping provider URLs (HEAD requests) to report live/dead servers.
 * Returns [ index => bool ].
 */
function viko_ping_servers( $urls, $timeout = 4 ) {
	$status = array();
	foreach ( $urls as $i => $url ) {
		if ( ! $url ) {
			$status[ $i ] = false;
			continue;
		}
		$res = wp_remote_head(
			$url,
			array(
				'timeout'     => $timeout,
				'sslverify'   => false,
				'redirection' => 2,
			)
		);
		$code         = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		$status[ $i ] = ( $code >= 200 && $code < 500 );
	}
	return $status;
}
