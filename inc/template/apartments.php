<?php
/**
 * Apartments section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_sub_field' ) ) {
	return;
}

$section_id           = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'apartments' );
$eyebrow              = (string) get_sub_field( 'eyebrow' );
$title                 = (string) get_sub_field( 'title' );
$items                 = get_sub_field( 'items' );
$items                 = is_array( $items ) ? $items : array();
$hint                  = (string) get_sub_field( 'hint' );
$apartments_slider_id  = project_theme_unique_dom_id( 'apartments-slider' );
$apartment_items       = array();
$plan_labels           = array(
	'1' => __( 'Умовна схема однокімнатної квартири', 'project-theme' ),
	'2' => __( 'Умовна схема двокімнатної квартири', 'project-theme' ),
	'3' => __( 'Умовна схема трикімнатної квартири', 'project-theme' ),
);

foreach ( $items as $item_index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$anchor_id       = isset( $item['anchor_id'] ) ? (string) $item['anchor_id'] : '';
	$item['dom_id']  = project_theme_unique_dom_id( '' !== trim( $anchor_id ) ? $anchor_id : 'apartment-' . ( (int) $item_index + 1 ) );
	$apartment_items[] = $item;
}
?>
<section class="section apartments" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container">
		<div class="section-intro section-intro--apartments reveal">
			<div>
				<?php if ( '' !== $eyebrow ) : ?>
					<p class="eyebrow"><span></span><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $title ) : ?>
					<h2><?php echo esc_html( $title ); ?></h2>
				<?php endif; ?>
			</div>
			<?php if ( $apartment_items ) : ?>
				<div class="apartment-tabs" aria-label="<?php echo esc_attr__( 'Швидкий перехід до типів квартир', 'project-theme' ); ?>" data-apartment-tabs>
					<?php foreach ( $apartment_items as $tab_index => $apartment_item ) : ?>
						<?php
						$tab_rooms_number = isset( $apartment_item['rooms_number'] ) ? (string) $apartment_item['rooms_number'] : '';
						$tab_rooms_label  = isset( $apartment_item['rooms_label'] ) ? (string) $apartment_item['rooms_label'] : '';
						$tab_label        = trim( $tab_rooms_number . ' ' . $tab_rooms_label );
						?>
						<a href="#<?php echo esc_attr( (string) $apartment_item['dom_id'] ); ?>"<?php if ( 0 === $tab_index ) : ?> aria-current="true"<?php endif; ?> data-apartment-tab><?php echo esc_html( $tab_label ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $apartment_items ) : ?>
			<div class="apartment-list" id="<?php echo esc_attr( $apartments_slider_id ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Типи квартир', 'project-theme' ); ?>" data-slider>
				<?php foreach ( $apartment_items as $apartment_item ) : ?>
					<?php
					$rooms_number  = isset( $apartment_item['rooms_number'] ) ? (string) $apartment_item['rooms_number'] : '';
					$rooms_label   = isset( $apartment_item['rooms_label'] ) ? (string) $apartment_item['rooms_label'] : '';
					$item_label    = isset( $apartment_item['label'] ) ? (string) $apartment_item['label'] : '';
					$area          = isset( $apartment_item['area'] ) ? (string) $apartment_item['area'] : '';
					$description   = isset( $apartment_item['description'] ) ? (string) $apartment_item['description'] : '';
					$note          = isset( $apartment_item['note'] ) ? (string) $apartment_item['note'] : '';
					$button_text   = isset( $apartment_item['button_text'] ) ? (string) $apartment_item['button_text'] : '';
					$interest      = isset( $apartment_item['interest'] ) ? (string) $apartment_item['interest'] : '';
					$context       = isset( $apartment_item['context'] ) ? (string) $apartment_item['context'] : '';
					$plan_label    = isset( $plan_labels[ $rooms_number ] )
						? $plan_labels[ $rooms_number ]
						: sprintf( __( 'Умовна схема квартири: %s', 'project-theme' ), $item_label );
					$apartment_type = '' !== $rooms_number
						? sprintf( __( '%s-кімнатна квартира', 'project-theme' ), $rooms_number )
						: $item_label;
					?>
					<article class="apartment-row reveal" id="<?php echo esc_attr( (string) $apartment_item['dom_id'] ); ?>">
						<div class="room-pill"><strong><?php echo esc_html( $rooms_number ); ?></strong><span><?php echo esc_html( $rooms_label ); ?></span></div>
						<?php echo project_theme_apartment_plan_svg( $rooms_number, $plan_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allowlisted theme SVG. ?>
						<div class="apartment-copy"><span><?php echo esc_html( $item_label ); ?></span><h3><?php echo esc_html( $area ); ?></h3><p><?php echo esc_html( $description ); ?></p><small><?php echo esc_html( $note ); ?></small></div>
						<?php if ( '' !== $button_text ) : ?>
							<button class="button button--clay apartment-card-cta" type="button" data-open-lead data-interest="<?php echo esc_attr( $interest ); ?>" data-apartment-type="<?php echo esc_attr( $apartment_type ); ?>" data-context="<?php echo esc_attr( $context ); ?>"><?php echo esc_html( $button_text ); ?></button>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="swipe-hint">
				<span><?php echo esc_html( $hint ); ?></span>
				<div class="slider-controls" aria-label="<?php echo esc_attr__( 'Керування плануваннями', 'project-theme' ); ?>">
					<button class="slider-control" type="button" data-slider-prev="<?php echo esc_attr( $apartments_slider_id ); ?>" aria-controls="<?php echo esc_attr( $apartments_slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Попереднє планування', 'project-theme' ); ?>">←</button>
					<button class="slider-control" type="button" data-slider-next="<?php echo esc_attr( $apartments_slider_id ); ?>" aria-controls="<?php echo esc_attr( $apartments_slider_id ); ?>" aria-label="<?php echo esc_attr__( 'Наступне планування', 'project-theme' ); ?>">→</button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
