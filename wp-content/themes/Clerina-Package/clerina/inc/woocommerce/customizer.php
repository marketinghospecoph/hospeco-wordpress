<?php
/**
 * Theme options of WooCommerce.
 *
 * @package 'Clerina'
 */


/**
 * Adds theme options sections of WooCommerce.
 *
 * @param array $sections Theme options sections.
 *
 * @return array
 */
function clerina_woocommerce_customize_sections( $sections ) {
	$sections = array_merge( $sections, array(
		'shop_page_header'             => array(
			'title'       => esc_html__( 'Page Header on Shop Page', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'woocommerce',
		),
		'single_product_page_header'   => array(
			'title'       => esc_html__( 'Page Header on Single Product', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'woocommerce',
		),
	) );

	return $sections;
}

add_filter( 'clerina_customize_sections', 'clerina_woocommerce_customize_sections' );

/**
 * Adds theme options of WooCommerce.
 *
 * @param array $settings Theme options.
 *
 * @return array
 */
function clerina_woocommerce_customize_settings( $fields ) {
	// Product page.
	$fields = array_merge( $fields, array(
			'page_header_shop'                      => array(
				'type'        => 'toggle',
				'label'       => esc_html__( 'Page Header', 'clerina' ),
				'default'     => 1,
				'section'     => 'shop_page_header',
				'priority'    => 10,
				'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
			),
			'page_header_shop_els'                  => array(
				'type'            => 'multicheck',
				'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
				'section'         => 'shop_page_header',
				'default'         => array( 'breadcrumb', 'title' ),
				'priority'        => 20,
				'choices'         => array(
					'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
					'title'      => esc_html__( 'Title', 'clerina' ),
				),
				'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),
			'page_header_shop_bg'                   => array(
				'type'            => 'image',
				'label'           => esc_html__( 'Background Image', 'clerina' ),
				'section'         => 'shop_page_header',
				'default'         => '',
				'priority'        => 30,
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),
			'page_header_shop_color'        => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Text Color', 'clerina' ),
				'section'     => 'shop_page_header',
				'default'     => 'dark',
				'priority'    => 30,
				'description' => esc_html__( 'Select default color for default page page header.', 'clerina' ),
				'choices'     => array(
					'dark'  => esc_html__( 'Dark', 'clerina' ),
					'light' => esc_html__( 'Light', 'clerina' ),
				),
			),
			'page_header_shop_parallax'             => array(
				'type'            => 'toggle',
				'label'           => esc_html__( 'Parallax', 'clerina' ),
				'default'         => 1,
				'section'         => 'shop_page_header',
				'priority'        => 30,
				'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),

			// Shop Page
			'shop_layout'                  => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Shop Layout', 'clerina' ),
				'default'     => 'full-content',
				'section'     => 'woocommerce_product_catalog',
				'priority'    => 5,
				'description' => esc_html__( 'Select default sidebar for shop page.', 'clerina' ),
				'choices'     => array(
					'sidebar-content' => esc_html__( 'Left Sidebar', 'clerina' ),
					'content-sidebar' => esc_html__( 'Right Sidebar', 'clerina' ),
					'full-content'    => esc_html__( 'Full Content', 'clerina' ),
				),
			),

			// Page Header for Single Product
			'single_product_page_header'                      => array(
				'type'        => 'toggle',
				'label'       => esc_html__( 'Page Header', 'clerina' ),
				'default'     => 1,
				'section'     => 'single_product_page_header',
				'priority'    => 10,
				'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
			),
			'single_product_page_header_els'                  => array(
				'type'            => 'multicheck',
				'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
				'section'         => 'single_product_page_header',
				'default'         => array( 'breadcrumb', 'title' ),
				'priority'        => 20,
				'choices'         => array(
					'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
					'title'      => esc_html__( 'Title', 'clerina' ),
				),
				'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),
			'single_product_page_header_bg'                   => array(
				'type'            => 'image',
				'label'           => esc_html__( 'Background Image', 'clerina' ),
				'section'         => 'single_product_page_header',
				'default'         => '',
				'priority'        => 30,
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),
			'single_product_page_header_color'        => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Text Color', 'clerina' ),
				'section'     => 'single_product_page_header',
				'default'     => 'dark',
				'priority'    => 30,
				'description' => esc_html__( 'Select default color for default page page header.', 'clerina' ),
				'choices'     => array(
					'dark'  => esc_html__( 'Dark', 'clerina' ),
					'light' => esc_html__( 'Light', 'clerina' ),
				),
			),
			'single_product_page_header_parallax'             => array(
				'type'            => 'toggle',
				'label'           => esc_html__( 'Parallax', 'clerina' ),
				'default'         => 1,
				'section'         => 'single_product_page_header',
				'priority'        => 30,
				'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
				'active_callback' => array(
					array(
						'setting'  => 'page_page_header',
						'operator' => '==',
						'value'    => 1,
					),
				),
			),
		)
	);

	return $fields;
}

add_filter( 'clerina_customize_fields', 'clerina_woocommerce_customize_settings', 30 );