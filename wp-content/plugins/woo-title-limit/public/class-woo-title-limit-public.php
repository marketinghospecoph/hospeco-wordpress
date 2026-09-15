<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.dimitri-wolf.de
 * @since      2.0.0
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/public
 * @author     Dima W. <wtl@dimitri-wolf.de>
 */
class Woo_Title_Limit_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string $plugin_name The ID of this plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var      string $version The current version of this plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Title_Limit_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Title_Limit_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-title-limit-public.css', [], $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Title_Limit_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Title_Limit_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-title-limit-public.js', [ 'jquery' ], $this->version, FALSE );

	}

	/**
	 * @param $page
	 *
	 * @return mixed
	 */
	public function get_wtl_options( $page ) {
		return get_option( "wtl_opt_{$page}" );
	}

	/**
	 * @param $title
	 * @param $id
	 *
	 * @return false|string
	 */
	public function get_shorten_product_title( $title, $id ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			if ( get_post_type( $id ) !== 'product' ) {
				return $title;
			}

			if ( is_product() ) {
				return $this->shorten_title( 'product', $title );
			}
			if ( is_product_category() ) {
				return $this->shorten_title( 'category', $title );
			}
			if ( is_product_tag() ) {
				return $this->shorten_title( 'tag', $title );
			}
			if ( is_shop() ) {
				return $this->shorten_title( 'shop', $title );
			}
			if ( is_home() || is_front_page() ) {
				return $this->shorten_title( 'home', $title );
			}
		}

		return $title;
	}

	/**
	 * @param $wtl_page
	 * @param $title
	 *
	 * @return false|string
	 */
	public function shorten_title( $wtl_page, $title ) {

		$general_options = get_option( 'wtl_opt_general' );
		$wordcutter      = isset( $general_options['wordcutter'] ) ? $general_options['wordcutter'] : 'off';
		$options         = $this->get_wtl_options( $wtl_page );
		$dots            = isset( $options['dots'] ) ? $options['dots'] : 'off';
		$count           = isset( $options['count'] ) ? $options['count'] : 0;

		$pos = 0;
		if ( isset( $options ) && $count > 0 ) {
			if ( $dots == 'off' && $count < strlen( $title ) ) {
				if ( $wordcutter == 'on' ) {
					$pos = strpos( $title, ' ', $count );
					if ( ! $pos ) {
						return $title;
					} else {
						return substr( $title, 0, $pos );
					}
				} else {
					return substr( $title, 0, $count );
				}
			} else if ( $dots == 'on' && $count < strlen( $title ) ) {
				if ( $wordcutter == 'on' ) {
					$pos = strpos( $title, ' ', $count );
					if ( ! $pos ) {
						return $title;
					} else {
						return substr( $title, 0, $pos ) . '...';
					}
				} else {
					return substr( $title, 0, $count ) . '...';
				}
			} else {
				return $title;
			}
		} else {
			return $title;
		}


	}

}
