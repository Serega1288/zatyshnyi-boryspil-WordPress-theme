<?php
/**
 * Theme footer.
 *
 * @package ProjectTheme
 */

$footer_logo           = get_field( 'footer_logo', 'option' );
$footer_logo_alt       = get_field( 'footer_logo_alt', 'option' );
$footer_description    = get_field( 'footer_description', 'option' );
$footer_cta_text       = get_field( 'footer_cta_text', 'option' );
$footer_cta_interest   = get_field( 'footer_cta_interest', 'option' );
$footer_cta_context    = get_field( 'footer_cta_context', 'option' );
$footer_sales_title    = get_field( 'footer_sales_title', 'option' );
$footer_address_title  = get_field( 'footer_address_title', 'option' );
$footer_address        = get_field( 'footer_address', 'option' );
$footer_address_url    = get_field( 'footer_address_url', 'option' );
$footer_map_text       = get_field( 'footer_map_text', 'option' );
$footer_nav_title      = get_field( 'footer_nav_title', 'option' );
$footer_copyright      = get_field( 'footer_copyright', 'option' );
$footer_developer_link = get_field( 'footer_developer_link', 'option' );
$sales_kicker          = get_field( 'sales_kicker', 'option' );
$sales_phones          = get_field( 'sales_phones', 'option' );
$sales_address_label   = get_field( 'sales_address_label', 'option' );
$sales_address         = get_field( 'sales_address', 'option' );
$sales_address_url     = get_field( 'sales_address_url', 'option' );
$lead_eyebrow          = get_field( 'lead_eyebrow', 'option' );
$lead_title            = get_field( 'lead_title', 'option' );
$lead_description      = get_field( 'lead_description', 'option' );
$lead_context_label    = get_field( 'lead_context_label', 'option' );
$lead_default_context  = get_field( 'lead_default_context', 'option' );
$lead_form_note        = get_field( 'lead_form_note', 'option' );

$logo_fallback = get_template_directory_uri() . '/assets/zatyshnyi-logo.svg';
$footer_logo_url = $logo_fallback;

if ( is_array( $footer_logo ) ) {
	$footer_logo_id = isset( $footer_logo['ID'] ) ? (int) $footer_logo['ID'] : ( isset( $footer_logo['id'] ) ? (int) $footer_logo['id'] : 0 );
	$footer_logo_url = ! empty( $footer_logo['url'] ) ? (string) $footer_logo['url'] : (string) wp_get_attachment_image_url( $footer_logo_id, 'full' );
} elseif ( is_numeric( $footer_logo ) ) {
	$footer_logo_url = (string) wp_get_attachment_image_url( (int) $footer_logo, 'full' );
} elseif ( is_string( $footer_logo ) && '' !== trim( $footer_logo ) ) {
	$footer_logo_url = $footer_logo;
}

if ( '' === $footer_logo_url ) {
	$footer_logo_url = $logo_fallback;
}

$sales_kicker        = is_string( $sales_kicker ) && '' !== trim( $sales_kicker ) ? $sales_kicker : __( 'Відділ продажу', 'project-theme' );
$sales_address_label = is_string( $sales_address_label ) && '' !== trim( $sales_address_label ) ? $sales_address_label : __( 'Адреса', 'project-theme' );
$sales_address       = is_string( $sales_address ) && '' !== trim( $sales_address ) ? $sales_address : __( 'м. Бориспіль, вул. Коломичівська, 73', 'project-theme' );
$sales_address_url   = is_string( $sales_address_url ) && '' !== trim( $sales_address_url ) ? $sales_address_url : 'https://www.google.com/maps/search/?api=1&query=%D0%BC.%20%D0%91%D0%BE%D1%80%D0%B8%D1%81%D0%BF%D1%96%D0%BB%D1%8C%2C%20%D0%B2%D1%83%D0%BB.%20%D0%9A%D0%BE%D0%BB%D0%BE%D0%BC%D0%B8%D1%87%D1%96%D0%B2%D1%81%D1%8C%D0%BA%D0%B0%2C%2073';

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
			'phone_label' => '067-329-27-23',
			'phone_url'   => 'tel:+380673292723',
		),
		array(
			'phone_label' => '067-445-58-59',
			'phone_url'   => 'tel:+380674455859',
		),
	);
}

