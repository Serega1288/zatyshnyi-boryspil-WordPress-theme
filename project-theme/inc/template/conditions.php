<?php
/**
 * Purchase conditions section.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_id  = project_theme_section_id( (string) get_sub_field( 'section_id' ), 'conditions' );
$eyebrow     = (string) get_sub_field( 'eyebrow' );
$title       = (string) get_sub_field( 'title' );
$description = (string) get_sub_field( 'description' );
$button_text = (string) get_sub_field( 'button_text' );
$interest    = (string) get_sub_field( 'interest' );
$context     = (string) get_sub_field( 'context' );
$stats       = get_sub_field( 'stats' );
$stats       = is_array( $stats ) ? $stats : array();
$note        = (string) get_sub_field( 'note' );
?>
<section class="section conditions" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container conditions-panel reveal">
		<div class="conditions-copy"><p class="eyebrow eyebrow--light"><span></span><?php echo esc_html( $eyebrow ); ?></p><h2><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $description ); ?></p><button class="button button--white" type="button" data-open-lead data-interest="<?php echo esc_attr( $interest ); ?>" data-context="<?php echo esc_attr( $context ); ?>"><?php echo esc_html( $button_text ); ?></button></div>
		<div class="conditions-stats">
			<?php foreach ( $stats as $stat ) : ?>
				<?php
				if ( ! is_array( $stat ) ) {
					continue;
				}
				$stat_label  = isset( $stat['label'] ) ? (string) $stat['label'] : '';
				$stat_prefix = isset( $stat['prefix'] ) ? (string) $stat['prefix'] : '';
				$stat_value  = isset( $stat['value'] ) ? (string) $stat['value'] : '';
				$stat_suffix = isset( $stat['suffix'] ) ? (string) $stat['suffix'] : '';
				?>
				<article><span><?php echo esc_html( $stat_label ); ?></span><?php if ( '' === $stat_prefix && '' === $stat_suffix ) : ?><strong class="conditions-value--text"><?php echo esc_html( $stat_value ); ?></strong><?php else : ?><strong><?php if ( '' !== $stat_prefix ) : ?><small><?php echo esc_html( $stat_prefix ); ?></small><?php endif; ?><b><?php echo esc_html( $stat_value ); ?></b><?php if ( '' !== $stat_suffix ) : ?><em><?php echo esc_html( $stat_suffix ); ?></em><?php endif; ?></strong><?php endif; ?></article>
			<?php endforeach; ?>
		</div>
		<p class="conditions-note"><?php echo esc_html( $note ); ?></p>
	</div>
</section>
