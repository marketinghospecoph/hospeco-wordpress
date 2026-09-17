<?php
/**
 * Custom layout functions
 *
 * @package 'Clerina'
 */


/**
 * Get classes for content area
 *
 * @since  1.0
 *
 * @return string of classes
 */

if ( ! function_exists( 'clerina_content_container_class' ) ) :
	function clerina_content_container_class() {
		if ( clerina_is_portfolio() && clerina_get_option( 'portfolio_layout' ) == 'full-width' ) {
			return 'clerina-container';
		}

		if ( clerina_is_service() ) {
			return 'clerina-container';
		}

		if ( is_page_template( 'template-homepage.php' ) ) {
			return 'container-fluid';
		}

		return 'container';
	}

endif;

/**
 * Get classes for content area
 *
 * @since  1.0
 *
 * @return string of classes
 */

if ( ! function_exists( 'clerina_footer_container_class' ) ) :
	function clerina_footer_container_class() {

		if ( clerina_get_option( 'footer_layout' ) == 'full-width' ) {
			return 'clerina-container';
		}

		return 'container';
	}

endif;


if ( ! function_exists( 'clerina_get_layout' ) ) :
	function clerina_get_layout() {
		$layout = clerina_get_option( 'blog_layout' );
		if ( is_404() ) {
			$layout = 'full-content';
		} elseif ( is_singular( 'post' ) ) {
			if ( get_post_meta( get_the_ID(), 'custom_page_layout', true ) ) {
				$layout = get_post_meta( get_the_ID(), 'layout', true );
			} else {
				$layout = clerina_get_option( 'single_post_layout' );
			}
		} elseif ( is_page_template( array( 'template-fullwidth.php', 'template-homepage.php' ) ) ) {
			$layout = 'full-content';
		} elseif ( is_page() ) {
			if ( get_post_meta( get_the_ID(), 'custom_page_layout', true ) ) {
				$layout = get_post_meta( get_the_ID(), 'layout', true );
			} else {
				$layout = clerina_get_option( 'page_layout' );
			}
		} elseif ( is_singular( 'portfolio' ) || clerina_is_portfolio() ) {
			$layout = 'full-content';
		} elseif ( clerina_is_service() ) {
			$layout = clerina_get_option( 'service_layout' );
		} elseif ( is_singular( 'service' ) ) {
			$layout = clerina_get_option( 'service_sidebar' );
		} elseif ( clerina_is_catalog() || ( function_exists( 'is_product' ) && is_product() )) {
			$layout = clerina_get_option( 'shop_layout' );
		} elseif ( ! is_active_sidebar( 'blog-sidebar' ) ) {
			$layout = 'full-content';
		}

		return apply_filters( 'clerina_site_layout', $layout );
	}

endif;

/**
 * Get Bootstrap column classes for content area
 *
 * @since  1.0
 *
 * @return array Array of classes
 */

if ( ! function_exists( 'clerina_get_content_columns' ) ) :
	function clerina_get_content_columns( $layout = null ) {
		$layout = $layout ? $layout : clerina_get_layout();

		if ( $layout == 'full-content' ) {
			return array( 'col-md-12', 'col-sm-12', 'col-xs-12' );
		}

		if ( clerina_is_catalog() || ( function_exists( 'is_product' ) && is_product() ) ) {
			return array( 'col-md-9', 'col-sm-12', 'col-xs-12' );
		} elseif ( clerina_is_service() || is_singular( 'service' ) ) {
			return array( 'col-md-8', 'col-sm-12', 'col-xs-12' );
		} else {
			if ( $layout == 'sidebar-content' ) {
				return array( 'sidebar-content', 'col-md-8', 'col-sm-12', 'col-xs-12' );
			} else {
				return array( 'col-md-8', 'col-sm-12', 'col-xs-12' );
			}
		}
	}

endif;

/**
 * Echos Bootstrap column classes for content area
 *
 * @since 1.0
 */

if ( ! function_exists( 'clerina_content_columns' ) ) :
	function clerina_content_columns( $layout = null ) {
		echo implode( ' ', clerina_get_content_columns( $layout ) );
	}
endif;
