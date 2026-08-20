<?php
/**
 * VikoStream — homepage blocks manager UI.
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blocks = viko_get_blocks();
?>
<div class="wrap viko-admin viko-blocks">
	<h1><?php esc_html_e( 'Homepage Blocks', 'vikostream' ); ?></h1>
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Blocks zimehifadhiwa ✓', 'vikostream' ); ?></p></div>
	<?php endif; ?>
	<p class="description">
		<?php esc_html_e( 'Panga, ongeza au futa blocks za homepage. Kila block ya content inaweza kutumia rule ya type, genre au Recommended. Block za "slider" na "alphabet" ni za pekee.', 'vikostream' ); ?>
	</p>

	<form method="post" id="viko-blocks-form">
		<?php wp_nonce_field( 'viko_blocks', 'viko_blocks_nonce' ); ?>
		<input type="hidden" name="viko_action" id="viko-action" value="update">
		<input type="hidden" name="viko_idx" id="viko-idx" value="">
		<input type="hidden" name="viko_dir" id="viko-dir" value="">

		<table class="widefat viko-blocks-table" style="max-width:1080px">
			<thead>
				<tr>
					<th style="width:70px"><?php esc_html_e( 'Order', 'vikostream' ); ?></th>
					<th style="width:50px"><?php esc_html_e( 'On', 'vikostream' ); ?></th>
					<th><?php esc_html_e( 'Title', 'vikostream' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Style', 'vikostream' ); ?></th>
					<th style="width:200px"><?php esc_html_e( 'Rule (type / genre)', 'vikostream' ); ?></th>
					<th style="width:130px"><?php esc_html_e( 'Sort', 'vikostream' ); ?></th>
					<th style="width:70px"><?php esc_html_e( 'Count', 'vikostream' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Actions', 'vikostream' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $blocks as $i => $b ) : ?>
					<tr class="<?php echo $b['enabled'] ? '' : 'viko-row-off'; ?>">
						<td>
							<span class="viko-order"><?php echo esc_html( $i + 1 ); ?></span>
							<button type="submit" class="button button-small viko-move" data-idx="<?php echo esc_attr( $i ); ?>" data-dir="-1" title="<?php esc_attr_e( 'Pandisha', 'vikostream' ); ?>" <?php disabled( 0 === $i ); ?>>↑</button>
							<button type="submit" class="button button-small viko-move" data-idx="<?php echo esc_attr( $i ); ?>" data-dir="1" title="<?php esc_attr_e( 'Shusha', 'vikostream' ); ?>" <?php disabled( $i === count( $blocks ) - 1 ); ?>>↓</button>
						</td>
						<td><input type="checkbox" name="blocks[<?php echo esc_attr( $i ); ?>][enabled]" value="1" <?php checked( $b['enabled'] ); ?>></td>
						<td>
							<input type="hidden" name="blocks[<?php echo esc_attr( $i ); ?>][id]" value="<?php echo esc_attr( $b['id'] ); ?>">
							<input type="text" name="blocks[<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $b['title'] ); ?>" class="widefat">
						</td>
						<td>
							<select name="blocks[<?php echo esc_attr( $i ); ?>][style]">
								<?php
								$styles = array(
									'slider'   => __( 'Slider', 'vikostream' ),
									'alphabet' => __( 'A–Z Filter', 'vikostream' ),
									'row'      => __( 'Row (scroll)', 'vikostream' ),
									'grid'     => __( 'Grid', 'vikostream' ),
								);
								foreach ( $styles as $sv => $sl ) {
									echo '<option value="' . esc_attr( $sv ) . '" ' . selected( $b['style'], $sv, false ) . '>' . esc_html( $sl ) . '</option>';
								}
								?>
							</select>
						</td>
						<td><select name="blocks[<?php echo esc_attr( $i ); ?>][rule]"><?php echo viko_rule_options( $b['rule'] ); // phpcs:ignore ?></select></td>
						<td><select name="blocks[<?php echo esc_attr( $i ); ?>][sort]"><?php echo viko_sort_options( $b['sort'] ); // phpcs:ignore ?></select></td>
						<td><input type="number" min="1" max="30" name="blocks[<?php echo esc_attr( $i ); ?>][count]" value="<?php echo esc_attr( $b['count'] ); ?>"></td>
						<td>
							<button type="submit" class="button button-small viko-del" data-idx="<?php echo esc_attr( $i ); ?>" style="color:#d63638"><?php esc_html_e( 'Futa', 'vikostream' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<button type="submit" class="button button-primary" id="viko-save-blocks"><?php esc_html_e( '💾 Hifadhi Blocks', 'vikostream' ); ?></button>
			<button type="submit" class="button" id="viko-add-block">＋ <?php esc_html_e( 'Ongeza Block (custom rule)', 'vikostream' ); ?></button>
			<button type="submit" class="button" id="viko-reset-blocks" style="color:#d63638"><?php esc_html_e( 'Rudisha defaults', 'vikostream' ); ?></button>
		</p>
	</form>

	<div class="card" style="max-width:1080px;margin-top:20px">
		<h3><?php esc_html_e( 'Mifano ya custom blocks', 'vikostream' ); ?></h3>
		<ul>
			<li><code><?php esc_html_e( 'Block "Top Rated Action" → rule: genre:action, sort: top rated, style: grid', 'vikostream' ); ?></code></li>
			<li><code><?php esc_html_e( 'Block "Best of Korea" → ongeza genre au tumia type:asian-drama + sort: top rated', 'vikostream' ); ?></code></li>
			<li><code><?php esc_html_e( 'Block "Weekend Picks" → rule: recommended, sort: random, style: slider', 'vikostream' ); ?></code></li>
		</ul>
	</div>
</div>
