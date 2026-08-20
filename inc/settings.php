<?php
/**
 * VikoStream — Settings & Server Embed Manager.
 * Fully editable & rearrangeable server settings, Asian drama domains, and TMDB configuration.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function viko_settings_menu() {
	add_submenu_page(
		'edit.php?post_type=viko_title',
		__( 'VikoStream Settings & Server Manager', 'vikostream' ),
		__( '⚙ Settings & Servers', 'vikostream' ),
		'manage_options',
		'viko-settings',
		'viko_settings_page'
	);
}
add_action( 'admin_menu', 'viko_settings_menu' );

function viko_settings_save() {
	if ( ! isset( $_POST['viko_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['viko_settings_nonce'] ), 'viko_settings' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle Reset to Defaults
	if ( isset( $_POST['viko_reset_servers'] ) ) {
		foreach ( array( 'movie', 'tv', 'asian-drama' ) as $grp ) {
			delete_option( 'viko_server_list_' . $grp );
			delete_option( 'viko_providers_' . $grp );
			delete_option( 'viko_custom_providers_' . $grp );
		}
		add_settings_error( 'viko', 'reset', __( 'Server list zimerudishwa kwenye mpangilio chaguomsingi (VidSrc ME = Server 1) ✓', 'vikostream' ), 'success' );
		return;
	}

	update_option( 'viko_tmdb_key', sanitize_text_field( wp_unslash( $_POST['viko_tmdb_key'] ?? '' ) ) );
	update_option( 'viko_default_quality', sanitize_text_field( wp_unslash( $_POST['viko_default_quality'] ?? 'HD' ) ) );
	update_option( 'viko_autoreco', isset( $_POST['viko_autoreco'] ) ? 1 : 0 );
	update_option(
		'viko_drama_countries',
		sanitize_text_field( wp_unslash( $_POST['viko_drama_countries'] ?? 'KR,CN,JP,TW,TH,ID,VN' ) )
	);
	update_option(
		'viko_dramacool_domain',
		esc_url_raw( wp_unslash( $_POST['viko_dramacool_domain'] ?? 'https://dramacool9.com.ro' ) )
	);

	// Save Editable & Rearrangeable Servers per group
	foreach ( array( 'movie', 'tv', 'asian-drama' ) as $group ) {
		if ( isset( $_POST['servers'][ $group ] ) && is_array( $_POST['servers'][ $group ] ) ) {
			$raw_servers = wp_unslash( $_POST['servers'][ $group ] );
			$cleaned_servers = array();

			foreach ( $raw_servers as $idx => $srv ) {
				$label = sanitize_text_field( $srv['label'] ?? '' );
				$url   = esc_url_raw( trim( $srv['url'] ?? '' ) );
				if ( empty( $label ) || empty( $url ) ) {
					continue;
				}
				$order = isset( $srv['order'] ) ? (int) $srv['order'] : ( $idx + 1 );
				$enabled = ! empty( $srv['enabled'] ) ? 1 : 0;
				$id    = sanitize_key( $srv['id'] ?? ( 'srv_' . substr( md5( $label . $url ), 0, 6 ) ) );

				$cleaned_servers[] = array(
					'id'      => $id,
					'label'   => $label,
					'url'     => $url,
					'enabled' => $enabled,
					'order'   => $order,
				);
			}

			// Sort by order
			usort( $cleaned_servers, function( $a, $b ) {
				return $a['order'] - $b['order'];
			});

			update_option( 'viko_server_list_' . $group, $cleaned_servers );
		}
	}

	add_settings_error( 'viko', 'saved', __( 'Settings na Servers zimehifadhiwa kikamilifu ✓', 'vikostream' ), 'success' );
}
add_action( 'admin_init', 'viko_settings_save' );

function viko_settings_page() {
	settings_errors( 'viko' );
	$key            = get_option( 'viko_tmdb_key', '' );
	$quality        = get_option( 'viko_default_quality', 'HD' );
	$autoreco       = get_option( 'viko_autoreco', 0 );
	$countries      = get_option( 'viko_drama_countries', 'KR,CN,JP,TW,TH,ID,VN' );
	$drama_domain   = get_option( 'viko_dramacool_domain', 'https://dramacool9.com.ro' );

	$groups = array(
		'movie'       => array( 'title' => __( '🎬 Movie Embed Servers', 'vikostream' ), 'hint' => '{imdb}, {tmdb}' ),
		'tv'          => array( 'title' => __( '📺 TV Show Embed Servers', 'vikostream' ), 'hint' => '{imdb}, {tmdb}, {season}, {episode}' ),
		'asian-drama' => array( 'title' => __( '🏮 Asian Drama Embed Servers', 'vikostream' ), 'hint' => '{imdb}, {tmdb}, {slug}, {season}, {episode}' ),
	);
	?>
	<div class="wrap viko-admin" style="max-width:1300px;">
		<h1 style="display:flex; align-items:center; gap:10px;">
			<span class="dashicons dashicons-admin-generic" style="font-size:32px; width:32px; height:32px; color:#00d4ff;"></span>
			<?php esc_html_e( 'VikoStream — Settings & Server Embed Manager', 'vikostream' ); ?>
		</h1>
		<p class="description" style="margin-bottom:20px; font-size:14px;">
			<?php esc_html_e( 'Weka API keys, badilisha na kupanga upya (reorder) servers zote za movies, tv shows na asian dramas, na badili domain ya DramaCool.', 'vikostream' ); ?>
		</p>

		<form method="post" id="viko-settings-form">
			<?php wp_nonce_field( 'viko_settings', 'viko_settings_nonce' ); ?>

			<!-- General Settings Card -->
			<div class="viko-card" style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; margin-bottom:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
				<h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; font-size:1.2rem;">
					<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'General & API Configuration', 'vikostream' ); ?>
				</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th style="width:250px;"><label for="viko_tmdb_key"><?php esc_html_e( 'TMDB API Key (v3)', 'vikostream' ); ?></label></th>
						<td>
							<input type="text" id="viko_tmdb_key" name="viko_tmdb_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text code" style="width:400px;" placeholder="e.g. 8b2a7c4e…">
							<a href="https://www.themoviedb.org/settings/api" target="_blank" class="button" style="margin-left:8px;"><?php esc_html_e( 'Pata Key Bure ↗', 'vikostream' ); ?></a>
							<?php if ( ! $key ) : ?>
								<p class="description" style="color:#d63638; font-weight:bold; margin-top:5px;"><?php esc_html_e( '⚠ TMDB API key inahitajika kwa ajili ya ku-import taarifa, seasons na episodes.', 'vikostream' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Default Quality Badge', 'vikostream' ); ?></label></th>
						<td>
							<select name="viko_default_quality">
								<?php foreach ( array( '4K UHD', 'FHD', 'HD', 'CAM' ) as $qq ) : ?>
									<option value="<?php echo esc_attr( $qq ); ?>" <?php selected( $quality, $qq ); ?>><?php echo esc_html( $qq ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Auto-mark as Recommended', 'vikostream' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="viko_autoreco" value="1" <?php checked( $autoreco, 1 ); ?>> <?php esc_html_e( 'Weka kila title inayoingizwa kuwa ★ Recommended kwenye homepage', 'vikostream' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Asian Drama Country Codes', 'vikostream' ); ?></label></th>
						<td>
							<input type="text" name="viko_drama_countries" value="<?php echo esc_attr( $countries ); ?>" class="regular-text code" style="width:350px;">
							<p class="description"><?php esc_html_e( 'Series kutoka nchi hizi zitaingia automatically kama Asian Drama (KR = Korea, CN = China, JP = Japan, TH = Thailand).', 'vikostream' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="viko_dramacool_domain"><?php esc_html_e( 'Asian Drama Source Domain', 'vikostream' ); ?></label></th>
						<td>
							<input type="url" id="viko_dramacool_domain" name="viko_dramacool_domain" value="<?php echo esc_attr( $drama_domain ); ?>" class="regular-text code" style="width:400px;" required>
							<div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
								<span style="font-size:12px; color:#666;">⚡ Quick Mirror Presets:</span>
								<button type="button" class="button button-small btn-domain-preset" data-url="https://dramacool9.com.ro">dramacool9.com.ro</button>
								<button type="button" class="button button-small btn-domain-preset" data-url="https://dramacool.ch">dramacool.ch</button>
								<button type="button" class="button button-small btn-domain-preset" data-url="https://dramacool.sr">dramacool.sr</button>
								<button type="button" class="button button-small btn-domain-preset" data-url="https://asianc.to">asianc.to</button>
								<button type="button" class="button button-small btn-domain-preset" data-url="https://watchasia.to">watchasia.to</button>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Dynamic Server Manager Cards for Movie, TV, Drama -->
			<?php foreach ( $groups as $group => $gdata ) : 
				$providers = viko_get_providers( $group );
				// Sort by order
				usort( $providers, function( $a, $b ) {
					return ( (int) ( $a['order'] ?? 0 ) ) - ( (int) ( $b['order'] ?? 0 ) );
				});
			?>
			<div class="viko-card server-group-card" data-group="<?php echo esc_attr( $group ); ?>" style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; margin-bottom:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:12px; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
					<div>
						<h2 style="margin:0; font-size:1.2rem; color:#1d2327;">
							<?php echo esc_html( $gdata['title'] ); ?>
						</h2>
						<p class="description" style="margin:4px 0 0 0;">
							<?php esc_html_e( 'Server ya kwanza hapa ndiyo itakuwa Player ya Kwanza (Primary). Placeholders unazoweza kutumia:', 'vikostream' ); ?>
							<code style="color:#0073aa; font-weight:bold;"><?php echo esc_html( $gdata['hint'] ); ?></code>
						</p>
					</div>
					<button type="button" class="button button-secondary btn-add-server" data-group="<?php echo esc_attr( $group ); ?>">
						<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span> <?php esc_html_e( '+ Ongeza Server Mpya', 'vikostream' ); ?>
					</button>
				</div>

				<table class="widefat striped server-table" id="server-table-<?php echo esc_attr( $group ); ?>">
					<thead>
						<tr>
							<th style="width:70px; text-align:center;"><?php esc_html_e( 'Order', 'vikostream' ); ?></th>
							<th style="width:60px; text-align:center;"><?php esc_html_e( 'Active', 'vikostream' ); ?></th>
							<th style="width:220px;"><?php esc_html_e( 'Server Label', 'vikostream' ); ?></th>
							<th><?php esc_html_e( 'Player URL Pattern', 'vikostream' ); ?></th>
							<th style="width:130px; text-align:center;"><?php esc_html_e( 'Panga (Move)', 'vikostream' ); ?></th>
							<th style="width:70px; text-align:center;"><?php esc_html_e( 'Futa', 'vikostream' ); ?></th>
						</tr>
					</thead>
					<tbody class="server-tbody">
						<?php foreach ( $providers as $idx => $p ) : 
							$p_order = $idx + 1;
							$p_id = ! empty( $p['id'] ) ? $p['id'] : 'srv_' . $idx;
						?>
						<tr class="server-row">
							<td style="text-align:center;">
								<span class="order-badge" style="background:#f0f0f1; border:1px solid #ccc; font-weight:bold; padding:3px 8px; border-radius:4px; font-size:12px;">
									#<span class="order-num"><?php echo $p_order; ?></span>
								</span>
								<input type="hidden" class="input-order" name="servers[<?php echo esc_attr( $group ); ?>][<?php echo $idx; ?>][order]" value="<?php echo $p_order; ?>">
								<input type="hidden" name="servers[<?php echo esc_attr( $group ); ?>][<?php echo $idx; ?>][id]" value="<?php echo esc_attr( $p_id ); ?>">
							</td>
							<td style="text-align:center;">
								<input type="checkbox" name="servers[<?php echo esc_attr( $group ); ?>][<?php echo $idx; ?>][enabled]" value="1" <?php checked( ! empty( $p['enabled'] ) ); ?>>
							</td>
							<td>
								<input type="text" class="widefat input-label" name="servers[<?php echo esc_attr( $group ); ?>][<?php echo $idx; ?>][label]" value="<?php echo esc_attr( $p['label'] ); ?>" required style="font-weight:600;">
							</td>
							<td>
								<input type="text" class="widefat code input-url" name="servers[<?php echo esc_attr( $group ); ?>][<?php echo $idx; ?>][url]" value="<?php echo esc_attr( $p['url'] ); ?>" required style="font-size:12px;">
							</td>
							<td style="text-align:center;">
								<button type="button" class="button button-small btn-move-up" title="Peleka Juu">▲</button>
								<button type="button" class="button button-small btn-move-down" title="Peleka Chini">▼</button>
							</td>
							<td style="text-align:center;">
								<button type="button" class="button button-small button-link-delete btn-remove-server" title="Futa Server">✕</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endforeach; ?>

			<!-- Submit & Reset Action Bar -->
			<div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; flex-wrap:wrap; gap:10px;">
				<button type="submit" name="viko_save_all" class="button button-primary button-hero" style="font-size:16px; font-weight:bold;">
					<span class="dashicons dashicons-saved" style="vertical-align:middle;"></span> <?php esc_html_e( 'Hifadhi Mabadiliko Yote (Save All)', 'vikostream' ); ?>
				</button>
				<button type="submit" name="viko_reset_servers" class="button button-secondary" onclick="return confirm('Je, una uhakika unataka kurudisha servers zote kwenye default (VidSrc ME = Server 1)?');" style="color:#d63638; border-color:#d63638;">
					<span class="dashicons dashicons-image-rotate" style="vertical-align:middle;"></span> <?php esc_html_e( 'Reset Servers to Default', 'vikostream' ); ?>
				</button>
			</div>
		</form>
	</div>

	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		// 1. Mirror Preset Switcher
		document.querySelectorAll('.btn-domain-preset').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var input = document.getElementById('viko_dramacool_domain');
				if (input) {
					input.value = this.getAttribute('data-url');
					input.focus();
				}
			});
		});

		// 2. Re-indexing rows helper
		function reindexGroup(tbody, group) {
			var rows = tbody.querySelectorAll('.server-row');
			rows.forEach(function(row, idx) {
				var orderNum = idx + 1;
				var badge = row.querySelector('.order-num');
				if (badge) badge.textContent = orderNum;

				var orderInput = row.querySelector('.input-order');
				if (orderInput) {
					orderInput.value = orderNum;
					orderInput.name = 'servers[' + group + '][' + idx + '][order]';
				}
				var idInput = row.querySelector('input[type="hidden"]:not(.input-order)');
				if (idInput) idInput.name = 'servers[' + group + '][' + idx + '][id]';

				var check = row.querySelector('input[type="checkbox"]');
				if (check) check.name = 'servers[' + group + '][' + idx + '][enabled]';

				var label = row.querySelector('.input-label');
				if (label) label.name = 'servers[' + group + '][' + idx + '][label]';

				var url = row.querySelector('.input-url');
				if (url) url.name = 'servers[' + group + '][' + idx + '][url]';
			});
		}

		// 3. Move Up / Down Handlers
		document.querySelectorAll('.server-tbody').forEach(function(tbody) {
			var card = tbody.closest('.server-group-card');
			var group = card ? card.getAttribute('data-group') : 'movie';

			tbody.addEventListener('click', function(e) {
				var row = e.target.closest('.server-row');
				if (!row) return;

				if (e.target.classList.contains('btn-move-up')) {
					var prev = row.previousElementSibling;
					if (prev && prev.classList.contains('server-row')) {
						tbody.insertBefore(row, prev);
						reindexGroup(tbody, group);
					}
				} else if (e.target.classList.contains('btn-move-down')) {
					var next = row.nextElementSibling;
					if (next && next.classList.contains('server-row')) {
						tbody.insertBefore(next, row);
						reindexGroup(tbody, group);
					}
				} else if (e.target.classList.contains('btn-remove-server')) {
					if (tbody.querySelectorAll('.server-row').length > 1) {
						row.remove();
						reindexGroup(tbody, group);
					} else {
						alert('Huwezi kufuta server yote! Lazima ubakiwe na angalau server 1.');
					}
				}
			});
		});

		// 4. Add New Server Row
		document.querySelectorAll('.btn-add-server').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var group = this.getAttribute('data-group');
				var table = document.getElementById('server-table-' + group);
				if (!table) return;
				var tbody = table.querySelector('.server-tbody');
				var rowCount = tbody.querySelectorAll('.server-row').length;
				var newOrder = rowCount + 1;

				var tr = document.createElement('tr');
				tr.className = 'server-row';
				tr.innerHTML = '<td style="text-align:center;"><span class="order-badge" style="background:#f0f0f1; border:1px solid #ccc; font-weight:bold; padding:3px 8px; border-radius:4px; font-size:12px;">#<span class="order-num">' + newOrder + '</span></span><input type="hidden" class="input-order" name="servers[' + group + '][' + rowCount + '][order]" value="' + newOrder + '"><input type="hidden" name="servers[' + group + '][' + rowCount + '][id]" value="custom_' + Date.now() + '"></td>' +
					'<td style="text-align:center;"><input type="checkbox" name="servers[' + group + '][' + rowCount + '][enabled]" value="1" checked></td>' +
					'<td><input type="text" class="widefat input-label" name="servers[' + group + '][' + rowCount + '][label]" value="Server ' + newOrder + ' (Custom)" required style="font-weight:600;"></td>' +
					'<td><input type="text" class="widefat code input-url" name="servers[' + group + '][' + rowCount + '][url]" value="https://..." required style="font-size:12px;"></td>' +
					'<td style="text-align:center;"><button type="button" class="button button-small btn-move-up" title="Peleka Juu">▲</button> <button type="button" class="button button-small btn-move-down" title="Peleka Chini">▼</button></td>' +
					'<td style="text-align:center;"><button type="button" class="button button-small button-link-delete btn-remove-server" title="Futa Server">✕</button></td>';
				tbody.appendChild(tr);
				reindexGroup(tbody, group);
			});
		});
	});
	</script>
	<?php
}
