<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.dimitri-wolf.de
 * @since      1.0.0
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/admin
 * @author     Dima W. <wtl@dimitri-wolf.de>
 */
class Woo_Title_Limit_Admin {

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
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-title-limit-admin.css', [], $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-title-limit-admin.js', [ 'jquery' ], $this->version, FALSE );

	}
	/**
	 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage)
	 *
	 * @since 2.0.4
	 */
	public function set_hpos_support_for_wc() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_dir_path( dirname( __FILE__ ) ) . 'woo-title-limit.php', true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', plugin_dir_path( dirname( __FILE__ ) ) . 'woo-title-limit.php', true );
		}
	}

	public function wtl_admin_notices() {
		if ( is_plugin_inactive( 'woocommerce/woocommerce.php' ) ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e( 'Woo Title Limit Notice: WooCommerce is either not installed or not activated', 'woo-title-limit' ); ?></strong>
                </p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
			<?php
		}
	}

	/**
	 *
	 */
	public function create_settings_menu() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-osa.php';
		if ( class_exists( 'WP_OSA' ) ) {
			/**
			 * Object Instantiation.
			 *
			 * Object for the class `WP_OSA`.
			 */
			$wtl_opt_obj = new WP_OSA();


			// Section: Basic Settings.
			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_general',
					'title' => __( 'General', 'woo-title-limit' ),
					'desc'  => __( 'Some settings that affect all areas.', 'woo-title-limit' ),
				]
			);

			// Section: Basic Settings.
			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_shop',
					'title' => __( 'Shop Page', 'woo-title-limit' ),
					'desc'  => __( 'Influences product titles on the shop page.', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_product',
					'title' => __( 'Product Page', 'woo-title-limit' ),
					'desc'  => __( 'Influences product titles on product pages.', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_category',
					'title' => __( 'Category Page', 'woo-title-limit' ),
					'desc'  => __( 'Influences product titles on product category pages.', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_home',
					'title' => __( 'Home Page', 'woo-title-limit' ),
					'desc'  => __( 'Influences product titles on the home page.', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_section(
				[
					'id'    => 'wtl_opt_tag',
					'title' => __( 'Tag Page', 'woo-title-limit' ),
					'desc'  => __( 'Influences product titles on product tag pages.', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_general',
				[
					'id'   => 'wordcutter',
					'type' => 'checkbox',
					'name' => __( 'Shorten the product title at the end of the word?', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_general',
				[
					'id'   => 'auto_widgets',
					'type' => 'checkbox',
					'name' => __( 'Shorten product title for widgets automatically? <br> (still beta ¯\_(ツ)_/¯)', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			// Field: Number.
			$wtl_opt_obj->add_field(
				'wtl_opt_shop',
				[
					'id'                => 'count',
					'type'              => 'number',
					'name'              => __( 'Product Title length', 'woo-title-limit' ),
					'desc'              => __( 'Set the limit for the product titles (amount of maximum characters).', 'woo-title-limit' ),
					'default'           => 0,
					'sanitize_callback' => 'intval',
				]
			);

			// Field: Checkbox.
			$wtl_opt_obj->add_field(
				'wtl_opt_shop',
				[
					'id'   => 'dots',
					'type' => 'checkbox',
					'name' => __( 'Add "..." to the title', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_product',
				[
					'id'                => 'count',
					'type'              => 'number',
					'name'              => __( 'Product Title length', 'woo-title-limit' ),
					'desc'              => __( 'Set the limit for the product titles (amount of maximum characters).', 'woo-title-limit' ),
					'default'           => 0,
					'sanitize_callback' => 'intval',
				]
			);

			// Field: Checkbox.
			$wtl_opt_obj->add_field(
				'wtl_opt_product',
				[
					'id'   => 'dots',
					'type' => 'checkbox',
					'name' => __( 'Add "..." to the title', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_category',
				[
					'id'                => 'count',
					'type'              => 'number',
					'name'              => __( 'Product Title length', 'woo-title-limit' ),
					'desc'              => __( 'Set the limit for the product titles (amount of maximum characters).', 'woo-title-limit' ),
					'default'           => 0,
					'sanitize_callback' => 'intval',
				]
			);

			// Field: Checkbox.
			$wtl_opt_obj->add_field(
				'wtl_opt_category',
				[
					'id'   => 'dots',
					'type' => 'checkbox',
					'name' => __( 'Add "..." to the title', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_home',
				[
					'id'                => 'count',
					'type'              => 'number',
					'name'              => __( 'Product Title length', 'woo-title-limit' ),
					'desc'              => __( 'Set the limit for the product titles (amount of maximum characters).', 'woo-title-limit' ),
					'default'           => 0,
					'sanitize_callback' => 'intval',
				]
			);

			// Field: Checkbox.
			$wtl_opt_obj->add_field(
				'wtl_opt_home',
				[
					'id'   => 'dots',
					'type' => 'checkbox',
					'name' => __( 'Add "..." to the title', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);

			$wtl_opt_obj->add_field(
				'wtl_opt_tag',
				[
					'id'                => 'count',
					'type'              => 'number',
					'name'              => __( 'Product Title length', 'woo-title-limit' ),
					'desc'              => __( 'Set the limit for the product titles (amount of maximum characters).', 'woo-title-limit' ),
					'default'           => 0,
					'sanitize_callback' => 'intval',
				]
			);

			// Field: Checkbox.
			$wtl_opt_obj->add_field(
				'wtl_opt_tag',
				[
					'id'   => 'dots',
					'type' => 'checkbox',
					'name' => __( 'Add "..." to the title', 'woo-title-limit' ),
					'desc' => __( '', 'woo-title-limit' ),
				]
			);
		}
	}

	/**
	 * @return array
	 */
	public function plugin_settings_link( $links ) {
		$links[] = '<a href="' .
		           admin_url( 'options-general.php?page=woo-title-limit' ) .
		           '">' . __( 'Settings', 'woo-title-limit' ) . '</a>';

		return $links;
	}

}
