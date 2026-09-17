<?php
/**
 * Template part for displaying posts
 *
 * @link    https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package clerina
 */

?>
<article id="post-<?php the_ID(); ?>" class="col-xs-6 col-sm-4 service-wrapper">
	<div class="content-item">
		<div class="entry-header">
			<a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>" class="entry-thumbnail">
				<?php the_post_thumbnail( 'clerina-service' ); ?>
			</a>
		</div><!-- .entry-header -->

		<div class="entry-content">

			<div class="entry-title">
                <h3 class="title"><a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			</div>
			<?php clerina_service_descr(); ?>

			<div class="entry-read-more">
				<a href="<?php the_permalink(); ?>" class="read-more">
					<?php esc_html_e( 'Read more', 'clerina' ); ?>
				</a>
			</div>

		</div><!-- .entry-content -->
	</div>
</article>

