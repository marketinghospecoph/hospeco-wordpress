<?php

namespace DgoraWcas\Integrations\Plugins\ProductVisibilityByUserRole;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_name          = 'product-visibility-by-user-role-for-woocommerce-pro/product-visibility-by-user-role-for-woocommerce-pro.php';
	private $disallowed_products = [];
	private $visible_cat_ids     = [];
	private $current_user_roles  = [ 'guest' ];

	public function init() {
		if ( Config::isPluginActive( $this->plugin_name ) && get_option( 'alg_wc_pvbur_enabled' ) !== 'no' ) {
			$this->userContext();

			$this->setVisibleProducts();
			$this->excludeHiddenProducts();
			$this->setVisibleCategories();
			$this->excludeHiddenCategories();
		}
	}

	private function userContext() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			Helpers::loadUserFiles__premium_only();
		}

		$user       = wp_get_current_user();
		$user_roles = ! empty( $user->roles ) ? $user->roles : [ 'guest' ];

		$this->current_user_roles = array_values( array_unique( $user_roles ) );
	}

	/**
	 * Set disallowed products from transient
	 *
	 * @return void
	 */
	private function setVisibleProducts() {
		$key    = 'pvbur_visible_products_' . implode( '-', $this->current_user_roles );
		$result = get_transient( $key );

		if ( is_array( $result ) ) {
			$this->disallowed_products = array_values( array_unique( array_map( 'intval', $result ) ) );
		}
	}

	private function excludeHiddenProducts() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {

				if ( is_array( $this->disallowed_products ) ) {
					$ids = array_diff( $ids, $this->disallowed_products );
				}

				return $ids;
			}
		);
	}

	private function setVisibleCategories() {
		$key    = 'pvbur_visible_category_' . implode( '-', $this->current_user_roles );
		$result = get_transient( $key );

		if ( is_array( $result ) ) {
			$this->visible_cat_ids = array_values( array_unique( array_map( 'intval', $result ) ) );
		}
	}

	private function excludeHiddenCategories() {
		if ( empty( $this->visible_cat_ids ) ) {
			return;
		}

		add_filter(
			'dgwt/wcas/tnt/search_results/taxonomies',
			function ( $results ) {
				if ( empty( $results ) ) {
					return $results;
				}

				foreach ( $results as $index => $result ) {
					$taxonomy = $result['taxonomy'] ?? '';
					$term_id  = isset( $result['term_id'] ) ? (int) $result['term_id'] : 0;

					if ( $taxonomy === 'product_cat' && ! in_array( $term_id, $this->visible_cat_ids, true ) ) {
						unset( $results[ $index ] );
					}
				}

				return array_values( $results );
			}
		);
	}
}
