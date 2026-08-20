<?php
/**
 * VikoStream — A–Z filter block (AJAX powered).
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block = isset( $args['block'] ) ? $args['block'] : array();
?>
<section id="az" class="vk-alpha" aria-label="<?php esc_attr_e( 'Browse alphabetically', 'vikostream' ); ?>">
	<div class="vk-container">
		<header class="vk-sec-head">
			<h2 class="vk-sec-title"><span class="vk-sec-rule"></span><?php echo esc_html( $block['title'] ?? 'Browse A–Z' ); ?></h2>
			<select id="vk-alpha-type" class="vk-alpha__type" aria-label="<?php esc_attr_e( 'Filter by type', 'vikostream' ); ?>">
				<option value=""><?php esc_html_e( 'Zote', 'vikostream' ); ?></option>
				<option value="movie"><?php esc_html_e( 'Movies', 'vikostream' ); ?></option>
				<option value="tvshow"><?php esc_html_e( 'TV Shows', 'vikostream' ); ?></option>
				<option value="asian-drama"><?php esc_html_e( 'Asian Drama', 'vikostream' ); ?></option>
			</select>
		</header>

		<div class="vk-alpha__bar" role="group" aria-label="<?php esc_attr_e( 'Letters', 'vikostream' ); ?>">
			<button class="vk-alpha__letter vk-alpha__letter--active" data-letter="" type="button"><?php esc_html_e( 'ZOTE', 'vikostream' ); ?></button>
			<?php foreach ( range( 'A', 'Z' ) as $L ) : ?>
				<button class="vk-alpha__letter" data-letter="<?php echo esc_attr( $L ); ?>" type="button"><?php echo esc_html( $L ); ?></button>
			<?php endforeach; ?>
			<button class="vk-alpha__letter" data-letter="0" type="button">#</button>
		</div>

		<p id="vk-alpha-count" class="vk-alpha__count" aria-live="polite"></p>
		<div id="vk-alpha-grid" class="vk-grid vk-alpha__grid"></div>
	</div>
</section>
