<?php
/**
 * One-time, idempotent site bootstrap.
 *
 * Run explicitly with:
 * wp eval-file wp-content/themes/project-theme/inc/migrations/site/import.php
 *
 * @package ProjectTheme
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

if ( ! function_exists( 'acf_import_field_group' ) || ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF Pro must be active.' );
}

project_theme_import_acf_groups();

/**
 * Import one trusted bundled asset without creating duplicates.
 */
function project_theme_import_asset( string $filename, string $alt = '' ): int {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_zb_source_asset',
			'meta_value'     => $filename,
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = realpath( get_template_directory() . '/assets/' . $filename );
	$root   = realpath( get_template_directory() . '/assets' );
	if ( ! $source || ! $root || ! str_starts_with( $source, $root . DIRECTORY_SEPARATOR ) || ! is_file( $source ) ) {
		WP_CLI::error( 'Missing trusted theme asset: ' . $filename );
	}

	$contents = file_get_contents( $source );
	if ( false === $contents ) {
		WP_CLI::error( 'Cannot read theme asset: ' . $filename );
	}

	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$mimes     = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'svg'  => 'image/svg+xml',
		'webp' => 'image/webp',
	);
	$mime      = $mimes[ $extension ] ?? 'application/octet-stream';

	// WordPress deliberately blocks SVG uploads. This is a trusted, bundled brand
	// file, so copy only this exact source directly instead of enabling SVG uploads
	// globally for editors.
	if ( 'svg' === $extension ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			WP_CLI::error( (string) $uploads['error'] );
		}
		wp_mkdir_p( $uploads['path'] );
		$destination_name = wp_unique_filename( $uploads['path'], basename( $filename ) );
		$destination      = trailingslashit( $uploads['path'] ) . $destination_name;
		if ( ! copy( $source, $destination ) ) {
			WP_CLI::error( 'Cannot copy trusted SVG asset: ' . $filename );
		}
		$upload = array(
			'file'  => $destination,
			'url'   => trailingslashit( $uploads['url'] ) . $destination_name,
			'type'  => $mime,
			'error' => false,
		);
	} else {
		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		if ( ! empty( $upload['error'] ) ) {
			WP_CLI::error( (string) $upload['error'] );
		}
	}

	$title     = sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) );
	$id        = wp_insert_attachment(
		array(
			'post_mime_type' => $mime,
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	if ( 'svg' !== $extension ) {
		$metadata = wp_generate_attachment_metadata( $id, $upload['file'] );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $id, $metadata );
		}
	}
	update_post_meta( $id, '_zb_source_asset', $filename );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );

	return (int) $id;
}

/**
 * Return or create the one requested page.
 */
function project_theme_home_page(): int {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page' => 1,
			'title'          => 'Головна',
		)
	);
	if ( $pages ) {
		return (int) $pages[0]->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Головна',
			'post_name'   => 'home',
		)
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}

	return (int) $id;
}

/**
 * Create an anchor menu only when it does not yet exist.
 */
