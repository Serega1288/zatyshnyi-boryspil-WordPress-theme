<?php
/**
 * Administrator-only notification settings and lead delivery outbox.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Let WP-Cron reach Apache from inside the local Docker network.
 *
 * @param array $request Cron HTTP request.
 * @return array
 */
function project_theme_fix_local_cron_request( array $request ): array {
	$url  = wp_parse_url( (string) ( $request['url'] ?? '' ) );
	$home = wp_parse_url( home_url( '/' ) );

	if (
		getenv( 'WORDPRESS_DB_HOST' )
		&& in_array( $home['host'] ?? '', array( 'localhost', '127.0.0.1' ), true )
		&& ( $url['host'] ?? '' ) === ( $home['host'] ?? '' )
		&& '/wp-cron.php' === ( $url['path'] ?? '' )
	) {
		$request['url'] = 'http://127.0.0.1/wp-cron.php' . ( isset( $url['query'] ) ? '?' . $url['query'] : '' );
		$request['args']['headers']['Host'] = $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );
	}

	return $request;
}
add_filter( 'cron_request', 'project_theme_fix_local_cron_request' );

/**
 * Whether an ACF field belongs to the protected notification settings group.
 */
function project_theme_is_notification_field( array $field ): bool {
	return str_starts_with( (string) ( $field['key'] ?? '' ), 'field_zb_notify_' );
}

/**
 * Keep zero as the valid "no topic" sentinel for the already-imported ACF field.
 *
 * @param mixed $field ACF field definition.
 * @return mixed
 */
function project_theme_load_notification_topic_field( $field ) {
	if ( is_array( $field ) ) {
		$field['min']  = 0;
		$field['step'] = 1;
	}

	return $field;
}
add_filter( 'acf/load_field/key=field_zb_notify_topic', 'project_theme_load_notification_topic_field' );

/**
 * Hide technical fields from non-administrators and never refill the token.
 *
 * @param mixed $field ACF field definition.
 * @return mixed
 */
function project_theme_prepare_notification_field( $field ) {
	if ( ! is_array( $field ) || ! project_theme_is_notification_field( $field ) ) {
		return $field;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( 'zb_notify_token' === ( $field['name'] ?? '' ) ) {
		$field['value']       = '';
		$field['placeholder'] = get_option( 'zb_notify_token' )
			? __( 'Збережено. Порожнє поле залишить токен без змін.', 'project-theme' )
			: __( 'Введіть токен Telegram-бота', 'project-theme' );
	}

	return $field;
}
add_filter( 'acf/prepare_field', 'project_theme_prepare_notification_field' );

/**
 * Load shared notification options independently from ACF option suffixes.
 *
 * @param mixed $value   Stored ACF value.
 * @param mixed $post_id ACF post ID.
 * @param array $field   ACF field.
 * @return mixed
 */
function project_theme_load_notification_value( $value, $post_id, array $field ) {
	unset( $post_id );

	if ( ! project_theme_is_notification_field( $field ) || empty( $field['name'] ) ) {
		return $value;
	}
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	if ( in_array( $field['name'], array( 'zb_notify_token', 'zb_notify_clear_token' ), true ) ) {
		return '';
	}
	if ( 'zb_notify_topic' === $field['name'] ) {
		$topic = absint( get_option( 'zb_notify_topic', 0 ) );

		return $topic > 0 ? $topic : '';
	}

	return get_option( $field['name'], $value );
}
add_filter( 'acf/load_value', 'project_theme_load_notification_value', 20, 3 );

/**
 * Save notification settings as non-autoloaded, shared WordPress options.
 *
 * @param mixed $value   Submitted ACF value.
 * @param mixed $post_id ACF post ID.
 * @param array $field   ACF field.
 * @return mixed
 */
function project_theme_update_notification_value( $value, $post_id, array $field ) {
	unset( $post_id );

	if ( ! project_theme_is_notification_field( $field ) || empty( $field['name'] ) ) {
		return $value;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}

	$name = (string) $field['name'];
	if ( 'zb_notify_clear_token' === $name ) {
		if ( $value ) {
			delete_option( 'zb_notify_token' );
		}
		return '';
	}

	if ( 'zb_notify_token' === $name ) {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			update_option( $name, trim( $value ), false );
		}
		return '';
	}

	if ( in_array( $name, array( 'zb_notify_email_enabled', 'zb_notify_telegram_enabled' ), true ) ) {
		$clean_value = $value ? 1 : 0;
	} elseif ( 'zb_notify_email' === $name ) {
		$clean_value = sanitize_email( (string) $value );
	} elseif ( 'zb_notify_topic' === $name ) {
		$clean_value = absint( $value );
	} else {
		$clean_value = sanitize_text_field( (string) $value );
	}

	update_option( $name, $clean_value, false );

	// Keep technical values outside ACF's locale-sensitive options storage.
	return '';
}
add_filter( 'acf/update_value', 'project_theme_update_notification_value', 20, 3 );

