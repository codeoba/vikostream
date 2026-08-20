<?php
/**
 * VikoStream — import tool UI.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$has_key = (bool) get_option( 'viko_tmdb_key' );
?>
<div class="wrap viko-admin viko-import">
	<h1><?php esc_html_e( 'Viko Import — TMDB Auto-Importer', 'vikostream' ); ?></h1>

	<?php if ( ! $has_key ) : ?>
		<div class="notice notice-error"><p>
			<strong><?php esc_html_e( 'TMDB API key inahitajika!', 'vikostream' ); ?></strong>
			<?php esc_html_e( 'Nenda', 'vikostream' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=viko-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'vikostream' ); ?></a>
			<?php esc_html_e( 'uweke key yako ya bure kutoka themoviedb.org.', 'vikostream' ); ?>
		</p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper viko-tabs">
		<a href="#tab-search" class="nav-tab nav-tab-active"><?php esc_html_e( '🔎 Search', 'vikostream' ); ?></a>
		<a href="#tab-discover" class="nav-tab"><?php esc_html_e( '🎬 By Genre / Year', 'vikostream' ); ?></a>
		<a href="#tab-drama" class="nav-tab"><?php esc_html_e( '🏮 Asian Drama URL Scraper', 'vikostream' ); ?></a>
		<a href="#tab-bulk" class="nav-tab"><?php esc_html_e( '📦 Bulk Import', 'vikostream' ); ?></a>
		<a href="#tab-log" class="nav-tab"><?php esc_html_e( '📜 Log', 'vikostream' ); ?></a>
	</nav>

	<div class="viko-toolbar">
		<label class="viko-reco-toggle">
			<input type="checkbox" id="viko-reco"> <span><?php esc_html_e( '★ Weka imports kama Recommended', 'vikostream' ); ?></span>
		</label>
		<button class="button" id="viko-repair" title="<?php esc_attr_e( 'Hurekebisha types na husynci seasons/episodes kwa titles zote zilizopo', 'vikostream' ); ?>">
			🔧 <?php esc_html_e( 'Repair types + episodes (titles zote)', 'vikostream' ); ?>
		</button>
		<span id="viko-status" class="viko-status"></span>
	</div>

	<!-- SEARCH -->
	<section id="tab-search" class="viko-tab-panel">
		<p class="description"><?php esc_html_e( 'Tafuta kwa jina, au weka IMDb ID moja kwa moja (mfano: tt0137523).', 'vikostream' ); ?></p>
		<div class="viko-searchbar">
			<input type="text" id="viko-q" placeholder="<?php esc_attr_e( 'Mfano: Oppenheimer, Queen of Tears, tt0137523…', 'vikostream' ); ?>" <?php disabled( ! $has_key ); ?>>
			<button class="button button-primary" id="viko-do-search"><?php esc_html_e( 'Tafuta', 'vikostream' ); ?></button>
		</div>
		<div id="viko-results" class="viko-results"></div>
		<div class="viko-pager" id="viko-pager"></div>
	</section>

	<!-- DISCOVER -->
	<section id="tab-discover" class="viko-tab-panel" hidden>
		<p class="description"><?php esc_html_e( 'Discover kwa genre, mwaka na aina.', 'vikostream' ); ?></p>
		<div class="viko-searchbar viko-discover">
			<select id="viko-d-type">
				<option value="movie"><?php esc_html_e( 'Movies', 'vikostream' ); ?></option>
				<option value="tv"><?php esc_html_e( 'TV Shows', 'vikostream' ); ?></option>
				<option value="asian"><?php esc_html_e( 'Asian Dramas (Korea, China, Japan…)', 'vikostream' ); ?></option>
			</select>
			<select id="viko-d-genre">
				<option value="0"><?php esc_html_e( '— Genre zote —', 'vikostream' ); ?></option>
				<?php
				$genre_map = array(
					28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy', 80 => 'Crime',
					18 => 'Drama', 10751 => 'Family', 14 => 'Fantasy', 36 => 'History', 27 => 'Horror',
					9648 => 'Mystery', 10749 => 'Romance', 878 => 'Sci-Fi', 53 => 'Thriller', 10752 => 'War',
				);
				foreach ( $genre_map as $gid => $gname ) {
					echo '<option value="' . esc_attr( $gid ) . '">' . esc_html( $gname ) . '</option>';
				}
				?>
			</select>
			<input type="number" id="viko-d-year" min="1950" max="2030" placeholder="<?php esc_attr_e( 'Mwaka (mfano 2024)', 'vikostream' ); ?>" style="width:150px">
			<button class="button button-primary" id="viko-do-discover"><?php esc_html_e( 'Discover', 'vikostream' ); ?></button>
		</div>
		<div id="viko-results-d" class="viko-results"></div>
		<div class="viko-pager" id="viko-pager-d"></div>
	</section>

	<!-- ASIAN DRAMA DIRECT URL SCRAPER -->
	<section id="tab-drama" class="viko-tab-panel" hidden>
		<h2><?php esc_html_e( '🏮 Direct Asian Drama / DramaCool URL Importer', 'vikostream' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Weka link ya ukurasa wa drama yoyote kutoka DramaCool au mirror sources zake ili ku-import taarifa zote na episodes zote automatically.', 'vikostream' ); ?>
		</p>
		<div class="viko-searchbar" style="max-width:850px;">
			<input type="url" id="viko-drama-url" placeholder="https://dramacool9.com.ro/drama-detail/queen-of-tears.html..." style="flex:1;">
			<button class="button button-primary" id="viko-do-import-drama">
				<span class="dashicons dashicons-download" style="vertical-align:middle;"></span> <?php esc_html_e( 'Scrape & Import Drama', 'vikostream' ); ?>
			</button>
		</div>
		<div id="viko-drama-status" style="margin-top:15px;"></div>
	</section>

	<!-- BULK -->
	<section id="tab-bulk" class="viko-tab-panel" hidden>
		<h2><?php esc_html_e( 'Njia 1 — Chagua kutoka kwenye matokeo', 'vikostream' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Kwenye Search/Discover weka checkboxes kisha bofya:', 'vikostream' ); ?>
			<button class="button" id="viko-import-selected" disabled><?php esc_html_e( 'Import selected (0)', 'vikostream' ); ?></button>
			<button class="button" id="viko-import-page" disabled><?php esc_html_e( 'Import ukurasa mzima', 'vikostream' ); ?></button>
		</p>
		<div id="viko-bulk-progress" class="viko-progress" hidden><div class="viko-progress__bar"></div></div>
		<div id="viko-bulk-summary"></div>

		<h2 style="margin-top:28px"><?php esc_html_e( 'Njia 2 — Bandika orodha (jina au IMDb ID, moja kwa mstari)', 'vikostream' ); ?></h2>
		<textarea id="viko-bulk-list" rows="8" placeholder="Oppenheimer&#10;Dune: Part Two&#10;tt0137523&#10;Queen of Tears"></textarea>
		<p><button class="button button-primary" id="viko-bulk-go"><?php esc_html_e( 'Resolve + Import orodha', 'vikostream' ); ?></button></p>
		<div id="viko-bulk-list-summary"></div>
	</section>

	<!-- LOG -->
	<section id="tab-log" class="viko-tab-panel" hidden>
		<p><button class="button" id="viko-log-refresh"><?php esc_html_e( 'Refresh', 'vikostream' ); ?></button>
		<button class="button" id="viko-log-clear"><?php esc_html_e( 'Clear log', 'vikostream' ); ?></button></p>
		<table class="widefat striped" id="viko-log-table" style="max-width:900px">
			<thead><tr><th><?php esc_html_e( 'Wakati', 'vikostream' ); ?></th><th><?php esc_html_e( 'Title', 'vikostream' ); ?></th><th><?php esc_html_e( 'Hali', 'vikostream' ); ?></th></tr></thead>
			<tbody></tbody>
		</table>
	</section>
</div>
