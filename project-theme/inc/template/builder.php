<?php
/**
 * Builder section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_id            = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'developer' );
$eyebrow               = (string) get_sub_field( 'eyebrow' );
$builder_label         = (string) get_sub_field( 'builder_label' );
$title                 = (string) get_sub_field( 'title' );
$description           = (string) get_sub_field( 'description' );
$primary_link          = project_theme_link( get_sub_field( 'primary_link' ) );
$assurance_aria        = (string) get_sub_field( 'assurance_aria' );
$assurance_label       = (string) get_sub_field( 'assurance_label' );
$assurance_title       = (string) get_sub_field( 'assurance_title' );
$assurance_description = (string) get_sub_field( 'assurance_description' );
$secondary_link        = project_theme_link( get_sub_field( 'secondary_link' ) );
$primary_target        = '_blank' === $primary_link['target'] ? '_blank' : '';
$secondary_target      = '_blank' === $secondary_link['target'] ? '_blank' : '';
?>
<section class="section builder" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container builder-panel">
		<div class="builder-copy reveal">
			<p class="eyebrow eyebrow--light"><span></span><?php echo esc_html( $eyebrow ); ?></p>
			<span class="builder-label"><?php echo esc_html( $builder_label ); ?></span>
			<h2><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $description ); ?></p>
			<?php if ( '' !== $primary_link['url'] && '' !== $primary_link['title'] ) : ?>
				<a class="button button--white" href="<?php echo esc_url( $primary_link['url'] ); ?>"<?php if ( '' !== $primary_target ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $primary_link['title'] ); ?> <span aria-hidden="true">↗</span></a>
			<?php endif; ?>
		</div>
		<aside class="builder-assurance reveal reveal--delay" aria-label="<?php echo esc_attr( $assurance_aria ); ?>">
			<span><?php echo esc_html( $assurance_label ); ?></span>
			<strong><?php echo esc_html( $assurance_title ); ?></strong>
			<p><?php echo esc_html( $assurance_description ); ?></p>
			<?php if ( '' !== $secondary_link['url'] && '' !== $secondary_link['title'] ) : ?>
				<a href="<?php echo esc_url( $secondary_link['url'] ); ?>"<?php if ( '' !== $secondary_target ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $secondary_link['title'] ); ?> <span aria-hidden="true">→</span></a>
			<?php endif; ?>
		</aside>
	</div>
</section>
