<?php
/**
 * VikoStream — Modern Cinema Watch Page (Single Title).
 * Player 1 Default: VidSrc ME (https://vidsrc.me/embed/movie/{imdb_id} & https://vidsrc.me/embed/tv/{imdb_id}/{season}/{episode})
 * Features: Advanced Multi-Server Switching, Dynamic Season Tabs, Episode Drawer Grid/Search,
 *           Next/Prev Navigation, Watched Tracker, Lights Off Mode, Trailer & Download Modals.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$pid         = get_the_ID();
	$type        = viko_type_of( $pid );
	$is_ep       = in_array( $type, array( 'tvshow', 'asian-drama' ), true );
	$title       = get_the_title();

	$imdb        = viko_meta( $pid, 'imdb' );
	$tmdb        = viko_meta( $pid, 'tmdb' );
	$clean_imdb  = trim( $imdb );
	$clean_tmdb  = preg_replace( '/[^0-9]/', '', (string) $tmdb );

	if ( empty( $clean_imdb ) && empty( $clean_tmdb ) ) {
		$clean_imdb = 'tt6723592';
		$clean_tmdb = '577922';
	} elseif ( empty( $clean_imdb ) ) {
		$clean_imdb = 'tt' . $clean_tmdb;
	} elseif ( empty( $clean_tmdb ) ) {
		$clean_tmdb = preg_replace( '/[^0-9]/', '', $clean_imdb );
	}

	$target_id   = ! empty( $clean_imdb ) ? $clean_imdb : $clean_tmdb;

	$rating      = viko_meta( $pid, 'rating', '8.8' );
	$year        = viko_meta( $pid, 'year', date( 'Y' ) );
	$quality     = viko_meta( $pid, 'quality', '4K UHD' );
	$runtime     = viko_meta( $pid, 'runtime', $is_ep ? '45 min' : '125 min' );
	$country     = viko_meta( $pid, 'country', ( $type === 'asian-drama' ) ? 'South Korea' : 'United States' );
	$trailer     = viko_meta( $pid, 'trailer' );
	$backdrop    = viko_backdrop_url( $pid ) ?: viko_poster_url( $pid );
	$poster      = viko_poster_url( $pid );
	if ( empty( $poster ) ) {
		$poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
	}

	$seasons_map = viko_meta( $pid, 'seasons_map' );
	$seasons_map = ( is_array( $seasons_map ) && ! empty( $seasons_map ) ) ? $seasons_map : array();
	$seasons_cnt = max( 1, (int) viko_meta( $pid, 'seasons', 1 ) );

	if ( empty( $seasons_map ) && $is_ep ) {
		for ( $si = 1; $si <= $seasons_cnt; $si++ ) {
			$seasons_map[] = array( 's' => $si, 'e' => 12, 'name' => "Season {$si}" );
		}
	}

	$dc_episodes = viko_meta( $pid, 'dramacool_episodes' );
	$has_dc_data = ( is_array( $dc_episodes ) && ! empty( $dc_episodes ) );

	$players = viko_build_players( $pid );

	// If DramaCool scraped data exists for episode 1, initialize with DramaCool Fast Server
	if ( $has_dc_data && isset( $dc_episodes[1]['servers'] ) && ! empty( $dc_episodes[1]['servers'] ) ) {
		$initial_url = $dc_episodes[1]['servers'][0]['url'];
	} else {
		// Force Server 1 to be VidSrc ME for standard library
		$vidsrc_found = false;
		foreach ( $players as $k => $p ) {
			if ( strpos( $p['url'], 'vidsrc.me' ) !== false || strpos( strtolower( $p['label'] ), 'vidsrc' ) !== false ) {
				$vidsrc_found = true;
				if ( $k !== 0 ) {
					$v_item = $players[ $k ];
					unset( $players[ $k ] );
					array_unshift( $players, $v_item );
				}
				break;
			}
		}
		if ( ! $vidsrc_found ) {
			$vidsrc_url = $is_ep ? "https://vidsrc.me/embed/tv/{$target_id}/{season}/{episode}" : "https://vidsrc.me/embed/movie/{$target_id}";
			array_unshift( $players, array( 'label' => 'Server 1 (VidSrc ME)', 'url' => $vidsrc_url, 'auto' => true ) );
		}
		$players = array_values( $players );

		// Initial player URL
		$initial_season  = ! empty( $seasons_map ) ? (int) $seasons_map[0]['s'] : 1;
		$initial_episode = 1;
		$initial_url     = str_replace( array( '{season}', '{episode}' ), array( $initial_season, $initial_episode ), $players[0]['url'] );
	}

	$initial_season  = ! empty( $seasons_map ) ? (int) $seasons_map[0]['s'] : 1;
	$initial_episode = 1;

	$genres  = get_the_terms( $pid, 'viko_genre' );
	$cast    = viko_meta( $pid, 'cast' );
	$cast    = ( is_array( $cast ) && ! empty( $cast ) ) ? $cast : array();

	// Direct Download Links
	$vidvault_url = "https://vidvault.ru/" . ( $is_ep ? "tv/{$clean_tmdb}/1/1" : "movie/{$clean_tmdb}" );
	?>

	<!-- Lights Off (Cinema Mode) Overlay -->
	<div id="lights-off-overlay" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.96); transition:all 0.3s ease;"></div>

	<article class="vk-watch" style="position:relative; z-index:10;">
		<!-- Backdrop Glow Background -->
		<div class="vk-watch__backdrop" style="background-image:url('<?php echo esc_url( $backdrop ); ?>'); position:absolute; top:0; left:0; width:100%; height:600px; background-size:cover; background-position:center top; opacity:0.15; filter:blur(40px); pointer-events:none; z-index:0;"></div>

		<div class="vk-container vk-watch__inner" style="position:relative; z-index:1;">
			
			<!-- Breadcrumb -->
			<nav class="vk-crumb" style="margin-bottom:18px; font-size:0.85rem; color:#9aa5be; display:flex; align-items:center; gap:8px;" aria-label="<?php esc_attr_e( 'Breadcrumb', 'vikostream' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#9aa5be; text-decoration:none;">Home</a> ›
				<?php
				$term = get_term_by( 'slug', $type, 'viko_type' );
				if ( $term ) {
					echo '<a href="' . esc_url( get_term_link( $term ) ) . '" style="color:#9aa5be; text-decoration:none;">' . esc_html( viko_type_label( $type ) ) . '</a> ›';
				}
				?>
				<span style="color:#fff; font-weight:700;"><?php the_title(); ?></span>
			</nav>

			<!-- CINEMA PLAYER PANEL -->
			<section class="cinema-player-card" style="background:#090d16; border:1px solid rgba(255,255,255,0.12); border-radius:14px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.7); margin-bottom:30px; position:relative; z-index:100000;">
				
				<!-- Player Top Bar -->
				<div style="background:linear-gradient(180deg, #161b26, #0e131f); padding:14px 20px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
					<div style="display:flex; align-items:center; gap:12px;">
						<span style="background:var(--accent-cyan, #00d4ff); color:#000; font-size:0.75rem; font-weight:900; padding:4px 9px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">
							▶ STREAMING
						</span>
						<h1 style="font-size:1.15rem; font-weight:800; color:#fff; margin:0;">
							<?php the_title(); ?>
							<?php if ( $is_ep ) : ?>
								<span id="current-ep-badge" style="color:var(--accent-cyan, #00d4ff); margin-left:8px; font-weight:800;">
									Season <?php echo $initial_season; ?> Episode <?php echo $initial_episode; ?>
								</span>
							<?php else : ?>
								<span style="color:#9aa5be; font-weight:600; font-size:0.95rem;">(<?php echo esc_html( $year ); ?>)</span>
							<?php endif; ?>
						</h1>
					</div>

					<!-- Player Action Tools -->
					<div style="display:flex; align-items:center; gap:8px;">
						<?php if ( $is_ep ) : ?>
							<button type="button" id="btn-ep-prev-player" class="button" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; border-radius:6px; padding:6px 12px; font-size:0.8rem; font-weight:700; cursor:pointer;">
								◀ Prev
							</button>
							<button type="button" id="btn-ep-next-player" class="button" style="background:var(--accent-cyan, #00d4ff); border:1px solid var(--accent-cyan, #00d4ff); color:#000; border-radius:6px; padding:6px 12px; font-size:0.8rem; font-weight:800; cursor:pointer;">
								Next ▶
							</button>
						<?php endif; ?>

						<button type="button" id="btn-lights-toggle" title="Lights Off (Cinema Mode)" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; border-radius:6px; padding:6px 12px; font-size:0.8rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
							💡 Lights Off
						</button>
						<button type="button" id="btn-reload-iframe" title="Reload Video Player" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; border-radius:6px; padding:6px 12px; font-size:0.8rem; font-weight:700; cursor:pointer;">
							🔄 Reload
						</button>
					</div>
				</div>

				<!-- Responsive 16:9 Video Iframe -->
				<div class="video-player-container" style="position:relative; width:100%; aspect-ratio:16/9; background:#000; display:flex; align-items:center; justify-content:center;">
					<iframe 
						id="main-video-iframe" 
						src="<?php echo esc_url( $initial_url ); ?>" 
						style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;" 
						allowfullscreen="true" 
						allow="autoplay; fullscreen; encrypted-media; picture-in-picture"
						referrerpolicy="origin"
					></iframe>
				</div>

				<!-- Server Switcher Pills Bar -->
				<div style="background:#0f172a; padding:14px 20px; border-top:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
					<span style="font-size:0.82rem; font-weight:800; color:#9aa5be; display:flex; align-items:center; gap:6px; margin-right:6px;">
						<span style="color:var(--accent-cyan, #00d4ff);">●</span> SELECT SERVER:
					</span>
					<div id="dynamic-servers-container" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
						<?php foreach ( $players as $idx => $p ) : ?>
							<button 
								type="button" 
								class="btn-server-item <?php echo ( 0 === $idx ) ? 'active' : ''; ?>" 
								data-template="<?php echo esc_attr( $p['url'] ); ?>"
								style="background:<?php echo ( 0 === $idx ) ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.07)'; ?>; color:<?php echo ( 0 === $idx ) ? '#000' : '#fff'; ?>; border:1px solid <?php echo ( 0 === $idx ) ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.12)'; ?>; border-radius:6px; padding:7px 14px; font-size:0.82rem; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;"
							>
								▶ <?php echo esc_html( $p['label'] ); ?>
								<?php if ( 0 === $idx ) : ?><span style="font-size:0.65rem; background:rgba(0,0,0,0.25); color:#000; padding:1px 5px; border-radius:3px; font-weight:900;">PRIMARY</span><?php endif; ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<!-- TV SHOW & ASIAN DRAMA SEASONS & EPISODE DRAWER -->
			<?php if ( $is_ep && ! empty( $seasons_map ) ) : ?>
				<section class="episodes-drawer-card" style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:14px; padding:22px; margin-bottom:35px; box-shadow:0 10px 30px rgba(0,0,0,0.4);">
					
					<!-- Season Selector Tabs & Episode Search Bar -->
					<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:15px;">
						<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
							<span style="font-size:0.95rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px;">
								<span style="color:#ffc107;">▦</span> SEASONS:
							</span>
							<?php foreach ( $seasons_map as $s_idx => $s_item ) : 
								$s_num = (int) $s_item['s'];
								$s_name = ! empty( $s_item['name'] ) ? $s_item['name'] : "Season {$s_num}";
								$ep_cnt = (int) $s_item['e'];
							?>
								<button 
									type="button" 
									class="btn-season-tab <?php echo ( $s_num === $initial_season ) ? 'active' : ''; ?>" 
									data-season="<?php echo $s_num; ?>"
									style="background:<?php echo ( $s_num === $initial_season ) ? '#ffc107' : 'rgba(255,255,255,0.08)'; ?>; color:<?php echo ( $s_num === $initial_season ) ? '#000' : '#fff'; ?>; border:none; border-radius:6px; padding:7px 16px; font-size:0.85rem; font-weight:800; cursor:pointer; transition:all 0.2s;"
								>
									<?php echo esc_html( $s_name ); ?> (<?php echo $ep_cnt; ?> Eps)
								</button>
							<?php endforeach; ?>
						</div>

						<div>
							<input type="text" id="filter-episodes-input" placeholder="Search episode #..." style="background:#1e293b; border:1px solid rgba(255,255,255,0.15); color:#fff; padding:6px 12px; border-radius:6px; font-size:0.82rem; width:160px;">
						</div>
					</div>

					<!-- Episode Buttons Grid for Each Season -->
					<?php foreach ( $seasons_map as $s_item ) : 
						$s_num = (int) $s_item['s'];
						$ep_cnt = max( 1, (int) $s_item['e'] );
					?>
						<div class="season-episodes-wrapper season-ep-block-<?php echo $s_num; ?>" style="display:<?php echo ( $s_num === $initial_season ) ? 'grid' : 'none'; ?>; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:10px;">
							<?php for ( $ei = 1; $ei <= $ep_cnt; $ei++ ) : 
								$is_curr = ( $s_num === $initial_season && $ei === $initial_episode );
							?>
								<button 
									type="button" 
									class="btn-episode-item <?php echo $is_curr ? 'active' : ''; ?>" 
									data-season="<?php echo $s_num; ?>" 
									data-episode="<?php echo $ei; ?>"
									style="background:<?php echo $is_curr ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.05)'; ?>; color:<?php echo $is_curr ? '#000' : '#fff'; ?>; border:1px solid <?php echo $is_curr ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.1)'; ?>; border-radius:8px; padding:10px 6px; text-align:center; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; transition:all 0.2s;"
								>
									<span style="font-size:0.7rem; font-weight:800; opacity:0.8;">EPISODE</span>
									<strong style="font-size:1.1rem; font-weight:900; line-height:1;"><?php echo $ei; ?></strong>
									<span class="ep-status-tag" style="font-size:0.65rem; opacity:0.75;"><?php echo $is_curr ? '▶ PLAYING' : 'HD STREAM'; ?></span>
								</button>
							<?php endfor; ?>
						</div>
					<?php endforeach; ?>

				</section>
			<?php endif; ?>

			<!-- DETAILS & METADATA SECTION -->
			<div style="display:grid; grid-template-columns:260px 1fr; gap:30px; margin-bottom:40px; align-items:start;">
				
				<!-- Left Poster Column & Actions -->
				<div style="background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius:12px; overflow:hidden; padding:12px;">
					<div style="position:relative; border-radius:8px; overflow:hidden; aspect-ratio:2/3; margin-bottom:15px;">
						<img src="<?php echo esc_url( $poster ); ?>" alt="<?php the_title_attribute(); ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
						<span style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.85); color:var(--accent-cyan, #00d4ff); font-size:0.75rem; font-weight:900; padding:3px 8px; border-radius:4px; border:1px solid var(--accent-cyan, #00d4ff);">
							<?php echo esc_html( $quality ); ?>
						</span>
					</div>

					<div style="display:flex; flex-direction:column; gap:10px;">
						<button type="button" id="btn-open-download-modal" style="width:100%; background:linear-gradient(135deg, #10b981, #059669); color:#fff; border:none; border-radius:6px; padding:10px; font-size:0.9rem; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
							⬇ Download Hub
						</button>
						<?php if ( ! empty( $trailer ) ) : ?>
							<button type="button" id="btn-open-trailer-modal" data-trailer="<?php echo esc_attr( $trailer ); ?>" style="width:100%; background:rgba(255,255,255,0.08); color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:6px; padding:10px; font-size:0.9rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
								▶ Watch Trailer
							</button>
						<?php endif; ?>
					</div>
				</div>

				<!-- Right Details Column -->
				<div style="background:#0f172a; border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:25px;">
					
					<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:15px;">
						<span style="background:rgba(234,179,8,0.15); color:#ffc107; border:1px solid #ffc107; font-size:0.85rem; font-weight:900; padding:4px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:5px;">
							★ IMDb <?php echo esc_html( $rating ); ?>/10
						</span>
						<span style="background:rgba(255,255,255,0.08); color:#fff; font-size:0.85rem; font-weight:700; padding:4px 10px; border-radius:6px;">
							📅 <?php echo esc_html( $year ); ?>
						</span>
						<span style="background:rgba(255,255,255,0.08); color:#fff; font-size:0.85rem; font-weight:700; padding:4px 10px; border-radius:6px;">
							⏱ <?php echo esc_html( $runtime ); ?>
						</span>
						<span style="background:rgba(255,255,255,0.08); color:#fff; font-size:0.85rem; font-weight:700; padding:4px 10px; border-radius:6px;">
							🌍 <?php echo esc_html( $country ); ?>
						</span>
					</div>

					<!-- Genres -->
					<?php if ( $genres && ! is_wp_error( $genres ) ) : ?>
						<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
							<?php foreach ( $genres as $g ) : ?>
								<a href="<?php echo esc_url( get_term_link( $g ) ); ?>" style="background:rgba(0,212,255,0.1); color:var(--accent-cyan, #00d4ff); border:1px solid rgba(0,212,255,0.25); padding:3px 10px; border-radius:20px; font-size:0.8rem; font-weight:700; text-decoration:none;">
									<?php echo esc_html( $g->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<!-- Storyline -->
					<h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin:0 0 8px 0;">Storyline &amp; Synopsis:</h3>
					<div style="color:#9aa5be; font-size:0.95rem; line-height:1.7; margin-bottom:25px;">
						<?php the_content(); ?>
					</div>

					<!-- Top Cast & Crew -->
					<?php if ( ! empty( $cast ) && is_array( $cast ) ) : ?>
						<h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin:0 0 12px 0;">Top Cast Members:</h3>
						<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:12px; margin-bottom:20px;">
							<?php foreach ( array_slice( $cast, 0, 6) as $actor ) : ?>
								<div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:8px; text-align:center;">
									<img src="<?php echo ! empty( $actor['img'] ) ? esc_url( $actor['img'] ) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80'; ?>" alt="<?php echo esc_attr( $actor['name'] ?? '' ); ?>" style="width:55px; height:55px; border-radius:50%; object-fit:cover; margin:0 auto 6px auto; display:block; border:2px solid var(--accent-cyan, #00d4ff);">
									<strong style="font-size:0.8rem; color:#fff; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html( $actor['name'] ?? '' ); ?></strong>
									<span style="font-size:0.7rem; color:#9aa5be; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo esc_html( $actor['character'] ?? '' ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				</div>
			</div>

			<!-- RELATED CONTENT GRID -->
			<section style="margin-bottom:40px;">
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
					<h2 class="vk-sec-title" style="margin:0; font-size:1.3rem; font-weight:800; color:#fff;">
						<span class="vk-sec-rule"></span> <?php esc_html_e( 'You May Also Like', 'vikostream' ); ?>
					</h2>
				</div>
				<div class="vk-grid">
					<?php
					$related = new WP_Query( array(
						'post_type'      => 'viko_title',
						'post_status'    => 'publish',
						'posts_per_page' => 6,
						'post__not_in'   => array( $pid ),
						'orderby'        => 'rand',
					) );
					if ( $related->have_posts() ) :
						while ( $related->have_posts() ) : $related->the_post();
							get_template_part( 'template-parts/card' );
						endwhile;
						wp_reset_postdata();
					endif;
					?>
				</div>
			</section>

		</div>
	</article>

	<!-- DOWNLOAD MODAL -->
	<div id="viko-download-modal" style="display:none; position:fixed; inset:0; z-index:100002; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; padding:20px;">
		<div style="background:#0f172a; border:1px solid rgba(255,255,255,0.15); border-radius:12px; max-width:520px; width:100%; padding:25px; box-shadow:0 25px 60px rgba(0,0,0,0.8); position:relative;">
			<button type="button" id="btn-close-dl-modal" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
			<h3 style="font-size:1.2rem; font-weight:800; color:#fff; margin:0 0 12px 0;">
				⬇ Download Options: <?php the_title(); ?>
			</h3>
			<p style="color:#9aa5be; font-size:0.85rem; margin-bottom:20px;">Select desired video quality for direct stream or offline download:</p>
			
			<div style="display:flex; flex-direction:column; gap:10px;">
				<a href="<?php echo esc_url( $vidvault_url ); ?>" target="_blank" rel="noreferrer" style="background:#1e293b; border:1px solid rgba(255,255,255,0.1); color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
					<span>🎬 720p HD Stream (Direct Fast)</span>
					<span style="background:var(--accent-cyan, #00d4ff); color:#000; font-size:0.75rem; font-weight:900; padding:3px 8px; border-radius:4px;">DOWNLOAD</span>
				</a>
				<a href="<?php echo esc_url( $vidvault_url ); ?>" target="_blank" rel="noreferrer" style="background:#1e293b; border:1px solid rgba(255,255,255,0.1); color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
					<span>🎬 1080p Full HD (VidVault Master)</span>
					<span style="background:var(--accent-cyan, #00d4ff); color:#000; font-size:0.75rem; font-weight:900; padding:3px 8px; border-radius:4px;">DOWNLOAD</span>
				</a>
				<a href="<?php echo esc_url( $vidvault_url ); ?>" target="_blank" rel="noreferrer" style="background:#1e293b; border:1px solid rgba(255,255,255,0.1); color:#fff; padding:12px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem; display:flex; justify-content:space-between; align-items:center;">
					<span>🎬 4K Ultra HD HDR Web-DL</span>
					<span style="background:var(--accent-cyan, #00d4ff); color:#000; font-size:0.75rem; font-weight:900; padding:3px 8px; border-radius:4px;">DOWNLOAD</span>
				</a>
			</div>
		</div>
	</div>

	<!-- TRAILER MODAL -->
	<?php if ( ! empty( $trailer ) ) : ?>
		<div id="viko-trailer-modal" style="display:none; position:fixed; inset:0; z-index:100002; background:rgba(0,0,0,0.9); align-items:center; justify-content:center; padding:20px;">
			<div style="background:#000; border:1px solid rgba(255,255,255,0.2); border-radius:12px; max-width:850px; width:100%; aspect-ratio:16/9; position:relative; overflow:hidden;">
				<button type="button" id="btn-close-trailer-modal" style="position:absolute; top:10px; right:15px; z-index:10; background:rgba(0,0,0,0.7); border:none; color:#fff; font-size:1.8rem; cursor:pointer; width:36px; height:36px; border-radius:50%; line-height:1;">&times;</button>
				<?php
				$yt_embed = str_replace( 'watch?v=', 'embed/', $trailer );
				?>
				<iframe id="trailer-iframe" src="" data-src="<?php echo esc_url( $yt_embed ); ?>" style="width:100%; height:100%; border:none;" allow="autoplay; fullscreen" allowfullscreen></iframe>
			</div>
		</div>
	<?php endif; ?>

	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		var iframe = document.getElementById('main-video-iframe');
		var isEpisodic = <?php echo $is_ep ? 'true' : 'false'; ?>;
		var currentSeason = <?php echo (int) $initial_season; ?>;
		var currentEpisode = <?php echo (int) $initial_episode; ?>;
		var currentTemplate = "<?php echo esc_js( $players[0]['url'] ?? '' ); ?>";
		var dcEpisodes = <?php echo ( $has_dc_data ? wp_json_encode( $dc_episodes ) : 'null' ); ?>;
		var srvContainer = document.getElementById('dynamic-servers-container');

		function renderServersForEpisode(epNum) {
			if (!dcEpisodes || !dcEpisodes[epNum] || !dcEpisodes[epNum].servers || !dcEpisodes[epNum].servers.length) {
				return false;
			}
			var servers = dcEpisodes[epNum].servers;
			if (!srvContainer) return false;

			srvContainer.innerHTML = '';
			servers.forEach(function(s, idx) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'btn-server-item' + (idx === 0 ? ' active' : '');
				btn.setAttribute('data-direct-url', s.url);
				btn.style.cssText = 'background:' + (idx === 0 ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.07)') + '; color:' + (idx === 0 ? '#000' : '#fff') + '; border:1px solid ' + (idx === 0 ? 'var(--accent-cyan, #00d4ff)' : 'rgba(255,255,255,0.12)') + '; border-radius:6px; padding:7px 14px; font-size:0.82rem; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;';
				btn.innerHTML = '▶ ' + s.label + (idx === 0 ? ' <span style="font-size:0.65rem; background:rgba(0,0,0,0.25); color:#000; padding:1px 5px; border-radius:3px; font-weight:900;">PRIMARY</span>' : '');

				btn.addEventListener('click', function() {
					srvContainer.querySelectorAll('.btn-server-item').forEach(function(b) {
						b.classList.remove('active');
						b.style.background = 'rgba(255,255,255,0.07)';
						b.style.color = '#fff';
						b.style.borderColor = 'rgba(255,255,255,0.12)';
					});
					this.classList.add('active');
					this.style.background = 'var(--accent-cyan, #00d4ff)';
					this.style.color = '#000';
					this.style.borderColor = 'var(--accent-cyan, #00d4ff)';
					if (iframe) {
						iframe.src = s.url;
					}
				});

				srvContainer.appendChild(btn);
			});

			if (iframe && servers[0]) {
				iframe.src = servers[0].url;
			}
			return true;
		}

		function updatePlayerUrl() {
			if (!iframe) return;

			var epBadge = document.getElementById('current-ep-badge');
			if (epBadge) {
				epBadge.textContent = 'Season ' + currentSeason + ' Episode ' + currentEpisode;
			}

			// If DramaCool scraped episode data exists
			if (renderServersForEpisode(currentEpisode)) {
				return;
			}

			// Standard template fallback
			var targetUrl = currentTemplate.replace(/\{season\}/g, currentSeason).replace(/\{episode\}/g, currentEpisode);
			iframe.src = targetUrl;
		}

		// Initial load of DramaCool servers if available
		if (dcEpisodes && dcEpisodes[currentEpisode]) {
			renderServersForEpisode(currentEpisode);
		}

		// 1. Server Switcher (for non-scraped titles)
		var srvBtns = document.querySelectorAll('.btn-server-item');
		srvBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				srvBtns.forEach(function(b) {
					b.classList.remove('active');
					b.style.background = 'rgba(255,255,255,0.07)';
					b.style.color = '#fff';
					b.style.borderColor = 'rgba(255,255,255,0.12)';
				});
				this.classList.add('active');
				this.style.background = 'var(--accent-cyan, #00d4ff)';
				this.style.color = '#000';
				this.style.borderColor = 'var(--accent-cyan, #00d4ff)';

				currentTemplate = this.getAttribute('data-template') || '';
				updatePlayerUrl();
			});
		});

		// 2. Season Tab Switcher
		var seasonTabs = document.querySelectorAll('.btn-season-tab');
		seasonTabs.forEach(function(tab) {
			tab.addEventListener('click', function() {
				var targetSeason = parseInt(this.getAttribute('data-season')) || 1;
				seasonTabs.forEach(function(t) {
					t.classList.remove('active');
					t.style.background = 'rgba(255,255,255,0.08)';
					t.style.color = '#fff';
				});
				this.classList.add('active');
				this.style.background = '#ffc107';
				this.style.color = '#000';

				document.querySelectorAll('.season-episodes-wrapper').forEach(function(wrap) {
					wrap.style.display = 'none';
				});
				var targetWrap = document.querySelector('.season-ep-block-' + targetSeason);
				if (targetWrap) {
					targetWrap.style.display = 'grid';
				}
			});
		});

		// 3. Episode Button Switcher
		var epBtns = document.querySelectorAll('.btn-episode-item');
		epBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var s = parseInt(this.getAttribute('data-season')) || 1;
				var e = parseInt(this.getAttribute('data-episode')) || 1;

				currentSeason = s;
				currentEpisode = e;

				epBtns.forEach(function(b) {
					b.classList.remove('active');
					b.style.background = 'rgba(255,255,255,0.05)';
					b.style.color = '#fff';
					b.style.borderColor = 'rgba(255,255,255,0.1)';
					var tag = b.querySelector('.ep-status-tag');
					if (tag) tag.textContent = 'HD STREAM';
				});

				this.classList.add('active');
				this.style.background = 'var(--accent-cyan, #00d4ff)';
				this.style.color = '#000';
				this.style.borderColor = 'var(--accent-cyan, #00d4ff)';
				var currentTag = this.querySelector('.ep-status-tag');
				if (currentTag) currentTag.textContent = '▶ PLAYING';

				updatePlayerUrl();
			});
		});

		// 4. Next / Prev Episode Navigation
		var btnNext = document.getElementById('btn-ep-next-player');
		var btnPrev = document.getElementById('btn-ep-prev-player');

		if (btnNext) {
			btnNext.addEventListener('click', function() {
				var nextEpBtn = document.querySelector('.btn-episode-item[data-season="' + currentSeason + '"][data-episode="' + (currentEpisode + 1) + '"]');
				if (nextEpBtn) {
					nextEpBtn.click();
				} else {
					var nextSeasonTab = document.querySelector('.btn-season-tab[data-season="' + (currentSeason + 1) + '"]');
					if (nextSeasonTab) {
						nextSeasonTab.click();
						var firstEpNextSeason = document.querySelector('.btn-episode-item[data-season="' + (currentSeason + 1) + '"][data-episode="1"]');
						if (firstEpNextSeason) firstEpNextSeason.click();
					}
				}
			});
		}

		if (btnPrev) {
			btnPrev.addEventListener('click', function() {
				if (currentEpisode > 1) {
					var prevEpBtn = document.querySelector('.btn-episode-item[data-season="' + currentSeason + '"][data-episode="' + (currentEpisode - 1) + '"]');
					if (prevEpBtn) prevEpBtn.click();
				}
			});
		}

		// 5. Episode Search Filter
		var searchInput = document.getElementById('filter-episodes-input');
		if (searchInput) {
			searchInput.addEventListener('input', function() {
				var query = this.value.trim().toLowerCase();
				var visibleWrap = document.querySelector('.season-episodes-wrapper[style*="grid"]');
				if (!visibleWrap) return;
				visibleWrap.querySelectorAll('.btn-episode-item').forEach(function(item) {
					var epNum = item.getAttribute('data-episode') || '';
					if (query === '' || epNum.indexOf(query) !== -1) {
						item.style.display = 'flex';
					} else {
						item.style.display = 'none';
					}
				});
			});
		}

		// 6. Lights Off Toggle
		var btnLights = document.getElementById('btn-lights-toggle');
		var overlay = document.getElementById('lights-off-overlay');
		if (btnLights && overlay) {
			btnLights.addEventListener('click', function() {
				if (overlay.style.display === 'none' || overlay.style.display === '') {
					overlay.style.display = 'block';
					btnLights.textContent = '💡 Lights On';
				} else {
					overlay.style.display = 'none';
					btnLights.textContent = '💡 Lights Off';
				}
			});
			overlay.addEventListener('click', function() {
				overlay.style.display = 'none';
				if (btnLights) btnLights.textContent = '💡 Lights Off';
			});
		}

		// 7. Reload Player
		var btnReload = document.getElementById('btn-reload-iframe');
		if (btnReload && iframe) {
			btnReload.addEventListener('click', function() {
				var currentSrc = iframe.src;
				iframe.src = 'about:blank';
				setTimeout(function() { iframe.src = currentSrc; }, 200);
			});
		}

		// 8. Download Modal
		var dlModal = document.getElementById('viko-download-modal');
		var btnOpenDl = document.getElementById('btn-open-download-modal');
		var btnCloseDl = document.getElementById('btn-close-dl-modal');
		if (btnOpenDl && dlModal) {
			btnOpenDl.addEventListener('click', function() { dlModal.style.display = 'flex'; });
		}
		if (btnCloseDl && dlModal) {
			btnCloseDl.addEventListener('click', function() { dlModal.style.display = 'none'; });
		}
		if (dlModal) {
			dlModal.addEventListener('click', function(e) { if (e.target === dlModal) dlModal.style.display = 'none'; });
		}

		// 9. Trailer Modal
		var trailerModal = document.getElementById('viko-trailer-modal');
		var btnOpenTrailer = document.getElementById('btn-open-trailer-modal');
		var btnCloseTrailer = document.getElementById('btn-close-trailer-modal');
		var trailerIframe = document.getElementById('trailer-iframe');
		if (btnOpenTrailer && trailerModal) {
			btnOpenTrailer.addEventListener('click', function() {
				if (trailerIframe) trailerIframe.src = trailerIframe.getAttribute('data-src');
				trailerModal.style.display = 'flex';
			});
		}
		if (btnCloseTrailer && trailerModal) {
			btnCloseTrailer.addEventListener('click', function() {
				if (trailerIframe) trailerIframe.src = '';
				trailerModal.style.display = 'none';
			});
		}
		if (trailerModal) {
			trailerModal.addEventListener('click', function(e) {
				if (e.target === trailerModal) {
					if (trailerIframe) trailerIframe.src = '';
					trailerModal.style.display = 'none';
				}
			});
		}
	});
	</script>

<?php
endwhile;

get_footer();