/** Validate a replacement Telegram token without requiring one initially. */
function project_theme_validate_notification_token( $valid, $value ) {
	if ( true === $valid && '' !== (string) $value && ! preg_match( '/^[0-9]+:[A-Za-z0-9_-]{20,}$/D', trim( (string) $value ) ) ) {
		return __( 'Перевірте формат токена Telegram-бота.', 'project-theme' );
	}

	return $valid;
}
add_filter( 'acf/validate_value/key=field_zb_notify_token', 'project_theme_validate_notification_token', 10, 2 );

/** Validate a numeric/group Telegram chat identifier. */
function project_theme_validate_notification_chat( $valid, $value ) {
	if ( true === $valid && '' !== (string) $value && ! preg_match( '/^-?[0-9]+$|^@[A-Za-z0-9_]+$/D', trim( (string) $value ) ) ) {
		return __( 'Вкажіть Chat ID групи, наприклад -100…, або її публічне @ім’я.', 'project-theme' );
	}

	return $valid;
}
add_filter( 'acf/validate_value/key=field_zb_notify_chat', 'project_theme_validate_notification_chat', 10, 2 );

/**
 * Return a protected route that opens a lead after login and capability checks.
 */
function project_theme_lead_notification_link( int $lead_id ): string {
	return add_query_arg(
		array(
			'action'  => 'zb_open_lead',
			'lead_id' => $lead_id,
		),
		admin_url( 'admin-post.php' )
	);
}