function project_theme_seed_menu( string $name, array $items ): int {
	$menu = wp_get_nav_menu_object( $name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			WP_CLI::error( $menu_id->get_error_message() );
		}
		$menu = wp_get_nav_menu_object( $menu_id );
	}

	$existing_items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $existing_items ) {
		foreach ( $items as $item ) {
			wp_update_nav_menu_item(
				$menu->term_id,
				0,
				array(
					'menu-item-title'  => $item[0],
					'menu-item-url'    => '#' . ltrim( $item[1], '#' ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
		}
	}

	return (int) $menu->term_id;
}

/**
 * Add disabled notification defaults without overwriting saved credentials.
 */
function project_theme_seed_notification_options(): void {
	add_option( 'zb_notify_email_enabled', 0, '', false );
	add_option( 'zb_notify_email', sanitize_email( (string) get_option( 'admin_email', '' ) ), '', false );
	add_option( 'zb_notify_telegram_enabled', 0, '', false );
	add_option( 'zb_notify_chat', '', '', false );
	add_option( 'zb_notify_topic', 0, '', false );
}

/**
 * Apply the September 2026 client copy and finish-list corrections.
 *
 * Existing editor content is only changed when it still contains the exact old
 * complex name or the exact original finish-list order.
 */
function project_theme_upgrade_client_content_v3( int $home_id ): bool {
	$sections = get_field( 'constructor', $home_id );
	if ( ! is_array( $sections ) ) {
		WP_CLI::warning( 'Homepage constructor is unavailable; client content upgrade was not applied.' );
		return false;
	}

	$old_name          = 'ЖК «Затишний»';
	$correct_name      = 'ЖК «Затишний Бориспіль»';
	$name_replacements = 0;
	$replace_name      = static function ( $value ) use ( &$replace_name, &$name_replacements, $old_name, $correct_name ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $replace_name( $item );
			}
			return $value;
		}

		if ( is_string( $value ) ) {
			$replacement_count = 0;
			$value             = str_replace( $old_name, $correct_name, $value, $replacement_count );
			$name_replacements += $replacement_count;
		}

		return $value;
	};

	$sections = $replace_name( $sections );
	$old_specs = array(
		'Індивідуальне газове опалення',
		'Газовий котел у вартості',
		'Лазерна стяжка підлоги',
		'Штукатурка стін',
		'Розведена електрика по квартирі',
		'Встановлені лічильники',
		'Радіатори під кожним вікном',
		'Двокамерні склопакети',
		'Шестикамерний профіль',
		'Утеплення мінеральною ватою',
	);
	$correct_specs = array(
		'Індивідуальне газове опалення',
		'Газовий котел у вартості',
		'Лазерна стяжка підлоги',
		'Штукатурка стін',
		'Розведена електрика по квартирі',
		'Радіатори під кожним вікном',
		'Встановлені лічильники',
		'Двокамерні склопакети',
		'Шестикамерний профіль',
		'Утеплення мінеральною ватою',
	);
	$specs_reordered = false;
	$reordered_indexes = array();

	foreach ( $sections as $section_index => $section ) {
		if ( ! is_array( $section ) || 'template-finish' !== ( $section['acf_fc_layout'] ?? '' ) || ! isset( $section['specs'] ) || ! is_array( $section['specs'] ) ) {
			continue;
		}

		$current_specs = array_map(
			static fn( $spec ): string => is_array( $spec ) && isset( $spec['text'] ) ? (string) $spec['text'] : '',
			$section['specs']
		);
		if ( $old_specs !== $current_specs ) {
			continue;
		}

		$spec_rows = array();
		foreach ( $section['specs'] as $spec ) {
			$spec_rows[ (string) $spec['text'] ] = $spec;
		}
		$sections[ $section_index ]['specs'] = array_map(
			static fn( string $text ): array => $spec_rows[ $text ],
			$correct_specs
		);
		$specs_reordered = true;
		$reordered_indexes[] = $section_index;
	}

	if ( $name_replacements > 0 || $specs_reordered ) {
		update_field( 'field_zb_constructor', $sections, $home_id );
		$verified_sections = get_field( 'constructor', $home_id );
		$verified_json     = is_array( $verified_sections ) ? wp_json_encode( $verified_sections, JSON_UNESCAPED_UNICODE ) : '';
		if ( ! is_string( $verified_json ) || str_contains( $verified_json, $old_name ) ) {
			WP_CLI::warning( 'Homepage client content could not be updated.' );
			return false;
		}
		foreach ( $reordered_indexes as $section_index ) {
			$verified_specs = isset( $verified_sections[ $section_index ]['specs'] ) && is_array( $verified_sections[ $section_index ]['specs'] )
				? array_map( static fn( $spec ): string => is_array( $spec ) && isset( $spec['text'] ) ? (string) $spec['text'] : '', $verified_sections[ $section_index ]['specs'] )
				: array();
			if ( $correct_specs !== $verified_specs ) {
				WP_CLI::warning( 'Homepage finish list could not be reordered.' );
				return false;
			}
		}
	}

	$attachment_ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => $old_name,
					'compare' => 'LIKE',
				),
			),
		)
	);
	$attachment_replacements = 0;
	foreach ( $attachment_ids as $attachment_id ) {
		$current_alt = (string) get_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', true );
		$updated_alt = str_replace( $old_name, $correct_name, $current_alt );
		if ( $updated_alt !== $current_alt ) {
			update_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', $updated_alt );
			++$attachment_replacements;
		}
	}

	WP_CLI::log(
		sprintf(
			'Client content v3: %d name corrections, %d attachment alt corrections, finish list %s.',
			$name_replacements,
			$attachment_replacements,
			$specs_reordered ? 'reordered' : 'already current or editor-modified'
		)
	);

	return true;
}

