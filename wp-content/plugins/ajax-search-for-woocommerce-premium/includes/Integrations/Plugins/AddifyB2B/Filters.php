<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\AddifyB2B;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public $plugin_names = [
		'b2b/addify_b2b.php',
	];

	private $applied_products  = [];
	private $appied_categories = [];
	private $action            = '';

	public function init() {
		foreach ( $this->plugin_names as $plugin_name ) {
			if ( Config::isPluginActive( $plugin_name ) ) {
				$this->setData();
				$this->excludeOrShowProductsAndCategories();

				break;
			}
		}
	}

	/**
	 * Set applied products and categories from transient
	 *
	 * @return void
	 */
	private function setData() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			Helpers::loadUserFiles__premium_only();
		}

		$b2b_data = get_transient( 'dgwt_wcas_addify_b2b_' . get_current_user_id() );

		if ( ! empty( $b2b_data['applied-products'] ) ) {
			$this->applied_products = $b2b_data['applied-products'];
		}
		if ( ! empty( $b2b_data['applied-categories'] ) ) {
			$this->appied_categories = $b2b_data['applied-categories'];
		}
		if ( ! empty( $b2b_data['action'] ) ) {
			$this->action = $b2b_data['action'];
		}
	}

	/**
	 * Show/hide products and categories returned by plugin
	 */
	private function excludeOrShowProductsAndCategories() {
		add_filter(
			'dgwt/wcas/tnt/search_results/ids',
			function ( $ids ) {
				// Exclude hidden products.
				if ( $this->action === 'hide' && ! empty( $this->applied_products ) && is_array( $this->applied_products ) ) {
					$ids = array_diff( $ids, $this->applied_products );
				}
				// Show visible products.
				if ( $this->action === 'show' && ! empty( $this->applied_products ) && is_array( $this->applied_products ) ) {
					$ids = array_intersect( $ids, $this->applied_products );
				}

				return $ids;
			}
		);

		add_filter(
			'dgwt/wcas/search_results/term_ids',
			function ( $ids ) {
				// Exclude hidden categories.
				if ( $this->action === 'hide' && ! empty( $this->appied_categories ) && is_array( $this->appied_categories ) ) {
					$ids = array_diff( $ids, $this->appied_categories );
				}
				// Show visible categories.
				if ( $this->action === 'show' && ! empty( $this->appied_categories ) && is_array( $this->appied_categories ) ) {
					$ids = array_intersect( $ids, $this->appied_categories );
				}

				return $ids;
			}
		);
	}
}
