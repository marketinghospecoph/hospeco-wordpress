<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package 'Clerina'
 */

get_header(); ?>

<div id="primary" class="<?php clerina_content_columns(); ?> content-area content-single-service">
		<?php while ( have_posts() ) : the_post(); ?>

			<div <?php post_class() ?>>

				<div class="col-xs-12 content-sv">
                    <div class="entry-thumbnail row"><?php echo get_the_post_thumbnail(get_the_ID(), 'full'); ?></div>
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

</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
