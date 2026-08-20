<?php
/**
 * VikoStream — homepage.
 * Renders blocks from the Homepage Blocks manager (order = render order).
 *
 * @package VikoStream
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

foreach ( viko_get_blocks() as $block ) {
	if ( empty( $block['enabled'] ) ) {
		continue;
	}
	switch ( $block['style'] ) {
		case 'slider':
			get_template_part( 'template-parts/block-slider', null, array( 'block' => $block ) );
			break;
		case 'alphabet':
			get_template_part( 'template-parts/block-alphabet', null, array( 'block' => $block ) );
			break;
		case 'row':
		case 'grid':
		default:
			get_template_part( 'template-parts/block-row', null, array( 'block' => $block ) );
			break;
	}
}

get_footer();
