<?php
/**
 * Theme supports and navigation locations.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function project_theme_setup(): void {
	load_theme_textdomain( 'project-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'header-menu' => __( 'Головне меню', 'project-theme' ),
			'footer-menu' => __( 'Меню у футері', 'project-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'project_theme_setup' );

/**
 * Keep the bundled brand mark as a favicon until an administrator sets a Site Icon.
 */
function project_theme_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}
	?>
	<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/zatyshnyi-logo.svg' ); ?>" type="image/svg+xml">
	<?php
}
add_action( 'wp_head', 'project_theme_favicon', 2 );

/**
 * Browser chrome colour from the supplied design.
 */
function project_theme_theme_colour(): void {
	echo '<meta name="theme-color" content="#163d29">' . "\n";
}
add_action( 'wp_head', 'project_theme_theme_colour', 2 );
