<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceB2B;

use DgoraWcas\Engines\TNTSearchMySQL\Config;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'woocommerce-b2b/woocommerce-b2b.php',
	];

	private $disallowed_products = [];
	private $disallowed_terms    = [];

	public function init() {
		foreach ( $this->plugin_names as $plugin_name ) {
			if ( Config::isPluginActive( $plugin_name ) ) {
				$this->setDisallowedProductsAndTerms();
				$this->excludeHidenProductsAndTerms();

				break;
			}
		}
	}

	/**
	 * Set disallowed products and terms from PHP Session
	 *
	 * @return void
	 */
	private function setDisallowedProductsAndTerms() {
		if ( ! session_id() ) {
			session_start();
		}

		if ( ! empty( $_SESSION['dgwt-wcas-b2b-woocommerce-disallowed-products'] ) ) {
			$this->disallowed_products = $_SESSION['dgwt-wcas-b2b-woocommerce-disallowed-products'];
		}
		if ( ! empty( $_SESSION['dgwt-wcas-b2b-woocommerce-disallowed-terms'] ) ) {
			$this->disallowed_terms = $_SESSION['dgwt-wcas-b2b-woocommerce-disallowed-terms'];
		}
	}

	/**
	 * Exclude products and terms returned by plugin
	 */
	private function excludeHidenProductsAndTerms() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				if ( ! empty( $this->disallowed_products ) && is_array( $this->disallowed_products ) ) {
					$ids = array_diff( $ids, $this->disallowed_products );
				}

				return $ids;
			}
		);

		add_filter(
			'dgwt/wcas/search_results/term_ids',
			function ( $ids ) {
				if ( ! empty( $this->disallowed_terms ) && is_array( $this->disallowed_terms ) ) {
					$ids = array_diff( $ids, $this->disallowed_terms );
				}

				return $ids;
			}
		);
	}
}
