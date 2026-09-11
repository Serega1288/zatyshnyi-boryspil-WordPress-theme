<?php
/**
 * Template Name: Constructor
 *
 * @package ProjectTheme
 */

get_header();
?>
<main id="main" role="main">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php get_template_part( 'inc/box', 'constructor' ); ?>
	<?php endwhile; ?>
</main>
<?php
get_footer();
