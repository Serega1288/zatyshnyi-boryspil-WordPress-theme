<?php
/**
 * Explicit Flexible Content dispatcher.
 *
 * @package ProjectTheme
 */

if ( ! function_exists( 'have_rows' ) || ! have_rows( 'constructor' ) ) {
	return;
}

$constructor_name = '';
if ( is_array( $args ) ) {
	$constructor_name = isset( $args['constructor_name'] ) ? (string) $args['constructor_name'] : '';
} elseif ( is_scalar( $args ) ) {
	$constructor_name = (string) $args;
}

$section_index = 0;
while ( have_rows( 'constructor' ) ) {
	the_row();
	$suffix = (string) $section_index++;
	if ( '' !== $constructor_name ) {
		$suffix .= '-' . sanitize_title( $constructor_name );
	}

	if ( get_sub_field( 'disable_block' ) ) {
		continue;
	}

	$layout = get_row_layout();
	$templates = array(
		'template-hero'       => 'hero',
		'template-about'      => 'about',
		'template-benefits'   => 'benefits',
		'template-apartments' => 'apartments',
		'template-finish'     => 'finish',
		'template-documents'  => 'documents',
		'template-builder'    => 'builder',
		'template-conditions' => 'conditions',
		'template-contact'    => 'contact',
	);

	if ( isset( $templates[ $layout ] ) ) {
		get_template_part( 'inc/template/' . $templates[ $layout ], null, $suffix );
	}
}
