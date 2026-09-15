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

<div id="primary" class="content-area col-md-12 col-sm-12 col-xs-12">
    <div class="container">
        <?php do_action( 'clerina_service_before_content' ); ?>
        <main id="main" class="site-main row" role="main">

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
                    get_template_part( 'template-parts/content', 'service' );
                endwhile;

                ?>

                <?php
            else :
                get_template_part( 'template-parts/content', 'none' );
            endif;
            ?>
        </main><!-- #main -->

        <?php clerina_pagination();?>

    </div>
</div><!-- #primary -->

<?php do_action( 'clerina_service_after_content' ); ?>
<?php get_footer(); ?>
