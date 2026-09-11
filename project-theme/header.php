<?php
/**
 * Theme header.
 *
 * @package ProjectTheme
 */

$header_logo         = get_field( 'header_logo', 'option' );
$header_logo_alt     = get_field( 'header_logo_alt', 'option' );
$contact_button_text = get_field( 'contact_button_text', 'option' );
$sales_kicker        = get_field( 'sales_kicker', 'option' );
$sales_title         = get_field( 'sales_title', 'option' );
$sales_phones        = get_field( 'sales_phones', 'option' );
$sales_address_label = get_field( 'sales_address_label', 'option' );
$sales_address       = get_field( 'sales_address', 'option' );
$sales_address_url   = get_field( 'sales_address_url', 'option' );
$header_cta_text     = get_field( 'header_cta_text', 'option' );
$header_cta_interest = get_field( 'header_cta_interest', 'option' );
$header_cta_context  = get_field( 'header_cta_context', 'option' );

$logo_fallback = get_template_directory_uri() . '/assets/zatyshnyi-logo.svg';
$header_logo_url = $logo_fallback;

if ( is_array( $header_logo ) ) {
	$header_logo_id = isset( $header_logo['ID'] ) ? (int) $header_logo['ID'] : ( isset( $header_logo['id'] ) ? (int) $header_logo['id'] : 0 );
	$header_logo_url = ! empty( $header_logo['url'] ) ? (string) $header_logo['url'] : (string) wp_get_attachment_image_url( $header_logo_id, 'full' );
} elseif ( is_numeric( $header_logo ) ) {
	$header_logo_url = (string) wp_get_attachment_image_url( (int) $header_logo, 'full' );
} elseif ( is_string( $header_logo ) && '' !== trim( $header_logo ) ) {
	$header_logo_url = $header_logo;
}

if ( '' === $header_logo_url ) {
	$header_logo_url = $logo_fallback;
}

$header_logo_alt     = is_string( $header_logo_alt ) ? $header_logo_alt : '';
$contact_button_text = is_string( $contact_button_text ) && '' !== trim( $contact_button_text ) ? $contact_button_text : __( 'Контакти', 'project-theme' );
$sales_kicker        = is_string( $sales_kicker ) && '' !== trim( $sales_kicker ) ? $sales_kicker : __( 'Відділ продажу', 'project-theme' );
$sales_title         = is_string( $sales_title ) && '' !== trim( $sales_title ) ? $sales_title : __( 'Контакти', 'project-theme' );
$sales_address_label = is_string( $sales_address_label ) && '' !== trim( $sales_address_label ) ? $sales_address_label : __( 'Адреса', 'project-theme' );
$sales_address       = is_string( $sales_address ) && '' !== trim( $sales_address ) ? $sales_address : __( 'м. Бориспіль, вул. Коломичівська, 73', 'project-theme' );
$sales_address_url   = is_string( $sales_address_url ) && '' !== trim( $sales_address_url ) ? $sales_address_url : 'https://www.google.com/maps/search/?api=1&query=%D0%BC.%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%BE%D0%BB%D0%BE%D0%BC%D0%B8%D1%87%D1%96%D0%B2%D1%81%D1%8C%D0%BA%D0%B0%2C%2073';
$header_cta_text     = is_string( $header_cta_text ) && '' !== trim( $header_cta_text ) ? $header_cta_text : __( 'Обрати квартиру', 'project-theme' );
$header_cta_interest = is_string( $header_cta_interest ) && '' !== trim( $header_cta_interest ) ? $header_cta_interest : __( 'Квартира', 'project-theme' );
$header_cta_context  = is_string( $header_cta_context ) && '' !== trim( $header_cta_context ) ? $header_cta_context : __( 'Підбір квартири', 'project-theme' );

if ( is_array( $sales_phones ) ) {
	$sales_phones = array_values(
		array_filter(
			$sales_phones,
			static function ( $phone ): bool {
				return is_array( $phone )
					&& ! empty( $phone['phone_label'] )
					&& ! empty( $phone['phone_url'] );
			}
		)
	);
}

