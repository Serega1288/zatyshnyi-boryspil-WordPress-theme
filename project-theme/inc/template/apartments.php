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

$section_id          = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'apartments' );
$eyebrow             = (string) get_sub_field( 'eyebrow' );
$title               = (string) get_sub_field( 'title' );
$items               = get_sub_field( 'items' );
$items               = is_array( $items ) ? $items : array();
$hint                = (string) get_sub_field( 'hint' );
$apartments_tabs_id   = project_theme_unique_dom_id( 'apartment-tabs' );
$apartments_list_id   = project_theme_unique_dom_id( 'apartment-panels' );
$plan_dialog_id       = project_theme_unique_dom_id( 'apartment-plan-dialog' );
$plan_dialog_title_id = project_theme_unique_dom_id( 'apartment-plan-dialog-title' );
$apartment_items      = array();
$has_apartment_plans  = false;
$plan_labels          = array(
	'1' => __( 'Планування однокімнатної квартири', 'project-theme' ),
	'2' => __( 'Планування двокімнатної квартири', 'project-theme' ),
	'3' => __( 'Планування трикімнатної квартири', 'project-theme' ),
);

foreach ( $items as $item_index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$anchor_id      = isset( $item['anchor_id'] ) ? (string) $item['anchor_id'] : '';
	$item['dom_id'] = project_theme_unique_dom_id( '' !== trim( $anchor_id ) ? $anchor_id : 'apartment-' . ( (int) $item_index + 1 ) );
	$item['tab_id'] = project_theme_unique_dom_id( $item['dom_id'] . '-tab' );
	$rooms_number   = isset( $item['rooms_number'] ) ? (string) $item['rooms_number'] : '';
	$item_label     = isset( $item['label'] ) ? (string) $item['label'] : '';
	$plan_label     = isset( $plan_labels[ $rooms_number ] )
		? $plan_labels[ $rooms_number ]
		: sprintf( __( 'Планування квартири: %s', 'project-theme' ), $item_label );
	$raw_plans      = isset( $item['plans'] ) && is_array( $item['plans'] ) ? $item['plans'] : array();
	$plans          = array();

	foreach ( $raw_plans as $plan_index => $plan ) {
		if ( ! is_array( $plan ) ) {
			continue;
		}

		$image_value = $plan['image'] ?? 0;
		$image_id    = is_array( $image_value )
			? (int) ( $image_value['ID'] ?? $image_value['id'] ?? 0 )
			: (int) $image_value;
		$image_url   = $image_id > 0 ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '';
		if ( '' === $image_url && is_array( $image_value ) && isset( $image_value['url'] ) ) {
			$image_url = (string) $image_value['url'];
		}
		if ( '' === $image_url ) {
			continue;
		}

		$image_alt = isset( $plan['image_alt'] ) ? trim( (string) $plan['image_alt'] ) : '';
		if ( '' === $image_alt && is_array( $image_value ) && isset( $image_value['alt'] ) ) {
			$image_alt = trim( (string) $image_value['alt'] );
		}
		if ( '' === $image_alt && $image_id > 0 ) {
			$image_alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
		}

		$code = isset( $plan['code'] ) ? trim( (string) $plan['code'] ) : '';
		$area = isset( $plan['area'] ) ? trim( (string) $plan['area'] ) : '';
		if ( '' === $image_alt ) {
			$image_alt = trim( $plan_label . ( '' !== $code ? ' ' . $code : '' ) . ( '' !== $area ? ', ' . $area : '' ) );
		}

		$image_attributes = array(
			'alt'      => $image_alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 700px) calc(100vw - 84px), (max-width: 980px) calc(100vw - 128px), 560px',
		);
		$image_html       = $image_id > 0 ? wp_get_attachment_image( $image_id, 'large', false, $image_attributes ) : '';
		if ( ! $image_html ) {
			$image_html = sprintf(
				'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async">',
				esc_url( $image_url ),
				esc_attr( $image_alt )
			);
		}

		$thumbnail_html = $image_id > 0
			? wp_get_attachment_image(
				$image_id,
				'thumbnail',
				false,
				array(
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			)
			: '';
		if ( ! $thumbnail_html ) {
			$thumbnail_html = sprintf( '<img src="%s" alt="" loading="lazy" decoding="async">', esc_url( $image_url ) );
		}

		$plans[] = array(
			'dom_id'         => project_theme_unique_dom_id( $item['dom_id'] . '-plan-' . ( (int) $plan_index + 1 ) ),
			'image_url'      => $image_url,
			'image_alt'      => $image_alt,
			'image_html'     => $image_html,
			'thumbnail_html' => $thumbnail_html,
			'code'           => $code,
			'area'           => $area,
		);
	}

	$item['plans']       = $plans;
	$has_apartment_plans = $has_apartment_plans || ! empty( $plans );
	$apartment_items[]   = $item;
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
				<div class="apartment-tabs" id="<?php echo esc_attr( $apartments_tabs_id ); ?>" role="tablist" aria-label="<?php echo esc_attr__( 'Швидкий перехід до типів квартир', 'project-theme' ); ?>">
					<?php foreach ( $apartment_items as $tab_index => $apartment_item ) : ?>
						<?php
						$tab_rooms_number = isset( $apartment_item['rooms_number'] ) ? (string) $apartment_item['rooms_number'] : '';
						$tab_rooms_label  = isset( $apartment_item['rooms_label'] ) ? (string) $apartment_item['rooms_label'] : '';
						$tab_label        = trim( $tab_rooms_number . ' ' . $tab_rooms_label );
						if ( '' === $tab_label ) {
							$tab_label = isset( $apartment_item['label'] ) ? (string) $apartment_item['label'] : '';
						}
						?>
						<a id="<?php echo esc_attr( (string) $apartment_item['tab_id'] ); ?>" href="#<?php echo esc_attr( (string) $apartment_item['dom_id'] ); ?>" role="tab" aria-controls="<?php echo esc_attr( (string) $apartment_item['dom_id'] ); ?>" aria-selected="<?php echo 0 === $tab_index ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $tab_index ? '0' : '-1'; ?>" data-apartment-tab><?php echo esc_html( $tab_label ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $apartment_items ) : ?>
			<div class="apartment-list" id="<?php echo esc_attr( $apartments_list_id ); ?>">
				<?php foreach ( $apartment_items as $item_index => $apartment_item ) : ?>
					<?php
					$rooms_number   = isset( $apartment_item['rooms_number'] ) ? (string) $apartment_item['rooms_number'] : '';
					$rooms_label    = isset( $apartment_item['rooms_label'] ) ? (string) $apartment_item['rooms_label'] : '';
					$item_label     = isset( $apartment_item['label'] ) ? (string) $apartment_item['label'] : '';
					$area           = isset( $apartment_item['area'] ) ? (string) $apartment_item['area'] : '';
					$description    = isset( $apartment_item['description'] ) ? (string) $apartment_item['description'] : '';
					$note           = isset( $apartment_item['note'] ) ? (string) $apartment_item['note'] : '';
					$button_text    = isset( $apartment_item['button_text'] ) ? (string) $apartment_item['button_text'] : '';
					$interest       = isset( $apartment_item['interest'] ) ? (string) $apartment_item['interest'] : '';
					$context        = isset( $apartment_item['context'] ) ? (string) $apartment_item['context'] : '';
					$plans          = isset( $apartment_item['plans'] ) && is_array( $apartment_item['plans'] ) ? $apartment_item['plans'] : array();
					$plan_label     = isset( $plan_labels[ $rooms_number ] )
						? $plan_labels[ $rooms_number ]
						: sprintf( __( 'Планування квартири: %s', 'project-theme' ), $item_label );
					$apartment_type = '' !== $rooms_number
						? sprintf( __( '%s-кімнатна квартира', 'project-theme' ), $rooms_number )
						: $item_label;
					$row_classes     = array( 'apartment-row', 'apartment-row--tone-' . ( (int) $item_index % 3 ), 'reveal' );
					?>
					<article class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>" id="<?php echo esc_attr( (string) $apartment_item['dom_id'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( (string) $apartment_item['tab_id'] ); ?>" tabindex="0" data-apartment-panel>
						<div class="room-pill"><strong><?php echo esc_html( $rooms_number ); ?></strong><span><?php echo esc_html( $rooms_label ); ?></span></div>
						<?php if ( $plans ) : ?>
							<div class="apartment-plan-gallery" data-apartment-plan-gallery>
								<div class="apartment-plan-stage" aria-live="polite">
									<?php foreach ( $plans as $plan_index => $plan ) : ?>
										<?php
										$plan_code       = (string) $plan['code'];
										$plan_area       = (string) $plan['area'];
										$plan_name       = trim( $plan_code . ( '' !== $plan_area ? ', ' . $plan_area : '' ) );
										$open_plan_label = '' !== $plan_name
											? sprintf( __( 'Відкрити планування %s у повному розмірі', 'project-theme' ), $plan_name )
											: __( 'Відкрити планування у повному розмірі', 'project-theme' );
										?>
										<figure class="apartment-plan-slide" id="<?php echo esc_attr( (string) $plan['dom_id'] ); ?>" data-plan-slide<?php if ( 0 !== $plan_index ) : ?> hidden<?php endif; ?>>
											<button class="apartment-plan-open" type="button" aria-label="<?php echo esc_attr( $open_plan_label ); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $plan_dialog_id ); ?>" data-plan-open data-plan-index="<?php echo esc_attr( (string) $plan_index ); ?>" data-plan-full="<?php echo esc_url( (string) $plan['image_url'] ); ?>" data-plan-alt="<?php echo esc_attr( (string) $plan['image_alt'] ); ?>" data-plan-code="<?php echo esc_attr( $plan_code ); ?>" data-plan-area="<?php echo esc_attr( $plan_area ); ?>">
												<?php echo $plan['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by WordPress or escaped above. ?>
												<span class="apartment-plan-zoom" aria-hidden="true"><?php esc_html_e( 'Збільшити', 'project-theme' ); ?> ↗</span>
											</button>
											<?php if ( '' !== $plan_code || '' !== $plan_area ) : ?>
												<figcaption class="apartment-plan-meta">
													<?php if ( '' !== $plan_code ) : ?><span><?php echo esc_html( $plan_code ); ?></span><?php endif; ?>
													<?php if ( '' !== $plan_area ) : ?><strong><?php echo esc_html( $plan_area ); ?></strong><?php endif; ?>
												</figcaption>
											<?php endif; ?>
										</figure>
									<?php endforeach; ?>
								</div>
								<?php if ( count( $plans ) > 1 ) : ?>
									<div class="apartment-plan-thumbnails" role="group" aria-label="<?php echo esc_attr__( 'Вибір планування', 'project-theme' ); ?>">
										<?php foreach ( $plans as $plan_index => $plan ) : ?>
											<?php
											$thumbnail_name  = trim( (string) $plan['code'] . ( '' !== (string) $plan['area'] ? ', ' . (string) $plan['area'] : '' ) );
											$thumbnail_label = '' !== $thumbnail_name
												? sprintf( __( 'Показати планування %s', 'project-theme' ), $thumbnail_name )
												: sprintf( __( 'Показати планування %d', 'project-theme' ), (int) $plan_index + 1 );
											?>
											<button class="apartment-plan-thumbnail" type="button" aria-label="<?php echo esc_attr( $thumbnail_label ); ?>" aria-controls="<?php echo esc_attr( (string) $plan['dom_id'] ); ?>" aria-pressed="<?php echo 0 === $plan_index ? 'true' : 'false'; ?>" data-plan-thumbnail data-plan-index="<?php echo esc_attr( (string) $plan_index ); ?>">
												<span class="apartment-plan-thumbnail-image"><?php echo $plan['thumbnail_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by WordPress or escaped above. ?></span>
												<span class="apartment-plan-thumbnail-copy">
													<?php if ( '' !== (string) $plan['code'] ) : ?><b><?php echo esc_html( (string) $plan['code'] ); ?></b><?php endif; ?>
													<?php if ( '' !== (string) $plan['area'] ) : ?><small><?php echo esc_html( (string) $plan['area'] ); ?></small><?php endif; ?>
												</span>
											</button>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php else : ?>
							<div class="apartment-plan-fallback">
								<?php echo project_theme_apartment_plan_svg( $rooms_number, $plan_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Allowlisted theme SVG. ?>
							</div>
						<?php endif; ?>
						<div class="apartment-copy"><span><?php echo esc_html( $item_label ); ?></span><h3><?php echo esc_html( $area ); ?></h3><p><?php echo esc_html( $description ); ?></p><small><?php echo esc_html( $note ); ?></small></div>
						<?php if ( '' !== $button_text ) : ?>
							<button class="button button--clay apartment-card-cta" type="button" data-open-lead data-interest="<?php echo esc_attr( $interest ); ?>" data-apartment-type="<?php echo esc_attr( $apartment_type ); ?>" data-apartment-base-type="<?php echo esc_attr( $apartment_type ); ?>" data-context="<?php echo esc_attr( $context ); ?>" data-apartment-base-context="<?php echo esc_attr( $context ); ?>"><?php echo esc_html( $button_text ); ?></button>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="swipe-hint apartment-category-controls">
				<span><?php echo esc_html( $hint ); ?></span>
				<div class="slider-controls" aria-label="<?php echo esc_attr__( 'Керування типами квартир', 'project-theme' ); ?>">
					<button class="slider-control" type="button" aria-controls="<?php echo esc_attr( $apartments_list_id ); ?>" aria-label="<?php echo esc_attr__( 'Попередній тип квартир', 'project-theme' ); ?>" data-apartment-category-prev>←</button>
					<button class="slider-control" type="button" aria-controls="<?php echo esc_attr( $apartments_list_id ); ?>" aria-label="<?php echo esc_attr__( 'Наступний тип квартир', 'project-theme' ); ?>" data-apartment-category-next>→</button>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $has_apartment_plans ) : ?>
		<dialog class="apartment-plan-dialog" id="<?php echo esc_attr( $plan_dialog_id ); ?>" aria-labelledby="<?php echo esc_attr( $plan_dialog_title_id ); ?>" data-apartment-plan-dialog>
			<div class="apartment-plan-dialog-shell">
				<header class="apartment-plan-dialog-header">
					<div>
						<span><?php esc_html_e( 'Планування квартири', 'project-theme' ); ?></span>
						<h2 id="<?php echo esc_attr( $plan_dialog_title_id ); ?>"><?php esc_html_e( 'Перегляд схеми', 'project-theme' ); ?></h2>
					</div>
					<div class="apartment-plan-dialog-actions">
						<button class="apartment-plan-dialog-zoom" type="button" aria-pressed="false" data-plan-dialog-zoom data-label-actual="<?php echo esc_attr__( 'Масштаб 100%', 'project-theme' ); ?>" data-label-fit="<?php echo esc_attr__( 'Вписати', 'project-theme' ); ?>">
							<span data-plan-dialog-zoom-label><?php esc_html_e( 'Масштаб 100%', 'project-theme' ); ?></span>
						</button>
						<button class="apartment-plan-dialog-close" type="button" aria-label="<?php echo esc_attr__( 'Закрити перегляд', 'project-theme' ); ?>" data-plan-dialog-close>
							<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
						</button>
					</div>
				</header>
				<div class="apartment-plan-dialog-frame" tabindex="0" aria-label="<?php echo esc_attr__( 'Область перегляду планування', 'project-theme' ); ?>">
					<img alt="" data-plan-dialog-image hidden>
				</div>
				<footer class="apartment-plan-dialog-footer">
					<button class="apartment-plan-dialog-nav" type="button" aria-label="<?php echo esc_attr__( 'Попереднє планування', 'project-theme' ); ?>" data-plan-dialog-prev>←</button>
					<p aria-live="polite">
						<span data-plan-dialog-code></span>
						<strong data-plan-dialog-area></strong>
						<small data-plan-dialog-counter></small>
					</p>
					<button class="apartment-plan-dialog-nav" type="button" aria-label="<?php echo esc_attr__( 'Наступне планування', 'project-theme' ); ?>" data-plan-dialog-next>→</button>
				</footer>
			</div>
		</dialog>
	<?php endif; ?>
</section>