$home_id = project_theme_home_page();
update_post_meta( $home_id, '_wp_page_template', 'page-constructor.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
update_option( 'page_for_posts', 0 );
update_option( 'permalink_structure', '/%category%/%postname%/' );
update_option( 'blogname', 'ЖК «Затишний Бориспіль»' );
update_option( 'blogdescription', 'Малоповерховий житловий комплекс у Борисполі' );

$header_menu_id = project_theme_seed_menu(
	'Головне меню',
	array(
		array( 'Про комплекс', 'about' ),
		array( 'Переваги', 'advantages' ),
		array( 'Квартири', 'apartments' ),
		array( 'Документи', 'documents' ),
		array( 'Забудовник', 'developer' ),
		array( 'Умови', 'conditions' ),
	)
);
$footer_menu_id = project_theme_seed_menu(
	'Меню у футері',
	array(
		array( 'Про комплекс', 'about' ),
		array( 'Квартири', 'apartments' ),
		array( 'Документи', 'documents' ),
		array( 'Забудовник', 'developer' ),
		array( 'Умови придбання', 'conditions' ),
	)
);
$locations = get_theme_mod( 'nav_menu_locations', array() );
$locations['header-menu'] = $header_menu_id;
$locations['footer-menu'] = $footer_menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

project_theme_seed_notification_options();

$seed_version = (int) get_option( 'zb_seed_version', 0 );
if ( $seed_version >= 1 ) {
	if ( $seed_version < 2 ) {
		$legacy_note = 'Демонстраційна форма: дані нікуди не надсилаються.';
		$current_note = get_field( 'lead_form_note', 'option' );
		if ( ! is_string( $current_note ) || '' === trim( $current_note ) || $legacy_note === trim( $current_note ) ) {
			update_field( 'lead_form_note', 'Менеджер зв’яжеться з вами за вказаним номером телефону.', 'option' );
		}
		update_option( 'zb_seed_version', 2, false );
	}
	if ( $seed_version < 3 ) {
		if ( ! project_theme_upgrade_client_content_v3( $home_id ) ) {
			WP_CLI::error( 'Client content v3 upgrade was not completed.' );
		}
		update_option( 'zb_seed_version', 3, false );
	}

	flush_rewrite_rules();
	WP_CLI::success( 'Existing editable content preserved; client corrections, lead notifications, page settings and menus verified.' );
	return;
}

$images = array(
	'logo'       => project_theme_import_asset( 'zatyshnyi-logo.svg', 'Логотип ЖК «Затишний Бориспіль»' ),
	'hero'       => project_theme_import_asset( 'concept-front-entrance.jpg', 'Передпроєктна візуалізація п’ятиповерхових цегляних будинків ЖК «Затишний Бориспіль»' ),
	'courtyard'  => project_theme_import_asset( 'concept-central-courtyard.jpg', 'Передпроєктна візуалізація центрального озелененого двору ЖК «Затишний Бориспіль»' ),
	'aerial'     => project_theme_import_asset( 'concept-aerial.jpg', 'Передпроєктна візуалізація житлового комплексу з висоти' ),
	'facades'    => project_theme_import_asset( 'concept-facades.jpg', 'Передпроєктна візуалізація фасадів із червоної цегли та в’їзду до паркінгу' ),
	'playground' => project_theme_import_asset( 'concept-playground.jpg', 'Передпроєктна візуалізація дитячого майданчика на території ЖК «Затишний Бориспіль»' ),
	'finish'     => project_theme_import_asset( 'apartment-finish.png', 'Візуалізація квартири у стані під чистове оздоблення' ),
);

$map_search_url = 'https://www.google.com/maps/search/?api=1&query=%D0%BC.%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%BE%D0%BB%D0%BE%D0%BC%D0%B8%D1%87%D1%96%D0%B2%D1%81%D1%8C%D0%BA%D0%B0%2C%2073';
$map_route_url  = 'https://www.google.com/maps/dir/?api=1&destination=%D0%BC.%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%BE%D0%BB%D0%BE%D0%BC%D0%B8%D1%87%D1%96%D0%B2%D1%81%D1%8C%D0%BA%D0%B0%2C%2073';
$map_embed_url  = 'https://www.google.com/maps?output=embed&saddr=%D0%BC.%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%BE%D0%BB%D0%BE%D0%BC%D0%B8%D1%87%D1%96%D0%B2%D1%81%D1%8C%D0%BA%D0%B0%2C%2073&daddr=%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%D1%81%D1%8C%D0%BA%D0%B0%20%D0%BC%D1%96%D1%81%D1%8C%D0%BA%D0%B0%20%D1%80%D0%B0%D0%B4%D0%B0%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%B8%D1%97%D0%B2%D1%81%D1%8C%D0%BA%D0%B8%D0%B9%20%D0%A8%D0%BB%D1%8F%D1%85%2C%2072%2C%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C&dirflg=w&hl=uk';

$options = array(
	'header_logo'          => $images['logo'],
	'header_logo_alt'      => 'ЖК «Затишний Бориспіль», на початок сторінки',
	'contact_button_text'  => 'Контакти',
	'sales_kicker'         => 'Відділ продажу',
	'sales_title'          => 'Контакти',
	'sales_phones'         => array(
		array( 'phone_label' => '067-445-58-59', 'phone_url' => 'tel:+380674455859' ),
		array( 'phone_label' => '067-329-27-23', 'phone_url' => 'tel:+380673292723' ),
	),
	'sales_address_label'  => 'Адреса',
	'sales_address'        => 'м. Бориспіль, вул. Коломичівська, 73',
	'sales_address_url'    => $map_search_url,
	'header_cta_text'      => 'Обрати квартиру',
	'header_cta_interest'  => 'Квартира',
	'header_cta_context'   => 'Підбір квартири',
	'footer_logo'          => $images['logo'],
	'footer_logo_alt'      => 'Логотип ЖК «Затишний Бориспіль»',
	'footer_description'   => 'Житловий комплекс для спокійного сімейного життя у Борисполі.',
	'footer_cta_text'      => 'Обрати квартиру',
	'footer_cta_interest'  => 'Квартира',
	'footer_cta_context'   => 'Підбір квартири',
	'footer_sales_title'   => 'Відділ продажу',
	'footer_address_title' => 'Адреса',
	'footer_address'       => "м. Бориспіль,\nвул. Коломичівська, 73",
	'footer_address_url'   => $map_search_url,
	'footer_map_text'      => 'Відкрити на карті',
	'footer_nav_title'     => 'Навігація',
	'footer_copyright'     => '© {year} ЖК «Затишний Бориспіль»',
	'footer_developer_link'=> array( 'url' => 'https://agrobudmeh.com.ua/o-kompanii.html', 'title' => 'Забудовник: Агробудмеханізація', 'target' => '_blank' ),
	'lead_eyebrow'         => 'Консультація',
	'lead_title'           => 'Поговорімо про вашу майбутню квартиру',
	'lead_description'     => 'Залиште контакти — менеджер допоможе з плануванням, наявністю та умовами придбання.',
	'lead_context_label'   => 'Ваш запит',
	'lead_default_context' => 'Підбір квартири',
	'lead_form_note'       => 'Менеджер зв’яжеться з вами за вказаним номером телефону.',
);
foreach ( $options as $field_name => $value ) {
	update_field( $field_name, $value, 'option' );
}

$sections = array(
	array(
		'acf_fc_layout' => 'template-hero',
		'disable_block' => 0,
		'section_id' => 'top',
		'eyebrow' => 'Житловий комплекс у Борисполі',
		'title' => 'Дім, де легко бути собою',
		'lead' => 'Малоповерховий житловий комплекс із цегляними будинками, індивідуальним газовим опаленням, закритою територією та підземним паркінгом.',
		'primary_link' => array( 'url' => '#apartments', 'title' => 'Обрати планування', 'target' => '' ),
		'secondary_button_text' => 'Поставити запитання',
		'secondary_interest' => 'Питання про комплекс',
		'secondary_context' => 'Питання про комплекс',
		'developer_prefix' => 'Забудовник',
		'developer_name' => '«Агробудмеханізація»',
		'image' => $images['hero'],
		'image_alt' => 'Передпроєктна візуалізація п’ятиповерхових цегляних будинків ЖК «Затишний Бориспіль»',
		'image_caption' => 'Візуалізація концепції',
		'badge_value' => '5',
		'badge_label' => 'поверхів',
		'metrics' => array(
			array( 'value' => '3', 'label' => 'будинки' ),
			array( 'value' => '8', 'label' => 'секцій' ),
			array( 'value' => '5', 'label' => 'поверхів' ),
			array( 'value' => '36,73 м²', 'label' => 'мінімальна площа' ),
		),
	),
	array(
		'acf_fc_layout' => 'template-about',
		'disable_block' => 0,
		'section_id' => 'about',
		'eyebrow' => 'Про комплекс',
		'title' => 'Малоповерховий дім для спокійного життя',
		'intro' => 'ЖК «Затишний Бориспіль» — це три п’ятиповерхові будинки на вісім секцій. Будинки зводяться з червоної цегли, утеплюються мінеральною ватою та обладнуються вантажопасажирськими ліфтами.',
		'gallery' => array(
			array( 'image' => $images['courtyard'], 'image_alt' => 'Передпроєктна візуалізація центрального озелененого двору ЖК «Затишний Бориспіль»', 'caption' => 'Озеленений двір', 'is_wide' => 1 ),
			array( 'image' => $images['aerial'], 'image_alt' => 'Передпроєктна візуалізація житлового комплексу з висоти', 'caption' => 'Планування території', 'is_wide' => 0 ),
			array( 'image' => $images['facades'], 'image_alt' => 'Передпроєктна візуалізація фасадів із червоної цегли та в’їзду до паркінгу', 'caption' => 'Цегляна архітектура', 'is_wide' => 0 ),
		),
		'gallery_hint' => 'Гортайте, щоб побачити більше',
		'territory_id' => 'territory',
		'territory_eyebrow' => 'Простір біля дому',
		'territory_title' => 'Усе потрібне — на території комплексу',
		'territory_description' => 'Велика закрита територія об’єднує простір для прогулянок і дитячих ігор із повсякденною інфраструктурою.',
		'territory_image' => $images['playground'],
		'territory_image_alt' => 'Передпроєктна візуалізація дитячого майданчика на території ЖК «Затишний Бориспіль»',
		'territory_caption_kicker' => 'Візуалізація концепції',
		'territory_caption_title' => 'Велика закрита територія',
		'territory_caption_text' => 'Простір для прогулянок та дитячих ігор.',
		'territory_tags' => array_map( static fn( string $text ): array => array( 'text' => $text ), array( 'Дитячий майданчик', 'Підземний паркінг', 'Мініторговий центр', 'Комерційні приміщення на першому поверсі', 'Комори можна придбати окремо', 'Закрита територія' ) ),
		'location_id' => 'location',
		'location_eyebrow' => 'Локація',
		'location_distance' => '1100 м',
		'location_title_suffix' => 'до центру Борисполя',
		'location_description' => 'У центральній частині міста зосереджені магазини, сервіси та необхідна повсякденна інфраструктура.',
		'address_label' => 'Точна адреса',
		'address_text' => 'м. Бориспіль, вул. Коломичівська, 73',
		'route_link' => array( 'url' => $map_route_url, 'title' => 'Прокласти маршрут до ЖК', 'target' => '_blank' ),
		'map_from_label' => 'ЖК «Затишний Бориспіль»',
		'map_distance' => '≈ 1,1 км',
		'map_to_label' => 'Центр Борисполя',
		'map_embed_url' => $map_embed_url,
		'map_title' => 'Маршрут від ЖК «Затишний Бориспіль» до центру Борисполя',
	),
	array(
		'acf_fc_layout' => 'template-benefits',
		'disable_block' => 0,
		'section_id' => 'advantages',
		'eyebrow' => 'Продумано для життя',
		'title' => 'Усе важливе — вже в концепції комплексу',
		'items' => array(
			array( 'icon' => 'heating', 'overline' => 'Ваш власний комфорт', 'title' => 'Індивідуальне газове опалення', 'description' => 'Газовий котел входить у вартість кожної квартири, тож ви самі керуєте температурою вдома.', 'badge' => 'У кожній квартирі' ),
			array( 'icon' => 'brick', 'overline' => '', 'title' => 'Червона цегла', 'description' => 'Будинки з утепленням мінеральною ватою.', 'badge' => '' ),
			array( 'icon' => 'building', 'overline' => '', 'title' => 'Лише п’ять поверхів', 'description' => 'Зручний малоповерховий формат.', 'badge' => '' ),
			array( 'icon' => 'shield', 'overline' => '', 'title' => 'Закрита територія', 'description' => 'Великий простір для комфорту мешканців.', 'badge' => '' ),
			array( 'icon' => 'parking', 'overline' => '', 'title' => 'Підземний паркінг', 'description' => 'Зручне місце для автомобіля на території ЖК.', 'badge' => '' ),
			array( 'icon' => 'storage', 'overline' => '', 'title' => 'Окремі комори', 'description' => 'Додатковий простір можна придбати окремо.', 'badge' => '' ),
		),
		'hint' => 'Гортайте переваги',
	),
	array(
		'acf_fc_layout' => 'template-apartments',
		'disable_block' => 0,
		'section_id' => 'apartments',
		'eyebrow' => 'Оберіть свій формат',
		'title' => 'Квартири для різних сценаріїв життя',
		'items' => array(
			array( 'anchor_id' => 'apartment-1', 'rooms_number' => '1', 'rooms_label' => 'кімната', 'label' => 'Однокімнатні квартири', 'area' => '36,73–46,80 м²', 'description' => 'Компактний простір для першого власного житла.', 'note' => 'Актуальну наявність уточнюйте', 'button_text' => 'Уточнити наявність', 'interest' => 'Квартира', 'context' => 'Однокімнатні квартири · 36,73–46,80 м²' ),
			array( 'anchor_id' => 'apartment-2', 'rooms_number' => '2', 'rooms_label' => 'кімнати', 'label' => 'Двокімнатні квартири', 'area' => '55,44–60,70 м²', 'description' => 'Збалансоване планування для пари або молодої родини.', 'note' => 'Актуальну наявність уточнюйте', 'button_text' => 'Уточнити наявність', 'interest' => 'Квартира', 'context' => 'Двокімнатні квартири · 55,44–60,70 м²' ),
			array( 'anchor_id' => 'apartment-3', 'rooms_number' => '3', 'rooms_label' => 'кімнати', 'label' => 'Трикімнатні квартири', 'area' => '83,26–85,99 м²', 'description' => 'Просторий формат для комфортного сімейного життя.', 'note' => 'Актуальну наявність уточнюйте', 'button_text' => 'Уточнити наявність', 'interest' => 'Квартира', 'context' => 'Трикімнатні квартири · 83,26–85,99 м²' ),
		),
		'hint' => 'Гортайте планування',
	),
	array(
		'acf_fc_layout' => 'template-finish',
		'disable_block' => 0,
		'section_id' => 'finish',
		'image' => $images['finish'],
		'image_alt' => 'Візуалізація квартири у стані під чистове оздоблення',
		'image_caption' => 'Візуалізація · під чистове оздоблення',
		'eyebrow' => 'До ремонту вже ближче',
		'title' => 'Базові роботи виконані',
		'ceiling_label' => 'Висота стелі',
		'ceiling_value' => '2,80 м',
		'specs' => array_map( static fn( string $text ): array => array( 'text' => $text ), array( 'Індивідуальне газове опалення', 'Газовий котел у вартості', 'Лазерна стяжка підлоги', 'Штукатурка стін', 'Розведена електрика по квартирі', 'Радіатори під кожним вікном', 'Встановлені лічильники', 'Двокамерні склопакети', 'Шестикамерний профіль', 'Утеплення мінеральною ватою' ) ),
	),
	array(
		'acf_fc_layout' => 'template-documents',
		'disable_block' => 0,
		'section_id' => 'documents',
		'eyebrow' => 'Документи',
		'title' => 'Відкрито про будівництво',
		'intro' => 'Тут буде зібрана дозвільна та проєктна документація щодо будівництва ЖК «Затишний Бориспіль».',
		'documents' => array(
			array( 'type' => 'Дозвільні матеріали', 'title' => 'Документи на будівництво', 'description' => 'Дозвільні матеріали щодо виконання будівельних робіт.', 'note' => 'Файл буде додано після отримання від замовника', 'file' => 0, 'button_text' => '' ),
			array( 'type' => 'Земельна ділянка', 'title' => 'Правовстановлювальні документи', 'description' => 'Правовстановлювальні та супровідні матеріали.', 'note' => 'Файл буде додано після отримання від замовника', 'file' => 0, 'button_text' => '' ),
			array( 'type' => 'Проєктні матеріали', 'title' => 'Містобудівна документація', 'description' => 'Матеріали, що визначають параметри та реалізацію проєкту.', 'note' => 'Файл буде додано після отримання від замовника', 'file' => 0, 'button_text' => '' ),
		),
		'hint' => 'Гортайте документи',
		'cta_text' => 'Запросити документи',
		'cta_interest' => 'Документи',
		'cta_context' => 'Документи на будівництво',
	),
	array(
		'acf_fc_layout' => 'template-builder',
		'disable_block' => 0,
		'section_id' => 'developer',
		'eyebrow' => 'Забудовник',
		'builder_label' => 'ПрАТ «Агробудмеханізація»',
		'title' => 'Відповідальність за результат',
		'description' => 'ЖК «Затишний Бориспіль» у Борисполі реалізує багатопрофільна будівельна компанія «Агробудмеханізація».',
		'primary_link' => array( 'url' => 'https://agrobudmeh.com.ua/', 'title' => 'Сайт забудовника', 'target' => '_blank' ),
		'assurance_aria' => 'Надійність забудовника',
		'assurance_label' => 'Надійність у строках',
		'assurance_title' => 'Вчасне введення об’єктів',
		'assurance_description' => 'За інформацією забудовника, завершені ним об’єкти вводилися в експлуатацію у заявлені строки.',
		'secondary_link' => array( 'url' => 'https://agrobudmeh.com.ua/o-kompanii.html', 'title' => 'Докладніше про компанію', 'target' => '_blank' ),
	),
	array(
		'acf_fc_layout' => 'template-conditions',
		'disable_block' => 0,
		'section_id' => 'conditions',
		'eyebrow' => 'Гнучкі умови',
		'title' => 'Власна квартира — крок за кроком',
		'description' => 'Розтермінування безпосередньо від забудовника.',
		'button_text' => 'Дізнатися умови',
		'interest' => 'Умови придбання',
		'context' => 'Умови придбання та розтермінування',
		'stats' => array(
			array( 'label' => 'Перший внесок', 'prefix' => 'від', 'value' => '30%', 'suffix' => '' ),
			array( 'label' => 'Розтермінування', 'prefix' => 'до', 'value' => '24', 'suffix' => 'міс.' ),
			array( 'label' => 'Актуальна вартість', 'prefix' => '', 'value' => 'За запитом', 'suffix' => '' ),
		),
		'note' => 'Державні програми та сертифікати уточнюйте у відділі продажу відповідно до етапу введення будинку в експлуатацію.',
	),
	array(
		'acf_fc_layout' => 'template-contact',
		'disable_block' => 0,
		'section_id' => 'contact',
		'eyebrow' => 'Відділ продажу',
		'title' => 'Знайдемо планування для вашої родини',
		'description' => 'Залиште контакти — розповімо про доступні квартири та умови розтермінування.',
		'action_eyebrow' => 'Персональна консультація',
		'action_title' => 'Підберемо квартиру під ваші потреби',
		'action_description' => 'Уточніть доступні планування, актуальну наявність та умови розтермінування.',
		'button_text' => 'Залишити заявку',
		'interest' => 'Квартира',
		'context' => 'Підбір квартири',
	),
);

update_field( 'field_zb_constructor', $sections, $home_id );
update_post_meta( $home_id, '_zb_content_import_version', 1 );
update_option( 'zb_seed_version', 3, false );
flush_rewrite_rules();

WP_CLI::success( 'Homepage, media, editable ACF content, menus and reading settings imported.' );
