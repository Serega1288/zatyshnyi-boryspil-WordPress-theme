<?php
/**
 * Front-end assets.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function project_theme_enqueue_assets(): void {
	$theme      = wp_get_theme();
	$version    = $theme->get( 'Version' ) ?: '1.0.0';
	$theme_uri  = get_template_directory_uri();
	$style_path = get_template_directory() . '/assets/styles.css';
	$script_path = get_template_directory() . '/assets/script.js';

	wp_enqueue_style(
		'project-theme-fonts',
		'https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'project-theme-styles',
		$theme_uri . '/assets/styles.css',
		array( 'project-theme-fonts' ),
		is_file( $style_path ) ? (string) filemtime( $style_path ) : $version
	);

	wp_enqueue_script(
		'project-theme-script',
		$theme_uri . '/assets/script.js',
		array(),
		is_file( $script_path ) ? (string) filemtime( $script_path ) : $version,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'project-theme-script',
		'zatyshnyiTheme',
		array(
			'openMenu'    => __( 'Відкрити меню', 'project-theme' ),
			'closeMenu'   => __( 'Закрити меню', 'project-theme' ),
			'carousel'    => __( 'карусель', 'project-theme' ),
			'defaultInterest' => __( 'Квартира', 'project-theme' ),
			'defaultContext'  => __( 'Підбір квартири', 'project-theme' ),
			'demoStatus'  => __( 'Форму заповнено. Підключіть обробник заявок перед публікацією.', 'project-theme' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'project_theme_enqueue_assets' );
