<?php
/**
 * 'Clerina' theme customizer
 *
 * @package 'Clerina'
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clerina_Customize {
	/**
	 * Customize settings
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * The class constructor
	 *
	 * @param array $config
	 */
	public function __construct( $config ) {
		$this->config = $config;

		if ( ! class_exists( 'Kirki' ) ) {
			return;
		}

		$this->register();
	}

	/**
	 * Register settings
	 */
	public function register() {

		/**
		 * Add the theme configuration
		 */
		if ( ! empty( $this->config['theme'] ) ) {
			Kirki::add_config(
				$this->config['theme'], array(
					'capability'  => 'edit_theme_options',
					'option_type' => 'theme_mod',
				)
			);
		}

		/**
		 * Add panels
		 */
		if ( ! empty( $this->config['panels'] ) ) {
			foreach ( $this->config['panels'] as $panel => $settings ) {
				Kirki::add_panel( $panel, $settings );
			}
		}

		/**
		 * Add sections
		 */
		if ( ! empty( $this->config['sections'] ) ) {
			foreach ( $this->config['sections'] as $section => $settings ) {
				Kirki::add_section( $section, $settings );
			}
		}

		/**
		 * Add fields
		 */
		if ( ! empty( $this->config['theme'] ) && ! empty( $this->config['fields'] ) ) {
			foreach ( $this->config['fields'] as $name => $settings ) {
				if ( ! isset( $settings['settings'] ) ) {
					$settings['settings'] = $name;
				}

				Kirki::add_field( $this->config['theme'], $settings );
			}
		}
	}

	/**
	 * Get config ID
	 *
	 * @return string
	 */
	public function get_theme() {
		return $this->config['theme'];
	}

	/**
	 * Get customize setting value
	 *
	 * @param string $name
	 *
	 * @return bool|string
	 */
	public function get_option( $name ) {

		$default = $this->get_option_default( $name );

		return get_theme_mod( $name, $default );
	}

	/**
	 * Get default option values
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_option_default( $name ) {
		if ( ! isset( $this->config['fields'][ $name ] ) ) {
			return false;
		}

		return isset( $this->config['fields'][ $name ]['default'] ) ? $this->config['fields'][ $name ]['default'] : false;
	}
}

/**
 * This is a short hand function for getting setting value from customizer
 *
 * @param string $name
 *
 * @return bool|string
 */
function clerina_get_option( $name ) {
	global $clerina_customize;

	$value = false;

	if ( class_exists( 'Kirki' ) ) {
		$value = Kirki::get_option( 'clerina', $name );
	} elseif ( ! empty( $clerina_customize ) ) {
		$value = $clerina_customize->get_option( $name );
	}

	return apply_filters( 'clerina_get_option', $value, $name );
}

/**
 * Get default option values
 *
 * @param $name
 *
 * @return mixed
 */
function clerina_get_option_default( $name ) {
	global $clerina_customize;

	if ( empty( $clerina_customize ) ) {
		return false;
	}

	return $clerina_customize->get_option_default( $name );
}

/**
 * Move some default sections to `general` panel that registered by theme
 *
 * @param object $wp_customize
 */
function clerina_customize_modify( $wp_customize ) {
	$wp_customize->get_section( 'title_tagline' )->panel     = 'general';
	$wp_customize->get_section( 'static_front_page' )->panel = 'general';
}

add_action( 'customize_register', 'clerina_customize_modify' );


/**
 * Get customize settings
 *
 * @return array
 */