/** Open a notification's lead in WordPress admin. */
function project_theme_open_lead_from_notification(): void {
	$lead_id    = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
	$return_url = project_theme_lead_notification_link( $lead_id );

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( $return_url ) );
		exit;
	}

	if ( ! $lead_id || 'zb_lead' !== get_post_type( $lead_id ) || ! current_user_can( 'edit_post', $lead_id ) ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=zb_lead' ) );
		exit;
	}

	wp_safe_redirect( admin_url( 'post.php?post=' . $lead_id . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_zb_open_lead', 'project_theme_open_lead_from_notification' );
add_action( 'admin_post_nopriv_zb_open_lead', 'project_theme_open_lead_from_notification' );

/**
 * Build plain-text content without interpreting customer input as markup.
 */
function project_theme_lead_notification_text( int $lead_id, array $data ): string {
	$lines = array(
		__( 'Нова заявка', 'project-theme' ) . ' #' . $lead_id,
		__( 'Ім’я', 'project-theme' ) . ': ' . (string) ( $data['name'] ?? '' ),
		__( 'Телефон', 'project-theme' ) . ': ' . (string) ( $data['phone'] ?? '' ),
		__( 'Інтерес', 'project-theme' ) . ': ' . (string) ( $data['interest'] ?? '' ),
		__( 'Контекст', 'project-theme' ) . ': ' . (string) ( $data['request_context'] ?? '' ),
	);

	if ( ! empty( $data['apartment_type'] ) ) {
		$lines[] = __( 'Тип квартири', 'project-theme' ) . ': ' . $data['apartment_type'];
	}
	if ( ! empty( $data['message'] ) ) {
		$lines[] = '';
		$lines[] = __( 'Повідомлення', 'project-theme' ) . ': ' . $data['message'];
	}
	if ( ! empty( $data['source_url'] ) ) {
		$lines[] = '';
		$lines[] = __( 'Сторінка', 'project-theme' ) . ': ' . $data['source_url'];
	}

	return implode( "\n", $lines );
}

/**
 * Deliver one channel and return a redacted status suitable for post meta.
 *
 * @return array{status:string,detail:string}
 */
function project_theme_send_lead_channel( string $channel, int $lead_id, array $data ): array {
	$text = project_theme_lead_notification_text( $lead_id, $data );
	$link = project_theme_lead_notification_link( $lead_id );

	if ( 'email' === $channel ) {
		$email = (string) get_option( 'zb_notify_email', '' );
		if ( ! is_email( $email ) ) {
			return array(
				'status' => 'failed',
				'detail' => __( 'Не вказана коректна пошта одержувача.', 'project-theme' ),
			);
		}

		$sent = wp_mail(
			$email,
			__( 'Нова заявка', 'project-theme' ) . ' #' . $lead_id . ' — ' . get_bloginfo( 'name' ),
			$text . "\n\n" . $link,
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);

		return $sent
			? array(
				'status' => 'accepted',
				'detail' => __( 'Поштовий транспорт прийняв лист. Це не підтверджує доставку до скриньки.', 'project-theme' ),
			)
			: array(
				'status' => 'failed',
				'detail' => __( 'Поштовий транспорт відхилив лист. Перевірте SMTP.', 'project-theme' ),
			);
	}

	$token = (string) get_option( 'zb_notify_token', '' );
	$chat  = (string) get_option( 'zb_notify_chat', '' );
	if ( ! preg_match( '/^[0-9]+:[A-Za-z0-9_-]{20,}$/D', $token ) || ! preg_match( '/^-?[0-9]+$|^@[A-Za-z0-9_]+$/D', $chat ) ) {
		return array(
			'status' => 'failed',
			'detail' => __( 'Заповніть токен бота й Chat ID у налаштуваннях.', 'project-theme' ),
		);
	}

	$telegram_text = mb_substr( $text, 0, 1700 );
	if ( mb_strlen( $text ) > 1700 ) {
		$telegram_text .= "\n…";
	}

	// Telegram URL buttons reject the single-label localhost hostname.
	if ( 'localhost' === wp_parse_url( $link, PHP_URL_HOST ) ) {
		$link = str_replace( '://localhost', '://127.0.0.1', $link );
	}

	$body = array(
		'chat_id'              => $chat,
		'text'                 => $telegram_text,
		'link_preview_options' => array( 'is_disabled' => true ),
		'reply_markup'         => array(
			'inline_keyboard' => array(
				array(
					array(
						'text' => __( 'Перейти до замовлення', 'project-theme' ),
						'url'  => $link,
					),
				),
			),
		),
	);

	$topic = absint( get_option( 'zb_notify_topic', 0 ) );
	if ( $topic > 0 ) {
		$body['message_thread_id'] = $topic;
	}

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . $token . '/sendMessage',
		array(
			'timeout'             => 10,
			'redirection'         => 0,
			'limit_response_size' => 16000,
			'headers'             => array( 'Content-Type' => 'application/json' ),
			'body'                => wp_json_encode( $body ),
		)
	);

	// Never persist the raw URL, transport error or response body: each may expose the token.
	if ( is_wp_error( $response ) ) {
		return array(
			'status' => 'unknown',
			'detail' => __( 'Немає підтвердження Telegram. Перевірте групу перед повтором.', 'project-theme' ),
		);
	}

	$code   = wp_remote_retrieve_response_code( $response );
	$result = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( 200 === $code && true === ( $result['ok'] ?? false ) ) {
		return array(
			'status' => 'sent',
			'detail' => __( 'Telegram підтвердив надсилання.', 'project-theme' ),
		);
	}
	if ( 401 === $code ) {
		return array(
			'status' => 'failed',
			'detail' => __( 'Telegram не прийняв токен бота (HTTP 401). Оновіть токен.', 'project-theme' ),
		);
	}
	if ( 403 === $code ) {
		return array(
			'status' => 'failed',
			'detail' => __( 'Telegram заборонив надсилання (HTTP 403). Перевірте участь і права бота у групі.', 'project-theme' ),
		);
	}

	return array(
		'status' => $code >= 400 && $code < 500 ? 'failed' : 'unknown',
		'detail' => sprintf(
			/* translators: %d: Telegram HTTP status code. */
			__( 'Telegram: HTTP %d. Перевірте налаштування та дозвіл бота писати у групу.', 'project-theme' ),
			(int) $code
		),
	);
}

/** Queue enabled channels after the lead is safely stored. */
function project_theme_queue_lead_notifications( int $lead_id ): void {
	$state   = array();
	$pending = false;

	foreach ( array( 'email', 'telegram' ) as $channel ) {
		$enabled = (bool) get_option( 'zb_notify_' . $channel . '_enabled', false );
		$state[ $channel ] = array(
			'status'   => $enabled ? 'pending' : 'disabled',
			'attempts' => 0,
		);
		$pending = $pending || $enabled;
	}

	if ( ! add_post_meta( $lead_id, '_zb_lead_notifications', $state, true ) ) {
		return;
	}
	if ( ! $pending ) {
		return;
	}

	if ( ! wp_schedule_single_event( time(), 'zb_notify_lead', array( $lead_id ) ) ) {
		foreach ( $state as &$channel_state ) {
			if ( 'pending' === ( $channel_state['status'] ?? '' ) ) {
				$channel_state['status'] = 'failed';
				$channel_state['detail'] = __( 'Не вдалося запустити фонову чергу. Повторіть надсилання з картки заявки.', 'project-theme' );
			}
		}
		unset( $channel_state );
		update_post_meta( $lead_id, '_zb_lead_notifications', $state );
		return;
	}
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		add_action( 'shutdown', 'spawn_cron' );
	}
}
add_action( 'project_theme_lead_created', 'project_theme_queue_lead_notifications' );
add_action( 'zb_notify_lead', 'project_theme_process_lead_notifications' );

