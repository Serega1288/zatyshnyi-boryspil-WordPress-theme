<?php
/**
 * The template for requests that do not match published content.
 *
 * @package ProjectTheme
 */

if ( ! headers_sent() ) {
	status_header( 404 );
	nocache_headers();
}

$project_theme_404_title_filter = static function ( array $parts ): array {
	$parts['title'] = __( 'Сторінку не знайдено', 'project-theme' );

	return $parts;
};

$project_theme_404_menu_filter = static function ( array $attributes ): array {
	$href = isset( $attributes['href'] ) ? (string) $attributes['href'] : '';

	if ( str_starts_with( $href, '#' ) ) {
		$attributes['href'] = home_url( '/' . $href );
	}

	return $attributes;
};

add_filter( 'document_title_parts', $project_theme_404_title_filter );
add_filter( 'nav_menu_link_attributes', $project_theme_404_menu_filter );

get_header();
?>
<main id="main" class="not-found-page" role="main">
	<section class="not-found-section" aria-labelledby="not-found-title">
		<div class="container">
			<div class="not-found-card">
				<div class="not-found-visual" aria-hidden="true">
					<span class="not-found-code">404</span>
					<img class="not-found-mark" src="<?php echo esc_url( get_template_directory_uri() . '/assets/zatyshnyi-favicon.svg' ); ?>" alt="" width="512" height="512" />
				</div>
				<div class="not-found-copy">
					<p class="eyebrow"><span></span><?php esc_html_e( 'Помилка 404', 'project-theme' ); ?></p>
					<h1 id="not-found-title"><?php esc_html_e( 'Такої сторінки немає', 'project-theme' ); ?></h1>
					<p><?php esc_html_e( 'Можливо, адресу введено з помилкою або сторінку було переміщено. Поверніться на головну, щоб переглянути актуальну інформацію про комплекс.', 'project-theme' ); ?></p>
					<a class="button button--green not-found-action" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Повернутися на головну', 'project-theme' ); ?></a>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();

remove_filter( 'document_title_parts', $project_theme_404_title_filter );
remove_filter( 'nav_menu_link_attributes', $project_theme_404_menu_filter );
