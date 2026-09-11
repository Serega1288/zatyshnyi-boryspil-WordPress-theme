<?php
/**
 * Private lead inbox and server-side lead submission.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the private, administrator-only lead inbox.
 */
function project_theme_register_lead_post_type(): void {
	register_post_type(
		'zb_lead',
		array(
			'labels'              => array(
				'name'          => __( 'Заявки', 'project-theme' ),
				'singular_name' => __( 'Заявка', 'project-theme' ),
				'menu_name'     => __( 'Заявки', 'project-theme' ),
				'edit_item'     => __( 'Переглянути заявку', 'project-theme' ),
				'view_item'     => __( 'Переглянути заявку', 'project-theme' ),
				'search_items'  => __( 'Шукати заявки', 'project-theme' ),
				'not_found'     => __( 'Заявок не знайдено.', 'project-theme' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 26,
			'map_meta_cap'        => false,
			'delete_with_user'    => false,
			'capabilities'        => array(
				'edit_post'              => 'manage_options',
				'read_post'              => 'manage_options',
				'delete_post'            => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'create_posts'           => 'do_not_allow',
			),
		)
	);
}
add_action( 'init', 'project_theme_register_lead_post_type' );

/**
 * Leads are records, not editorial content, so keep every saved lead private.
 *
 * @param array $data    Sanitized post data.
 * @param array $postarr Raw post data.
 * @return array
 */
function project_theme_force_private_lead( array $data, array $postarr ): array {
	unset( $postarr );

	if (
		'zb_lead' === ( $data['post_type'] ?? '' )
		&& ! in_array( $data['post_status'] ?? '', array( 'trash', 'auto-draft' ), true )
	) {
		$data['post_status'] = 'private';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'project_theme_force_private_lead', 10, 2 );

/**
 * Supported values from the public interest selector.
 *
 * @return string[]
 */
function project_theme_lead_interests(): array {
	return array(
		__( 'Квартира', 'project-theme' ),
		__( 'Паркінг', 'project-theme' ),
		__( 'Комора', 'project-theme' ),
		__( 'Комерційне приміщення', 'project-theme' ),
		__( 'Документи', 'project-theme' ),
		__( 'Умови придбання', 'project-theme' ),
		__( 'Питання про комплекс', 'project-theme' ),
	);
}

/**
 * Return a consistently shaped lead validation error.
 */
function project_theme_lead_error( string $message, int $status = 422 ): WP_Error {
	return new WP_Error( 'lead_error', $message, array( 'status' => $status ) );
}

/**
 * Read, bound and sanitize a text payload value.
 *
 * @param array  $payload  Submitted payload.
 * @param string $key      Payload key.
 * @param int    $limit    Maximum character count.
 * @param bool   $required Whether an empty value is invalid.
 * @param bool   $textarea Preserve textarea line breaks.
 * @return string|WP_Error
 */
function project_theme_lead_text_value( array $payload, string $key, int $limit, bool $required = false, bool $textarea = false ) {
	$value = $payload[ $key ] ?? '';

	if ( ! is_string( $value ) || mb_strlen( $value ) > $limit ) {
		return project_theme_lead_error( __( 'Перевірте заповнення форми.', 'project-theme' ) );
	}

	$value = $textarea ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	if ( $required && '' === trim( $value ) ) {
		return project_theme_lead_error( __( 'Заповніть обов’язкові поля.', 'project-theme' ) );
	}

	return $value;
}

/**
 * Validate an optional same-site source URL.
 *
 * Preventing external URLs keeps untrusted links out of the administrator inbox
 * and notification messages.
 *
 * @return string|WP_Error
 */
function project_theme_validate_lead_source_url( string $value ) {
	$value = trim( $value );
	if ( '' === $value ) {
		return '';
	}
	if ( mb_strlen( $value ) > 2048 ) {
		return project_theme_lead_error( __( 'Некоректне джерело заявки.', 'project-theme' ) );
	}

	$url          = esc_url_raw( $value, array( 'http', 'https' ) );
	$source_parts = $url ? wp_parse_url( $url ) : false;
	$home_parts   = wp_parse_url( home_url( '/' ) );

	if (
		! is_array( $source_parts )
		|| ! is_array( $home_parts )
		|| empty( $source_parts['scheme'] )
		|| empty( $source_parts['host'] )
		|| empty( $home_parts['scheme'] )
		|| empty( $home_parts['host'] )
		|| isset( $source_parts['user'] )
		|| isset( $source_parts['pass'] )
	) {
		return project_theme_lead_error( __( 'Некоректне джерело заявки.', 'project-theme' ) );
	}

	$source_scheme = strtolower( (string) $source_parts['scheme'] );
	$home_scheme   = strtolower( (string) $home_parts['scheme'] );
	$source_port   = isset( $source_parts['port'] ) ? (int) $source_parts['port'] : ( 'https' === $source_scheme ? 443 : 80 );
	$home_port     = isset( $home_parts['port'] ) ? (int) $home_parts['port'] : ( 'https' === $home_scheme ? 443 : 80 );

	if (
		$source_scheme !== $home_scheme
		|| strtolower( (string) $source_parts['host'] ) !== strtolower( (string) $home_parts['host'] )
		|| $source_port !== $home_port
	) {
		return project_theme_lead_error( __( 'Некоректне джерело заявки.', 'project-theme' ) );
	}

	return $url;
}

/**
 * Validate and normalize the public lead payload.
 *
 * @return array|WP_Error
 */
function project_theme_prepare_lead_data( array $payload ) {
	$name = project_theme_lead_text_value( $payload, 'name', 100, true );
	if ( is_wp_error( $name ) ) {
		return $name;
	}

	$phone = project_theme_lead_text_value( $payload, 'phone', 40, true );
	if ( is_wp_error( $phone ) ) {
		return $phone;
	}
	$phone_digits = preg_replace( '/\D+/', '', $phone );
	if ( ! is_string( $phone_digits ) || strlen( $phone_digits ) < 10 || strlen( $phone_digits ) > 15 ) {
		return project_theme_lead_error( __( 'Вкажіть коректний номер телефону.', 'project-theme' ) );
	}

	$interest = project_theme_lead_text_value( $payload, 'interest', 100, true );
	if ( is_wp_error( $interest ) ) {
		return $interest;
	}
	if ( ! in_array( $interest, project_theme_lead_interests(), true ) ) {
		return project_theme_lead_error( __( 'Оберіть коректний тип запиту.', 'project-theme' ) );
	}

	$message = project_theme_lead_text_value( $payload, 'message', 2000, false, true );
	if ( is_wp_error( $message ) ) {
		return $message;
	}
	$request_context = project_theme_lead_text_value( $payload, 'request_context', 200 );
	if ( is_wp_error( $request_context ) ) {
		return $request_context;
	}
	$apartment_type = project_theme_lead_text_value( $payload, 'apartment_type', 100 );
	if ( is_wp_error( $apartment_type ) ) {
		return $apartment_type;
	}

	$source_value = $payload['source_url'] ?? '';
	if ( ! is_string( $source_value ) ) {
		return project_theme_lead_error( __( 'Некоректне джерело заявки.', 'project-theme' ) );
	}
	$source_url = project_theme_validate_lead_source_url( $source_value );
	if ( is_wp_error( $source_url ) ) {
		return $source_url;
	}

	return array(
		'name'              => $name,
		'phone'             => $phone,
		'interest'          => $interest,
		'message'           => $message,
		'request_context'   => $request_context,
		'apartment_type'    => $apartment_type,
		'source_url'        => $source_url,
	);
}

/**
 * Create a lead once for a browser-generated request UUID.
 *
 * The database lock closes the race between the idempotency lookup and insert.
 * Repeating an identical request returns the original lead; reusing its UUID for
 * different content is rejected.
 *
 * @return array|WP_Error
 */
function project_theme_create_lead( array $payload ) {
	global $wpdb;

	$request_id = $payload['request_id'] ?? '';
	if ( ! is_string( $request_id ) || ! wp_is_uuid( $request_id, 4 ) ) {
		return project_theme_lead_error( __( 'Невірний ідентифікатор запиту.', 'project-theme' ) );
	}
	$request_id = strtolower( $request_id );

	$data = project_theme_prepare_lead_data( $payload );
	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$encoded_data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $encoded_data ) ) {
		return project_theme_lead_error( __( 'Не вдалося підготувати заявку.', 'project-theme' ), 500 );
	}
	$hash = hash( 'sha256', $encoded_data );
	$lock = 'zb_lead_' . substr( hash( 'sha256', $wpdb->prefix . $request_id ), 0, 48 );

	if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {
		return project_theme_lead_error( __( 'Заявка ще обробляється. Спробуйте повторити надсилання.', 'project-theme' ), 409 );
	}

	try {
		$existing_ids = get_posts(
			array(
				'post_type'              => 'zb_lead',
				'post_status'            => array( 'private', 'trash' ),
				'numberposts'            => 1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'DESC',
				'meta_key'               => '_zb_lead_request',
				'meta_value'             => $request_id,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( $existing_ids ) {
			$existing_id = (int) $existing_ids[0];
			if ( ! hash_equals( (string) get_post_meta( $existing_id, '_zb_lead_hash', true ), $hash ) ) {
				return project_theme_lead_error( __( 'Цей запит уже використано для іншої заявки.', 'project-theme' ), 409 );
			}

			return array( 'lead_id' => $existing_id );
		}

		$lead_id = wp_insert_post(
			array(
				'post_type'   => 'zb_lead',
				'post_status' => 'private',
				'post_author' => get_current_user_id(),
				'post_title'  => sprintf(
					/* translators: 1: lead name, 2: submission date and time. */
					__( 'Заявка — %1$s — %2$s', 'project-theme' ),
					$data['name'],
					current_time( 'd.m.Y H:i:s' )
				),
				'meta_input'  => array(
					'_zb_lead_request' => $request_id,
					'_zb_lead_hash'    => $hash,
					'_zb_lead_data'    => $data,
					'_zb_lead_status'  => 'new',
				),
			),
			true
		);

		if ( is_wp_error( $lead_id ) ) {
			return project_theme_lead_error( __( 'Не вдалося зберегти заявку. Спробуйте ще раз.', 'project-theme' ), 500 );
		}

		$lead_id = (int) $lead_id;
		if (
			$request_id !== get_post_meta( $lead_id, '_zb_lead_request', true )
			|| $hash !== get_post_meta( $lead_id, '_zb_lead_hash', true )
			|| ! is_array( get_post_meta( $lead_id, '_zb_lead_data', true ) )
		) {
			wp_delete_post( $lead_id, true );
			return project_theme_lead_error( __( 'Не вдалося зберегти дані заявки.', 'project-theme' ), 500 );
		}

		try {
			do_action( 'project_theme_lead_created', $lead_id );
		} catch ( Throwable $error ) {
			unset( $error );
			$detail = __( 'Не вдалося поставити сповіщення в чергу.', 'project-theme' );
			update_post_meta(
				$lead_id,
				'_zb_lead_notifications',
				array(
					'email'    => array(
						'status'   => 'failed',
						'attempts' => 0,
						'detail'   => $detail,
					),
					'telegram' => array(
						'status'   => 'failed',
						'attempts' => 0,
						'detail'   => $detail,
					),
				)
			);
		}

		return array( 'lead_id' => $lead_id );
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

/**
 * Increment the short-lived per-IP public submission counter.
 */
function project_theme_lead_rate_limit_reached(): bool {
	$remote_address = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: 'unknown';
	$key            = 'zb_lead_rate_' . hash_hmac( 'sha256', $remote_address, wp_salt( 'nonce' ) );
	$attempts       = (int) get_transient( $key );

	if ( $attempts >= 10 ) {
		return true;
	}

	set_transient( $key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * Receive a public AJAX lead submission.
 */
function project_theme_submit_lead(): void {
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] )
		? strtoupper( $_SERVER['REQUEST_METHOD'] )
		: '';

	if ( 'POST' !== $request_method || ! check_ajax_referer( 'zb_submit_lead', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Оновіть сторінку перед надсиланням заявки.', 'project-theme' ) ), 403 );
	}

	$raw_payload = isset( $_POST['payload'] ) && is_string( $_POST['payload'] )
		? wp_unslash( $_POST['payload'] )
		: '';
	if ( '' === $raw_payload || strlen( $raw_payload ) > 12000 ) {
		wp_send_json_error( array( 'message' => __( 'Некоректний або завеликий запит.', 'project-theme' ) ), 413 );
	}

	$payload = json_decode( $raw_payload, true, 16 );
	if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() ) {
		wp_send_json_error( array( 'message' => __( 'Некоректний запит.', 'project-theme' ) ), 422 );
	}

	$honeypot = $payload['website'] ?? '';
	if ( ! is_string( $honeypot ) || '' !== trim( $honeypot ) ) {
		wp_send_json_error( array( 'message' => __( 'Некоректний запит.', 'project-theme' ) ), 422 );
	}

	if ( project_theme_lead_rate_limit_reached() ) {
		wp_send_json_error( array( 'message' => __( 'Забагато спроб. Спробуйте пізніше.', 'project-theme' ) ), 429 );
	}

	if ( ! isset( $payload['source_url'] ) || '' === $payload['source_url'] ) {
		$referer               = wp_get_raw_referer();
		$payload['source_url'] = is_string( $referer ) ? $referer : '';
	}

	$result = project_theme_create_lead( $payload );
	if ( is_wp_error( $result ) ) {
		$error_data = $result->get_error_data();
		$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 500;
		wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Ми отримали ваш запит. Менеджер відділу продажу ЖК «Затишний Бориспіль» зв’яжеться з вами найближчим часом.', 'project-theme' ),
		)
	);
}
add_action( 'wp_ajax_zb_submit_lead', 'project_theme_submit_lead' );
add_action( 'wp_ajax_nopriv_zb_submit_lead', 'project_theme_submit_lead' );

/**
 * Human-readable workflow states.
 *
 * @return array<string,string>
 */
function project_theme_lead_statuses(): array {
	return array(
		'new'        => __( 'Нова', 'project-theme' ),
		'processing' => __( 'В роботі', 'project-theme' ),
		'completed'  => __( 'Завершена', 'project-theme' ),
		'cancelled'  => __( 'Скасована', 'project-theme' ),
	);
}

/**
 * Build useful inbox columns.
 */
function project_theme_lead_admin_columns( array $columns ): array {
	$result = array();
	if ( isset( $columns['cb'] ) ) {
		$result['cb'] = $columns['cb'];
	}
	$result['title']        = __( 'Заявка', 'project-theme' );
	$result['lead_phone']   = __( 'Телефон', 'project-theme' );
	$result['lead_interest'] = __( 'Інтерес', 'project-theme' );
	$result['lead_context'] = __( 'Контекст', 'project-theme' );
	$result['lead_status']  = __( 'Статус', 'project-theme' );
	$result['date']         = $columns['date'] ?? __( 'Створено', 'project-theme' );

	return $result;
}
add_filter( 'manage_zb_lead_posts_columns', 'project_theme_lead_admin_columns' );

/**
 * Render private lead inbox column values.
 */
function project_theme_lead_admin_column( string $column, int $lead_id ): void {
	$data = get_post_meta( $lead_id, '_zb_lead_data', true );
	if ( ! is_array( $data ) ) {
		return;
	}

	if ( 'lead_phone' === $column ) {
		echo esc_html( (string) ( $data['phone'] ?? '' ) );
	} elseif ( 'lead_interest' === $column ) {
		echo esc_html( (string) ( $data['interest'] ?? '' ) );
	} elseif ( 'lead_context' === $column ) {
		$context   = (string) ( $data['request_context'] ?? '' );
		$apartment = (string) ( $data['apartment_type'] ?? '' );
		echo esc_html( $context );
		if ( '' !== $context && '' !== $apartment ) {
			echo '<br>';
		}
		if ( '' !== $apartment ) {
			echo esc_html( $apartment );
		}
	} elseif ( 'lead_status' === $column ) {
		$status = (string) get_post_meta( $lead_id, '_zb_lead_status', true );
		echo esc_html( project_theme_lead_statuses()[ $status ] ?? project_theme_lead_statuses()['new'] );
	}
}
add_action( 'manage_zb_lead_posts_custom_column', 'project_theme_lead_admin_column', 10, 2 );

/**
 * Add read-only details and the editable workflow status to a lead.
 */
function project_theme_add_lead_details_meta_box(): void {
	add_meta_box(
		'zb_lead_details',
		__( 'Дані заявки', 'project-theme' ),
		'project_theme_render_lead_details_meta_box',
		'zb_lead',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_zb_lead', 'project_theme_add_lead_details_meta_box' );

/**
 * Render lead details with output escaping.
 */
function project_theme_render_lead_details_meta_box( WP_Post $post ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$data = get_post_meta( $post->ID, '_zb_lead_data', true );
	if ( ! is_array( $data ) ) {
		echo '<p>' . esc_html__( 'Дані заявки недоступні.', 'project-theme' ) . '</p>';
		return;
	}

	$fields = array(
		'name'            => __( 'Ім’я', 'project-theme' ),
		'phone'           => __( 'Телефон', 'project-theme' ),
		'interest'        => __( 'Інтерес', 'project-theme' ),
		'request_context' => __( 'Контекст заявки', 'project-theme' ),
		'apartment_type'  => __( 'Тип квартири', 'project-theme' ),
		'message'         => __( 'Коментар', 'project-theme' ),
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ( $fields as $key => $label ) {
		$value = (string) ( $data[ $key ] ?? '' );
		echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ?: '—' ) ) . '</td></tr>';
	}

	$source_url = (string) ( $data['source_url'] ?? '' );
	echo '<tr><th style="width:180px">' . esc_html__( 'Джерело', 'project-theme' ) . '</th><td>';
	if ( '' !== $source_url ) {
		echo '<a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $source_url ) . '</a>';
	} else {
		echo '—';
	}
	echo '</td></tr></tbody></table>';

	$status   = (string) get_post_meta( $post->ID, '_zb_lead_status', true );
	$statuses = project_theme_lead_statuses();
	if ( ! isset( $statuses[ $status ] ) ) {
		$status = 'new';
	}

	wp_nonce_field( 'zb_lead_status_' . $post->ID, 'zb_lead_status_nonce' );
	echo '<p><label for="zb-lead-status"><strong>' . esc_html__( 'Статус заявки', 'project-theme' ) . '</strong></label><br>';
	echo '<select name="zb_lead_status" id="zb-lead-status">';
	foreach ( $statuses as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p>';
}

/**
 * Save only the administrator-controlled workflow status.
 */
function project_theme_save_lead_status( int $lead_id ): void {
	if (
		! current_user_can( 'manage_options' )
		|| wp_is_post_autosave( $lead_id )
		|| wp_is_post_revision( $lead_id )
		|| ! isset( $_POST['zb_lead_status_nonce'] )
		|| ! is_string( $_POST['zb_lead_status_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['zb_lead_status_nonce'] ) ),
			'zb_lead_status_' . $lead_id
		)
	) {
		return;
	}

	$status = isset( $_POST['zb_lead_status'] ) && is_string( $_POST['zb_lead_status'] )
		? sanitize_key( wp_unslash( $_POST['zb_lead_status'] ) )
		: '';
	if ( isset( project_theme_lead_statuses()[ $status ] ) ) {
		update_post_meta( $lead_id, '_zb_lead_status', $status );
	}
}
add_action( 'save_post_zb_lead', 'project_theme_save_lead_status' );
