<?php
/**
 * Apartment finish section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_id    = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'finish' );
$image_id      = (int) get_sub_field( 'image' );
$image_alt     = (string) get_sub_field( 'image_alt' );
$image_caption = (string) get_sub_field( 'image_caption' );
$eyebrow       = (string) get_sub_field( 'eyebrow' );
$title         = (string) get_sub_field( 'title' );
$ceiling_label = (string) get_sub_field( 'ceiling_label' );
$ceiling_value = (string) get_sub_field( 'ceiling_value' );
$specs         = get_sub_field( 'specs' );
$specs         = is_array( $specs ) ? $specs : array();
?>
<section class="section finish" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container finish-panel">
		<figure class="finish-image reveal">
			<?php echo project_theme_image_html( $image_id, $image_alt, array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<figcaption><?php echo esc_html( $image_caption ); ?></figcaption>
		</figure>
		<div class="finish-content reveal reveal--delay">
			<p class="eyebrow"><span></span><?php echo esc_html( $eyebrow ); ?></p>
			<h2><?php echo esc_html( $title ); ?></h2>
			<div class="ceiling-chip"><span><?php echo esc_html( $ceiling_label ); ?></span><strong><?php echo esc_html( $ceiling_value ); ?></strong></div>
			<ul class="spec-grid">
				<?php foreach ( $specs as $spec ) : ?>
					<?php $spec_text = is_array( $spec ) && isset( $spec['text'] ) ? (string) $spec['text'] : ''; ?>
					<li><?php echo esc_html( $spec_text ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
