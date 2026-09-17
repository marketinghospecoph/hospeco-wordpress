<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceShowSingleVariations;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\SourceQuery;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with WooCommerce Show Single Variations by Iconic
 *
 * Plugin URL: https://iconicwp.com/products/woocommerce-show-single-variations/
 * Author: Iconic
 */
class WooCommerceShowSingleVariations extends AbstractPluginIntegration {
	protected const LABEL       = 'WooCommerce Show Single Variations by Iconic';
	protected const MIN_VERSION = '1.20.0';

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}
		if ( ! class_exists( 'Iconic_WSSV' ) ) {
			return false;
		}

		if ( version_compare( \Iconic_WSSV::$version, '1.20.0' ) < 0 ) {
			return false;
		}

		return true;
	}

	public static function pluginVersion(): string {
		if ( class_exists( 'Iconic_WSSV' ) ) {
			$version = \Iconic_WSSV::$version;
			if ( \is_string( $version ) || \is_numeric( $version ) ) {
				return (string) $version;
			}
		}
		return '';
	}

	public function init(): void {
		add_filter( 'dgwt/wcas/variation_support_modes', [ $this, 'variation_support_modes' ] );

		add_filter( 'dgwt/wcas/indexer/variation_parent_ids', [ $this, 'variation_parent_ids' ] );

		add_filter( 'dgwt/wcas/indexer/variation_ids', [ $this, 'filter_variation_ids_by_visibility' ] );
		add_filter( 'dgwt/wcas/variations_update/variation_ids', [ $this, 'filter_variation_ids_by_visibility' ] );

		add_filter( 'dgwt/wcas/variation/insert', [ $this, 'variation_insert' ], 5, 2 );

		add_filter( 'dgwt/wcas/indexer/updater/can_index', [ $this, 'can_index_after_update' ], 10, 3 );
	}

	public function variation_support_modes( $modes ): array {
		$modes   = array_diff( $modes, [ 'exact_match' ] );
		$modes[] = 'as_single_product';

		return array_values( $modes );
	}

	public function variation_parent_ids() {
		// Often parents are hidden in catalog, but we need them to get its variations.
		add_filter( 'dgwt/wcas/indexer/source_query/where/exclude_from_search', '__return_empty_string', 5 );
		$source = new SourceQuery( [ 'ids' => true ] );
		remove_filter( 'dgwt/wcas/indexer/source_query/where/exclude_from_search', '__return_empty_string', 5 );

		return $source->getData();
	}

	/**
	 * Filter variation IDs by visibility based on the WooCommerce Show Single Variations settings
	 *
	 * @param string[] $variationIds List of variation IDs
	 *
	 * @return array
	 */
	public function filter_variation_ids_by_visibility( $variationIds ) {
		global $wpdb;

		$placeholders = array_fill( 0, count( $variationIds ), '%d' );
		$format       = implode( ', ', $placeholders );

		// TODO
		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$data     = $wpdb->get_results( $wpdb->prepare( "SELECT post_id AS id, meta_value AS v FROM $wpdb->postmeta WHERE post_id IN ($format) AND meta_key = '_visibility'", $variationIds ), ARRAY_A );
		$toRemove = [];
		if ( is_array( $data ) && ! empty( $data ) ) {
			foreach ( $data as $row ) {
				$visibility = maybe_unserialize( $row['v'] );
				if ( is_array( $visibility ) && ! in_array( 'search', $visibility ) ) {
					$toRemove[] = $row['id'];
				}
			}
		}

		return array_values( array_diff( $variationIds, $toRemove ) );
	}

	/**
	 * Filter variation data in readable index
	 */
	public function variation_insert( $data, $product ) {
		// The plugin takes care of variant titles on its own, so we only need to retrieve the product title.
		/** @var \WC_Product_Variation $product */
		$data['title'] = $product->get_title();

		return $data;
	}

	/**
	 * Allow to index variable product after update
	 *
	 * We need allow to index variable products after update, because during the update we will retrieve the variations.
	 * Without this there is no way to index the variations.
	 * As side effect, the variable product will be in readable index, but not in the searchable.
	 */
	public function can_index_after_update( $can_index, $product_id, $wc_product ) {
		/** @var $wc_product \WC_Product */
		if ( $wc_product->get_type() === 'variable' ) {
			$can_index = true;
		}

		return $can_index;
	}
}
