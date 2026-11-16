<?php
/**
 * Administrator-only CSV download of the complete lead inbox.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Add a native admin button, independent of the list's filters and pagination. */
function project_theme_lead_export_button( string $which ): void {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'edit-zb_lead' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=zb_export_leads' ), 'zb_export_leads' );
	$description = __( 'Усі заявки з усіх сторінок, без кошика. Пошук і фільтри не враховуються.', 'project-theme' );
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '" title="' . esc_attr( $description ) . '" aria-describedby="zb-export-description">' . esc_html__( 'Завантажити всі заявки (CSV)', 'project-theme' ) . '</a>';
	echo '<span class="screen-reader-text" id="zb-export-description">' . esc_html( $description ) . '</span></div>';
}
add_action( 'manage_posts_extra_tablenav', 'project_theme_lead_export_button' );

/**
 * Treat formula-like values as text, including whitespace-prefixed formulas.
 * CSV quoting alone does not prevent Excel from evaluating a cell.
 */
function project_theme_lead_csv_cell( string $value ): string {
	if ( preg_match( '/^[\s\p{Z}\x{FEFF}]*[=+@\-]/u', $value ) || preg_match( '/^[\t\r\n]/', $value ) ) {
		return "'" . $value;
	}
	return $value;
}

/** Stream all saved private leads; never accept query filters from the request. */
function project_theme_export_leads(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав для експорту заявок.', 'project-theme' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'zb_export_leads' );

	$output = fopen( 'php://output', 'w' );
	if ( false === $output ) {
		wp_die( esc_html__( 'Не вдалося створити CSV.', 'project-theme' ), '', array( 'response' => 500 ) );
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="zatyshnyi-leads-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
	header( 'X-Content-Type-Options: nosniff' );
	// UTF-8 BOM and Excel's separator directive support Ukrainian Windows locales.
	fwrite( $output, "\xEF\xBB\xBFsep=;\r\n" );
	$columns = project_theme_lead_admin_columns( array() );
	fputcsv( $output, array_values( $columns ), ';', '"', '', "\r\n" );

	global $wpdb;
	// Keyset batches avoid loading the entire inbox and offset skips on deletion.
	$before_id = PHP_INT_MAX;
	do {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND ID < %d ORDER BY ID DESC LIMIT 500",
			'zb_lead', 'private', $before_id
		) );
		foreach ( $ids as $id ) {
			$before_id = (int) $id;
			$post = get_post( (int) $id );
			if ( ! $post || 'private' !== $post->post_status ) {
				continue;
			}
			$row  = array( $post->post_title );
			foreach ( array( 'lead_phone', 'lead_interest', 'lead_context', 'lead_status' ) as $column ) {
				$row[] = project_theme_lead_column_value( $column, (int) $id );
			}
			$row[] = get_post_time( 'Y-m-d H:i:s', false, $post );
			fputcsv( $output, array_map( 'project_theme_lead_csv_cell', $row ), ';', '"', '', "\r\n" );
		}
	} while ( 500 === count( $ids ) );
	fclose( $output );
	exit;
}
add_action( 'admin_post_zb_export_leads', 'project_theme_export_leads' );
