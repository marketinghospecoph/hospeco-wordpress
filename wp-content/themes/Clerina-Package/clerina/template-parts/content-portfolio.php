<?php
/**
 * Template part for displaying posts
 *
 * @link    https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package clerina
 */

?>
<article id="post-<?php the_ID(); ?>" class="<?php clerina_portfolio_class(); ?> <?php clerina_portfolio_slug(); ?>" data-category="<?php clerina_portfolio_slug(); ?>">
	<div class="content-item">
		<div class="entry-header">
			<?php clerina_portfolio_hover(); ?>
			<a href="<?php the_permalink(); ?>" class="entry-thumbnail">
				<?php clerina_portfolio_thumbnail(); ?>
			</a>
		</div><!-- .entry-header -->
		<?php if(clerina_get_option( 'portfolio_layout' ) == 'grid') : ?>
		<div class="entry-content">

			<?php clerina_portfolio_meta(); ?>

			<div class="entry-title">
                <h3 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			</div>

		</div><!-- .entry-content -->
		<?php endif;?>
	</div>

</article>

