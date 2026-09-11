<?php
/**
 * Benefits section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_sub_field' ) ) {
	return;
}

$section_id           = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'advantages' );
$eyebrow              = (string) get_sub_field( 'eyebrow' );
$title                 = (string) get_sub_field( 'title' );
$items                 = get_sub_field( 'items' );
$items                 = is_array( $items ) ? $items : array();
$hint                  = (string) get_sub_field( 'hint' );
$benefits_slider_id    = project_theme_unique_dom_id( 'benefits-slider' );
$benefit_variants      = array( 'benefit--main', '', 'benefit--sage', 'benefit--clay', '', 'benefit--sand' );
$benefit_reveal_delays = array( '', 'reveal--delay-1', 'reveal--delay-2', '', 'reveal--delay-1', 'reveal--delay-2' );
?>
<section class="section benefits" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container">
		<div class="section-title reveal">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="eyebrow"><span></span><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
		</div>

		<?php if ( $items ) : ?>
			<div class="benefit-bento" id="<?php echo esc_attr( $benefits_slider_id ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Переваги житлового комплексу', 'project-theme' ); ?>" data-slider>
				<?php foreach ( $items as $item_index => $item ) : ?>
					<?php
					if ( ! is_array( $item ) ) {
						continue;
					}

					$index       = (int) $item_index;
					$position    = $index % count( $benefit_variants );
					$icon        = isset( $item['icon'] ) ? (string) $item['icon'] : '';
					$overline    = isset( $item['overline'] ) ? (string) $item['overline'] : '';
					$item_title  = isset( $item['title'] ) ? (string) $item['title'] : '';
					$description = isset( $item['description'] ) ? (string) $item['description'] : '';
					$badge       = isset( $item['badge'] ) ? (string) $item['badge'] : '';
					$classes     = array( 'benefit' );

					if ( '' !== $benefit_variants[ $position ] ) {
						$classes[] = $benefit_variants[ $position ];
					}
					$classes[] = 'reveal';
					if ( '' !== $benefit_reveal_delays[ $position ] ) {
						$classes[] = $benefit_reveal_delays[ $position ];
					}
					?>
					<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
						<?php if ( '' !== $icon ) : ?>
							<div class="benefit-icon" aria-hidden="true"><?php echo project_theme_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allowlisted theme SVG. ?></div>
						<?php endif; ?>
						<?php if ( '' !== $overline ) : ?>
							<span><?php echo esc_html( $overline ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $item_title ) : ?>
							<h3><?php echo esc_html( $item_title ); ?></h3>
						<?php endif; ?>
						<?php if ( '' !== $description ) : ?>
							<p><?php echo esc_html( $description ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $badge ) : ?>
							<strong><?php echo esc_html( $badge ); ?></strong>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="swipe-hint">
				<span><?php echo esc_html( $hint ); ?></span>
				<div class="slider-controls" aria-label="<?php echo esc_attr__( 'Керування перевагами', 'project-theme' ); ?>">
					<button class="slider-control" type="button" data-slider-prev="<?php echo esc_attr( $benefits_slider_id ); ?>" aria-controls="<?php echo esc_attr( $benefits_slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Попередня перевага', 'project-theme' ); ?>">←</button>
					<button class="slider-control" type="button" data-slider-next="<?php echo esc_attr( $benefits_slider_id ); ?>" aria-controls="<?php echo esc_attr( $benefits_slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Наступна перевага', 'project-theme' ); ?>">→</button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
