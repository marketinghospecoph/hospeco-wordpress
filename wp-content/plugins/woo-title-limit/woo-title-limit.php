<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the
 * plugin admin area. This file also includes all of the dependencies used by
 * the plugin, registers the activation and deactivation functions, and defines
 * a function that starts the plugin.
 *
 * @link              https://www.dimitri-wolf.de
 * @since             2.0.0
 * @package           Woo_Title_Limit
 *
 * @wordpress-plugin
 * Plugin Name:       Title Limit for WooCommerce
 * Plugin URI:        https://www.dimitri-wolf.de
 * Description:       Allows you to set product title lengths for WooCommerce products.
 * Version:           2.0.6
 * Author: Dima W.
 * Author URI:        https://www.dimitri-wolf.de
 * License:           GPL-2.0+ License
 * URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Text Domain: woo-title-limit
 * Domain Path: /languages
 * 
 * WC tested up to: 10.2.2
 * WC requires at least: 3.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WOO_TITLE_LIMIT_VERSION', '2.0.6' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-title-limit-activator.php
 */
function activate_woo_title_limit() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-title-limit-activator.php';
	Woo_Title_Limit_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-title-limit-deactivator.php
 */
function deactivate_woo_title_limit() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-title-limit-deactivator.php';
	Woo_Title_Limit_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_title_limit' );
register_deactivation_hook( __FILE__, 'deactivate_woo_title_limit' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-title-limit.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_woo_title_limit() {

	$plugin = new Woo_Title_Limit();
	$plugin->run();

}

run_woo_title_limit();
