<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              piwebsolution.com
 * @since             1.1.10.6
 * @package           Disable_Payment_Method_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       PiWeb Disable payment method / Partial payment for WooCommerce
 * Plugin URI:        https://www.piwebsolution.com/product/disable-payment-method-payment-fees-partial-payment-for-woocommerce/
 * Description:       Disable any payment method based on various conditions 
 * Version:           1.1.10.6
 * Author:            PI Websolution
 * Author URI:        https://www.piwebsolution.com/product/disable-payment-method-payment-fees-partial-payment-for-woocommerce/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       disable-payment-method-for-woocommerce
 * Domain Path:       /languages
 * WC tested up to: 11.0
 * Requires plugins: woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if(!is_plugin_active( 'woocommerce/woocommerce.php')){
    function pisol_dpmw_woo_active() {
        ?>
        <div class="error notice">
            <p><?php esc_html_e( 'Disable payment method for WooCommerce plugin need WooCommerce active, so activate WooCommerce in your website', 'disable-payment-method-for-woocommerce' ); ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'pisol_dpmw_woo_active' );
    return;
}

if(is_plugin_active( 'disable-payment-method-for-woocommerce-pro/disable-payment-method-for-woocommerce.php')){
    function pi_dpmw_my_free_pro_notice() {
        ?>
        <div class="error notice">
            <p><?php esc_html_e( 'Please deactivate the free version of <strong>Disable payment method for WooCommerce</strong> as you have the PRO version', 'disable-payment-method-for-woocommerce'); ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'pi_dpmw_my_free_pro_notice' );
    deactivate_plugins(plugin_basename(__FILE__));
    return;
}else{

/**
 * Currently plugin version.
 * Start at version 1.1.10.6 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DISABLE_PAYMENT_METHOD_FOR_WOOCOMMERCE_VERSION', '1.1.10.6' );
define( 'DISABLE_PAYMENT_METHOD_FOR_WOOCOMMERCE_DOCUMENTATION_URL', 'https://www.piwebsolution.com' );
define( 'DISABLE_PAYMENT_METHOD_FOR_WOOCOMMERCE_DOCUMENTATION_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/admin/advance-fees/templates/' );

define('DISABLE_PAYMENT_METHOD_FOR_WOOCOMMERCE_PRICE', '$19');
define('DISABLE_PAYMENT_METHOD_FOR_WOOCOMMERCE_BUY_URL', 'https://www.piwebsolution.com/product/disable-payment-method-payment-fees-partial-payment-for-woocommerce/?utm_campaign=disable-payment-free-plugin&utm_source=website&utm_medium=direct-buy');

/**
 * Declare compatible with HPOS new order table 
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-disable-payment-method-for-woocommerce-activator.php
 */
function activate_disable_payment_method_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-disable-payment-method-for-woocommerce-activator.php';
	Disable_Payment_Method_For_Woocommerce_Activator::activate();
    Pi_dpmw_Blocklist_DB::install();
    add_option('pi_dpmw_do_activation_redirect', true);
}

add_action('plugins_loaded', ['Pi_dpmw_Blocklist_DB', 'maybe_upgrade']);

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-disable-payment-method-for-woocommerce-deactivator.php
 */
function deactivate_disable_payment_method_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-disable-payment-method-for-woocommerce-deactivator.php';
	Disable_Payment_Method_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_disable_payment_method_for_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_disable_payment_method_for_woocommerce' );

add_action('admin_init', function (){
    if (get_option('pi_dpmw_do_activation_redirect', false)) {
        delete_option('pi_dpmw_do_activation_redirect');
        if(!isset($_GET['activate-multi']))
        {
            wp_redirect("admin.php?page=pisol-dpmw-settings");
        }
    }
});

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-disable-payment-method-for-woocommerce.php';

function pisol_dpmw_links( $links ) {
	$links = array_merge( array(
        '<a href="' . esc_url( admin_url( '/admin.php?page=pisol-dpmw-settings' ) ) . '">' . __( 'Settings','disable-payment-method-for-woocommerce' ) . '</a>'
	), $links );
	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pisol_dpmw_links' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.1.10.6
 */
function run_disable_payment_method_for_woocommerce() {

	$plugin = new Disable_Payment_Method_For_Woocommerce();
	$plugin->run();

}
run_disable_payment_method_for_woocommerce();

}