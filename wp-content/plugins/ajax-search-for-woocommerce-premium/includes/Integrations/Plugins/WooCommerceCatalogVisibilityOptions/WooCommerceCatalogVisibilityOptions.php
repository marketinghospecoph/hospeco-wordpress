<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceCatalogVisibilityOptions;

use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with WooCommerce Catalog Visibility Options
 *
 * Plugin URL: https://woocommerce.com/products/catalog-visibility-options/
 * Author: Lucas Stark
 */
class WooCommerceCatalogVisibilityOptions extends AbstractPluginIntegration {
	protected const LABEL         = 'WooCommerce Catalog Visibility Options';
	protected const VERSION_CONST = 'WC_CATALOG_VISIBILITY_OPTIONS_VERSION';
	protected const MIN_VERSION   = '2.8.5';

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}

		return parent::isActive();
	}

	public function init(): void {
		add_action( 'init', [ $this, 'storeDisallowedProductsInTransient' ], 20 );

		add_filter( 'dgwt/wcas/troubleshooting/renamed_plugins', [ $this, 'getFolderRenameInfo' ] );
	}

	/**
	 * Store disallowed product IDs in transient
	 */
	public function storeDisallowedProductsInTransient() {
		if ( class_exists( 'WC_Catalog_Restrictions_Query' ) ) {
			$wc_catalog_restrictions_query = \WC_Catalog_Restrictions_Query::instance();
			$disallowed_products           = $wc_catalog_restrictions_query->get_disallowed_products();

			set_transient( 'dgwt_wcas_wccvo_disallowed_products_' . get_current_user_id(), $disallowed_products, HOUR_IN_SECONDS );
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

		$result = Helpers::getFolderRenameInfo__premium_only( 'WooCommerce Catalog Visibility Options', $filters->plugin_names );
		if ( $result ) {
			$plugins[] = $result;
		}

		return $plugins;
	}
}
