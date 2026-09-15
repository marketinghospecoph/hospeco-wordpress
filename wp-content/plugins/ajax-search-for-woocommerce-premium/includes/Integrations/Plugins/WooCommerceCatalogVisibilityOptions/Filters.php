<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceCatalogVisibilityOptions;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'woocommerce-catalog-visibility-options/woocommerce-catalog-visibility-options.php',
	];

	private $disallowed_products = [];

	public function init() {

		foreach ( $this->plugin_names as $plugin_name ) {

			if ( Config::isPluginActive( $plugin_name ) ) {

				$this->setVisibleProducts();
				$this->excludeHiddenProducts();

				break;
			}
		}
	}

	/**
	 * Set disallowed products from transient
	 *
	 * @return void
	 */
	private function setVisibleProducts() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			Helpers::loadUserFiles__premium_only();
		}

		$result = get_transient( 'dgwt_wcas_wccvo_disallowed_products_' . get_current_user_id() );

		if ( is_array( $result ) ) {
			$this->disallowed_products = $result;
		}
	}

	/**
	 * Exclude products returned by WooCommerce Catalog Visibility Options
	 */
	private function excludeHiddenProducts() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				if ( ! empty( $this->disallowed_products ) && is_array( $this->disallowed_products ) ) {
					$ids = array_diff( $ids, $this->disallowed_products );
				}

				return $ids;
			}
		);
	}
}