$footer_logo_alt      = is_string( $footer_logo_alt ) && '' !== trim( $footer_logo_alt ) ? $footer_logo_alt : __( 'Логотип ЖК «Затишний Бориспіль»', 'project-theme' );
$footer_description   = is_string( $footer_description ) && '' !== trim( $footer_description ) ? $footer_description : __( 'Житловий комплекс для спокійного сімейного життя у Борисполі.', 'project-theme' );
$footer_cta_text      = is_string( $footer_cta_text ) && '' !== trim( $footer_cta_text ) ? $footer_cta_text : __( 'Обрати квартиру', 'project-theme' );
$footer_cta_interest  = is_string( $footer_cta_interest ) && '' !== trim( $footer_cta_interest ) ? $footer_cta_interest : __( 'Квартира', 'project-theme' );
$footer_cta_context   = is_string( $footer_cta_context ) && '' !== trim( $footer_cta_context ) ? $footer_cta_context : __( 'Підбір квартири', 'project-theme' );
$footer_sales_title   = is_string( $footer_sales_title ) && '' !== trim( $footer_sales_title ) ? $footer_sales_title : $sales_kicker;
$footer_address_title = is_string( $footer_address_title ) && '' !== trim( $footer_address_title ) ? $footer_address_title : $sales_address_label;
$footer_address       = is_string( $footer_address ) && '' !== trim( $footer_address ) ? $footer_address : $sales_address;
$footer_address_url   = is_string( $footer_address_url ) && '' !== trim( $footer_address_url ) ? $footer_address_url : $sales_address_url;
$footer_map_text      = is_string( $footer_map_text ) && '' !== trim( $footer_map_text ) ? $footer_map_text : __( 'Відкрити на карті', 'project-theme' );
$footer_nav_title     = is_string( $footer_nav_title ) && '' !== trim( $footer_nav_title ) ? $footer_nav_title : __( 'Навігація', 'project-theme' );
$footer_copyright     = is_string( $footer_copyright ) && '' !== trim( $footer_copyright ) ? $footer_copyright : __( '© {year} ЖК «Затишний Бориспіль»', 'project-theme' );
$footer_copyright     = str_replace( '{year}', wp_date( 'Y' ), $footer_copyright );
$lead_eyebrow         = is_string( $lead_eyebrow ) && '' !== trim( $lead_eyebrow ) ? $lead_eyebrow : __( 'Консультація', 'project-theme' );
$lead_title           = is_string( $lead_title ) && '' !== trim( $lead_title ) ? $lead_title : __( 'Поговорімо про вашу майбутню квартиру', 'project-theme' );
$lead_description     = is_string( $lead_description ) && '' !== trim( $lead_description ) ? $lead_description : __( 'Залиште контакти — менеджер допоможе з плануванням, наявністю та умовами придбання.', 'project-theme' );
$lead_context_label   = is_string( $lead_context_label ) && '' !== trim( $lead_context_label ) ? $lead_context_label : __( 'Ваш запит', 'project-theme' );
$lead_default_context = is_string( $lead_default_context ) && '' !== trim( $lead_default_context ) ? $lead_default_context : __( 'Підбір квартири', 'project-theme' );
$lead_form_note       = is_string( $lead_form_note ) && '' !== trim( $lead_form_note ) ? $lead_form_note : __( 'Менеджер зв’яжеться з вами за вказаним номером телефону.', 'project-theme' );

if ( ! is_array( $footer_developer_link ) ) {
	$footer_developer_link = array(
		'url'    => 'https://agrobudmeh.com.ua/o-kompanii.html',
		'title'  => __( 'Забудовник: Агробудмеханізація', 'project-theme' ),
		'target' => '_blank',
	);
}

