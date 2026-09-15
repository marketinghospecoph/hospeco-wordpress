<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Helpers;
use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VariationsSourceQuery extends SourceQuery {

	protected $type = 'variation_source_query';

	public function prepare_variation_source_query() {
		$variationSupportModes = Helpers::getVariationSupportModes__premium_only();
		if ( in_array( 'as_single_product', $variationSupportModes ) && apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/include_attributes_in_title', true ) ) {
			$this->add_attributes_to_variation_titles();
		}
	}

	/**
	 * Add attributes to variation titles
	 */
	private function add_attributes_to_variation_titles() {
		add_filter(
			'dgwt/wcas/tnt/variation_source_query/data',
			function ( $data, $sourceQuery, $onlyIDs ) {
				if ( $onlyIDs ) {
					return $data;
				}

				foreach ( $data as $index => $row ) {
					$product = wc_get_product( $row['ID'] );
					if ( is_a( $product, 'WC_Product_Variation' ) ) {
						$variationAttrs = (string) wc_get_formatted_variation( $product, true, false, false );
						if ( ! empty( $variationAttrs ) ) {
							$data[ $index ]['name'] .= ', ' . $variationAttrs;
						}
					}
				}

				return $data;
			},
			10,
			3
		);
	}

	/**
	 * Build SQL query which select variations with all necessary fields
	 *
	 * @return void
	 */
	protected function buildQuery() {
		global $wpdb;

		// -------------------------------
		// SELECT
		// -------------------------------
		$select = '';

		// Select product ID.
		$select .= $this->selectID();

		// Select post title.
		$select .= $this->selectTitle();

		// Select description. We treat the variant description as "Short description".
		$variationSupportModes = Helpers::getVariationSupportModes__premium_only();
		if (
			is_array( $variationSupportModes ) &&
			in_array( 'as_single_product', $variationSupportModes ) &&
			DGWT_WCAS()->settings->getOption( 'search_in_product_excerpt' ) === 'on'
		) {
			$select .= $this->selectDescription();
		}

		// Select the SKU.
		if (
			is_array( $variationSupportModes ) &&
			in_array( 'search_in_sku', $variationSupportModes ) ) {
			$select .= $this->selectSku();
		}

		// Select the Global Unique ID.
		if (
			is_array( $variationSupportModes ) &&
			in_array( 'search_in_global_unique_id', $variationSupportModes ) ) {
			$select .= $this->selectGlobalUniqueId();
		}

		// Select post type.
		$select .= $this->selectPostType();

		// Select language.
		if ( Multilingual::isMultilingual() ) {
			$select .= $this->selectLang();
		}

		// Select only IDs
		$onlyIDs = $this->onlyIDs();
		if ( $onlyIDs ) {
			$select = 'posts.ID';
		}

		// -------------------------------
		// WHERE
		// -------------------------------
		$where = '';

		if ( DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on' ) {
			$where .= $this->whereExcludeOutOfStock();
		};

		// Set range of products set.
		if ( ! empty( $this->args['package'] ) ) {
			$where .= $this->whereNarrowDownToTheSet( $this->args['package'] );
		}

		if ( ! empty( $this->args['parentIds'] ) ) {
			$where .= $this->whereNarrowDownToParents( $this->args['parentIds'] );
		}

		// Narrow all posts to only selected product type.
		$where .= $this->wherePostTypes( [ $this->getPostType() ] );

		$where .= $this->whereHasParent();

		// Get only published products.
		$where .= $this->wherePublished();

		$select = apply_filters( "dgwt/wcas/indexer/{$this->type}/select", $select, $this, $onlyIDs );
		$join   = apply_filters( "dgwt/wcas/indexer/{$this->type}/join", '', $this, $onlyIDs );
		$where  = apply_filters( "dgwt/wcas/indexer/{$this->type}/where", $where, $this, $onlyIDs );

		$sql = "SELECT $select
                FROM $wpdb->posts posts
                $join
                WHERE  1=1
                $where
               ";

		$this->query = apply_filters( "dgwt/wcas/indexer/{$this->type}/query", $sql, $this, $onlyIDs );
	}

	/**
	 * Part of the SQL where we could narrow down to the set of products with specific parent IDs
	 *
	 * @param array $parentIds
	 *
	 * @return string part of the SQL WHERE
	 */
	protected function whereNarrowDownToParents( $parentIds = [] ) {
		global $wpdb;

		$placeholders = array_fill( 0, count( $parentIds ), '%d' );
		$format       = implode( ', ', $placeholders );

		//phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$where = $wpdb->prepare( " AND posts.post_parent IN ($format)", $parentIds );

		return apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/where/parents_set', $where );
	}

	/**
	 * Part of the SQL we could narrow down to the set of products that have parent
	 *
	 * @return string part of the SQL WHERE
	 */
	protected function whereHasParent() {

		$where = ' AND posts.post_parent > 0';

		return apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/where/has_parent', $where );
	}

	/**
	 * Part of the SQL select statement which retrieves the variation description
	 *
	 * @param string $groupName the name of the group of data
	 *
	 * @return string part of the SQL SELECT
	 */
	public function selectDescription( string $groupName = 'desc' ): string {
		global $wpdb;

		$select = $wpdb->prepare( ", (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = posts.ID AND meta_key='_variation_description' LIMIT 1) AS %s", $groupName );

		return apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/select/description', $select, $groupName );
	}

	/**
	 * Part of the SQL where statement which doesn't index variations with the stock status "outofstock"
	 *
	 * @return string part of the SQL WHERE
	 */
	public function whereExcludeOutOfStock() {
		global $wpdb;

		$where = " AND (SELECT stock_status
            FROM $wpdb->wc_product_meta_lookup
            WHERE product_id = posts.id
            LIMIT 1) != 'outofstock'";

		return apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/where/exclude_outofstock', $where );
	}
}
