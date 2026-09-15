<?php
/**
 * Registering meta boxes
 *
 * All the definitions of meta boxes are listed below with comments.
 *
 * For more information, please visit:
 * @link http://www.deluxeblogtips.com/meta-box/
 */

/**
 * Enqueue script for handling actions with meta boxes
 *
 * @since 1.0
 *
 * @param string $hook
 */
function clerina_meta_box_scripts( $hook ) {
	// Detect to load un-minify scripts when WP_DEBUG is enable
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
		wp_enqueue_script( 'clerina-meta-boxes', get_template_directory_uri() . "/js/backend/meta-boxes$min.js", array( 'jquery' ), '20181007', true );
	}
}
add_action( 'admin_enqueue_scripts', 'clerina_meta_box_scripts' );

/**
 * Registering meta boxes
 *
 * Using Meta Box plugin: http://www.deluxeblogtips.com/meta-box/
 *
 * @see http://www.deluxeblogtips.com/meta-box/docs/define-meta-boxes
 *
 * @param array $meta_boxes Default meta boxes. By default, there are no meta boxes.
 *
 * @return array All registered meta boxes
 */
function clerina_register_meta_boxes( $meta_boxes ) {
	// Post format's meta box
	$meta_boxes[] = array(
		'id'       => 'post-format-settings',
		'title'    => esc_html__( 'Format Details', 'clerina' ),
		'pages'    => array( 'post' ),
		'context'  => 'normal',
		'priority' => 'high',
		'autosave' => true,
		'fields'   => array(
			array(
				'name'             => esc_html__( 'Image', 'clerina' ),
				'id'               => 'image',
				'type'             => 'image_advanced',
				'class'            => 'image',
				'max_file_uploads' => 1,
			),
			array(
				'name'  => esc_html__( 'Gallery', 'clerina' ),
				'id'    => 'images',
				'type'  => 'image_advanced',
				'class' => 'gallery',
			),
			array(
				'name'  => esc_html__( 'Video', 'clerina' ),
				'id'    => 'video',
				'type'  => 'textarea',
				'cols'  => 20,
				'rows'  => 2,
				'class' => 'video',
			),
		),
	);

	// Portfolio
	$meta_boxes[] = array(
		'id'       => 'testimonial-info',
		'title'    => esc_html__( 'Testimonial Info', 'clerina' ),
		'pages'    => array( 'testimonial' ),
		'context'  => 'normal',
		'priority' => 'high',
		'autosave' => true,
		'fields'   => array(
			array(
				'name'  => esc_html__( 'Job', 'clerina' ),
				'id'    => 'job',
				'type'  => 'text',
				'class' => 'job',
			),
			array(
				'name'       => esc_html__( 'Rating', 'clerina' ),
				'id'         => 'rating',
				'type'       => 'slider',
				'js_options' => array(
					'min'  => 0,
					'max'  => 10,
					'step' => 1,
				),
			),
		),
	);

	//Page Header Settings
	$meta_boxes[] = array(
		'id'       => 'page-header-settings',
		'title'    => esc_html__( 'Page Header Settings', 'clerina' ),
		'pages'    => array( 'post', 'page', 'portfolio', 'service' ),
		'context'  => 'normal',
		'priority' => 'high',
		'fields'   => array(
			array(
				'name'  => esc_html__( 'Hide Page Header', 'clerina' ),
				'id'    => 'hide_page_header',
				'type'  => 'checkbox',
				'std'   => false,
				'class' => 'hide-homepage',
			),
			array(
				'name'  => esc_html__( 'Hide Breadcrumb', 'clerina' ),
				'id'    => 'hide_breadcrumb',
				'type'  => 'checkbox',
				'std'   => false,
				'class' => 'hide-homepage',
			),
			array(
				'name'  => esc_html__( 'Hide Title', 'clerina' ),
				'id'    => 'hide_title',
				'type'  => 'checkbox',
				'std'   => false,
				'class' => 'hide-homepage',
			),
			array(
				'name'  => esc_html__( 'Disable Parallax', 'clerina' ),
				'id'    => 'hide_parallax',
				'type'  => 'checkbox',
				'std'   => false,
				'class' => 'hide-homepage',
			),
			array(
				'name'    => esc_html__( 'Text Color', 'clerina' ),
				'id'      => 'text_color',
				'type'    => 'select',
				'std'     => '',
				'options' => array(
					''      => esc_html__( '', 'clerina' ),
					'dark'  => esc_html__( 'Dark', 'clerina' ),
					'light' => esc_html__( 'Light', 'clerina' ),
				),
				'class'   => 'hide-homepage',
			),
			array(
				'name'             => esc_html__( 'Background Image', 'clerina' ),
				'id'               => 'page_bg',
				'type'             => 'image_advanced',
				'max_file_uploads' => 1,
				'std'              => false,
				'class'            => 'hide-homepage',
			),
		),
	);

	return $meta_boxes;
}

add_filter( 'rwmb_meta_boxes', 'clerina_register_meta_boxes' );
