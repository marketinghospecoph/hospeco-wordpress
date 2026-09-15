<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceMemberships;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'woocommerce-memberships/woocommerce-memberships.php',
	];

	private $visible_products;

	public function init() {

		foreach ( $this->plugin_names as $plugin_name ) {

			if ( Config::isPluginActive( $plugin_name ) ) {

				$this->setVisibleProducts();
				$this->excludeHidenProducts();

				break;
			}
		}
	}

	/**
	 * Set visible products from transient
	 *
	 * @return void
	 */
	private function setVisibleProducts() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			Helpers::loadUserFiles__premium_only();
		}

		$result = get_transient( 'dgwt_wcas_woocommerce_memberships_visible_products_' . get_current_user_id() );

		if ( is_array( $result ) ) {
			$this->visible_products = $result;
		}
	}

	/**
	 * Include only products returned by WooCommerce Memberships
	 */
	private function excludeHidenProducts() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				if ( is_array( $this->visible_products ) ) {
					$ids = array_intersect( $ids, $this->visible_products );
				}

				return $ids;
			}
		);
	}
}