/** Process pending channels under a database lock. */
function project_theme_process_lead_notifications( int $lead_id, ?string $only_channel = null ): void {
	global $wpdb;

	if ( 'zb_lead' !== get_post_type( $lead_id ) || 'private' !== get_post_status( $lead_id ) ) {
		return;
	}

	$lock = 'zb_notify_' . substr( hash( 'sha256', $wpdb->prefix . $lead_id ), 0, 48 );
	if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) {
		return;
	}

	try {
		$data  = get_post_meta( $lead_id, '_zb_lead_data', true );
		$state = get_post_meta( $lead_id, '_zb_lead_notifications', true );
		if ( ! is_array( $data ) || ! is_array( $state ) ) {
			return;
		}

		foreach ( array( 'email', 'telegram' ) as $channel ) {
			if ( null !== $only_channel && $only_channel !== $channel ) {
				continue;
			}

			$current  = isset( $state[ $channel ] ) && is_array( $state[ $channel ] ) ? $state[ $channel ] : array();
			$status   = (string) ( $current['status'] ?? 'disabled' );
			$attempts = absint( $current['attempts'] ?? 0 );
			if ( ! in_array( $status, array( 'pending', 'failed' ), true ) || $attempts >= 3 ) {
				continue;
			}
			if ( ! get_option( 'zb_notify_' . $channel . '_enabled', false ) ) {
				$state[ $channel ]['status'] = 'disabled';
				continue;
			}

			// Mark as unknown before I/O so a crash cannot cause an automatic duplicate.
			$state[ $channel ] = array(
				'status'   => 'unknown',
				'attempts' => $attempts + 1,
				'detail'   => __( 'Надсилання почалося; підтвердження ще немає.', 'project-theme' ),
				'time'     => current_time( 'mysql' ),
			);
			update_post_meta( $lead_id, '_zb_lead_notifications', $state );

			try {
				$result = project_theme_send_lead_channel( $channel, $lead_id, $data );
			} catch ( Throwable $error ) {
				unset( $error );
				$result = array(
					'status' => 'unknown',
					'detail' => __( 'Внутрішня помилка доставки. Перевірте канал перед повтором.', 'project-theme' ),
				);
			}

			$state[ $channel ] = array_merge( $state[ $channel ], $result );
			update_post_meta( $lead_id, '_zb_lead_notifications', $state );
		}

		update_post_meta( $lead_id, '_zb_lead_notifications', $state );
		foreach ( $state as $channel_state ) {
			if (
				'failed' === ( $channel_state['status'] ?? '' )
				&& absint( $channel_state['attempts'] ?? 0 ) < 3
				&& ! wp_next_scheduled( 'zb_notify_lead', array( $lead_id ) )
			) {
				wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, 'zb_notify_lead', array( $lead_id ) );
			}
		}
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

