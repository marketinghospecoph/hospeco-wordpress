<?php
/**
 * The template part for displaying related posts
 *
 * @package Sober
 */

// Only support posts
if ( 'post' != get_post_type() ) {
	return;
}

if ( ! intval( clerina_get_option( 'related_posts' ) ) ) {
	return;
}

$layout = clerina_get_option( 'single_post_layout' );

if ( $layout == 'full-content' ) {
	$css_class = 'col-md-4 col-xs-6 col-sm-6';
} else {
	$css_class = 'col-md-6 col-xs-6 col-sm-6';
}

$numbers = intval( clerina_get_option( 'related_posts_numbers' ) );

$related_posts = new WP_Query(
	array(
		'posts_per_page'         => $numbers,
		'ignore_sticky_posts'    => 1,
		'category__in'           => wp_get_post_categories( get_the_ID() ),
		'post__not_in'           => array( get_the_ID() ),
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	)
);

$size = 'clerina-related-post-grid';

if ( ! $related_posts->have_posts() ) {
	return;
}

?>
	<div class="clerina-related-posts blog-grid">
        <?php if ( !empty( clerina_get_option( 'related_posts_title' ) ) ) : ?>
            <div class="related-section-title">
                <h3><?php echo clerina_get_option( 'related_posts_title' ); ?></h3>
            </div>
        <?php endif; ?>
		<div class="list-post row">
			<?php while ( $related_posts->have_posts() ) : $related_posts->the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( $css_class ); ?>>
					<div class="blog-wrapper">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="entry-thumbnail">
								<a class="blog-thumb" href="<?php the_permalink() ?>"><?php the_post_thumbnail( $size ) ?></a>
							</div>
						<?php endif; ?>

						<div class="entry-summary">
							<div class="entry-header">
                                <?php clerina_posted_on(); ?>
								<h3 class="entry-title"><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h3>
								<?php //clerina_entry_meta() ?>
							</div>
						</div><!-- .entry-content -->
					</div>
				</article><!-- #post-## -->

			<?php endwhile; ?>
		</div>
	</div>

<?php
wp_reset_postdata();