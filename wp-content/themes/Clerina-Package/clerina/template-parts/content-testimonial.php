<?php
/**
 * @package Clerina
 */

$css_class = 'testimonial-item col-xs-6 col-md-6 col-sm-6 col-xs-12';

$job = get_post_meta( get_the_ID(), 'job', true );

$rating = get_post_meta( get_the_ID(), 'rating', true );

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $css_class ); ?>>
    <div class="cl-testimonial-wrapper">
        <div class="entry-header">
            <a href="<?php the_permalink(); ?>" class="entry-thumbnail">
				<?php the_post_thumbnail( 'thumbnail' ); ?>
            </a>
        </div><!-- .entry-header -->

        <div class="entry-content">
            <div class="entry-title">
                <h3 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<?php if ( $job ) : ?>
                    <div class="entry-job">
						<?php echo wp_kses_post($job); ?>
                    </div>
				<?php endif; ?>
            </div>
            <div class="entry-excerpt">
				<?php
				if ( is_post_type_archive( 'testimonial' ) ) {
					the_content();
				} else {
					echo clerina_content_limit( get_the_content(), 50, '' );
				}
				?>
				<?php echo clerina_entry_rating( $rating ); ?>
            </div>

        </div><!-- .entry-content -->
    </div>
</article>

