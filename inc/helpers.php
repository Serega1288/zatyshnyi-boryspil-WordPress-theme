<?php
/**
 * Small rendering helpers shared by section templates.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an ACF option while retaining a useful theme fallback.
 *
 * @param string $name    Field name.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function project_theme_option( string $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'option' );
	return false === $value || null === $value || '' === $value ? $default : $value;
}

/**
 * Normalise an editor-provided anchor and keep every rendered DOM ID unique.
 */
function project_theme_unique_dom_id( string $requested ): string {
	static $used = array();

	$base = sanitize_title( ltrim( trim( $requested ), '#' ) );
	if ( '' === $base ) {
		$base = 'section';
	}

	if ( ! isset( $used[ $base ] ) ) {
		$used[ $base ] = 1;
		return $base;
	}

	++$used[ $base ];
	$candidate = $base . '-' . $used[ $base ];
	while ( isset( $used[ $candidate ] ) ) {
		++$used[ $base ];
		$candidate = $base . '-' . $used[ $base ];
	}
	$used[ $candidate ] = 1;

	return $candidate;
}

/**
 * Resolve a Flexible Content section anchor.
 */
function project_theme_section_id( string $requested, string $fallback ): string {
	return project_theme_unique_dom_id( '' !== trim( $requested ) ? $requested : $fallback );
}

/**
 * Render an ACF attachment with responsive WordPress image attributes.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $alt           Editor-provided alternative text.
 * @param array  $attributes    Additional img attributes.
 */
function project_theme_image_html( int $attachment_id, string $alt = '', array $attributes = array() ): string {
	if ( $attachment_id < 1 ) {
		return '';
	}

	$attributes['alt'] = $alt;
	$html = wp_get_attachment_image( $attachment_id, 'full', false, $attributes );
	if ( $html ) {
		return $html;
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return '';
	}

	$attribute_html = '';
	foreach ( $attributes as $name => $value ) {
		if ( false === $value || null === $value ) {
			continue;
		}
		$attribute_html .= sprintf( ' %s="%s"', esc_attr( (string) $name ), esc_attr( (string) $value ) );
	}

	return sprintf( '<img src="%s"%s>', esc_url( $url ), $attribute_html );
}

/**
 * Normalise an ACF Link field.
 *
 * @param mixed $value Link field value.
 * @return array{url:string,title:string,target:string}
 */
function project_theme_link( $value ): array {
	if ( ! is_array( $value ) ) {
		return array( 'url' => '', 'title' => '', 'target' => '' );
	}

	return array(
		'url'    => isset( $value['url'] ) ? (string) $value['url'] : '',
		'title'  => isset( $value['title'] ) ? (string) $value['title'] : '',
		'target' => isset( $value['target'] ) ? (string) $value['target'] : '',
	);
}

/**
 * Allowlisted icons from the supplied benefits cards.
 */
function project_theme_icon_svg( string $icon ): string {
	$paths = array(
		'heating' => '<path d="M12.8 2.5c.5 3.2-1.2 4.7-2.8 6.4-1.3 1.4-2.4 2.9-2.4 5.2A4.5 4.5 0 0 0 12 18.6a4.5 4.5 0 0 0 4.5-4.5c0-1.7-.7-3.1-1.9-4.5.1 2.2-.8 3.6-2.3 4.5.2-2.3-.9-3.6-2-4.8"/>',
		'brick'   => '<path d="M3 5h18v14H3zM3 10h18M3 15h18M8 5v5m8-5v5m-5 0v5m-5 0v4m10-4v4"/>',
		'building'=> '<path d="M5 21V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v17M3 21h18M9 6h2m2 0h2M9 10h2m2 0h2M9 14h2m2 0h2M10 21v-3h4v3"/>',
		'shield'  => '<path d="m12 3 8 3v5c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.5"/>',
		'parking' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 17V7.2h3.4a3.1 3.1 0 0 1 0 6.2H9.5m0-6.2v6.2"/>',
		'storage' => '<path d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10"/>',
	);

	if ( ! isset( $paths[ $icon ] ) ) {
		$icon = 'building';
	}

	return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths[ $icon ] . '</svg>';
}

/**
 * Allowlisted apartment plan drawings from the source markup.
 */
function project_theme_apartment_plan_svg( string $rooms, string $label ): string {
	$plans = array(
		'1' => '<rect x="10" y="10" width="240" height="160" rx="10"/><path d="M105 10v95H10M105 70h75v100M180 70h70M180 125h70"/><path class="door" d="M105 105a34 34 0 0 0-34 34M180 125a30 30 0 0 1 30 30"/>',
		'2' => '<rect x="10" y="10" width="240" height="160" rx="10"/><path d="M95 10v160M175 10v105M95 95h80M175 60h75M175 115h75"/><path class="door" d="M95 95a30 30 0 0 1-30 30M175 115a28 28 0 0 0 28 28"/>',
		'3' => '<rect x="10" y="10" width="240" height="160" rx="10"/><path d="M85 10v160M165 10v160M85 75h80M165 55h85M165 120h85"/><path class="door" d="M85 75a28 28 0 0 1-28 28M165 120a28 28 0 0 0 28 28"/>',
	);
	$key = isset( $plans[ $rooms ] ) ? $rooms : '1';

	return sprintf(
		'<svg class="plan" viewBox="0 0 260 180" role="img" aria-label="%s">%s</svg>',
		esc_attr( $label ),
		$plans[ $key ]
	);
}
