<?php
/**
 * VikoStream — title data metabox.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function viko_add_metabox() {
	add_meta_box( 'viko_data', __( 'VikoStream — Title Data', 'vikostream' ), 'viko_metabox_html', 'viko_title', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'viko_add_metabox' );

function viko_metabox_html( $post ) {
	wp_nonce_field( 'viko_metabox', 'viko_metabox_nonce' );
	$f = function ( $k, $d = '' ) use ( $post ) {
		return esc_attr( viko_meta( $post->ID, $k, $d ) );
	};
	$type = viko_type_of( $post->ID );
	$tabs = viko_build_players( $post->ID );
	?>
	<style>.viko-mb{display:grid;grid-template-columns:1fr 1fr;gap:12px 20px}.viko-mb label{display:block;font-weight:600;font-size:12px;margin-bottom:4px}.viko-mb input,.viko-mb select,.viko-mb textarea{width:100%}</style>
	<div class="viko-mb">
		<div>
			<label><?php esc_html_e( 'Type', 'vikostream' ); ?></label>
			<?php foreach ( array( 'movie', 'tvshow', 'asian-drama' ) as $t ) : ?>
				<label style="display:inline-block;font-weight:400;margin-right:14px">
					<input type="radio" name="viko_type" value="<?php echo esc_attr( $t ); ?>" <?php checked( $type, $t ); ?>>
					<?php echo esc_html( viko_type_label( $t ) ); ?>
				</label>
			<?php endforeach; ?>
			<p class="description" style="margin-top:6px">
				<?php esc_html_e( 'Sasa:', 'vikostream' ); ?> <strong><?php echo esc_html( viko_type_label( $type ) ); ?></strong>
				— <?php esc_html_e( 'chagua na u-save, itaonekana kwenye block husika ya homepage.', 'vikostream' ); ?>
			</p>
		</div>
		<div>
			<label><input type="checkbox" name="viko_recommended" value="1" <?php checked( viko_meta( $post->ID, 'recommended' ), 1 ); ?>>
			★ <?php esc_html_e( 'Recommended (itaonekana kwenye block ya Recommended + slider)', 'vikostream' ); ?></label>
		</div>
		<div style="grid-column:1/-1">
			<label><?php esc_html_e( 'Genres (chagua zinazofaa):', 'vikostream' ); ?></label>
			<div style="display:flex;flex-wrap:wrap;gap:4px 16px">
				<?php
				$cur_genres = wp_get_post_terms( $post->ID, 'viko_genre', array( 'fields' => 'slugs' ) );
				$cur_genres = is_wp_error( $cur_genres ) ? array() : $cur_genres;
				$all_genres = get_terms( array( 'taxonomy' => 'viko_genre', 'hide_empty' => false ) );
				if ( $all_genres && ! is_wp_error( $all_genres ) ) {
					foreach ( $all_genres as $g ) {
						echo '<label style="display:inline-block;font-weight:400;margin:2px 0"><input type="checkbox" name="viko_genres[]" value="' . esc_attr( $g->slug ) . '" ' . checked( in_array( $g->slug, $cur_genres, true ), true, false ) . '> ' . esc_html( $g->name ) . '</label>';
					}
				}
				?>
			</div>
		</div>
		<div><label>IMDb ID</label><input type="text" name="viko_imdb" value="<?php echo $f( 'imdb' ); ?>" placeholder="tt0137523"></div>
		<div><label>TMDB ID</label><input type="text" name="viko_tmdb" value="<?php echo $f( 'tmdb' ); ?>"></div>
		<div><label><?php esc_html_e( 'Year', 'vikostream' ); ?></label><input type="text" name="viko_year" value="<?php echo $f( 'year' ); ?>"></div>
		<div><label><?php esc_html_e( 'Rating (0–10)', 'vikostream' ); ?></label><input type="text" name="viko_rating" value="<?php echo $f( 'rating' ); ?>"></div>
		<div><label><?php esc_html_e( 'Runtime (min)', 'vikostream' ); ?></label><input type="text" name="viko_runtime" value="<?php echo $f( 'runtime' ); ?>"></div>
		<div><label><?php esc_html_e( 'Seasons (TV/drama)', 'vikostream' ); ?></label><input type="text" name="viko_seasons" value="<?php echo $f( 'seasons' ); ?>"></div>
		<div><label><?php esc_html_e( 'Country', 'vikostream' ); ?></label><input type="text" name="viko_country" value="<?php echo $f( 'country' ); ?>"></div>
		<div>
			<label><?php esc_html_e( 'Quality', 'vikostream' ); ?></label>
			<select name="viko_quality">
				<?php foreach ( array( 'CAM', 'HD', 'FHD', '4K' ) as $qq ) : ?>
					<option value="<?php echo esc_attr( $qq ); ?>" <?php selected( viko_meta( $post->ID, 'quality', 'HD' ), $qq ); ?>><?php echo esc_html( $qq ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div><label><?php esc_html_e( 'Trailer URL', 'vikostream' ); ?></label><input type="text" name="viko_trailer" value="<?php echo $f( 'trailer' ); ?>"></div>
		<div><label><?php esc_html_e( 'Drama slug (kwa DramaCool)', 'vikostream' ); ?></label><input type="text" name="viko_drama_slug" value="<?php echo $f( 'drama_slug' ); ?>" placeholder="queen-of-tears"></div>

		<?php if ( in_array( $type, array( 'tvshow', 'asian-drama' ), true ) ) : ?>
			<div style="grid-column:1/-1;border:1px dashed #c3c4c7;padding:12px 14px;background:#f6f7f7">
				<strong>📺 <?php esc_html_e( 'Seasons & Episodes', 'vikostream' ); ?></strong>
				<?php
				$map = viko_meta( $post->ID, 'seasons_map' );
				if ( is_array( $map ) && $map ) {
					$tot = array_sum( array_map( fn( $s ) => (int) $s['e'], $map ) );
					echo ' — <span style="color:#00a32a">' . esc_html( count( $map ) ) . ' seasons · ' . esc_html( $tot ) . ' episodes ✓</span>';
				} else {
					echo ' — <span style="color:#d63638">' . esc_html__( 'bado haija-sync', 'vikostream' ) . '</span>';
				}
				?>
				<p style="margin:8px 0 0">
					<button type="button" class="button button-secondary" id="viko-sync-eps" data-post="<?php echo esc_attr( $post->ID ); ?>">
						⟳ <?php esc_html_e( 'Sync Seasons / Episodes / Cast (TMDB)', 'vikostream' ); ?>
					</button>
					<span id="viko-sync-result"></span>
				</p>
			</div>
		<?php endif; ?>

		<div style="grid-column:1/-1">
			<label><?php esc_html_e( 'Manual players (hiari — mstari mmoja = server mmoja)', 'vikostream' ); ?> — <code>Label|https://…</code></label>
			<textarea name="viko_players" rows="4" placeholder="Server 1|https://vidsrc.xyz/embed/movie/tt0137523&#10;DramaCool|https://embtaku.pro/streaming.php?id=my-drama-episode-1"><?php echo esc_textarea( viko_meta( $post->ID, 'players' ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Acha tupu kutumia auto-servers kutoka Settings.', 'vikostream' ); ?></p>
		</div>
		<div style="grid-column:1/-1">
			<strong><?php esc_html_e( 'Auto servers kwa title hii:', 'vikostream' ); ?></strong>
			<ul style="margin:6px 0">
				<?php foreach ( $tabs as $t ) : ?>
					<li><code style="font-size:11px"><?php echo esc_html( $t['label'] . ' → ' . $t['url'] ); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="button" id="viko-test-servers" data-urls="<?php echo esc_attr( wp_json_encode( wp_list_pluck( $tabs, 'url' ) ) ); ?>">
				<?php esc_html_e( 'Test servers (ping)', 'vikostream' ); ?>
			</button>
			<span id="viko-test-result"></span>
		</div>
	</div>
	<?php
}

function viko_save_metabox( $post_id ) {
	if ( ! isset( $_POST['viko_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['viko_metabox_nonce'] ), 'viko_metabox' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'viko_imdb' => 'imdb', 'viko_tmdb' => 'tmdb', 'viko_year' => 'year',
		'viko_rating' => 'rating', 'viko_runtime' => 'runtime', 'viko_seasons' => 'seasons',
		'viko_country' => 'country', 'viko_quality' => 'quality', 'viko_trailer' => 'trailer',
		'viko_drama_slug' => 'drama_slug', 'viko_players' => 'players',
	);
	foreach ( $fields as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_viko_' . $key, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
	update_post_meta( $post_id, '_viko_recommended', isset( $_POST['viko_recommended'] ) ? 1 : 0 );

	/* ---- type: accept radio (string), old checkboxes (array), or core tax_input ---- */
	$type_value = null;
	if ( isset( $_POST['viko_type'] ) ) {
		$raw = wp_unslash( $_POST['viko_type'] );
		if ( is_array( $raw ) ) {
			$picked = array_keys( array_filter( $raw ) );
			$type_value = $picked ? sanitize_key( $picked[0] ) : null;
		} elseif ( '' !== $raw ) {
			$type_value = sanitize_key( $raw );
		}
	} elseif ( isset( $_POST['tax_input']['viko_type'] ) && is_array( $_POST['tax_input']['viko_type'] ) ) {
		$ids = array_filter( array_map( 'intval', wp_unslash( $_POST['tax_input']['viko_type'] ) ) );
		if ( $ids ) {
			$term = get_term( (int) reset( $ids ), 'viko_type' );
			if ( $term && ! is_wp_error( $term ) ) {
				$type_value = $term->slug;
			}
		}
	}
	if ( $type_value ) {
		viko_assign_type( $post_id, $type_value );
	}

	/* genres are fully owned by this metabox (core boxes suppressed) */
	$genres = isset( $_POST['viko_genres'] ) && is_array( $_POST['viko_genres'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['viko_genres'] ) )
		: array();
	viko_assign_genres( $post_id, $genres );
}
add_action( 'save_post_viko_title', 'viko_save_metabox' );
