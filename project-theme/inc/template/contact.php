<?php
/**
 * Contact call-to-action section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_id         = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'contact' );
$eyebrow            = (string) get_sub_field( 'eyebrow' );
$title              = (string) get_sub_field( 'title' );
$description        = (string) get_sub_field( 'description' );
$action_eyebrow     = (string) get_sub_field( 'action_eyebrow' );
$action_title       = (string) get_sub_field( 'action_title' );
$action_description = (string) get_sub_field( 'action_description' );
$button_text        = (string) get_sub_field( 'button_text' );
$interest           = (string) get_sub_field( 'interest' );
$context            = (string) get_sub_field( 'context' );
?>
<section class="section contact" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container contact-panel">
		<div class="contact-copy reveal"><p class="eyebrow eyebrow--light"><span></span><?php echo esc_html( $eyebrow ); ?></p><h2><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $description ); ?></p></div>
		<div class="contact-action reveal reveal--delay">
			<span><?php echo esc_html( $action_eyebrow ); ?></span>
			<strong><?php echo esc_html( $action_title ); ?></strong>
			<p><?php echo esc_html( $action_description ); ?></p>
			<button class="button button--clay" type="button" data-open-lead data-interest="<?php echo esc_attr( $interest ); ?>" data-context="<?php echo esc_attr( $context ); ?>"><?php echo esc_html( $button_text ); ?></button>
		</div>
	</div>
</section>
