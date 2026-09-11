<?php
/**
 * Classic theme fallback.
 *
 * @package ProjectTheme
 */

get_header();
?>
<main id="main" class="section" role="main">
	<div class="container">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