$footer_developer_url    = isset( $footer_developer_link['url'] ) && '' !== trim( (string) $footer_developer_link['url'] ) ? (string) $footer_developer_link['url'] : 'https://agrobudmeh.com.ua/o-kompanii.html';
$footer_developer_title  = isset( $footer_developer_link['title'] ) && '' !== trim( (string) $footer_developer_link['title'] ) ? (string) $footer_developer_link['title'] : __( 'Забудовник: Агробудмеханізація', 'project-theme' );
$footer_developer_target = isset( $footer_developer_link['target'] ) && '_blank' === $footer_developer_link['target'] ? '_blank' : '_self';
?>
	<footer class="footer">
		<div class="container footer-shell">
			<div class="footer-grid">
				<div class="footer-brand">
					<img src="<?php echo esc_url( $footer_logo_url ); ?>" alt="<?php echo esc_attr( $footer_logo_alt ); ?>" width="1240" height="840" />
					<p><?php echo esc_html( $footer_description ); ?></p>
					<button class="button button--clay footer-cta" type="button" data-open-lead data-interest="<?php echo esc_attr( $footer_cta_interest ); ?>" data-context="<?php echo esc_attr( $footer_cta_context ); ?>"><?php echo esc_html( $footer_cta_text ); ?></button>
				</div>
				<div class="footer-group footer-sales">
					<h3><?php echo esc_html( $footer_sales_title ); ?></h3>
					<?php foreach ( $sales_phones as $phone ) : ?>
						<?php
						$phone_label = isset( $phone['phone_label'] ) ? (string) $phone['phone_label'] : '';
						$phone_url   = isset( $phone['phone_url'] ) ? (string) $phone['phone_url'] : '';

						if ( '' === trim( $phone_label ) || '' === trim( $phone_url ) ) {
							continue;
						}
						?>
						<a class="footer-phone" href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone_label ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="footer-group">
					<h3><?php echo esc_html( $footer_address_title ); ?></h3>
					<a href="<?php echo esc_url( $footer_address_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo nl2br( esc_html( $footer_address ) ); ?></a>
					<a class="footer-map-link" href="<?php echo esc_url( $footer_address_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $footer_map_text ); ?> <span aria-hidden="true">↗</span></a>
				</div>
				<nav class="footer-group footer-nav" aria-label="<?php echo esc_attr__( 'Навігація у футері', 'project-theme' ); ?>">
					<h3><?php echo esc_html( $footer_nav_title ); ?></h3>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer-menu',
							'container'      => false,
							'menu_class'     => 'menu',
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			</div>
			<div class="footer-bottom">
				<span><?php echo esc_html( $footer_copyright ); ?></span>
				<a href="<?php echo esc_url( $footer_developer_url ); ?>" target="<?php echo esc_attr( $footer_developer_target ); ?>"<?php echo '_blank' === $footer_developer_target ? ' rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $footer_developer_title ); ?> <span aria-hidden="true">↗</span></a>
			</div>
		</div>
	</footer>

	<button class="back-to-top" type="button" aria-label="<?php echo esc_attr__( 'Повернутися на початок сторінки', 'project-theme' ); ?>" aria-hidden="true" tabindex="-1" data-back-to-top>
		<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 10 6-6 6 6M12 4v16" /></svg>
	</button>

	<dialog class="lead-dialog" id="lead-dialog" aria-labelledby="lead-dialog-title" aria-describedby="lead-dialog-description" data-lead-dialog>
		<div class="lead-dialog-shell">
			<button class="dialog-close" type="button" aria-label="<?php echo esc_attr__( 'Закрити форму', 'project-theme' ); ?>" data-close-lead>
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
			</button>
			<div class="lead-dialog-copy">
				<p class="eyebrow eyebrow--light"><span></span><?php echo esc_html( $lead_eyebrow ); ?></p>
				<h2 id="lead-dialog-title"><?php echo esc_html( $lead_title ); ?></h2>
				<p id="lead-dialog-description"><?php echo esc_html( $lead_description ); ?></p>
				<div class="lead-context"><span><?php echo esc_html( $lead_context_label ); ?></span><strong data-lead-context-output><?php echo esc_html( $lead_default_context ); ?></strong></div>
				<p class="lead-contact-note">
					<?php echo esc_html( $sales_kicker ); ?>:
					<?php
					$rendered_phone_count = 0;
					foreach ( $sales_phones as $phone ) :
						$phone_label = isset( $phone['phone_label'] ) ? (string) $phone['phone_label'] : '';
						$phone_url   = isset( $phone['phone_url'] ) ? (string) $phone['phone_url'] : '';

						if ( '' === trim( $phone_label ) || '' === trim( $phone_url ) ) {
							continue;
						}

						if ( 0 < $rendered_phone_count ) {
							echo ', ';
						}
						?>
						<a href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone_label ); ?></a><?php $rendered_phone_count++; ?>
					<?php endforeach; ?>
					<br /><?php echo esc_html( $sales_address ); ?>
				</p>
			</div>
			<form class="contact-form lead-form" method="post" data-form data-lead-form>
				<div class="lead-form-fields">
					<div class="form-honeypot" aria-hidden="true"><label for="lead-website">Website</label><input id="lead-website" name="website" type="text" tabindex="-1" autocomplete="off" /></div>
					<div><label for="lead-name"><?php esc_html_e( 'Ваше ім’я', 'project-theme' ); ?></label><input id="lead-name" name="name" type="text" maxlength="100" autocomplete="name" placeholder="<?php echo esc_attr__( 'Як до вас звертатися', 'project-theme' ); ?>" required /></div>
					<div><label for="lead-phone"><?php esc_html_e( 'Номер телефону', 'project-theme' ); ?></label><input id="lead-phone" name="phone" type="tel" maxlength="40" inputmode="tel" autocomplete="tel" placeholder="+380" required /></div>
					<div class="form-field--interest">
						<label for="lead-interest"><?php esc_html_e( 'Що вас цікавить?', 'project-theme' ); ?></label>
						<select id="lead-interest" name="interest" data-lead-interest required>
							<option><?php esc_html_e( 'Квартира', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Паркінг', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Комора', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Комерційне приміщення', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Документи', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Умови придбання', 'project-theme' ); ?></option>
							<option><?php esc_html_e( 'Питання про комплекс', 'project-theme' ); ?></option>
						</select>
					</div>
					<div class="form-field--wide"><label for="lead-message"><?php esc_html_e( 'Коментар (необов’язково)', 'project-theme' ); ?></label><textarea id="lead-message" name="message" rows="4" maxlength="2000" placeholder="<?php echo esc_attr__( 'Напишіть ваше запитання або побажання', 'project-theme' ); ?>"></textarea></div>
					<input type="hidden" name="request_context" value="<?php echo esc_attr( $lead_default_context ); ?>" data-lead-context-input />
					<input type="hidden" name="apartment_type" value="" data-lead-apartment-input />
					<button class="button button--clay" type="submit" data-lead-submit><?php esc_html_e( 'Отримати консультацію', 'project-theme' ); ?></button>
					<p class="form-note"><?php echo esc_html( $lead_form_note ); ?></p>
					<p class="form-status" role="status" aria-live="polite" data-status></p>
				</div>
				<div class="lead-success" role="status" aria-live="polite" aria-atomic="true" tabindex="-1" data-lead-success hidden>
					<span class="lead-success-icon" aria-hidden="true">✓</span>
					<h3><?php esc_html_e( 'Дякуємо за інтерес!', 'project-theme' ); ?></h3>
					<p data-lead-success-message></p>
					<small><?php esc_html_e( 'Вікно автоматично закриється через 8 секунд.', 'project-theme' ); ?></small>
				</div>
			</form>
		</div>
	</dialog>
	<?php wp_footer(); ?>
</body>
</html>