/** Render per-channel status and a protected manual retry action. */
function project_theme_render_lead_notifications_metabox( WP_Post $post ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$labels = array(
		'pending'  => __( 'У черзі', 'project-theme' ),
		'disabled' => __( 'Вимкнено', 'project-theme' ),
		'sent'     => __( 'Надіслано', 'project-theme' ),
		'accepted' => __( 'Прийнято поштовим транспортом', 'project-theme' ),
		'failed'   => __( 'Помилка', 'project-theme' ),
		'unknown'  => __( 'Потребує перевірки', 'project-theme' ),
	);
	$state  = get_post_meta( $post->ID, '_zb_lead_notifications', true );
	$state  = is_array( $state ) ? $state : array();

	foreach ( array( 'email' => 'Email', 'telegram' => 'Telegram' ) as $key => $name ) {
		$channel = isset( $state[ $key ] ) && is_array( $state[ $key ] ) ? $state[ $key ] : array( 'status' => 'disabled' );
		$channel_status = (string) ( $channel['status'] ?? 'disabled' );
		echo '<p><strong>' . esc_html( $name ) . ':</strong> ' . esc_html( $labels[ $channel_status ] ?? $channel_status );
		if ( ! empty( $channel['detail'] ) ) {
			echo '<br>' . esc_html( $channel['detail'] );
		}
		echo '</p>';
	}

	echo '<p>' . esc_html__( 'Перед повтором непідтвердженого надсилання перевірте скриньку або Telegram-групу, щоб уникнути дубля.', 'project-theme' ) . '</p>';
	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=zb_retry_lead_notifications&lead_id=' . $post->ID ),
		'zb_retry_lead_notifications_' . $post->ID,
		'_zb_notify_nonce'
	);
	echo '<button type="submit" class="button" formmethod="post" formaction="' . esc_url( $url ) . '">' . esc_html__( 'Повторити ненадіслані', 'project-theme' ) . '</button>';
}

/** Add the notification status box to private leads. */
function project_theme_add_lead_notifications_metabox(): void {
	add_meta_box(
		'zb_lead_notifications',
		__( 'Сповіщення', 'project-theme' ),
		'project_theme_render_lead_notifications_metabox',
		'zb_lead',
		'side'
	);
}
add_action( 'add_meta_boxes_zb_lead', 'project_theme_add_lead_notifications_metabox' );

/** Queue manual retries for channels that were not confirmed as successful. */
function project_theme_retry_lead_notifications(): void {
	global $wpdb;

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'project-theme' ), '', array( 'response' => 403 ) );
	}

	$lead_id = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
	check_admin_referer( 'zb_retry_lead_notifications_' . $lead_id, '_zb_notify_nonce' );
	if ( ! $lead_id || 'zb_lead' !== get_post_type( $lead_id ) || 'private' !== get_post_status( $lead_id ) ) {
		wp_die( esc_html__( 'Заявка недоступна.', 'project-theme' ) );
	}

	$lock = 'zb_notify_' . substr( hash( 'sha256', $wpdb->prefix . $lead_id ), 0, 48 );
	if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {
		wp_die( esc_html__( 'Сповіщення ще надсилається. Повторіть пізніше.', 'project-theme' ) );
	}

	$scheduled = false;
	try {
		$state = get_post_meta( $lead_id, '_zb_lead_notifications', true );
		$state = is_array( $state ) ? $state : array();
		foreach ( array( 'email', 'telegram' ) as $channel ) {
			if ( ! in_array( $state[ $channel ]['status'] ?? '', array( 'sent', 'accepted' ), true ) ) {
				$state[ $channel ] = array(
					'status'   => 'pending',
					'attempts' => 0,
				);
			}
		}
		update_post_meta( $lead_id, '_zb_lead_notifications', $state );
		wp_clear_scheduled_hook( 'zb_notify_lead', array( $lead_id ) );
		$scheduled = wp_schedule_single_event( time(), 'zb_notify_lead', array( $lead_id ) );
		if ( ! $scheduled ) {
			foreach ( $state as &$channel_state ) {
				if ( 'pending' === ( $channel_state['status'] ?? '' ) ) {
					$channel_state['status'] = 'failed';
					$channel_state['detail'] = __( 'Не вдалося запустити фонову чергу. Повторіть спробу пізніше.', 'project-theme' );
				}
			}
			unset( $channel_state );
			update_post_meta( $lead_id, '_zb_lead_notifications', $state );
		}
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}

	if ( $scheduled ) {
		add_action( 'shutdown', 'spawn_cron' );
	}
	wp_safe_redirect( admin_url( 'post.php?post=' . $lead_id . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_zb_retry_lead_notifications', 'project_theme_retry_lead_notifications' );

/** Remove orphaned cron jobs with a permanently deleted lead. */
function project_theme_clear_deleted_lead_cron( int $post_id ): void {
	if ( 'zb_lead' === get_post_type( $post_id ) ) {
		wp_clear_scheduled_hook( 'zb_notify_lead', array( $post_id ) );
	}
}
add_action( 'before_delete_post', 'project_theme_clear_deleted_lead_cron' );
