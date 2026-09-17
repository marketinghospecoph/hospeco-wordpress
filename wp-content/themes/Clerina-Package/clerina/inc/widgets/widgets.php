<?php
/**
 * Load and register widgets
 *
 * @package Clerina
 */

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function clerina_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Blog Sidebar', 'clerina' ),
		'id'            => 'blog-sidebar',
		'description'   => esc_html__( 'Add widgets here in order to display them on blog pages.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Top bar Left', 'clerina' ),
		'id'            => 'topbar-left',
		'description'   => esc_html__( 'Add widgets here in order to display them on top bar left.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Top bar Right', 'clerina' ),
		'id'            => 'topbar-right',
		'description'   => esc_html__( 'Add widgets here in order to display them on top bar right.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Top bar mobile', 'clerina' ),
		'id'            => 'topbar-mobile',
		'description'   => esc_html__( 'Add widgets here in order to display them on top bar mobile.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Header Sidebar', 'clerina' ),
		'id'            => 'header-sidebar',
		'description'   => esc_html__( 'Add widgets here in order to display them on site header.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Service Sidebar', 'clerina' ),
		'id'            => 'service-sidebar',
		'description'   => esc_html__( 'Add widgets here in order to display them on service page.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Shop Sidebar', 'clerina' ),
		'id'            => 'shop-sidebar',
		'description'   => esc_html__( 'Add widgets here in order to display them on shop page.', 'clerina' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	for ( $i = 1; $i < 5; $i ++ ) {
		register_sidebar( array(
			'name'          => sprintf( esc_html__( 'Footer Sidebar %s', 'clerina' ), $i ),
			'id'            => 'footer-' . $i,
			'description'   => esc_html__( 'Add widgets here in order to display on footer', 'clerina' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		) );
	}
}

add_action( 'widgets_init', 'clerina_widgets_init' );

require_once get_template_directory() . '/inc/widgets/popular-posts.php';
require_once get_template_directory() . '/inc/widgets/service-category.php';
require_once get_template_directory() . '/inc/widgets/social-media-links.php';

/**
 * Register widgets
 *
 * @since  1.0
 *
 * @return void
 */
function clerina_register_widgets() {
	register_widget( 'Clerina_PopularPost_Widget' );
	register_widget( 'Clerina_Service_Category_Widget' );
	register_widget( 'Clerina_Social_Links_Widget' );


}

add_action( 'widgets_init', 'clerina_register_widgets' );