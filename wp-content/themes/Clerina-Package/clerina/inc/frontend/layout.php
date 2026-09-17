<?php
/**
 * Custom layout functions  by hooking templates
 *
 * @package 'Clerina'
 */


/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 *
 * @return array
 */
function clerina_body_classes( $classes ) {

	$header_layout = clerina_get_option( 'header_layout' );
	$classes[]     = 'header-v' . $header_layout;

	$footer_layout = clerina_get_option( 'footer_layout' );
	$classes[]     = 'footer-' . $footer_layout;

	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}
	if ( intval( clerina_get_option( 'header_sticky' ) ) ) {
		$classes[] = 'header-sticky';
	}
	if ( clerina_is_portfolio() && clerina_get_option( 'portfolio_layout' ) == 'grid' ) {
		$classes[] = 'portfolio-grid';
	}
	if ( clerina_is_portfolio() && clerina_get_option( 'portfolio_layout' ) == 'full-width' ) {
		$classes[] = 'portfolio-layout-full-width';
	}

	if ( ( clerina_is_blog() ) ) {
		if ( clerina_get_option( 'blog_layout' ) == 'masonry' ) {
			$classes[] = 'blog-masonry';
		}
	}

	if ( is_singular( 'post' ) ) {
		$classes[] = clerina_get_option( 'single_post_layout' );
	} elseif ( clerina_is_blog() || is_search() ) {
		$classes[] = clerina_get_option( 'blog_view' );
		if ( clerina_get_option( 'blog_view' ) == 'grid' ) {
			$classes[] = clerina_get_option( 'blog_layout' );
		}
		if ( is_active_sidebar( 'blog-sidebar' ) ) {
			$classes[] = clerina_get_option( 'blog_layout' );
		}
	} elseif ( clerina_is_portfolio() ) {
		if ( clerina_get_option( 'portfolio_cats_filters' ) == '1' ) {
			$classes[] = 'clerina-portfolio-isotope';
		}
		$classes[] = 'clerina-portfolio';
	} elseif ( clerina_is_service() ) {
		$classes[] = 'clerina-service';
	} elseif ( is_singular( 'service' ) ) {
		$classes[] = clerina_get_option( 'service_sidebar' );
	} elseif ( clerina_is_testimonial() ) {
		$classes[] = 'clerina-testimonial';
	} else {
		$classes[] = clerina_get_layout();
	}

	if ( $skin = clerina_get_option( 'color_skin' ) ) {
		$classes[] = 'cl-' . $skin . '-skin';
	}

	if ( intval( clerina_get_option( 'mobile_nav' ) ) ) {
		$classes[] = 'show-nav-mobile';
	}

	return $classes;
}

add_filter( 'body_class', 'clerina_body_classes' );

/**
 * Print the open tags of site content container
 */

if ( ! function_exists( 'clerina_open_site_content_container' ) ) :
	function clerina_open_site_content_container() {

		printf( '<div class="%s"><div class="row">', esc_attr( apply_filters( 'clerina_site_content_container_class', clerina_content_container_class() ) ) );
	}
endif;

add_action( 'clerina_after_site_content_open', 'clerina_open_site_content_container' );

/**
 * Print the close tags of site content container
 */

if ( ! function_exists( 'clerina_close_site_content_container' ) ) :
	function clerina_close_site_content_container() {
		print( '</div></div>' );
	}

endif;

add_action( 'clerina_before_site_content_close', 'clerina_close_site_content_container' );

