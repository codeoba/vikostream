<?php
/**
 * VikoStream — Homepage Blocks Manager UI.
 * Configures homepage block order, content rules, display styles, and post count per block.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blocks = viko_get_blocks();
?>
<div class="wrap viko-admin viko-blocks" style="max-width:1200px;">
	<h1 style="display:flex; align-items:center; gap:10px;">
		<span class="dashicons dashicons-grid-view" style="font-size:32px; width:32px; height:32px; color:#00d4ff;"></span>
		<?php esc_html_e( 'VikoStream — Homepage Blocks Manager', 'vikostream' ); ?>
	</h1>
	
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><strong>✓ <?php esc_html_e( 'Blocks za homepage zimehifadhiwa kikamilifu!', 'vikostream' ); ?></strong></p></div>
	<?php endif; ?>

	<p class="description" style="margin-bottom:20px; font-size:14px;">
		<?php esc_html_e( 'Hapa unaweza kuwasha/kuzima, kupanga mpangilio (reorder), kubadilisha mtindo (Normal Grid / Slider / A-Z Filter), na kuweka idadi halisi ya movies/shows (Count) za kuonyesha kwenye kila block ya homepage.', 'vikostream' ); ?>
	</p>

	<form method="post" id="viko-blocks-form">
		<?php wp_nonce_field( 'viko_blocks', 'viko_blocks_nonce' ); ?>
		<input type="hidden" name="viko_action" id="viko-action" value="update">
		<input type="hidden" name="viko_idx" id="viko-idx" value="">
		<input type="hidden" name="viko_dir" id="viko-dir" value="">

		<div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:20px;">
			<table class="widefat striped viko-blocks-table">
				<thead>
					<tr>
						<th style="width:75px; text-align:center;"><?php esc_html_e( 'Order', 'vikostream' ); ?></th>
						<th style="width:55px; text-align:center;"><?php esc_html_e( 'Washa', 'vikostream' ); ?></th>
						<th style="width:230px;"><?php esc_html_e( 'Kichwa cha Block (Title)', 'vikostream' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( 'Mtindo (Style)', 'vikostream' ); ?></th>
						<th style="width:210px;"><?php esc_html_e( 'Maudhui (Rule)', 'vikostream' ); ?></th>
						<th style="width:170px;"><?php esc_html_e( 'Mpangilio (Sort)', 'vikostream' ); ?></th>
						<th style="width:110px; text-align:center;"><?php esc_html_e( 'Idadi ya Movies (Count)', 'vikostream' ); ?></th>
						<th style="width:70px; text-align:center;"><?php esc_html_e( 'Futa', 'vikostream' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $blocks as $i => $b ) : 
						$count_val = max( 1, (int) ( $b['count'] ?? 12 ) );
					?>
						<tr class="<?php echo $b['enabled'] ? '' : 'viko-row-off'; ?>">
							<td style="text-align:center;">
								<span class="viko-order" style="font-weight:bold; margin-right:4px;">#<?php echo esc_html( $i + 1 ); ?></span>
								<button type="submit" class="button button-small viko-move" data-idx="<?php echo esc_attr( $i ); ?>" data-dir="-1" title="<?php esc_attr_e( 'Pandisha Juu', 'vikostream' ); ?>" <?php disabled( 0 === $i ); ?>>▲</button>
								<button type="submit" class="button button-small viko-move" data-idx="<?php echo esc_attr( $i ); ?>" data-dir="1" title="<?php esc_attr_e( 'Shusha Chini', 'vikostream' ); ?>" <?php disabled( $i === count( $blocks ) - 1 ); ?>>▼</button>
							</td>
							<td style="text-align:center;">
								<input type="checkbox" name="blocks[<?php echo esc_attr( $i ); ?>][enabled]" value="1" <?php checked( $b['enabled'] ); ?>>
							</td>
							<td>
								<input type="hidden" name="blocks[<?php echo esc_attr( $i ); ?>][id]" value="<?php echo esc_attr( $b['id'] ); ?>">
								<input type="text" name="blocks[<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $b['title'] ); ?>" class="widefat" style="font-weight:600;" required>
							</td>
							<td>
								<select name="blocks[<?php echo esc_attr( $i ); ?>][style]" class="widefat">
									<?php
									$styles = array(
										'grid'     => __( 'Normal Grid (Kawaida)', 'vikostream' ),
										'slider'   => __( 'Hero Slider (Juu)', 'vikostream' ),
										'alphabet' => __( 'A–Z Filter', 'vikostream' ),
									);
									foreach ( $styles as $sv => $sl ) {
										echo '<option value="' . esc_attr( $sv ) . '" ' . selected( $b['style'], $sv, false ) . '>' . esc_html( $sl ) . '</option>';
									}
									?>
								</select>
							</td>
							<td><select name="blocks[<?php echo esc_attr( $i ); ?>][rule]" class="widefat"><?php echo viko_rule_options( $b['rule'] ); // phpcs:ignore ?></select></td>
							<td><select name="blocks[<?php echo esc_attr( $i ); ?>][sort]" class="widefat"><?php echo viko_sort_options( $b['sort'] ); // phpcs:ignore ?></select></td>
							<td style="text-align:center;">
								<input type="number" min="1" max="100" name="blocks[<?php echo esc_attr( $i ); ?>][count]" value="<?php echo esc_attr( $count_val ); ?>" style="width:75px; text-align:center; font-weight:bold; color:#0073aa;" title="Idadi ya movies/shows za kuonyesha kwenye homepage">
							</td>
							<td style="text-align:center;">
								<button type="submit" class="button button-small button-link-delete viko-del" data-idx="<?php echo esc_attr( $i ); ?>" style="color:#d63638;" title="Futa Block">✕</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
			<div style="display:flex; gap:8px;">
				<button type="submit" class="button button-primary button-hero" id="viko-save-blocks" style="font-size:15px; font-weight:bold;">
					<span class="dashicons dashicons-saved" style="vertical-align:middle;"></span> <?php esc_html_e( 'Hifadhi Mabadiliko ya Blocks', 'vikostream' ); ?>
				</button>
				<button type="submit" class="button" id="viko-add-block" style="font-weight:600;">
					<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span> <?php esc_html_e( '+ Ongeza Block Mpya', 'vikostream' ); ?>
				</button>
			</div>
			<button type="submit" class="button button-secondary" id="viko-reset-blocks" style="color:#d63638; border-color:#d63638;">
				<span class="dashicons dashicons-image-rotate" style="vertical-align:middle;"></span> <?php esc_html_e( 'Rudisha Defaults', 'vikostream' ); ?>
			</button>
		</div>
	</form>

	<div class="card" style="max-width:1200px; margin-top:25px; padding:15px 20px; border-radius:8px;">
		<h3 style="margin-top:0; font-size:1.1rem; color:#1d2327;">💡 <?php esc_html_e( 'Miongozo na Vidokezo vya Homepage Blocks', 'vikostream' ); ?></h3>
		<ul style="color:#50575e; line-height:1.7;">
			<li><strong>Normal Grid (Kawaida):</strong> Inaonyesha movies na vipindi kwenye safu safi ya grid (bila kuteleza / sliding), ikijaza ukurasa vizuri kwenye simu na kompyuta.</li>
			<li><strong>Idadi ya Movies (Count):</strong> Weka idadi halisi (mfano: 6, 12, 18, 24, 30 n.k.) unayotaka ionekane kwenye block husika.</li>
			<li><strong>Hero Slider:</strong> Huwekwa mara nyingi juu kabisa kwa ajili ya kuonyesha maudhui yaliyopendekezwa (Recommended) kwa staili ya sinema kubwa.</li>
		</ul>
	</div>
</div>
