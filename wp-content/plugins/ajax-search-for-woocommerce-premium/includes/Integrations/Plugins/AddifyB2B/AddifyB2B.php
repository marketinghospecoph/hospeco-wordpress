<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\AddifyB2B;

use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with B2B for WooCommerce
 *
 * Plugin URL: https://woocommerce.com/products/b2b-for-woocommerce/
 * Author: Addify
 */
class AddifyB2B extends AbstractPluginIntegration {
	protected const LABEL = 'B2B for WooCommerce';

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}
		if ( ! class_exists( 'Addify_B2B_Plugin' ) ) {
			return false;
		}
		return true;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'storeInTransientProductsAndTerms' ], 20 );

		add_filter( 'dgwt/wcas/troubleshooting/renamed_plugins', [ $this, 'getFolderRenameInfo' ] );
	}

	/**
	 * Store disallowed product and term ids in transient
	 */
	public function storeInTransientProductsAndTerms() {
		/*
		 * This part of code is from \Addify_Products_Visibility_Front::afpvu_custom_pre_get_posts_query().
		 */
		$afpvu_enable_global = get_option( 'afpvu_enable_global' );
		$curr_role           = is_user_logged_in() ? current( wp_get_current_user()->roles ) : 'guest';
		$role_selected_data  = (array) get_option( 'afpvu_user_role_visibility' );

		if ( empty( $role_selected_data ) && 'yes' !== $afpvu_enable_global ) {
			return;
		}

		$role_data = isset( $role_selected_data[ $curr_role ]['afpvu_enable_role'] ) ? $role_selected_data[ $curr_role ]['afpvu_enable_role'] : 'no';

		if ( 'yes' === $afpvu_enable_global ) {
			$afpvu_show_hide          = get_option( 'afpvu_show_hide' );
			$afpvu_applied_products   = (array) get_option( 'afpvu_applied_products' );
			$afpvu_applied_categories = (array) get_option( 'afpvu_applied_categories' );
		}

		if ( 'yes' === $role_data ) {
			$_data                    = $role_selected_data[ $curr_role ];
			$afpvu_show_hide          = isset( $_data['afpvu_show_hide_role'] ) ? $_data['afpvu_show_hide_role'] : 'hide';
			$afpvu_applied_products   = isset( $_data['afpvu_applied_products_role'] ) ? (array) $_data['afpvu_applied_products_role'] : [];
			$afpvu_applied_categories = isset( $_data['afpvu_applied_categories_role'] ) ? (array) $_data['afpvu_applied_categories_role'] : [];
		}

		if ( empty( $afpvu_applied_products ) && empty( $afpvu_applied_categories ) ) {
			return;
		}

		$products_ids = [];

		if ( ! empty( $afpvu_applied_categories ) ) {
			$product_args = [
				'numberposts' => - 1,
				'post_status' => [ 'publish' ],
				'post_type'   => [ 'product' ], // skip types
				'fields'      => 'ids',
			];

			$product_args['tax_query'] = [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'id',
					'terms'    => $afpvu_applied_categories,
					'operator' => 'IN',
				],
			];

			$products_ids = (array) get_posts( $product_args );
		}

		$afpvu_applied_products = array_merge( (array) $afpvu_applied_products, (array) $products_ids );

		$b2b_data = [];

		if ( ! empty( $afpvu_show_hide ) && 'hide' == $afpvu_show_hide ) {
			$b2b_data['applied-products']   = $afpvu_applied_products;
			$b2b_data['applied-categories'] = $afpvu_applied_categories;
			$b2b_data['action']             = 'hide';
		} elseif ( ! empty( $afpvu_show_hide ) && 'show' == $afpvu_show_hide ) {
			$b2b_data['applied-products']   = $afpvu_applied_products;
			$b2b_data['applied-categories'] = $afpvu_applied_categories;
			$b2b_data['action']             = 'show';
		}

		if ( ! empty( $b2b_data ) ) {
			set_transient( 'dgwt_wcas_addify_b2b_' . get_current_user_id(), $b2b_data, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Get info about renamed plugin folder
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function getFolderRenameInfo( $plugins ) {
		$filters = new Filters();

		$result = Helpers::getFolderRenameInfo__premium_only( 'B2B for WooCommerce', $filters->plugin_names );
		if ( $result ) {
			$plugins[] = $result;
		}

		return $plugins;
	}
}