if ( ! is_array( $sales_phones ) || empty( $sales_phones ) ) {
	$sales_phones = array(
		array(
			'phone_label' => '067-445-58-59',
			'phone_url'   => 'tel:+380674455859',
		),
		array(
			'phone_label' => '067-329-27-23',
			'phone_url'   => 'tel:+380673292723',
		),
	);
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo esc_attr__( 'ЖК «Затишний Бориспіль» — малоповерховий житловий комплекс із цегляними будинками, індивідуальним опаленням і закритою територією.', 'project-theme' ); ?>" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
	<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти до основного вмісту', 'project-theme' ); ?></a>

	<header class="soft-header" data-header>
		<div class="container header-row">
			<a class="brand" href="#top" aria-label="<?php echo esc_attr__( 'ЖК «Затишний Бориспіль», на початок сторінки', 'project-theme' ); ?>">
				<img src="<?php echo esc_url( $header_logo_url ); ?>" alt="<?php echo esc_attr( $header_logo_alt ); ?>" width="914" height="647" />
			</a>
			<button class="menu-button" type="button" aria-expanded="false" aria-controls="soft-nav" aria-label="<?php echo esc_attr__( 'Відкрити меню', 'project-theme' ); ?>" data-menu-button>
				<span></span><span></span><span></span>
			</button>
			<nav class="soft-nav" id="soft-nav" aria-label="<?php echo esc_attr__( 'Головна навігація', 'project-theme' ); ?>" data-menu>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'header-menu',
						'container'      => false,
						'menu_class'     => 'menu',
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
			<div class="header-actions">
				<button class="contact-toggle" type="button" aria-label="<?php echo esc_attr__( 'Контакти відділу продажу', 'project-theme' ); ?>" aria-expanded="false" aria-controls="sales-contacts" data-contact-button>
					<svg class="contact-toggle-phone" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 3.8 9.6 8 7.9 9.7c1.1 2.2 2.7 3.8 4.9 4.9l1.7-1.7 4.2 2.4-.7 3.4c-.2.9-1 1.5-1.9 1.5C9.3 20.2 3.8 14.7 3.8 7.9c0-.9.6-1.7 1.5-1.9l1.9-.4Z" /></svg>
					<span class="contact-toggle-label"><?php echo esc_html( $contact_button_text ); ?></span>
					<svg class="contact-toggle-chevron" viewBox="0 0 16 16" aria-hidden="true"><path d="m3 6 5 5 5-5" /></svg>
				</button>
				<button class="button button--clay header-button" type="button" data-open-lead data-interest="<?php echo esc_attr( $header_cta_interest ); ?>" data-context="<?php echo esc_attr( $header_cta_context ); ?>"><?php echo esc_html( $header_cta_text ); ?></button>
			</div>

			<aside class="contact-popover" id="sales-contacts" aria-labelledby="sales-contacts-title" data-contact-popover hidden inert tabindex="-1">
				<div class="contact-popover-head">
					<div><span><?php echo esc_html( $sales_kicker ); ?></span><h2 id="sales-contacts-title"><?php echo esc_html( $sales_title ); ?></h2></div>
					<button class="contact-popover-close" type="button" aria-label="<?php echo esc_attr__( 'Закрити контакти', 'project-theme' ); ?>" data-contact-close>
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
					</button>
				</div>
				<div class="contact-data-block">
					<span><?php esc_html_e( 'Телефони', 'project-theme' ); ?></span>
					<strong class="contact-phone-list">
						<?php foreach ( $sales_phones as $phone ) : ?>
							<?php
							$phone_label = isset( $phone['phone_label'] ) ? (string) $phone['phone_label'] : '';
							$phone_url   = isset( $phone['phone_url'] ) ? (string) $phone['phone_url'] : '';

							if ( '' === trim( $phone_label ) || '' === trim( $phone_url ) ) {
								continue;
							}
							?>
							<a href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone_label ); ?></a>
						<?php endforeach; ?>
					</strong>
				</div>
				<div class="contact-data-block contact-data-block--last">
					<span><?php echo esc_html( $sales_address_label ); ?></span>
					<strong><a href="<?php echo esc_url( $sales_address_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $sales_address ); ?></a></strong>
				</div>
				<button class="button button--clay contact-popover-cta" type="button" data-open-lead data-interest="<?php echo esc_attr( $header_cta_interest ); ?>" data-context="<?php echo esc_attr__( 'Зворотний дзвінок', 'project-theme' ); ?>"><?php esc_html_e( 'Залишити заявку', 'project-theme' ); ?></button>
			</aside>
		</div>
	</header>
