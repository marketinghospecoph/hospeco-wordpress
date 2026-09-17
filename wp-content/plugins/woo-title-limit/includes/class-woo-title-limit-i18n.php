<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.dimitri-wolf.de
 * @since      1.0.0
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.0.0
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/includes
 * @author     Dima W. <wtl@dimitri-wolf.de>
 */
class Woo_Title_Limit_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woo-title-limit',
			FALSE,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}


}
