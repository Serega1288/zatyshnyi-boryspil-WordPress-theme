<?php
/**
 * Editable ACF options and page-constructor schema.
 *
 * Definitions are imported only when the corresponding database field group does
 * not exist. Subsequent editor changes are therefore never overwritten at runtime.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function project_theme_register_options_page(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Глобальні налаштування', 'project-theme' ),
			'menu_title' => __( 'Налаштування теми', 'project-theme' ),
			'menu_slug'  => 'theme-settings',
			'capability' => 'manage_options',
			'redirect'   => false,
		)
	);
}
add_action( 'acf/init', 'project_theme_register_options_page', 5 );

/**
 * Build a field with consistent editor sizing and return formats.
 */
function project_theme_acf_field( string $name, string $label, string $type = 'text', int $width = 100, array $settings = array() ): array {
	$field = array(
		'name'    => $name,
		'label'   => $label,
		'type'    => $type,
		'wrapper' => array( 'width' => (string) $width ),
	);

	if ( 'textarea' === $type ) {
		$field += array( 'rows' => 3, 'new_lines' => '' );
	} elseif ( 'image' === $type ) {
		$field += array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
	} elseif ( 'file' === $type ) {
		$field += array( 'return_format' => 'id', 'library' => 'all' );
	} elseif ( 'link' === $type ) {
		$field += array( 'return_format' => 'array' );
	} elseif ( 'repeater' === $type ) {
		$field += array( 'layout' => 'block', 'button_label' => __( 'Додати елемент', 'project-theme' ) );
	} elseif ( 'true_false' === $type ) {
		$field += array( 'ui' => 1 );
	} elseif ( 'select' === $type ) {
		$field += array( 'ui' => 1, 'return_format' => 'value' );
	}

	return array_replace( $field, $settings );
}

function project_theme_acf_message( string $name, string $label, string $message = '' ): array {
	return project_theme_acf_field(
		$name,
		$label,
		'message',
		100,
		array(
			'message'   => $message,
			'new_lines' => 'wpautop',
			'esc_html'  => 1,
		)
	);
}

/**
 * Add deterministic keys to fields and nested repeaters.
 */
function project_theme_acf_key_fields( array $fields, string $path ): array {
	foreach ( $fields as &$field ) {
		$name = isset( $field['name'] ) ? (string) $field['name'] : 'field';
		$field['key'] = 'field_zb_' . sanitize_key( $path . '_' . $name );
		if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$field['sub_fields'] = project_theme_acf_key_fields( $field['sub_fields'], $path . '_' . $name );
		}
		if ( in_array( $field['type'], array( 'message', 'tab' ), true ) ) {
			$field['name'] = '';
		}
	}
	unset( $field );

	return $fields;
}

