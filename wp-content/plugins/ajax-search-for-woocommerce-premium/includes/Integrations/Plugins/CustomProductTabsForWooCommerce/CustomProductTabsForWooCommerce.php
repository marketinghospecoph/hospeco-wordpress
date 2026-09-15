<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\CustomProductTabsForWooCommerce;

// Exit if accessed directly
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with Custom Product Tabs for WooCommerce
 *
 * Plugin URL: https://yikesplugins.com/plugin/yikes-custom-product-tabs-for-woocommerce/
 * Author: YIKES, Inc.
 */
class CustomProductTabsForWooCommerce extends AbstractPluginIntegration {
	protected const LABEL         = 'Custom Product Tabs for WooCommerce';
	protected const VERSION_CONST = 'YIKES_Custom_Product_Tabs_Version';
	protected const MIN_VERSION   = '1.7.7';

	public function init(): void {
		add_filter( 'dgwt/wcas/indexer/searchable_custom_fields', [ $this, 'searchableCustomFields' ], 5 );

		add_filter( 'dgwt/wcas/indexer/items_row', [ $this, 'processTabsData' ] );
		add_filter(
			'dgwt/wcas/tnt/indexer/searchable/product_data',
			[
				$this,
				'processTabsDataInDocument',
			],
			10,
			3
		);
	}

	/**
	 * Add new item to the "Search in custom fields" list
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function searchableCustomFields( $fields ) {
		return array_merge(
			[
				[
					'label' => '[Custom Product Tabs for WooCommerce] Tab(s) content',
					'key'   => 'yikes_woo_products_tabs',
				],
			],
			$fields
		);
	}

	/**
	 * Process item custom fields and extract tabs contents
	 *
	 * @param $row
	 *
	 * @return mixed
	 */
	public function processTabsData( $row ) {
		foreach ( $row as $key => $value ) {
			if ( strpos( $key, 'cf_' ) === false ) {
				continue;
			}

			// Test if we have field with tabs data
			$data = maybe_unserialize( $value );
			if ( ! isset( $data[0]['title'] ) && ! isset( $data[0]['id'] ) && ! isset( $data[0]['content'] ) ) {
				continue;
			}

			// Replace serialized data with the contents of the tabs
			$row[ $key ] = $this->getTabsContent( $data );
		}

		return $row;
	}

	/**
	 * Process tabs content of single product when it's updated
	 *
	 * @param $document
	 * @param $productID
	 * @param $product
	 *
	 * @return mixed
	 */
	public function processTabsDataInDocument( $document, $productID, $product ) {
		return $this->processTabsData( $document );
	}

	/**
	 * Get tabs content
	 *
	 * We have to go through every tab
	 *
	 * @param $tabs
	 *
	 * @return string
	 */
	private function getTabsContent( $tabs ) {
		if ( empty( $tabs ) || ! is_array( $tabs ) ) {
			return '';
		}

		$contentArray = [];

		foreach ( $tabs as $tab ) {
			if ( ! empty( $tab['content'] ) ) {
				$contentArray[] = $tab['content'];
			}
		}

		return join( ' | ', $contentArray );
	}
}
