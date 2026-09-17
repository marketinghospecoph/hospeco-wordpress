<?php
/**
 * The template for displaying archive pages
 *
 * @link    https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package 'Clerina'
 */

get_header();
?>

<div id="primary" class="content-area <?php clerina_content_columns(); ?>">
	<main id="main" class="site-main" role="main">
        <?php do_action( 'clerina_before_content_testimonial' ); ?>
        <div id="list-testimonial" class="row list-testimonial">
		<?php if ( have_posts() ) : ?>

			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();
				/*
				 * Include the Post-Type-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
				 */
				get_template_part( 'template-parts/content', 'testimonial' );
			endwhile;

			?>

		<?php
		else :
			get_template_part( 'template-parts/content', 'none' );
		endif;
		?>
        </div>
		<?php clerina_pagination();?>

	</main><!-- #main -->

</div><!-- #primary -->

<?php do_action( 'clerina_testimonial_after_content' ); ?>
<?php get_footer(); ?>
