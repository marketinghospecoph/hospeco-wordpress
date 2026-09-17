<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\B2BKing;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'b2bking/b2bking.php',
		'/.*\/b2bking\.php/',
	];

	private $visible_products = null;
	private $visible_terms    = null;

	public function init() {
		foreach ( $this->plugin_names as $plugin_name ) {
			if ( Config::isPluginActive( $plugin_name ) ) {
				$this->setVisibleProductsAndTerms();
				$this->excludeHiddenProducts();

				break;
			}
		}
	}

	/**
	 * Set visible products from transient
	 *
	 * @return void
	 */
	private function setVisibleProductsAndTerms() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			Helpers::loadUserFiles__premium_only();
		}

		$visible_products = get_transient( 'dgwt_wcas_b2bking_visible_products_' . get_current_user_id() );

		if ( is_array( $visible_products ) ) {
			$this->visible_products = $visible_products;
		}

		$visible_terms = get_transient( 'dgwt_wcas_b2bking_visible_terms_' . get_current_user_id() );

		if ( is_array( $visible_terms ) ) {
			$this->visible_terms = $visible_terms;
		}
	}

	/**
	 * Include only products returned by B2BKing
	 */
	private function excludeHiddenProducts() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				// Filter products only if list of visible IDs have been passed via transient
				if ( is_array( $this->visible_products ) ) {
					$ids = array_intersect( $ids, $this->visible_products );
				}

				return $ids;
			}
		);

		add_filter(
			'dgwt/wcas/search_results/term_ids',
			function ( $termIds ) {
				if ( is_array( $this->visible_terms ) ) {
					$termIds = array_intersect( $termIds, $this->visible_terms );
				}

				return $termIds;
			}
		);
	}
}
