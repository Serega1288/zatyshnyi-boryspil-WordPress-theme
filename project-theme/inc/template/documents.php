<?php
/**
 * Documents section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_id  = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'documents' );
$slider_id   = project_theme_unique_dom_id( 'documents-slider' );
$eyebrow     = (string) get_sub_field( 'eyebrow' );
$title       = (string) get_sub_field( 'title' );
$intro       = (string) get_sub_field( 'intro' );
$documents   = get_sub_field( 'documents' );
$documents   = is_array( $documents ) ? $documents : array();
$hint        = (string) get_sub_field( 'hint' );
$cta_text    = (string) get_sub_field( 'cta_text' );
$cta_interest = (string) get_sub_field( 'cta_interest' );
$cta_context = (string) get_sub_field( 'cta_context' );
?>
<section class="section documents" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container">
		<div class="section-intro reveal">
			<div><p class="eyebrow"><span></span><?php echo esc_html( $eyebrow ); ?></p><h2><?php echo esc_html( $title ); ?></h2></div>
			<p><?php echo esc_html( $intro ); ?></p>
		</div>
		<div class="document-track" id="<?php echo esc_attr( $slider_id ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Категорії документів', 'project-theme' ); ?>" data-slider>
			<?php foreach ( $documents as $document_index => $document ) : ?>
				<?php
				if ( ! is_array( $document ) ) {
					continue;
				}

				$card_position = (int) $document_index % 3;
				$card_classes  = array( 'document-card' );
				if ( 2 === $card_position ) {
					$card_classes[] = 'document-card--accent';
				}
				$card_classes[] = 'reveal';
				if ( 1 === $card_position ) {
					$card_classes[] = 'reveal--delay-1';
				} elseif ( 2 === $card_position ) {
					$card_classes[] = 'reveal--delay-2';
				}

				$document_type        = isset( $document['type'] ) ? (string) $document['type'] : '';
				$document_title       = isset( $document['title'] ) ? (string) $document['title'] : '';
				$document_description = isset( $document['description'] ) ? (string) $document['description'] : '';
				$document_note        = isset( $document['note'] ) ? (string) $document['note'] : '';
				$button_text          = isset( $document['button_text'] ) ? (string) $document['button_text'] : '';
				$file                 = $document['file'] ?? 0;
				$file_id              = is_array( $file ) && isset( $file['ID'] ) ? (int) $file['ID'] : (int) $file;
				$file_url             = $file_id > 0 ? wp_get_attachment_url( $file_id ) : false;
				?>
				<article class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
					<div class="document-number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', (int) $document_index + 1 ) ); ?></div>
					<span class="document-type"><?php echo esc_html( $document_type ); ?></span>
					<h3><?php echo esc_html( $document_title ); ?></h3>
					<p><?php echo esc_html( $document_description ); ?></p>
					<?php if ( $file_url ) : ?>
						<small><a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $button_text ); ?></a></small>
					<?php else : ?>
						<small><?php echo esc_html( $document_note ); ?></small>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<div class="document-footer reveal">
			<div class="swipe-hint">
				<span><?php echo esc_html( $hint ); ?></span>
				<div class="slider-controls" aria-label="<?php echo esc_attr__( 'Керування документами', 'project-theme' ); ?>">
					<button class="slider-control" type="button" data-slider-prev="<?php echo esc_attr( $slider_id ); ?>" aria-controls="<?php echo esc_attr( $slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Попередній документ', 'project-theme' ); ?>">←</button>
					<button class="slider-control" type="button" data-slider-next="<?php echo esc_attr( $slider_id ); ?>" aria-controls="<?php echo esc_attr( $slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Наступний документ', 'project-theme' ); ?>">→</button>
				</div>
			</div>
			<button class="button button--outline" type="button" data-open-lead data-interest="<?php echo esc_attr( $cta_interest ); ?>" data-context="<?php echo esc_attr( $cta_context ); ?>"><?php echo esc_html( $cta_text ); ?></button>
		</div>
	</div>
</section>
