<?php
/**
 * Hooks for template archive
 *
 * @package Clerina
 */

/**
 * Add blog categories
 *
 * @since  1.0
 *
 *
 */
if ( ! function_exists( 'clerina_blog_text_categories' ) ) :
	function clerina_blog_text_categories() {
		$cat_filter = intval( clerina_get_option( 'blog_cat_filter' ) );

		if ( ! $cat_filter ) {
			return;
		}

		clerina_taxs_list();
	}
endif;

add_action( 'clerina_before_post_list', 'clerina_blog_text_categories' );
add_action( 'clerina_before_archive_post_list', 'clerina_blog_text_categories' );


/**
 * Add icon list as svg at the footer
 * It is hidden
 */
function clerina_include_shadow_icons()
{
	echo '<div id="del-svg-defs" class="del-svg-defs hidden">';
	include get_template_directory() . '/images/sprite.svg';
	echo '</div>';
}

add_action('clerina_after_footer', 'clerina_include_shadow_icons');

/**
 * Add title and description - testimonial page
 */
function clerina_before_content_testimonial() {
	$title = clerina_get_option( 'testimonial_title' );
	$description = clerina_get_option( 'testimonial_description' );
	if ( empty( $title ) && empty( $description ) ) {
		return;
	}

	?>
	<div class="header-testimonial">
		<div class="title"><h2><?php echo esc_html( $title ); ?></h2></div>
        <div class="description"><?php echo esc_html( $description ); ?></div>
	</div>
<?php
}

add_action( 'clerina_before_content_testimonial', 'clerina_before_content_testimonial' );