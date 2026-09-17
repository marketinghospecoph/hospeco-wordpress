<?php
/**
 * clerina functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package 'Clerina'
 */

if ( ! function_exists( 'clerina_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function clerina_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on clerina, use a find and replace
		 * to change  'clerina' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'clerina', get_template_directory() . '/lang' );

		// Theme supports
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );
		add_theme_support( 'customize-selective-refresh-widgets' );

		add_editor_style( 'css/editor-style.css' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'Primary', 'clerina' ),
		) );

		add_image_size( 'clerina-portfolio-grid', 600, 497, true );
		add_image_size( 'clerina-portfolio-full-width', 600, 500, true );
		add_image_size( 'clerina-widget-post-thumbnail', 80, 80, true );
		add_image_size( 'clerina-service', 375, 233, true );
		add_image_size( 'clerina-blog-thumb', 770, 520, true );
		add_image_size( 'clerina-blog-grid-thumb', 480, 324, true );
		add_image_size( 'clerina-blog-single-thumb', 1170, 608, true );
		add_image_size( 'clerina-related-post-grid', 375, 253, true );
	}
endif;
add_action( 'after_setup_theme', 'clerina_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function clerina_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'clerina_content_width', 640 );
}

add_action( 'after_setup_theme', 'clerina_content_width', 0 );


/**
 * Widgets additions.
 */
require get_template_directory() . '/inc/widgets/widgets.php';


/**
 * Custom functions for the theme.
 */
require get_template_directory() . '/inc/functions/header.php';
require get_template_directory() . '/inc/functions/layout.php';
require get_template_directory() . '/inc/functions/entry.php';
require get_template_directory() . '/inc/functions/comments.php';
require get_template_directory() . '/inc/functions/breadcrumbs.php';

/**
 * Custom functions for the theme by hooking
 */

require get_template_directory() . '/inc/frontend/header.php';
require get_template_directory() . '/inc/frontend/layout.php';
require get_template_directory() . '/inc/frontend/comments.php';
require get_template_directory() . '/inc/frontend/entry.php';
require get_template_directory() . '/inc/frontend/footer.php';

/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce/woocommerce.php';
}

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/backend/customizer.php';

if ( is_admin() ) {
	require get_template_directory() . '/inc/libs/class-tgm-plugin-activation.php';
	require get_template_directory() . '/inc/backend/plugins.php';
	require get_template_directory() . '/inc/backend/meta-boxes.php';
}