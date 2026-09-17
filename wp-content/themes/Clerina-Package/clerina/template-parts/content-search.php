<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package clerina
 */
global $cl_post;

$size = 'clerina-blog-thumb';
$css_class = 'cl-blog-wrapper';
$columns = intval( clerina_get_option( 'blog_grid_columns' ) );

if ( clerina_get_option( 'blog_view' ) == 'grid' ) {
	$size      = 'clerina-blog-grid-thumb';
	$css_class = 'col-xs-6 col-sm-6 cl-blog-wrapper';

	if ( $columns == '2' ) {
		$css_class .= ' blog-wrapper-col-2 col-md-6';
	} else {
		if ( 'full-content' == clerina_get_layout() ) {
			$css_class .= ' blog-wrapper-col-3 col-md-4';
		} else {
			$css_class .= ' blog-wrapper-col-2 col-md-6';
		}
	}
}

if ( isset($cl_post['css']) ) {
	$css_class .= $cl_post['css'];
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( $css_class ); ?>>
    <div class="blog-wrapper">
        <div class="entry-thumbnail">
			<?php clerina_post_thumbnail($size); ?>
        </div>
        <div class="entry-summary">
            <header class="entry-header">
				<?php clerina_posted_on(); ?>
				<?php the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' ); ?>
                <div class="entry-content">
					<?php
					clerina_blog_descr();
					?>
                </div><!-- .entry-content -->
            </header><!-- .entry-header -->

            <div class="entry-meta">
				<?php clerina_entry_author(); ?>
            </div>
        </div>
    </div>
</article><!-- #post-<?php the_ID(); ?> -->