function clerina_customize_settings() {
	/**
	 * Customizer configuration
	 */

	$settings = array(
		'theme' => 'clerina',
	);

	$panels = array(
		'general'     => array(
			'priority' => 10,
			'title'    => esc_html__( 'General', 'clerina' ),
		),
		'typography'  => array(
			'priority' => 10,
			'title'    => esc_html__( 'Typography', 'clerina' ),
		),
		'styling'     => array(
			'priority' => 10,
			'title'    => esc_html__( 'Styling', 'clerina' ),
		),
		'header'      => array(
			'priority' => 10,
			'title'    => esc_html__( 'Header', 'clerina' ),
		),
		'page'        => array(
			'title'      => esc_html__( 'Page', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
		),
		'blog'        => array(
			'title'      => esc_html__( 'Blog', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
		),
		'services'    => array(
			'title'      => esc_html__( 'Services', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
		),
		'portfolio'   => array(
			'title'      => esc_html__( 'Portfolio', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
		),
		'testimonial' => array(
			'title'      => esc_html__( 'Testimonial', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
		),
		'mobile'      => array(
			'title'      => esc_html__( 'Mobile', 'clerina' ),
			'priority'   => 360,
			'capability' => 'edit_theme_options',
		),
	);

	$sections = array(
		//header
		'topbar'                         => array(
			'title'       => esc_html__( 'Topbar', 'clerina' ),
			'description' => '',
			'priority'    => 20,
			'capability'  => 'edit_theme_options',
			'panel'       => 'header',
		),
		'header_layout'                  => array(
			'title'       => esc_html__( 'Header Layout', 'clerina' ),
			'description' => '',
			'priority'    => 20,
			'capability'  => 'edit_theme_options',
			'panel'       => 'header',
		),
		'logo'                           => array(
			'title'       => esc_html__( 'Logo', 'clerina' ),
			'description' => '',
			'priority'    => 20,
			'capability'  => 'edit_theme_options',
			'panel'       => 'header',
		),
		'body_typo'                    => array(
			'title'       => esc_html__( 'Body', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'typography',
		),
		'heading_typo'                 => array(
			'title'       => esc_html__( 'Heading', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'typography',
		),
		'header_typo'                  => array(
			'title'       => esc_html__( 'Header', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'typography',
		),
		'button_typo'                  => array(
			'title'       => esc_html__( 'Button', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'typography',
		),
		'footer_typo'                  => array(
			'title'       => esc_html__( 'Footer', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'typography',
		),
		'color_scheme'                 => array(
			'title'       => esc_html__( 'Color Scheme', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'styling',
		),
		//blog
		'blog_page_header'               => array(
			'title'       => esc_html__( 'Page Header on Blog Page', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'blog',
		),
		'blog_page'                      => array(
			'title'       => esc_html__( 'Blog Page', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'blog',
		),
		'single_page_header'             => array(
			'title'       => esc_html__( 'Page Header on Single Blog', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'blog',
		),
		'single_post'                    => array(
			'title'       => esc_html__( 'Single Post', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'blog',
		),
		//service
		'service_page_header'            => array(
			'title'       => esc_html__( 'Page Header on Service Page', 'clerina' ),
			'description' => '',
			'priority'    => 9,
			'capability'  => 'edit_theme_options',
			'panel'       => 'services',
		),
		'service_layout'                 => array(
			'title'      => esc_html__( 'Service Layout', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'services',
		),
		'single_service_page_header'     => array(
			'title'       => esc_html__( 'Page Header on Single Service', 'clerina' ),
			'description' => '',
			'priority'    => 11,
			'capability'  => 'edit_theme_options',
			'panel'       => 'services',
		),
		'single_service'                 => array(
			'title'       => esc_html__( 'Single Service', 'clerina' ),
			'description' => '',
			'priority'    => 11,
			'capability'  => 'edit_theme_options',
			'panel'       => 'services',
		),
		//portfolio
		'portfolio_page_header'          => array(
			'title'       => esc_html__( 'Page Header on Portfolio Page', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'portfolio',
		),
		'portfolio_layout'               => array(
			'title'      => esc_html__( 'Portfolio Layout', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'portfolio',
		),
		'single_portfolio_page_header'   => array(
			'title'       => esc_html__( 'Page Header on Single Portfolio', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'portfolio',
		),
		'portfolio_single'               => array(
			'title'      => esc_html__( 'Portfolio Single', 'clerina' ),
			'priority'   => 11,
			'capability' => 'edit_theme_options',
			'panel'      => 'portfolio',
		),
		//Testimonial
		'testimonial_page_header'        => array(
			'title'       => esc_html__( 'Page Header on Testimonial Page', 'clerina' ),
			'description' => '',
			'priority'    => 9,
			'capability'  => 'edit_theme_options',
			'panel'       => 'testimonial',
		),
		'testimonial_layout'             => array(
			'title'      => esc_html__( 'Testimonial Layout', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'testimonial',
		),
		'single_testimonial_page_header' => array(
			'title'       => esc_html__( 'Page Header on Single Testimonial', 'clerina' ),
			'description' => '',
			'priority'    => 11,
			'capability'  => 'edit_theme_options',
			'panel'       => 'testimonial',
		),

		'page_layout' => array(
			'title'      => esc_html__( 'Page Layout', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'page',
		),

		'page_header' => array(
			'title'      => esc_html__( 'Page Header', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'page',
		),

		'newsletter'    => array(
			'title'       => esc_html__( 'NewsLetter', 'clerina' ),
			'description' => '',
			'priority'    => 210,
			'capability'  => 'edit_theme_options',
			'panel'       => 'general',
		),

		// 404
		'not_found'     => array(
			'title'      => esc_html__( '404 Page', 'clerina' ),
			'priority'   => 10,
			'capability' => 'edit_theme_options',
			'panel'      => 'page',
		),
		'footer'        => array(
			'title'       => esc_html__( 'Footer', 'clerina' ),
			'description' => '',
			'priority'    => 350,
			'capability'  => 'edit_theme_options',
		),
		'mobile_topbar' => array(
			'title'       => esc_html__( 'Top Bar', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'mobile',
		),
		'mobile_nav'    => array(
			'title'       => esc_html__( 'Navigation Fixed', 'clerina' ),
			'description' => '',
			'priority'    => 10,
			'capability'  => 'edit_theme_options',
			'panel'       => 'mobile',
		),
	);
	// Contact form 7
	$mail_forms    = get_posts( 'post_type=wpcf7_contact_form&posts_per_page=-1' );
	$mail_form_ids = array(
		'' => esc_html__( 'Select Form', 'clerina' ),
	);
	foreach ( $mail_forms as $form ) {
		$mail_form_ids[ $form->ID ] = $form->post_title;
	}
	$fields = array(
		// Logo
		'logo'                          => array(
			'type'        => 'image',
			'label'       => esc_html__( 'Logo', 'clerina' ),
			'description' => esc_html__( 'This logo is used for all site.', 'clerina' ),
			'section'     => 'logo',
			'default'     => '',
			'priority'    => 20,
		),
		'logo_width'                    => array(
			'type'     => 'text',
			'label'    => esc_html__( 'Logo Width(px)', 'clerina' ),
			'section'  => 'logo',
			'priority' => 20,
			array(
				'setting'  => 'logo',
				'operator' => '!=',
				'value'    => '',
			),
		),
		'logo_height'                   => array(
			'type'     => 'text',
			'label'    => esc_html__( 'Logo Height(px)', 'clerina' ),
			'section'  => 'logo',
			'priority' => 20,
			array(
				'setting'  => 'logo',
				'operator' => '!=',
				'value'    => '',
			),
		),
		'logo_margins'                  => array(
			'type'     => 'spacing',
			'label'    => esc_html__( 'Logo Margin', 'clerina' ),
			'section'  => 'logo',
			'priority' => 20,
			'default'  => array(
				'top'    => '0',
				'bottom' => '0',
				'left'   => '0',
				'right'  => '0',
			),
			array(
				'setting'  => 'logo',
				'operator' => '!=',
				'value'    => '',
			),
		),

		// Typography
		'body_typo'                             => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Body', 'clerina' ),
			'section'  => 'body_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Lato',
				'variant'        => '400',
				'font-size'      => '16px',
				'line-height'    => '1.6',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#767676',
				'text-transform' => 'none',
			),
		),
		'heading1_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 1', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '60px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'heading2_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 2', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '40px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'heading3_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 3', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '30px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'heading4_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 4', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '24px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'heading5_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 5', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '18px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'heading6_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Heading 6', 'clerina' ),
			'section'  => 'heading_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'font-size'      => '16px',
				'line-height'    => '1.4',
				'letter-spacing' => '0',
				'subsets'        => array( 'latin-ext' ),
				'color'          => '#3e4140',
				'text-transform' => 'none',
			),
		),
		'menu_typo'                             => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Menu', 'clerina' ),
			'section'  => 'header_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'subsets'        => array( 'latin-ext' ),
				'font-size'      => '14px',
				'color'          => '#3e4140',
				'text-transform' => 'uppercase',
			),
		),
		'sub_menu_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Sub Menu', 'clerina' ),
			'section'  => 'header_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '500',
				'subsets'        => array( 'latin-ext' ),
				'font-size'      => '16px',
				'color'          => '#3e4140',
				'text-transform' => 'capitalize',
			),
		),
		'button_typo'                         => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Button', 'clerina' ),
			'section'  => 'button_typo',
			'priority' => 10,
			'default'  => array(
				'font-family'    => 'Poppins',
				'variant'        => '600',
				'subsets'        => array( 'latin-ext' ),
				'font-size'      => '14px',
				'color'          => '#3e4140',
			),
		),
		'footer_text_typo'                      => array(
			'type'     => 'typography',
			'label'    => esc_html__( 'Footer Text', 'clerina' ),
			'section'  => 'footer_typo',
			'priority' => 10,
			'default'  => array(
				'font-family' => 'Lato',
				'variant'     => '400',
				'subsets'     => array( 'latin-ext' ),
				'font-size'   => '16px',
				'color'   => '#a3a1a1',
			),
		),

		// Color Scheme
		'color_scheme'                          => array(
			'type'     => 'palette',
			'label'    => esc_html__( 'Base Color Scheme', 'clerina' ),
			'default'  => '',
			'section'  => 'color_scheme',
			'priority' => 10,
			'choices'  => array(
				'' => array( '#3065b8' ),
				'#4fa65c' => array( '#4fa65c' ),
				'#fcb700' => array( '#fcb700' ),
				'#8224e3' => array( '#8224e3' ),
				'#dd3333' => array( '#dd3333' ),
			),
		),
		'custom_color_scheme'                   => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Custom Color Scheme', 'clerina' ),
			'default'  => 0,
			'section'  => 'color_scheme',
			'priority' => 10,
		),
		'custom_color'                          => array(
			'type'            => 'color',
			'label'           => esc_html__( 'Color', 'clerina' ),
			'default'         => '',
			'section'         => 'color_scheme',
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'custom_color_scheme',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'color_skin'                              => array(
			'type'     => 'select',
			'label'    => esc_html__( 'Skin', 'clerina' ),
			'section'  => 'color_scheme',
			'default'  => '',
			'priority' => 10,
			'choices'  => array(
				''     => esc_html__( 'Light', 'clerina' ),
				'dark' => esc_html__( 'Dark', 'clerina' ),
			),
		),
		
		// Topbar
		'topbar'                        => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Top Bar', 'clerina' ),
			'default'     => '0',
			'section'     => 'topbar',
			'priority'    => 20,
			'description' => esc_html__( 'Go to Appearance > Widgets > Topbar Left and Topbar Right to add widgets content.', 'clerina' ),
		),
		'topbar-mobile'                 => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Top Bar On Mobile', 'clerina' ),
			'default'     => '0',
			'section'     => 'mobile_topbar',
			'priority'    => 20,
			'description' => esc_html__( 'Go to Appearance > Widgets > Topbar Mobile to add widgets content.', 'clerina' ),
		),
		// Header Layout
		'header_layout'                 => array(
			'type'     => 'select',
			'label'    => esc_html__( 'Header Layout', 'clerina' ),
			'section'  => 'header_layout',
			'default'  => '2',
			'priority' => 10,
			'choices'  => array(
				'1' => esc_html__( 'Header 1', 'clerina' ),
				'2' => esc_html__( 'Header 2', 'clerina' ),
				'3' => esc_html__( 'Header 3', 'clerina' ),
			),
		),
		'menu_extras'                   => array(
			'type'     => 'multicheck',
			'label'    => esc_html__( 'Menu Extras', 'clerina' ),
			'section'  => 'header_layout',
			'default'  => array('search'),
			'priority' => 20,
			'choices'  => array(
				'cart'   => esc_html__( 'Cart', 'clerina' ),
				'search' => esc_html__( 'Search', 'clerina' ),
			),
		),
		'menu_booking'                  => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Booking', 'clerina' ),
			'section'         => 'header_layout',
			'default'         => 0,
			'description'     => esc_html__( 'Enable this option to show booking button in the site header.', 'clerina' ),
			'priority'        => 20,
			'active_callback' => array(
				array(
					'setting'  => 'header_layout',
					'operator' => '==',
					'value'    => '2',
				),
			),
		),
		'menu_booking_text'             => array(
			'type'            => 'textarea',
			'label'           => esc_html__( 'Booking Text', 'clerina' ),
			'section'         => 'header_layout',
			'default'         => esc_html__( 'Booking Service', 'clerina' ),
			'priority'        => 20,
			'active_callback' => array(
				array(
					'setting'  => 'header_layout',
					'operator' => '==',
					'value'    => '2',
				),
			),
		),
		'menu_booking_link'             => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Booking Link', 'clerina' ),
			'section'         => 'header_layout',
			'default'         => '',
			'priority'        => 20,
			'active_callback' => array(
				array(
					'setting'  => 'header_layout',
					'operator' => '==',
					'value'    => '2',
				),
			),
		),
		'menu_custom_height'            => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Custom Menu Height', 'clerina' ),
			'section'  => 'header_layout',
			'default'  => 0,
			'priority' => 20,
		),
		'menu_height'                   => array(
			'type'            => 'number',
			'label'           => esc_html__( 'Menu Height', 'clerina' ),
			'section'         => 'header_layout',
			'default'         => 64,
			'priority'        => 20,
			'transport'       => 'postMessage',
			'js_vars'         => array(
				array(
					'element'  => '.primary-nav > ul > li, .site-header .menu-extras li',
					'property' => 'line-height',
					'units'    => 'px',
				),
			),
			'active_callback' => array(
				array(
					'setting'  => 'menu_custom_height',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		// NewsLetter
		'newsletter_popup'              => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Enable NewsLetter Popup', 'clerina' ),
			'default'     => '0',
			'section'     => 'newsletter',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show newsletter popup.', 'clerina' ),
		),
		'newsletter_home_popup'         => array(
			'type'        => 'toggle',
			'label'       => esc_html__( "Show in Homepage", 'clerina' ),
			'default'     => '1',
			'section'     => 'newsletter',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to disable newsletter popup in the homepage..', 'clerina' ),
		),
		'newsletter_bg_image'           => array(
			'type'     => 'image',
			'label'    => esc_html__( 'Background Image', 'clerina' ),
			'default'  => '',
			'section'  => 'newsletter',
			'priority' => 20,
		),
		'newsletter_content'            => array(
			'type'     => 'textarea',
			'label'    => esc_html__( 'Content', 'clerina' ),
			'default'  => '',
			'section'  => 'newsletter',
			'priority' => 20,
		),
		'newsletter_form'               => array(
			'type'        => 'textarea',
			'label'       => esc_html__( 'NewsLetter Form', 'clerina' ),
			'default'     => '',
			'description' => sprintf( wp_kses_post( 'Enter the shortcode of MailChimp form . You can edit your sign - up form in the <a href= "%s" > MailChimp for WordPress form settings </a>.', 'clerina' ), admin_url( 'admin.php?page=mailchimp-for-wp-forms' ) ),
			'section'     => 'newsletter',
			'priority'    => 20,
		),
		'newsletter_reappear'           => array(
			'type'        => 'number',
			'label'       => esc_html__( 'Reappear', 'clerina' ),
			'default'     => '1',
			'section'     => 'newsletter',
			'priority'    => 20,
			'description' => esc_html__( 'Reappear after how many day(s) using Cookie', 'clerina' ),
		),
		'newsletter_visible'            => array(
			'type'     => 'select',
			'label'    => esc_html__( 'Visible', 'clerina' ),
			'default'  => '1',
			'section'  => 'newsletter',
			'priority' => 20,
			'choices'  => array(
				'1' => esc_html__( 'After page loaded', 'clerina' ),
				'2' => esc_html__( 'After how many seconds', 'clerina' ),
			),
		),
		'newsletter_seconds'            => array(
			'type'            => 'number',
			'label'           => esc_html__( 'Seconds', 'clerina' ),
			'default'         => '10',
			'section'         => 'newsletter',
			'priority'        => 20,
			'active_callback' => array(
				array(
					'setting'  => 'newsletter_visible',
					'operator' => '==',
					'value'    => '2',
				),
			),
		),
		// Blog Page Header
		'blog_page_header'              => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'blog_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'blog_page_header_els'          => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'blog_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'blog_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'blog_page_header_bg'           => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'blog_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'blog_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'blog_page_header_text_color'   => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'blog_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for blog page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'blog_page_header_parallax'     => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'blog_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'blog_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Blog
		'blog_view'                     => array(
			'type'     => 'radio',
			'label'    => esc_html__( 'Blog View', 'clerina' ),
			'section'  => 'blog_page',
			'default'  => 'classic',
			'priority' => 10,
			'choices'  => array(
				'grid'    => esc_html__( 'Grid', 'clerina' ),
				'classic' => esc_html__( 'Classic', 'clerina' ),
			),
		),
		'blog_view_custom_field_1'      => array(
			'type'    => 'custom',
			'section' => 'blog_page',
			'default' => '<hr/>',
		),
		'blog_layout'                   => array(
			'type'            => 'select',
			'label'           => esc_html__( 'Blog Layout', 'clerina' ),
			'section'         => 'blog_page',
			'default'         => 'sidebar-content',
			'priority'        => 10,
			'description'     => esc_html__( 'Select default sidebar for blog page.', 'clerina' ),
			'choices'         => array(
				'content-sidebar' => esc_html__( 'Right Sidebar', 'clerina' ),
				'sidebar-content' => esc_html__( 'Left Sidebar', 'clerina' ),
				'full-content'    => esc_html__( 'Full Content', 'clerina' ),
			),
		),
		'blog_grid_columns'             => array(
			'type'            => 'select',
			'label'           => esc_html__( 'Blog Grid Columns', 'clerina' ),
			'section'         => 'blog_page',
			'default'         => '3',
			'priority'        => 10,
			'choices'         => array(
				'2' => esc_html__( '2 Columns', 'clerina' ),
				'3' => esc_html__( '3 Columns', 'clerina' ),
			),
			'active_callback' => array(
				array(
					'setting'  => 'blog_view',
					'operator' => '==',
					'value'    => 'grid',
				),
			),
		),
		'blog_excerpt_length'           => array(
			'type'     => 'number',
			'label'    => esc_html__( 'Blog Excerpt Length', 'clerina' ),
			'section'  => 'blog_page',
			'default'  => 40,
			'priority' => 10,
		),
		'blog_cat_filter'               => array(
			'type'            => 'toggle',
			'default'         => 0,
			'label'           => esc_html__( 'Categories Filter', 'clerina' ),
			'section'         => 'blog_page',
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'blog_view',
					'operator' => '==',
					'value'    => 'classic',
				),
			),
		),
		'blog_categories_numbers'       => array(
			'type'            => 'number',
			'label'           => esc_html__( 'Categories Numbers', 'clerina' ),
			'section'         => 'blog_page',
			'default'         => '5',
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'blog_cat_filter',
					'operator' => '==',
					'value'    => true,
				),
			),
		),
		'blog_categories'               => array(
			'type'            => 'textarea',
			'label'           => esc_html__( 'Categories slug', 'clerina' ),
			'section'         => 'blog_page',
			'default'         => '',
			'priority'        => 10,
			'description'     => esc_html__( 'Enter categories slug you want to display. Each slug is separated by comma character ",". If empty, it will display default', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'blog_cat_filter',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Post Page Header
		'single_page_header'            => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 0,
			'section'     => 'single_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'single_page_header_els'        => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'single_page_header',
			'default'         => array( 'breadcrumb' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_page_header_bg'         => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'single_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'single_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_page_header_text_color' => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'single_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for post page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'single_page_header_parallax'   => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'single_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Single Posts
		'single_post_layout'            => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Single Post Layout', 'clerina' ),
			'section'     => 'single_post',
			'default'     => 'sidebar-content',
			'priority'    => 5,
			'description' => esc_html__( 'Select default sidebar for the single post page.', 'clerina' ),
			'choices'     => array(
				'content-sidebar' => esc_html__( 'Right Sidebar', 'clerina' ),
				'sidebar-content' => esc_html__( 'Left Sidebar', 'clerina' ),
				'full-content'    => esc_html__( 'Full Content', 'clerina' ),
			),
		),
		'single_show_thumbnail'                => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Show Image Featured', 'clerina' ),
			'section'  => 'single_post',
			'default'  => '1',
			'priority' => 10,
		),
		'post_entry_meta' => array(
			'type'     => 'multicheck',
			'label'    => esc_html__( 'Entry Meta', 'clerina' ),
			'section'  => 'single_post',
			'default'  => true,
			'priority' => 10,
		),
		'post_entry_meta_posted_by' => array(
			'type'     => 'checkbox',
			'label'    => esc_html__( 'Author', 'clerina' ),
			'section'  => 'single_post',
			'default'  => true,
			'priority' => 10,
		),
		'post_entry_meta_posted_on' => array(
			'type'     => 'checkbox',
			'label'    => esc_html__( 'Date', 'clerina' ),
			'section'  => 'single_post',
			'default'  => true,
			'priority' => 10,
		),
		'show_author_box'                => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Show Author Box', 'clerina' ),
			'section'  => 'single_post',
			'default'  => 0,
			'priority' => 10,
		),
		'show_post_social_share'         => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Show Socials Share', 'clerina' ),
			'description' => esc_html__( 'Check this option to show socials share in the single post page.', 'clerina' ),
			'section'     => 'single_post',
			'default'     => 0,
			'priority'    => 10,
		),
		'post_socials_share'             => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Socials Share', 'clerina' ),
			'section'         => 'single_post',
			'default'         => array( 'facebook', 'twitter', 'linkedin' ),
			'choices'         => array(
				'twitter'   => esc_html__( 'Twitter', 'clerina' ),
				'facebook'  => esc_html__( 'Facebook', 'clerina' ),
				'google'    => esc_html__( 'Google Plus', 'clerina' ),
				'pinterest' => esc_html__( 'Pinterest', 'clerina' ),
				'linkedin'  => esc_html__( 'Linkedin', 'clerina' ),
				'vkontakte' => esc_html__( 'Vkontakte', 'clerina' ),
			),
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'show_post_social_share',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'related_custom_field_1'         => array(
			'type'    => 'custom',
			'section' => 'single_post',
			'default' => '<hr/>',
		),
		'related_posts'                  => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Related Post', 'clerina' ),
			'section'  => 'single_post',
			'default'  => '0',
			'priority' => 10,
		),
		'related_posts_title'            => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Related Post Title', 'clerina' ),
			'priority'        => 10,
			'default'         => '',
			'section'         => 'single_post',
			'active_callback' => array(
				array(
					'setting'  => 'related_posts',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'related_posts_numbers'          => array(
			'type'            => 'number',
			'label'           => esc_html__( 'Number Post', 'clerina' ),
			'priority'        => 10,
			'default'         => '2',
			'section'         => 'single_post',
			'active_callback' => array(
				array(
					'setting'  => 'related_posts',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		// Service Page Header
		'service_page_header'            => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'service_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'service_page_header_els'        => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'service_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'service_page_header_text_color' => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'service_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for service page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'service_page_header_bg'         => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'service_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'service_page_header_parallax'   => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'service_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		// Page Layout Service
		'service_section_title'          => array(
			'type'        => 'text',
			'label'       => esc_html__( 'Section Title', 'clerina' ),
			'description' => esc_html__( 'Section title for service layout', 'clerina' ),
			'priority'    => 10,
			'default'     => '',
			'section'     => 'service_layout',
		),
		'service_layout_description'     => array(
			'type'     => 'textarea',
			'label'    => esc_html__( 'Layout description ', 'clerina' ),
			'priority' => 10,
			'default'  => '',
			'section'  => 'service_layout',
		),

		'service_long_excerpt'  => array(
			'type'        => 'number',
			'label'       => esc_html__( 'Excerpt', 'clerina' ),
			'description' => esc_html__( 'Long Excerpt', 'clerina' ),
			'priority'    => 10,
			'default'     => '',
			'section'     => 'service_layout',
		),
		'service_pagination'                        => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Pagination', 'clerina' ),
			'priority' => 10,
			'default'  => '1',
			'section'  => 'service_layout',

		),
		'service_pagination_style'                  => array(
			'type'            => 'radio',
			'label'           => esc_html__( 'Pagination style', 'clerina' ),
			'priority'        => 10,
			'default'         => '1',
			'section'         => 'service_layout',
			'choices'         => array(
				'1' => esc_attr__( 'Number', 'clerina' ),
				'2' => esc_attr__( 'Load more', 'clerina' ),
			),
			'active_callback' => array(
				array(
					'setting'  => 'service_pagination',
					'operator' => '==',
					'value'    => '1',
				),
			),

		),
		'service_appointment'   => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Appointment', 'clerina' ),
			'section'  => 'service_layout',
			'default'  => 1,
			'priority' => 10,
		),
		'service_contact_title' => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Title', 'clerina' ),
			'description'     => esc_html__( 'Insert title appointment', 'clerina' ),
			'priority'        => 30,
			'default'         => '',
			'section'         => 'service_layout',
			'active_callback' => array(
				array(
					'setting'  => 'service_appointment',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),


		'service_contact_id'         => array(
			'type'            => 'select',
			'label'           => esc_html__( 'Contact Form 7', 'clerina' ),
			'section'         => 'service_layout',
			'default'         => '',
			'priority'        => 40,
			'choices'         => $mail_form_ids,
			'active_callback' => array(
				array(
					'setting'  => 'service_appointment',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
		'service_contact_background' => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'service_layout',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'service_appointment',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Page Header Single Service
		'single_service_page_header'                => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'single_service_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'single_service_page_header_els'            => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'single_service_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_service_page_header_bg'             => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'single_service_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'single_service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_service_page_header_text_color'     => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'single_service_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for single service page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'single_service_page_header_parallax'       => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'single_service_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_service_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		// Single Service
		'service_sidebar'                           => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Service Layout', 'clerina' ),
			'default'     => 'sidebar-content',
			'section'     => 'single_service',
			'priority'    => 5,
			'description' => esc_html__( 'Select default sidebar for service page.', 'clerina' ),
			'choices'     => array(
				'sidebar-content' => esc_html__( 'Left Sidebar', 'clerina' ),
				'content-sidebar' => esc_html__( 'Right Sidebar', 'clerina' ),
				'full-content'    => esc_html__( 'Full Content', 'clerina' ),
			),
		),
		'service_single_nav'                        => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Navigation', 'clerina' ),
			'default'  => 1,
			'section'  => 'single_service',
			'priority' => 10,
		),

		// Portfolio Page Header
		'portfolio_page_header'                     => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'portfolio_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'portfolio_page_header_els'                 => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'portfolio_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'portfolio_page_header_bg'                  => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'portfolio_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'portfolio_page_header_text_color'          => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'portfolio_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for portfolio page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'portfolio_page_header_parallax'            => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'portfolio_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Page Layout Portfolio
		'portfolio_layout'                          => array(
			'type'     => 'select',
			'label'    => esc_html__( 'Portfolio Layout', 'clerina' ),
			'priority' => 10,
			'default'  => 'full-width',
			'section'  => 'portfolio_layout',
			'choices'  => array(
				'grid'       => esc_html__( 'Grid', 'clerina' ),
				'full-width' => esc_html__( 'Full width', 'clerina' ),
			),
		),
		'portfolio_cats_filters'                    => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Portfolio Categories Filters', 'clerina' ),
			'priority' => 10,
			'default'  => '1',
			'section'  => 'portfolio_layout',

		),
		'portfolio_cat_number'                      => array(
			'type'            => 'number',
			'label'           => esc_html__( 'Categorize number', 'clerina' ),
			'priority'        => 10,
			'default'         => '10',
			'section'         => 'portfolio_layout',
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_cats_filters',
					'operator' => '==',
					'value'    => '1',
				),
			),

		),
		'portfolio_pagination'                      => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Pagination', 'clerina' ),
			'priority' => 10,
			'default'  => '1',
			'section'  => 'portfolio_layout',

		),
		'portfolio_pagination_style'                => array(
			'type'            => 'radio',
			'label'           => esc_html__( 'Pagination style', 'clerina' ),
			'priority'        => 10,
			'default'         => '1',
			'section'         => 'portfolio_layout',
			'choices'         => array(
				'1' => esc_attr__( 'Number', 'clerina' ),
				'2' => esc_attr__( 'Load more', 'clerina' ),
			),
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_pagination',
					'operator' => '==',
					'value'    => '1',
				),
			),

		),

		// Page Header Single Portfolio
		'single_portfolio_page_header'              => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'single_portfolio_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'single_portfolio_page_header_els'          => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'single_portfolio_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_portfolio_page_header_bg'           => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'single_portfolio_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'single_portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_portfolio_page_header_text_color'   => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'single_portfolio_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for single portfolio page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'single_portfolio_page_header_parallax'     => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'single_portfolio_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_portfolio_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Single Portfolio
		'portfolio_single_nav'                      => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Navigation', 'clerina' ),
			'default'  => 1,
			'section'  => 'portfolio_single',
			'priority' => 10,

		),
		'portfolio_single_project'                  => array(
			'type'            => 'text',
			'label'           => esc_html__( 'Go to Project Link', 'clerina' ),
			'default'         => '',
			'section'         => 'portfolio_single',
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'portfolio_single_nav',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Testimonial Page Header
		'testimonial_page_header'                   => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'testimonial_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'testimonial_page_header_els'               => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'testimonial_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'testimonial_page_header_text_color'        => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'testimonial_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for service page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'testimonial_page_header_bg'                => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'testimonial_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'testimonial_page_header_parallax'          => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'testimonial_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Page Layout Testimonial
		'testimonial_title'                         => array(
			'type'        => 'text',
			'label'       => esc_html__( 'Title', 'clerina' ),
			'description' => esc_html__( 'Insert title appointment', 'clerina' ),
			'priority'    => 10,
			'default'     => '',
			'section'     => 'testimonial_layout',
		),
		'testimonial_description'                   => array(
			'type'     => 'textarea',
			'label'    => esc_html__( 'Layout description ', 'clerina' ),
			'priority' => 10,
			'default'  => '',
			'section'  => 'testimonial_layout',
		),
		'testimonial_pagination'                    => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Pagination', 'clerina' ),
			'priority' => 10,
			'default'  => '1',
			'section'  => 'testimonial_layout',
		),
		'testimonial_pagination_style'              => array(
			'type'            => 'radio',
			'label'           => esc_html__( 'Pagination style', 'clerina' ),
			'priority'        => 10,
			'default'         => '1',
			'section'         => 'testimonial_layout',
			'choices'         => array(
				'1' => esc_attr__( 'Number', 'clerina' ),
				'2' => esc_attr__( 'Load more', 'clerina' ),
			),
			'active_callback' => array(
				array(
					'setting'  => 'testimonial_pagination',
					'operator' => '==',
					'value'    => '1',
				),
			),

		),


		// Page Header Single Testimonial
		'single_testimonial_page_header'            => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'single_testimonial_page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'single_testimonial_page_header_els'        => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'single_testimonial_page_header',
			'default'         => array( 'breadcrumb', 'title' ),
			'priority'        => 20,
			'choices'         => array(
				'breadcrumb' => esc_html__( 'BreadCrumb', 'clerina' ),
				'title'      => esc_html__( 'Title', 'clerina' ),
			),
			'description'     => esc_html__( 'Select which elements you want to show.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_testimonial_page_header_bg'         => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'single_testimonial_page_header',
			'default'         => '',
			'priority'        => 30,
			'active_callback' => array(
				array(
					'setting'  => 'single_testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),
		'single_testimonial_page_header_text_color' => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'single_testimonial_page_header',
			'default'     => 'dark',
			'priority'    => 10,
			'description' => esc_html__( 'Select default color for single testimonial page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'single_testimonial_page_header_parallax'   => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'single_testimonial_page_header',
			'priority'        => 30,
			'description'     => esc_html__( 'Check this option to enable parallax for the page header.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'single_testimonial_page_header',
					'operator' => '==',
					'value'    => 1,
				),
			),
		),

		// Page
		'page_layout'                               => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Page Layout', 'clerina' ),
			'section'     => 'page_layout',
			'default'     => 'full-content',
			'priority'    => 10,
			'description' => esc_html__( 'Select default sidebar for page default.', 'clerina' ),
			'choices'     => array(
				'full-content'    => esc_html__( 'Full Content', 'clerina' ),
				'sidebar-content' => esc_html__( 'Left Sidebar', 'clerina' ),
				'content-sidebar' => esc_html__( 'Right Sidebar', 'clerina' ),
			),
		),
		'page_page_header'                          => array(
			'type'        => 'toggle',
			'label'       => esc_html__( 'Page Header', 'clerina' ),
			'default'     => 1,
			'section'     => 'page_header',
			'priority'    => 10,
			'description' => esc_html__( 'Check this option to show the page header below the header.', 'clerina' ),
		),
		'page_page_header_els'                      => array(
			'type'            => 'multicheck',
			'label'           => esc_html__( 'Page Header Elements', 'clerina' ),
			'section'         => 'page_header',
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
		'page_page_header_bg'                       => array(
			'type'            => 'image',
			'label'           => esc_html__( 'Background Image', 'clerina' ),
			'section'         => 'page_header',
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
		'page_page_header_text_color'               => array(
			'type'        => 'select',
			'label'       => esc_html__( 'Text Color', 'clerina' ),
			'section'     => 'page_header',
			'default'     => 'dark',
			'priority'    => 30,
			'description' => esc_html__( 'Select default color for default page page header.', 'clerina' ),
			'choices'     => array(
				'dark'  => esc_html__( 'Dark', 'clerina' ),
				'light' => esc_html__( 'Light', 'clerina' ),
			),
		),
		'page_page_header_parallax'                 => array(
			'type'            => 'toggle',
			'label'           => esc_html__( 'Parallax', 'clerina' ),
			'default'         => 1,
			'section'         => 'page_header',
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
		// 404 background img
		'not_found_bg'                              => array(
			'type'     => 'image',
			'label'    => esc_html__( 'Background Image', 'clerina' ),
			'section'  => 'not_found',
			'default'  => '',
			'priority' => 10,
		),
		// Footer
		'footer_layout'                             => array(
			'type'     => 'select',
			'label'    => esc_html__( 'Footer Layout', 'clerina' ),
			'priority' => 10,
			'default'  => 'grid',
			'section'  => 'footer',
			'choices'  => array(
				'grid'       => esc_html__( 'Grid', 'clerina' ),
				'full-width' => esc_html__( 'Full width', 'clerina' ),
			),
		),
		'footer_custom_1'                           => array(
			'type'     => 'custom',
			'section'  => 'footer',
			'default'  => '<hr/>',
			'priority' => 10,
		),
		'footer_widget'                             => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Footer Widgets', 'clerina' ),
			'section'  => 'footer',
			'default'  => 1,
			'priority' => 10,
		),
		'footer_widget_columns'                     => array(
			'type'            => 'select',
			'label'           => esc_html__( 'Footer Columns', 'clerina' ),
			'section'         => 'footer',
			'default'         => '4',
			'priority'        => 20,
			'choices'         => array(
				'2' => esc_html__( '2 Columns', 'clerina' ),
				'3' => esc_html__( '3 Columns', 'clerina' ),
				'4' => esc_html__( '4 Columns', 'clerina' ),
			),
			'description'     => esc_html__( 'Go to Appearance > Widgets > Footer 1, 2, 3, 4 to add widgets content.', 'clerina' ),
			'active_callback' => array(
				array(
					'setting'  => 'footer_widget',
					'operator' => '==',
					'value'    => true,
				),
			),
		),
		'footer_custom_2'                           => array(
			'type'     => 'custom',
			'section'  => 'footer',
			'default'  => '<hr/>',
			'priority' => 20,
		),

		// Copyright
		'footer_copyright'                          => array(
			'type'        => 'textarea',
			'label'       => esc_html__( 'Footer Copyright', 'clerina' ),
			'description' => esc_html__( 'Shortcodes are allowed', 'clerina' ),
			'section'     => 'footer',
			'default'     => '',
			'priority'    => 20,
		),
		'footer_copyright_image'                    => array(
			'type'     => 'image',
			'label'    => esc_html__( 'Logo', 'clerina' ),
			'section'  => 'footer',
			'default'  => '',
			'priority' => 20,
		),
		'footer_copyright_social'                   => array(
			'type'     => 'repeater',
			'label'    => esc_html__( 'Socials', 'clerina' ),
			'section'  => 'footer',
			'priority' => 20,
			'fields'   => array(
				'link_url' => array(
					'type'        => 'text',
					'label'       => esc_html__( 'Social URL', 'clerina' ),
					'description' => esc_html__( 'Enter the URL for this social', 'clerina' ),
					'default'     => '',
				),
			),
		),

		//Back to top
		'footer_custom_3'                           => array(
			'type'     => 'custom',
			'section'  => 'footer',
			'default'  => '<hr/>',
			'priority' => 20,
		),
		'back_to_top'                               => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Back to Top', 'clerina' ),
			'section'  => 'footer',
			'default'  => 0,
			'priority' => 30,
		),

		//Navigation Fixed
		'mobile_nav'                                => array(
			'type'     => 'toggle',
			'label'    => esc_html__( 'Navigation Fixed', 'clerina' ),
			'section'  => 'mobile_nav',
			'default'  => '0',
			'priority' => 10,
		),
		'mobile_option'                             => array(
			'type'            => 'sortable',
			'label'           => esc_html__( 'Sort Items', 'clerina' ),
			'section'         => 'mobile_nav',
			'default'         => array(
				'home',
				'booking',
				'cart'
			),
			'choices'         => array(
				'home'    => esc_attr__( 'Home', 'clerina' ),
				'booking' => esc_attr__( 'Bokking', 'clerina' ),
				'cart'    => esc_attr__( 'Cart', 'clerina' ),
			),
			'priority'        => 10,
			'active_callback' => array(
				array(
					'setting'  => 'mobile_nav',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
		'mobile_booking'                    => array(
			'type'     => 'text',
			'label'       => esc_html__( 'Booking URL', 'clerina' ),
			'description' => esc_html__( 'Enter the URL for this booking form', 'clerina' ),
			'section'  => 'mobile_nav',
			'priority' => 20,
			'choices'  => array(
				'element'     => 'input',
				'type'        => 'text',
				'placeholder' => 'http://',
			),
			array(
				'setting'  => 'mobile_nav',
				'operator' => '==',
				'value'    => '1',
			),
		),

	);

	$settings['panels']   = apply_filters( 'clerina_customize_panels', $panels );
	$settings['sections'] = apply_filters( 'clerina_customize_sections', $sections );
	$settings['fields']   = apply_filters( 'clerina_customize_fields', $fields );

	return $settings;
}


$clerina_customize = new Clerina_Customize( clerina_customize_settings() );
