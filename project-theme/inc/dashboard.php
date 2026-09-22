<?php
/**
 * WordPress dashboard tailored to the lead inbox.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep the dashboard focused by replacing every core and plugin widget.
 *
 * Lead data remains visible only to users who already have access to the
 * private lead inbox.
 */
function project_theme_setup_dashboard(): void {
	global $wp_meta_boxes;

	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	$wp_meta_boxes['dashboard'] = array();

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'project_theme_lead_summary',
		__( 'Заявки', 'project-theme' ),
		'project_theme_render_lead_dashboard_widget',
		null,
		null,
		'normal',
		'high'
	);
}
add_action( 'wp_dashboard_setup', 'project_theme_setup_dashboard', PHP_INT_MAX );

/**
 * Return the number of saved, non-trashed leads.
 */
function project_theme_dashboard_lead_count(): int {
	$counts = wp_count_posts( 'zb_lead' );

	return isset( $counts->private ) ? (int) $counts->private : 0;
}

/**
 * Render the lead total and a direct link to the private inbox.
 */
function project_theme_render_lead_dashboard_widget(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$count = project_theme_dashboard_lead_count();
	$url   = admin_url( 'edit.php?post_type=zb_lead' );
	?>
	<a
		class="project-theme-dashboard-leads"
		href="<?php echo esc_url( $url ); ?>"
		aria-label="<?php echo esc_attr( sprintf( __( 'Переглянути заявки. Усього заявок: %s', 'project-theme' ), number_format_i18n( $count ) ) ); ?>"
	>
		<span class="project-theme-dashboard-leads__icon dashicons dashicons-clipboard" aria-hidden="true"></span>
		<span class="project-theme-dashboard-leads__content">
			<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
			<span><?php esc_html_e( 'Усього заявок', 'project-theme' ); ?></span>
		</span>
		<span class="project-theme-dashboard-leads__action">
			<?php esc_html_e( 'Переглянути заявки', 'project-theme' ); ?>
			<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
		</span>
	</a>
	<?php
}

/**
 * Use a single dashboard column for every user.
 */
function project_theme_dashboard_columns( array $columns ): array {
	$columns['dashboard'] = 1;

	return $columns;
}
add_filter( 'screen_layout_columns', 'project_theme_dashboard_columns' );

/**
 * Ignore previously saved multi-column dashboard preferences.
 */
function project_theme_dashboard_layout(): int {
	return 1;
}
add_filter( 'get_user_option_screen_layout_dashboard', 'project_theme_dashboard_layout' );

/**
 * Ensure the summary cannot remain hidden in an existing user's preferences.
 *
 * @param string[]  $hidden Hidden metabox IDs.
 * @param WP_Screen $screen Current admin screen.
 * @return string[]
 */
function project_theme_dashboard_visible_widget( array $hidden, WP_Screen $screen ): array {
	if ( 'dashboard' !== $screen->id ) {
		return $hidden;
	}

	return array_values( array_diff( $hidden, array( 'project_theme_lead_summary' ) ) );
}
add_filter( 'hidden_meta_boxes', 'project_theme_dashboard_visible_widget', 10, 2 );

/**
 * Load dashboard-only presentation styles.
 */
function project_theme_enqueue_dashboard_assets( string $hook_suffix ): void {
	if ( 'index.php' !== $hook_suffix || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$style_path = get_template_directory() . '/assets/admin-dashboard.css';

	wp_enqueue_style(
		'project-theme-admin-dashboard',
		get_template_directory_uri() . '/assets/admin-dashboard.css',
		array(),
		is_file( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' )
	);
}
add_action( 'admin_enqueue_scripts', 'project_theme_enqueue_dashboard_assets' );
