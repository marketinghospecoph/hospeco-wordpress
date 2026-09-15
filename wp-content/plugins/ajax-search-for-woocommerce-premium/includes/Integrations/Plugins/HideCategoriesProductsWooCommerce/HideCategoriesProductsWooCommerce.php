<?php

namespace DgoraWcas\Integrations\Plugins\HideCategoriesProductsWooCommerce;

// Exit if accessed directly
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with Hide Categories and Products for Woocommerce
 *
 * Plugin URL: https://wordpress.org/plugins/hide-categories-products-woocommerce/
 * Author: N.O.U.S. Open Useful and Simple
 */
class HideCategoriesProductsWooCommerce extends AbstractPluginIntegration {
	protected const LABEL = 'Hide Categories and Products for WooCommerce';

	public static function isActive(): bool {
		return function_exists( 'Hide_Categories_Products_WC' );
	}

	public function init(): void {
		add_filter( 'dgwt/wcas/search_query/args', [ $this, 'excludeHiddenProducts' ] );

		if ( dgoraAsfwFs()->is__premium_only() ) {
			add_filter( 'dgwt/wcas/indexer/source_query/where', [ $this, 'excludeHiddenProducts__premium_only' ] );
			add_filter( 'dgwt/wcas/search/product_cat/args', [ $this, 'excludeHiddenCategories__premium_only' ] );
		}
	}

	/**
	 * Exclude hidden products (native search)
	 */
	public function excludeHiddenProducts( $args ) {
		$hiddenCategories = $this->getExcludedCategories();
		if ( ! empty( $hiddenCategories ) ) {
			if ( ! isset( $args['tax_query'] ) ) {
				$args['tax_query'] = [];
			}
			$args['tax_query'][] = [
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $hiddenCategories,
				'operator' => 'NOT IN',
			];
		}

		return $args;
	}

	/**
	 * Exclude hidden products
	 */
	public function excludeHiddenProducts__premium_only( $where ) {
		global $wpdb;

		$hiddenCategories = $this->getExcludedCategories();

		// TODO Add support for multilanguage.

		if ( ! empty( $hiddenCategories ) ) {
			$placeholders = array_fill( 0, count( $hiddenCategories ), '%d' );
			$format       = implode( ', ', $placeholders );

			// TODO
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$where .= $wpdb->prepare(
				" AND ( posts.ID NOT IN (
                                                   SELECT object_id
                                                   FROM $wpdb->term_relationships
                                                   WHERE term_taxonomy_id IN ($format)
				                                ))",
				$hiddenCategories
			);
			// phpcs:enable
		}

		return $where;
	}

	/**
	 * Exclude hidden categories
	 */
	public function excludeHiddenCategories__premium_only( $args ) {
		$hiddenCategories = Hide_Categories_Products_WC()->get_hidden_cats();

		if ( ! empty( $hiddenCategories ) ) {
			if ( ! isset( $args['exclude'] ) ) {
				$args['exclude'] = [];
			}
			$args['exclude'] = array_merge( $args['exclude'], $hiddenCategories );
		}

		return $args;
	}

	/**
	 * Support both the legacy typo and the corrected API name.
	 *
	 * @return array
	 */
	private function getExcludedCategories(): array {
		$plugin = Hide_Categories_Products_WC();

		if ( ! is_object( $plugin ) ) {
			return [];
		}

		if ( method_exists( $plugin, 'get_excluded_cats' ) ) {
			$categories = $plugin->get_excluded_cats();

			return is_array( $categories ) ? $categories : [];
		}

		if ( method_exists( $plugin, 'get_exluded_cats' ) ) {
			$categories = $plugin->get_exluded_cats();

			return is_array( $categories ) ? $categories : [];
		}

		return [];
	}
}
