<?php
/**
 * @package Clerina
 */
get_header();

$job  = get_post_meta( get_the_ID(), 'job', true );

$rating  = get_post_meta( get_the_ID(), 'rating', true );

$css_class = 'testimonial-wrapper';

?>

<div id="primary" class="content-area">
	<div class="site-main">

		<?php while ( have_posts() ) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( $css_class ); ?>>
            <div class="entry-header">
                <a href="<?php the_permalink(); ?>" class="entry-thumbnail">
			        <?php the_post_thumbnail( 'thumbnail' ); ?>
                </a>
            </div><!-- .entry-header -->

            <div class="entry-content">
				<h3 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<?php if ( $job ) : ?>
                    <div class="entry-job">
						<?php echo wp_kses_post($job); ?>
                    </div>
				<?php endif; ?>
				<?php echo clerina_entry_rating( $rating ); ?>
				<div>
					<?php the_content(); ?>
				</div>
            </div>

        </article>
		<?php endwhile; ?>

</div>
</div><!-- #primary -->
<?php get_footer(); ?>

