<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceWholeSalePricesIntegration;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;
use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php',
	];

	private $visible_products  = [];
	private $hidden_categories = [];

	public function init() {
		foreach ( $this->plugin_names as $plugin_name ) {
			if ( Config::isPluginActive( $plugin_name ) ) {
				$this->getTransientData();
				$this->excludeHiddenProductsAndCategories();

				break;
			}
		}
	}

	/**
	 * Set visible products and hidden categories from transient
	 *
	 * @return void
	 */
	private function getTransientData() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			Helpers::loadUserFiles__premium_only();
		}
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$languageSuffix = ! empty( $_GET['l'] ) && Multilingual::isLangCode( $_GET['l'] ) ? $_GET['l'] . '_' : '';
		$result         = get_transient( 'dgwt_wcas_wcwsp_data_' . $languageSuffix . get_current_user_id() );

		if ( ! empty( $result['visible-products'] ) ) {
			$this->visible_products = $result['visible-products'];
		}
		if ( ! empty( $result['hidden-categories'] ) ) {
			$this->hidden_categories = $result['hidden-categories'];
		}
	}

	/**
	 * Include only products and categories returned by WooCommerce Wholesale Prices plugin
	 */
	private function excludeHiddenProductsAndCategories() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				if ( ! empty( $this->visible_products ) && is_array( $this->visible_products ) ) {
					$ids = array_intersect( $ids, $this->visible_products );
				}

				return $ids;
			}
		);

		add_filter(
			'dgwt/wcas/search_results/term_ids',
			function ( $ids ) {
				// Exclude hidden categories.
				if ( ! empty( $this->hidden_categories ) && is_array( $this->hidden_categories ) ) {
					$ids = array_diff( $ids, $this->hidden_categories );
				}

				return $ids;
			}
		);
	}
}