function project_theme_options_group(): array {
	$f = 'project_theme_acf_field';
	$m = 'project_theme_acf_message';

	$phones = array(
		$f( 'phone_label', __( 'Телефон', 'project-theme' ), 'text', 50 ),
		$f( 'phone_url', __( 'Посилання телефону', 'project-theme' ), 'text', 50, array( 'instructions' => 'Наприклад: tel:+380674455859' ) ),
	);

	$fields = array(
		$f( 'header_tab', 'Header', 'tab', 100, array( 'placement' => 'top' ) ),
		$m( 'header_brand_message', __( 'Логотип header', 'project-theme' ), __( 'Логотип і доступний текст посилання на початок сторінки.', 'project-theme' ) ),
		$f( 'header_logo', __( 'Логотип header', 'project-theme' ), 'image', 50 ),
		$f( 'header_logo_alt', __( 'Alt логотипу', 'project-theme' ), 'text', 50 ),
		$m( 'header_contacts_message', __( 'Контакти відділу продажу', 'project-theme' ), __( 'Кнопка та вміст контактного dropdown. Телефони й адреса повторно використовуються у footer та попапі заявки.', 'project-theme' ) ),
		$f( 'contact_button_text', __( 'Текст кнопки контактів', 'project-theme' ), 'text', 25 ),
		$f( 'sales_kicker', __( 'Надзаголовок контактів', 'project-theme' ), 'text', 25 ),
		$f( 'sales_title', __( 'Заголовок контактів', 'project-theme' ), 'text', 50 ),
		$f( 'sales_phones', __( 'Телефони', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => $phones, 'button_label' => __( 'Додати телефон', 'project-theme' ) ) ),
		$f( 'sales_address_label', __( 'Підпис адреси', 'project-theme' ), 'text', 20 ),
		$f( 'sales_address', __( 'Адреса', 'project-theme' ), 'text', 40 ),
		$f( 'sales_address_url', __( 'Посилання на карту', 'project-theme' ), 'url', 40 ),
		$m( 'header_cta_message', __( 'Кнопка header', 'project-theme' ), __( 'Контекст автоматично підставляється у форму заявки.', 'project-theme' ) ),
		$f( 'header_cta_text', __( 'Текст кнопки', 'project-theme' ), 'text', 34 ),
		$f( 'header_cta_interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 33 ),
		$f( 'header_cta_context', __( 'Контекст заявки', 'project-theme' ), 'text', 33 ),

		$f( 'footer_tab', 'Footer', 'tab', 100, array( 'placement' => 'top' ) ),
		$m( 'footer_brand_message', __( 'Бренд і кнопка footer', 'project-theme' ) ),
		$f( 'footer_logo', __( 'Логотип footer', 'project-theme' ), 'image', 50 ),
		$f( 'footer_logo_alt', __( 'Alt логотипу footer', 'project-theme' ), 'text', 50 ),
		$f( 'footer_description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
		$f( 'footer_cta_text', __( 'Текст кнопки', 'project-theme' ), 'text', 34 ),
		$f( 'footer_cta_interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 33 ),
		$f( 'footer_cta_context', __( 'Контекст заявки', 'project-theme' ), 'text', 33 ),
		$m( 'footer_contacts_message', __( 'Контакти footer', 'project-theme' ) ),
		$f( 'footer_sales_title', __( 'Заголовок телефонів', 'project-theme' ), 'text', 33 ),
		$f( 'footer_address_title', __( 'Заголовок адреси', 'project-theme' ), 'text', 33 ),
		$f( 'footer_nav_title', __( 'Заголовок меню', 'project-theme' ), 'text', 34 ),
		$f( 'footer_address', __( 'Адреса у footer', 'project-theme' ), 'textarea', 40 ),
		$f( 'footer_address_url', __( 'Посилання адреси', 'project-theme' ), 'url', 40 ),
		$f( 'footer_map_text', __( 'Текст посилання на карту', 'project-theme' ), 'text', 20 ),
		$m( 'footer_bottom_message', __( 'Нижній рядок footer', 'project-theme' ) ),
		$f( 'footer_copyright', __( 'Копірайт', 'project-theme' ), 'text', 50, array( 'instructions' => __( 'Маркер {year} автоматично замінюється поточним роком.', 'project-theme' ) ) ),
		$f( 'footer_developer_link', __( 'Посилання на забудовника', 'project-theme' ), 'link', 50 ),

		$f( 'lead_tab', __( 'Попап заявки', 'project-theme' ), 'tab', 100, array( 'placement' => 'top' ) ),
		$m( 'lead_content_message', __( 'Тексти попапу', 'project-theme' ) ),
		$f( 'lead_eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 25 ),
		$f( 'lead_title', __( 'Заголовок', 'project-theme' ), 'textarea', 75 ),
		$f( 'lead_description', __( 'Опис', 'project-theme' ), 'textarea', 75 ),
		$f( 'lead_context_label', __( 'Підпис контексту', 'project-theme' ), 'text', 25 ),
		$f( 'lead_default_context', __( 'Початковий контекст', 'project-theme' ), 'text', 50 ),
		$f( 'lead_form_note', __( 'Примітка під формою', 'project-theme' ), 'textarea', 50 ),
	);

	return array(
		'key'      => 'group_zb_global_options',
		'title'    => __( 'Глобальні налаштування', 'project-theme' ),
		'fields'   => project_theme_acf_key_fields( $fields, 'options' ),
		'location' => array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'theme-settings',
				),
			),
		),
		'active'   => true,
		'style'    => 'default',
	);
}

function project_theme_constructor_group(): array {
	$f = 'project_theme_acf_field';
	$m = 'project_theme_acf_message';
	$layouts = array();

	$define = static function ( string $name, string $label, string $anchor, array $fields ) use ( &$layouts, $f, $m ): void {
		$common = array(
			$m( 'settings_message', __( 'Налаштування секції', 'project-theme' ), __( 'Якір вводиться без символу #. Саме це значення використовуйте у пункті меню, наприклад #about.', 'project-theme' ) ),
			$f( 'disable_block', __( 'Приховати секцію', 'project-theme' ), 'true_false', 30, array( 'default_value' => 0 ) ),
			$f(
				'section_id',
				__( 'ID секції (якір)', 'project-theme' ),
				'text',
				70,
				array(
					'default_value' => $anchor,
					'instructions'  => __( 'Латинські літери, цифри, дефіс або підкреслення; без # і пробілів.', 'project-theme' ),
				)
			),
		);
		$layouts[ 'layout_zb_' . str_replace( '-', '_', $name ) ] = array(
			'key'        => 'layout_zb_' . str_replace( '-', '_', $name ),
			'name'       => 'template-' . $name,
			'label'      => $label,
			'display'    => 'block',
			'sub_fields' => project_theme_acf_key_fields( array_merge( $common, $fields ), 'constructor_' . str_replace( '-', '_', $name ) ),
		);
	};

	$define(
		'hero',
		__( 'Головний екран', 'project-theme' ),
		'top',
		array(
			$m( 'content_message', __( 'Тексти й кнопки', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 35 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 65 ),
			$f( 'lead', __( 'Вступний текст', 'project-theme' ), 'textarea', 100 ),
			$f( 'primary_link', __( 'Основна кнопка', 'project-theme' ), 'link', 50 ),
			$f( 'secondary_button_text', __( 'Текст другої кнопки', 'project-theme' ), 'text', 50 ),
			$f( 'secondary_interest', __( 'Інтерес другої кнопки', 'project-theme' ), 'text', 50 ),
			$f( 'secondary_context', __( 'Контекст другої кнопки', 'project-theme' ), 'text', 50 ),
			$f( 'developer_prefix', __( 'Підпис забудовника', 'project-theme' ), 'text', 35 ),
			$f( 'developer_name', __( 'Назва забудовника', 'project-theme' ), 'text', 65 ),
			$m( 'image_message', __( 'Зображення й позначка', 'project-theme' ) ),
			$f( 'image', __( 'Зображення', 'project-theme' ), 'image', 50 ),
			$f( 'image_alt', __( 'Alt зображення', 'project-theme' ), 'text', 50 ),
			$f( 'image_caption', __( 'Підпис зображення', 'project-theme' ), 'text', 50 ),
			$f( 'badge_value', __( 'Значення позначки', 'project-theme' ), 'text', 25 ),
			$f( 'badge_label', __( 'Підпис позначки', 'project-theme' ), 'text', 25 ),
			$m( 'metrics_message', __( 'Показники', 'project-theme' ) ),
			$f( 'metrics', __( 'Показники', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array( $f( 'value', __( 'Значення', 'project-theme' ), 'text', 40 ), $f( 'label', __( 'Підпис', 'project-theme' ), 'text', 60 ) ) ) ),
		)
	);

	$define(
		'about',
		__( 'Про комплекс', 'project-theme' ),
		'about',
		array(
			$m( 'intro_message', __( 'Вступ секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 70 ),
			$f( 'intro', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$m( 'gallery_message', __( 'Візуалізації комплексу', 'project-theme' ) ),
			$f( 'gallery', __( 'Галерея', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array( $f( 'image', __( 'Зображення', 'project-theme' ), 'image', 35 ), $f( 'image_alt', __( 'Alt', 'project-theme' ), 'text', 35 ), $f( 'caption', __( 'Підпис', 'project-theme' ), 'text', 20 ), $f( 'is_wide', __( 'Широка картка', 'project-theme' ), 'true_false', 10 ) ) ) ),
			$f( 'gallery_hint', __( 'Підказка галереї', 'project-theme' ), 'text', 100 ),
			$m( 'territory_message', __( 'Територія комплексу', 'project-theme' ) ),
			$f( 'territory_id', __( 'Внутрішній якір території', 'project-theme' ), 'text', 30, array( 'default_value' => 'territory' ) ),
			$f( 'territory_eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'territory_title', __( 'Заголовок', 'project-theme' ), 'textarea', 40 ),
			$f( 'territory_description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$f( 'territory_image', __( 'Зображення', 'project-theme' ), 'image', 50 ),
			$f( 'territory_image_alt', __( 'Alt зображення', 'project-theme' ), 'text', 50 ),
			$f( 'territory_caption_kicker', __( 'Надпис фото', 'project-theme' ), 'text', 25 ),
			$f( 'territory_caption_title', __( 'Заголовок фото', 'project-theme' ), 'text', 35 ),
			$f( 'territory_caption_text', __( 'Опис фото', 'project-theme' ), 'text', 40 ),
			$f( 'territory_tags', __( 'Переваги території', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array( $f( 'text', __( 'Текст', 'project-theme' ) ) ) ) ),
			$m( 'location_message', __( 'Локація', 'project-theme' ) ),
			$f( 'location_id', __( 'Внутрішній якір локації', 'project-theme' ), 'text', 30, array( 'default_value' => 'location' ) ),
			$f( 'location_eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'location_distance', __( 'Відстань', 'project-theme' ), 'text', 20 ),
			$f( 'location_title_suffix', __( 'Текст після відстані', 'project-theme' ), 'text', 50 ),
			$f( 'location_description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$f( 'address_label', __( 'Підпис адреси', 'project-theme' ), 'text', 25 ),
			$f( 'address_text', __( 'Адреса', 'project-theme' ), 'text', 75 ),
			$f( 'route_link', __( 'Кнопка маршруту', 'project-theme' ), 'link', 100 ),
			$f( 'map_from_label', __( 'Початок маршруту', 'project-theme' ), 'text', 30 ),
			$f( 'map_distance', __( 'Відстань на карті', 'project-theme' ), 'text', 20 ),
			$f( 'map_to_label', __( 'Кінець маршруту', 'project-theme' ), 'text', 30 ),
			$f( 'map_title', __( 'Доступна назва карти', 'project-theme' ), 'text', 20 ),
			$f( 'map_embed_url', __( 'Google Maps embed URL', 'project-theme' ), 'url', 100 ),
		)
	);

	$define(
		'benefits',
		__( 'Переваги', 'project-theme' ),
		'advantages',
		array(
			$m( 'content_message', __( 'Заголовок секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 35 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 65 ),
			$m( 'items_message', __( 'Картки переваг', 'project-theme' ) ),
			$f( 'items', __( 'Переваги', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array(
				$f( 'icon', __( 'Іконка', 'project-theme' ), 'select', 25, array( 'choices' => array( 'heating' => __( 'Опалення', 'project-theme' ), 'brick' => __( 'Цегла', 'project-theme' ), 'building' => __( 'Будинок', 'project-theme' ), 'shield' => __( 'Безпека', 'project-theme' ), 'parking' => __( 'Паркінг', 'project-theme' ), 'storage' => __( 'Комора', 'project-theme' ) ) ) ),
				$f( 'overline', __( 'Короткий підпис', 'project-theme' ), 'text', 25 ),
				$f( 'title', __( 'Заголовок', 'project-theme' ), 'text', 50 ),
				$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 75 ),
				$f( 'badge', __( 'Нижня позначка', 'project-theme' ), 'text', 25 ),
			) ) ),
			$f( 'hint', __( 'Підказка слайдера', 'project-theme' ) ),
		)
	);

	$define(
		'apartments',
		__( 'Квартири', 'project-theme' ),
		'apartments',
		array(
			$m( 'content_message', __( 'Заголовок секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 35 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 65 ),
			$m( 'items_message', __( 'Типи квартир', 'project-theme' ) ),
			$f( 'items', __( 'Квартири', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array(
				$f( 'anchor_id', __( 'Внутрішній якір картки', 'project-theme' ), 'text', 25 ),
				$f( 'rooms_number', __( 'Кількість кімнат', 'project-theme' ), 'select', 20, array( 'choices' => array( '1' => '1', '2' => '2', '3' => '3' ) ) ),
				$f( 'rooms_label', __( 'Підпис кімнат', 'project-theme' ), 'text', 25 ),
				$f( 'label', __( 'Назва типу', 'project-theme' ), 'text', 30 ),
				$f( 'area', __( 'Діапазон площі', 'project-theme' ), 'text', 35 ),
				$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 65 ),
				$f( 'note', __( 'Примітка', 'project-theme' ), 'text', 50 ),
				$f( 'button_text', __( 'Текст кнопки', 'project-theme' ), 'text', 50 ),
				$f( 'interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 40 ),
				$f( 'context', __( 'Контекст заявки', 'project-theme' ), 'text', 60 ),
			) ) ),
			$f( 'hint', __( 'Підказка слайдера', 'project-theme' ) ),
		)
	);

	$define(
		'finish',
		__( 'Оздоблення квартир', 'project-theme' ),
		'finish',
		array(
			$m( 'image_message', __( 'Зображення', 'project-theme' ) ),
			$f( 'image', __( 'Зображення', 'project-theme' ), 'image', 50 ),
			$f( 'image_alt', __( 'Alt зображення', 'project-theme' ), 'text', 50 ),
			$f( 'image_caption', __( 'Підпис зображення', 'project-theme' ), 'text', 100 ),
			$m( 'content_message', __( 'Зміст секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 35 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 65 ),
			$f( 'ceiling_label', __( 'Підпис висоти стелі', 'project-theme' ), 'text', 50 ),
			$f( 'ceiling_value', __( 'Висота стелі', 'project-theme' ), 'text', 50 ),
			$f( 'specs', __( 'Характеристики', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array( $f( 'text', __( 'Характеристика', 'project-theme' ) ) ) ) ),
		)
	);

	$define(
		'documents',
		__( 'Документи', 'project-theme' ),
		'documents',
		array(
			$m( 'content_message', __( 'Заголовок секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 40 ),
			$f( 'intro', __( 'Опис', 'project-theme' ), 'textarea', 30 ),
			$m( 'documents_message', __( 'Картки документів', 'project-theme' ) ),
			$f( 'documents', __( 'Документи', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array(
				$f( 'type', __( 'Категорія', 'project-theme' ), 'text', 30 ),
				$f( 'title', __( 'Заголовок', 'project-theme' ), 'text', 70 ),
				$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 60 ),
				$f( 'note', __( 'Примітка', 'project-theme' ), 'textarea', 40 ),
				$f( 'file', __( 'Файл', 'project-theme' ), 'file', 50 ),
				$f( 'button_text', __( 'Текст посилання на файл', 'project-theme' ), 'text', 50 ),
			) ) ),
			$f( 'hint', __( 'Підказка слайдера', 'project-theme' ), 'text', 50 ),
			$f( 'cta_text', __( 'Текст кнопки', 'project-theme' ), 'text', 50 ),
			$f( 'cta_interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 50 ),
			$f( 'cta_context', __( 'Контекст заявки', 'project-theme' ), 'text', 50 ),
		)
	);

	$define(
		'builder',
		__( 'Забудовник', 'project-theme' ),
		'developer',
		array(
			$m( 'builder_message', __( 'Основна інформація', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'builder_label', __( 'Назва компанії', 'project-theme' ), 'text', 70 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 50 ),
			$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 50 ),
			$f( 'primary_link', __( 'Основне посилання', 'project-theme' ), 'link', 100 ),
			$m( 'assurance_message', __( 'Картка надійності', 'project-theme' ) ),
			$f( 'assurance_aria', __( 'Доступна назва картки', 'project-theme' ), 'text', 40 ),
			$f( 'assurance_label', __( 'Короткий підпис', 'project-theme' ), 'text', 60 ),
			$f( 'assurance_title', __( 'Заголовок', 'project-theme' ), 'textarea', 50 ),
			$f( 'assurance_description', __( 'Опис', 'project-theme' ), 'textarea', 50 ),
			$f( 'secondary_link', __( 'Додаткове посилання', 'project-theme' ), 'link', 100 ),
		)
	);

	$define(
		'conditions',
		__( 'Умови придбання', 'project-theme' ),
		'conditions',
		array(
			$m( 'content_message', __( 'Зміст секції', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 70 ),
			$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$f( 'button_text', __( 'Текст кнопки', 'project-theme' ), 'text', 34 ),
			$f( 'interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 33 ),
			$f( 'context', __( 'Контекст заявки', 'project-theme' ), 'text', 33 ),
			$m( 'stats_message', __( 'Показники', 'project-theme' ) ),
			$f( 'stats', __( 'Показники', 'project-theme' ), 'repeater', 100, array( 'sub_fields' => array( $f( 'label', __( 'Підпис', 'project-theme' ), 'text', 35 ), $f( 'prefix', __( 'Префікс', 'project-theme' ), 'text', 15 ), $f( 'value', __( 'Значення', 'project-theme' ), 'text', 30 ), $f( 'suffix', __( 'Суфікс', 'project-theme' ), 'text', 20 ) ) ) ),
			$f( 'note', __( 'Примітка', 'project-theme' ), 'textarea', 100 ),
		)
	);

	$define(
		'contact',
		__( 'Заклик до консультації', 'project-theme' ),
		'contact',
		array(
			$m( 'intro_message', __( 'Основний текст', 'project-theme' ) ),
			$f( 'eyebrow', __( 'Надзаголовок', 'project-theme' ), 'text', 30 ),
			$f( 'title', __( 'Заголовок', 'project-theme' ), 'textarea', 70 ),
			$f( 'description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$m( 'action_message', __( 'Картка консультації', 'project-theme' ) ),
			$f( 'action_eyebrow', __( 'Короткий підпис', 'project-theme' ), 'text', 30 ),
			$f( 'action_title', __( 'Заголовок', 'project-theme' ), 'textarea', 70 ),
			$f( 'action_description', __( 'Опис', 'project-theme' ), 'textarea', 100 ),
			$f( 'button_text', __( 'Текст кнопки', 'project-theme' ), 'text', 34 ),
			$f( 'interest', __( 'Інтерес у формі', 'project-theme' ), 'text', 33 ),
			$f( 'context', __( 'Контекст заявки', 'project-theme' ), 'text', 33 ),
		)
	);

	return array(
		'key'      => 'group_zb_constructor',
		'title'    => __( 'Конструктор сторінки', 'project-theme' ),
		'fields'   => array(
			array(
				'key'          => 'field_zb_constructor',
				'name'         => 'constructor',
				'label'        => __( 'Секції сторінки', 'project-theme' ),
				'type'         => 'flexible_content',
				'layouts'      => $layouts,
				'button_label' => __( 'Додати секцію', 'project-theme' ),
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'page_template',
					'operator' => '==',
					'value'    => 'page-constructor.php',
				),
			),
		),
		'hide_on_screen' => array( 'the_content' ),
		'active'   => true,
		'style'    => 'default',
		'position' => 'normal',
	);
}

/**
 * Technical notification settings displayed on the existing theme options page.
 *
 * This is a separate database field group so adding notifications does not
 * overwrite editor changes made to the original global options group.
 */
function project_theme_notifications_group(): array {
	return array(
		'key'      => 'group_zb_notification_options',
		'title'    => __( 'Сповіщення заявок', 'project-theme' ),
		'fields'   => array(
			array(
				'key'       => 'field_zb_notify_tab',
				'name'      => '',
				'label'     => __( 'Сповіщення заявок', 'project-theme' ),
				'type'      => 'tab',
				'placement' => 'top',
			),
			array(
				'key'       => 'field_zb_notify_intro',
				'name'      => '',
				'label'     => __( 'Доставка сповіщень', 'project-theme' ),
				'type'      => 'message',
				'message'   => __( 'Заявка спочатку зберігається в адмінці, а потім канали обробляються у фоновій черзі. Ці технічні налаштування доступні лише адміністратору.', 'project-theme' ),
				'new_lines' => 'wpautop',
				'esc_html'  => 1,
			),
			array(
				'key'           => 'field_zb_notify_email_enabled',
				'name'          => 'zb_notify_email_enabled',
				'label'         => __( 'Надсилати на пошту', 'project-theme' ),
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 0,
				'wrapper'       => array( 'width' => '25' ),
			),
			array(
				'key'          => 'field_zb_notify_email',
				'name'         => 'zb_notify_email',
				'label'        => __( 'Пошта для нових заявок', 'project-theme' ),
				'type'         => 'email',
				'instructions' => __( 'Надсилання виконує WordPress через wp_mail(). Для реальної доставки налаштуйте SMTP окремим плагіном або на сервері.', 'project-theme' ),
				'wrapper'      => array( 'width' => '75' ),
			),
			array(
				'key'       => 'field_zb_notify_telegram_message',
				'name'      => '',
				'label'     => __( 'Telegram-група', 'project-theme' ),
				'type'      => 'message',
				'message'   => __( 'Додайте бота до групи, дозвольте йому надсилати повідомлення та вкажіть токен BotFather і Chat ID. Для форум-групи можна додати ID теми.', 'project-theme' ),
				'new_lines' => 'wpautop',
				'esc_html'  => 1,
			),
			array(
				'key'           => 'field_zb_notify_telegram_enabled',
				'name'          => 'zb_notify_telegram_enabled',
				'label'         => __( 'Надсилати в Telegram', 'project-theme' ),
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 0,
				'wrapper'       => array( 'width' => '25' ),
			),
			array(
				'key'          => 'field_zb_notify_token',
				'name'         => 'zb_notify_token',
				'label'        => __( 'Токен Telegram-бота', 'project-theme' ),
				'type'         => 'password',
				'instructions' => __( 'Після збереження токен не показується. Щоб замінити його, введіть новий; порожнє поле залишає поточний токен.', 'project-theme' ),
				'wrapper'      => array( 'width' => '75' ),
			),
			array(
				'key'     => 'field_zb_notify_chat',
				'name'    => 'zb_notify_chat',
				'label'   => __( 'Chat ID групи', 'project-theme' ),
				'type'    => 'text',
				'wrapper' => array( 'width' => '50' ),
			),
			array(
				'key'     => 'field_zb_notify_topic',
				'name'    => 'zb_notify_topic',
				'label'   => __( 'ID теми (необов’язково)', 'project-theme' ),
				'type'    => 'number',
				'min'     => 0,
				'step'    => 1,
				'wrapper' => array( 'width' => '25' ),
			),
			array(
				'key'           => 'field_zb_notify_clear_token',
				'name'          => 'zb_notify_clear_token',
				'label'         => __( 'Видалити збережений токен', 'project-theme' ),
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 0,
				'wrapper'       => array( 'width' => '25' ),
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'theme-settings',
				),
			),
		),
		'active'   => true,
		'show_in_rest' => 0,
		'style'    => 'default',
	);
}

/**
 * Import editable definitions once. Existing database structures win.
 */
function project_theme_import_acf_groups(): void {
	if ( ! function_exists( 'acf_import_field_group' ) || ! function_exists( 'acf_get_field_group' ) ) {
		return;
	}

	if ( ! acf_get_field_group( 'group_zb_global_options' ) ) {
		acf_import_field_group( project_theme_options_group() );
	}
	if ( ! acf_get_field_group( 'group_zb_constructor' ) ) {
		acf_import_field_group( project_theme_constructor_group() );
	}
	if ( ! acf_get_field_group( 'group_zb_notification_options' ) ) {
		acf_import_field_group( project_theme_notifications_group() );
	}
}
add_action( 'acf/init', 'project_theme_import_acf_groups', 20 );

/**
 * Reject anchors that would not round-trip cleanly into menu URLs.
 *
 * @param bool|string $valid Existing validation result.
 * @param mixed       $value Field value.
 * @return bool|string
 */
function project_theme_validate_anchor( $valid, $value ) {
	if ( true !== $valid || '' === (string) $value ) {
		return $valid;
	}
	if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', (string) $value ) ) {
		return __( 'Введіть ID без # і пробілів: латинські літери, цифри, дефіс або підкреслення; перший символ — літера.', 'project-theme' );
	}

	return $valid;
}
foreach ( array( 'section_id', 'territory_id', 'location_id', 'anchor_id' ) as $anchor_field_name ) {
	add_filter( 'acf/validate_value/name=' . $anchor_field_name, 'project_theme_validate_anchor', 10, 2 );
}
