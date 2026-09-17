<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.dimitri-wolf.de
 * @since      1.0.0
 *
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    Woo_Title_Limit
 * @subpackage Woo_Title_Limit/includes
 * @author     Dima W. <wtl@dimitri-wolf.de>
 */
class Woo_Title_Limit_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function activate() {
		$old_options   = get_option( 'wtl_opt' );
		$new_opt_check = get_option( 'wtl_opt_general' );

		if ( isset( $old_options ) && ! empty( $old_options ) && empty( $new_opt_check ) ) {
			$wtl_count_shop            = ( isset( $old_options['wtl_count_shop'] ) ? $old_options['wtl_count_shop'] : 0 );
			$wtl_count_category        = ( isset( $old_options['wtl_count_category'] ) ? $old_options['wtl_count_category'] : 0 );
			$wtl_count_product         = ( isset( $old_options['wtl_count_product'] ) ? $old_options['wtl_count_product'] : 0 );
			$wtl_count_home            = ( isset( $old_options['wtl_count_home'] ) ? $old_options['wtl_count_home'] : 0 );
			$wtl_checkbox_etc_shop     = ( isset( $old_options['wtl_checkbox_etc_shop'] ) && $old_options['wtl_checkbox_etc_shop'] == '1' ) ? 'on' : 'off';
			$wtl_checkbox_etc_category = ( isset( $old_options['wtl_checkbox_etc_category'] ) && $old_options['wtl_checkbox_etc_category'] == '1' ) ? 'on' : 'off';
			$wtl_checkbox_etc_product  = ( isset( $old_options['wtl_checkbox_etc_product'] ) && $old_options['wtl_checkbox_etc_product'] == '1' ) ? 'on' : 'off';
			$wtl_checkbox_etc_home     = ( isset( $old_options['wtl_checkbox_etc_home'] ) && $old_options['wtl_checkbox_etc_home'] == '1' ) ? 'on' : 'off';
			$wtl_checkbox_widgets      = ( isset( $old_options['wtl_checkbox_widgets'] ) && $old_options['wtl_checkbox_widgets'] == '1' ) ? 'on' : 'off';
			$wtl_checkbox_wordcutter   = ( isset( $old_options['wtl_checkbox_wordcutter'] ) && $old_options['wtl_checkbox_wordcutter'] == '1' ) ? 'on' : 'off';

			$new_options = [
				'wtl_opt_shop'     => [
					'count' => $wtl_count_shop,
					'dots'  => $wtl_checkbox_etc_shop,
				],
				'wtl_opt_category' => [
					'count' => $wtl_count_category,
					'dots'  => $wtl_checkbox_etc_category,
				],
				'wtl_opt_product'  => [
					'count' => $wtl_count_product,
					'dots'  => $wtl_checkbox_etc_product,
				],
				'wtl_opt_home'     => [
					'count' => $wtl_count_home,
					'dots'  => $wtl_checkbox_etc_home,
				],
				'wtl_opt_tag'      => [
					'count' => 0,
					'dots'  => 'off',
				],
				'wtl_opt_general'  => [
					'wordcutter'   => $wtl_checkbox_wordcutter,
					'auto_widgets' => $wtl_checkbox_widgets,
				],
			];

			foreach ( $new_options as $key => $value ) {
				update_option( $key, $value );
			}
		}
	}

}
