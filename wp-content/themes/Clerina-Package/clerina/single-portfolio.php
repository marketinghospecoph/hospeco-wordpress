<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package 'Clerina'
 */

get_header(); ?>

<div id="primary" class="col-xs-12 col-sm-12 content-area content-single-portfolio">
		<?php while ( have_posts() ) : the_post(); ?>

			<div <?php post_class() ?>>

				<?php portfolio_single_title();?>
				<div class="col-xs-12 content-sv">
					<?php the_content(); ?>
				</div>
			</div>

			<?php
			// If comments are open or we have at least one comment, load up the comment template
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>

		<?php endwhile; ?>

	<?php clerina_portfolio_nav(); ?>
</div><!-- #primary -->

<?php get_footer(); ?>
