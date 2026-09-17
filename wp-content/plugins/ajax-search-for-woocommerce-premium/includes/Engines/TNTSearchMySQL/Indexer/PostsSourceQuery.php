<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostsSourceQuery extends SourceQuery {

	protected $type = 'post_source_query';

	/**
	 * Build SQL query which select posts with all necessary fields
	 *
	 * @return void
	 */
	protected function buildQuery() {
		global $wpdb;

		// -------------------------------
		// SELECT
		// -------------------------------
		$select = '';

		// Select product ID
		$select .= $this->selectID();

		// Select post title
		$select .= $this->selectTitle();

		// Select post content
		$selectDescription = false;
		if ( in_array( $this->postType, [ 'post', 'page' ] ) ) {
			$selectDescription = apply_filters( 'dgwt/wcas/tnt/post_source_query/description', $selectDescription );
			$selectDescription = apply_filters( "dgwt/wcas/indexer/post_source_query/{$this->postType}/description", $selectDescription );
		} else {
			$selectDescription = apply_filters( "dgwt/wcas/indexer/post_source_query/{$this->postType}/description", $selectDescription );
		}
		if ( $selectDescription ) {
			$select .= $this->selectDescription();
		}

		// Select post type
		$select .= $this->selectPostType();

		// Select post language
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

		// Set range of products set
		if ( ! empty( $this->args['package'] ) ) {
			$where .= $this->whereNarrowDownToTheSet( $this->args['package'] );
		}

		// Narrow all posts to only selected post type
		$where .= $this->wherePostTypes( [ $this->getPostType() ] );

		// Get only published posts
		$where .= $this->wherePublished();

		// Excluded ID-s
		$where .= $this->whereExcludeIDsFromSearch();

		$select = apply_filters( 'dgwt/wcas/tnt/post_source_query/select', $select, $this, $onlyIDs ); // deprecated
		$where  = apply_filters( 'dgwt/wcas/tnt/post_source_query/where', $where, $this, $onlyIDs ); // deprecated

		$select = apply_filters( 'dgwt/wcas/indexer/post_source_query/select', $select, $this, $onlyIDs );
		$join   = apply_filters( 'dgwt/wcas/indexer/post_source_query/join', '', $this, $onlyIDs );
		$where  = apply_filters( 'dgwt/wcas/indexer/post_source_query/where', $where, $this, $onlyIDs );

		$sql = "SELECT $select
                FROM $wpdb->posts posts
                $join
                WHERE  1=1
                $where
               ";

		$this->query = apply_filters( 'dgwt/wcas/indexer/post_source_query/query', $sql, $this, $onlyIDs );
	}

	/**
	 * Part of the SQL where we could exclude posts with specific IDs
	 *
	 * @return string part of the SQL WHERE
	 */
	public function whereExcludeIDsFromSearch() {
		global $wpdb;

		$where       = '';
		$excludedIds = [];

		if ( $this->postType === 'page' ) {
			$wooPages = [
				'woocommerce_shop_page_id',
				'woocommerce_cart_page_id',
				'woocommerce_checkout_page_id',
				'woocommerce_myaccount_page_id',
				'woocommerce_edit_address_page_id',
				'woocommerce_view_order_page_id',
				'woocommerce_change_password_page_id',
				'woocommerce_logout_page_id',
			];
			foreach ( $wooPages as $page ) {
				$pageID = get_option( $page );
				if ( ! empty( $pageID ) && intval( $pageID ) > 0 ) {
					$excludedIds[] = intval( $pageID );
				}
			}
		}

		$excludedIds = apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/excluded_ids', $excludedIds, $this->postType );

		if ( ! empty( $excludedIds ) ) {
			$excludedIds  = array_map( 'intval', $excludedIds );
			$placeholders = array_fill( 0, count( $excludedIds ), '%d' );
			$format       = implode( ', ', $placeholders );
			//phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$where = $wpdb->prepare( " AND posts.ID NOT IN ($format)", $excludedIds );
		}

		return apply_filters( 'dgwt/wcas/indexer/' . $this->type . '/where/exclude_ids_from_search', $where, $this->postType );
	}
}
