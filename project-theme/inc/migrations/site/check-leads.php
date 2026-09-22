<?php
/**
 * Repeatable lead/outbox integration check with fully mocked transports.
 *
 * Run with:
 * wp eval-file wp-content/themes/project-theme/inc/migrations/site/check-leads.php
 *
 * @package ProjectTheme
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! function_exists( 'project_theme_create_lead' ) || ! function_exists( 'project_theme_process_lead_notifications' ) ) {
	WP_CLI::error( 'Activate project-theme before running this check.' );
}

$assert = static function ( $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$fake_token    = '123456:' . str_repeat( 'A', 24 );
$option_values = array(
	'zb_notify_email_enabled'    => 1,
	'zb_notify_email'            => 'notifications@example.test',
	'zb_notify_telegram_enabled' => 1,
	'zb_notify_token'            => $fake_token,
	'zb_notify_chat'             => '-100123456789',
	'zb_notify_topic'            => 7,
);
$option_backup = array();
$missing_value = '__zb_missing_' . wp_generate_uuid4();
foreach ( $option_values as $name => $value ) {
	$old_value = get_option( $name, $missing_value );
	$option_backup[ $name ] = array(
		'exists' => $missing_value !== $old_value,
		'value'  => $old_value,
	);
	update_option( $name, $value, false );
}

$mail_count = 0;
$http_count = 0;
$mail_ok    = true;
$http_mode = 'success';
$last_body = array();
$lead_ids  = array();
$old_user  = get_current_user_id();
$failure   = '';

$schedule_mock = static function ( $pre, $event ) {
	return isset( $event->hook ) && 'zb_notify_lead' === $event->hook ? true : $pre;
};
$mail_mock = static function ( $pre, array $attributes ) use ( &$mail_count, &$mail_ok ): bool {
	++$mail_count;
	if (
		'notifications@example.test' !== $attributes['to']
		|| ! str_contains( (string) $attributes['message'], 'Тест сповіщень' )
	) {
		throw new RuntimeException( 'Invalid mocked email payload.' );
	}

	return $mail_ok;
};
$http_mock = static function ( $pre, array $arguments, string $url ) use ( &$http_count, &$http_mode, &$last_body, $fake_token ) {
	if ( ! str_starts_with( $url, 'https://api.telegram.org/bot' . $fake_token . '/' ) ) {
		throw new RuntimeException( 'An unexpected external HTTP request was blocked.' );
	}

	++$http_count;
	$last_body = json_decode( (string) ( $arguments['body'] ?? '' ), true );
	if (
		! is_array( $last_body )
		|| '-100123456789' !== ( $last_body['chat_id'] ?? '' )
		|| 7 !== ( $last_body['message_thread_id'] ?? 0 )
		|| mb_strlen( (string) ( $last_body['text'] ?? '' ) ) > 1702
		|| isset( $last_body['parse_mode'] )
	) {
		throw new RuntimeException( 'Invalid mocked Telegram payload.' );
	}

	if ( 'transport' === $http_mode ) {
		return new WP_Error( 'timeout', 'Mocked timeout.' );
	}

	$success = 'success' === $http_mode;
	return array(
		'headers'  => array(),
		'body'     => wp_json_encode( array( 'ok' => $success ) ),
		'response' => array(
			'code'    => $success ? 200 : 400,
			'message' => $success ? 'OK' : 'Bad Request',
		),
		'cookies'  => array(),
		'filename' => null,
	);
};

add_filter( 'pre_schedule_event', $schedule_mock, 10, 2 );
add_filter( 'pre_wp_mail', $mail_mock, 10, 2 );
add_filter( 'pre_http_request', $http_mock, 10, 3 );

$make_lead = static function ( string $request_id ) use ( &$lead_ids ): array {
	$payload = array(
		'request_id'       => $request_id,
		'name'             => 'Тест сповіщень',
		'phone'            => '+380671234567',
		'interest'         => 'Квартира',
		'message'          => '[AUTOTEST-LEADS] ' . str_repeat( 'Тест ', 350 ),
		'request_context'  => 'Перевірка форми',
		'apartment_type'   => 'Двокімнатна',
		'source_url'       => home_url( '/' ),
	);
	$result = project_theme_create_lead( $payload );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
	$lead_ids[] = (int) $result['lead_id'];

	return array( (int) $result['lead_id'], $payload );
};

try {
	$type = get_post_type_object( 'zb_lead' );
	$assert(
		$type
		&& ! $type->public
		&& ! $type->publicly_queryable
		&& ! $type->show_in_rest
		&& 'manage_options' === $type->cap->read_post,
		'Lead post type is not private/admin-only.'
	);

	$sample = array( 'nested' => array( 'value' ) );
	$assert(
		$sample === apply_filters( 'acf/update_value', $sample, 'option', array( 'key' => 'field_unrelated', 'name' => 'unrelated' ) ),
		'Notification filter changed unrelated ACF data.'
	);

	list( $lead_id, $payload ) = $make_lead( wp_generate_uuid4() );
	$assert( 'private' === get_post_status( $lead_id ), 'Lead is not private.' );
	$assert( is_array( get_post_meta( $lead_id, '_zb_lead_data', true ) ), 'Lead data was not persisted.' );
	$state = get_post_meta( $lead_id, '_zb_lead_notifications', true );
	$assert( 'pending' === ( $state['email']['status'] ?? '' ) && 'pending' === ( $state['telegram']['status'] ?? '' ), 'Notification queue was not created.' );

	$duplicate = project_theme_create_lead( $payload );
	$assert( ! is_wp_error( $duplicate ) && $lead_id === (int) $duplicate['lead_id'], 'Idempotent retry created a duplicate lead.' );
	$conflict_payload            = $payload;
	$conflict_payload['message'] = 'Changed payload';
	$conflict                    = project_theme_create_lead( $conflict_payload );
	$assert( is_wp_error( $conflict ) && 409 === (int) ( $conflict->get_error_data()['status'] ?? 0 ), 'Reused request UUID was not rejected.' );

	project_theme_process_lead_notifications( $lead_id );
	$state = get_post_meta( $lead_id, '_zb_lead_notifications', true );
	$assert( 'accepted' === ( $state['email']['status'] ?? '' ) && 'sent' === ( $state['telegram']['status'] ?? '' ), 'Mocked delivery did not complete.' );
	$assert( 1 === $mail_count && 1 === $http_count, 'Initial mocked delivery count is incorrect.' );
	$button       = $last_body['reply_markup']['inline_keyboard'][0][0] ?? array();
	$button_url   = (string) ( $button['url'] ?? '' );
	$expected_url = project_theme_lead_notification_link( $lead_id );
	if ( 'localhost' === wp_parse_url( $expected_url, PHP_URL_HOST ) ) {
		$expected_url = str_replace( '://localhost', '://127.0.0.1', $expected_url );
	}
	$button_query = array();
	parse_str( (string) wp_parse_url( $button_url, PHP_URL_QUERY ), $button_query );
	$assert(
		'Перейти до замовлення' === ( $button['text'] ?? '' )
		&& $expected_url === $button_url
		&& 'zb_open_lead' === ( $button_query['action'] ?? '' )
		&& $lead_id === absint( $button_query['lead_id'] ?? 0 ),
		'Telegram order button does not point to the protected lead route.'
	);
	$assert( str_ends_with( (string) ( $last_body['text'] ?? '' ), '…' ), 'Telegram message truncation was not applied.' );
	$assert( ! str_contains( wp_json_encode( $state ), $option_values['zb_notify_token'] ), 'Telegram token leaked into lead status.' );
	project_theme_process_lead_notifications( $lead_id );
	$assert( 1 === $mail_count && 1 === $http_count, 'Successful channels were delivered more than once.' );

	$mail_ok   = false;
	$http_mode = 'reject';
	list( $failed_id ) = $make_lead( wp_generate_uuid4() );
	for ( $attempt = 0; $attempt < 4; ++$attempt ) {
		project_theme_process_lead_notifications( $failed_id );
	}
	$state = get_post_meta( $failed_id, '_zb_lead_notifications', true );
	$assert( 3 === ( $state['email']['attempts'] ?? 0 ) && 3 === ( $state['telegram']['attempts'] ?? 0 ), 'Automatic retry limit is incorrect.' );
	$assert( 4 === $mail_count && 4 === $http_count, 'Failed delivery count is incorrect.' );
	$assert( 'private' === get_post_status( $failed_id ), 'Delivery failure removed the saved lead.' );

	$mail_ok   = true;
	$http_mode = 'transport';
	list( $unknown_id ) = $make_lead( wp_generate_uuid4() );
	project_theme_process_lead_notifications( $unknown_id );
	project_theme_process_lead_notifications( $unknown_id );
	$state = get_post_meta( $unknown_id, '_zb_lead_notifications', true );
	$assert( 'unknown' === ( $state['telegram']['status'] ?? '' ) && 5 === $http_count, 'Uncertain Telegram delivery was repeated automatically.' );
	$assert( 5 === $mail_count, 'Accepted email was repeated unexpectedly.' );

	update_option( 'zb_notify_email_enabled', 0, false );
	update_option( 'zb_notify_telegram_enabled', 0, false );
	list( $disabled_id ) = $make_lead( wp_generate_uuid4() );
	$state = get_post_meta( $disabled_id, '_zb_lead_notifications', true );
	$assert( 'disabled' === ( $state['email']['status'] ?? '' ) && 'disabled' === ( $state['telegram']['status'] ?? '' ), 'Disabled channels were queued.' );
	project_theme_process_lead_notifications( $disabled_id );
	$assert( 5 === $mail_count && 5 === $http_count, 'Disabled channel attempted delivery.' );

	$administrators = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ids',
		)
	);
	$assert( ! empty( $administrators ), 'Administrator account not found for secret-field check.' );
	$field = function_exists( 'acf_get_field' ) ? acf_get_field( 'field_zb_notify_token' ) : false;
	$assert( is_array( $field ), 'Telegram token field is not imported.' );
	wp_set_current_user( (int) $administrators[0] );
	$topic_field = acf_get_field( 'field_zb_notify_topic' );
	$assert(
		is_array( $topic_field )
		&& 0 === (int) ( $topic_field['min'] ?? -1 )
		&& acf_validate_value( 0, $topic_field, 'acf[field_zb_notify_topic]' ),
		'Optional Telegram topic ID does not accept the zero sentinel.'
	);
	$prepared = apply_filters( 'acf/prepare_field', $field );
	$assert( is_array( $prepared ) && '' === ( $prepared['value'] ?? null ), 'Stored token is visible in the admin field.' );
	apply_filters( 'acf/update_value', '', 'option', $field );
	$assert( $fake_token === get_option( 'zb_notify_token' ), 'An empty token field did not preserve the stored secret.' );
	$clear_field = acf_get_field( 'field_zb_notify_clear_token' );
	$assert( is_array( $clear_field ), 'Telegram token clear field is not imported.' );
	apply_filters( 'acf/update_value', 1, 'option', $clear_field );
	$assert( false === get_option( 'zb_notify_token', false ), 'Explicit token clearing failed.' );
	apply_filters( 'acf/update_value', $fake_token, 'option', $field );
	$assert( $fake_token === get_option( 'zb_notify_token' ), 'Replacing the Telegram token failed.' );
	wp_set_current_user( 0 );
	$assert( false === apply_filters( 'acf/prepare_field', $field ), 'A non-administrator can access notification settings.' );
} catch ( Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	wp_set_current_user( $old_user );
	foreach ( array_unique( $lead_ids ) as $lead_id ) {
		wp_clear_scheduled_hook( 'zb_notify_lead', array( $lead_id ) );
		wp_delete_post( $lead_id, true );
	}
	foreach ( $option_backup as $name => $backup ) {
		if ( $backup['exists'] ) {
			update_option( $name, $backup['value'], false );
		} else {
			delete_option( $name );
		}
	}
	remove_filter( 'pre_schedule_event', $schedule_mock, 10 );
	remove_filter( 'pre_wp_mail', $mail_mock, 10 );
	remove_filter( 'pre_http_request', $http_mock, 10 );
}

WP_CLI::log( 'Removed test leads and restored notification options. No real email or Telegram request was sent.' );
if ( '' !== $failure ) {
	WP_CLI::error( $failure );
}
WP_CLI::success( 'Lead persistence, privacy, idempotency, ACF safety, queueing, retry bounds, mocked email/Telegram delivery and secret redaction verified.' );
