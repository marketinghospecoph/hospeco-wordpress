<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\SearchQuery;

use DgoraWcas\Engines\TNTSearchMySQL\Config;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProductVariationQuery {
	/**
	 * Variation Db table name
	 *
	 * @var string
	 */
	private $tableName = '';

	/**
	 * Field to search
	 *
	 * @var string
	 */
	private $field = '';

	/**
	 * Search phrase
	 *
	 * @var string
	 */
	private $phrase = '';

	/**
	 * All relevant products IDs
	 *
	 * @var array
	 */
	private $ids = [];

	/**
	 * Raw response from DB
	 *
	 * @var
	 */
	private $result;

	/**
	 * Lang
	 *
	 * @var
	 */
	private $lang = '';

	/**
	 * ProductVariationQuery constructor.
	 *
	 * @param string $phrase
	 * @param array $ids
	 * @param string $field
	 * @param string $lang
	 */
	public function __construct( $phrase, $ids, $field = 'sku', $lang = '' ) {

		if ( ! empty( $lang ) ) {
			$this->lang = $lang;
		}
		if ( ! empty( $field ) && in_array( $field, [ 'sku', 'global_unique_id' ] ) ) {
			$this->field = $field;
		}

		$this->setTable();
		$this->setPhrase( $phrase );
		$this->setIds( $ids );
		$this->search();
	}

	/**
	 * Set index table name
	 *
	 * @return void
	 */
	private function setTable() {
		global $wpdb;

		$this->tableName = $wpdb->prefix . Config::READABLE_INDEX;
	}

	private function setPhrase( $phrase ) {
		$this->phrase = $phrase;
	}

	/**
	 * Set product IDs
	 *
	 * @param $ids
	 */
	private function setIds( $ids ) {
		$productIds = [];
		if ( is_array( $ids ) ) {
			foreach ( $ids as $id ) {
				$productIds[] = absint( $id );
			}
		}
		$this->ids = $productIds;
	}

	/**
	 * Search variation
	 *
	 * @return void
	 */
	private function search() {
		global $wpdb;

		if ( ! [ $this->ids ] || empty( $this->ids ) || empty( $this->phrase ) ) {
			return;
		}

		$placeholders = array_fill( 0, count( $this->ids ), '%d' );
		$format       = implode( ', ', $placeholders );

		$pieces   = $this->ids;
		$pieces[] = $this->phrase;

		if ( ! empty( $this->lang ) ) {
			$pieces[] = $this->lang;
		}

		if ( $this->field === 'sku' ) {
			$rawSql = "SELECT *
                   FROM $this->tableName
                   WHERE post_or_parent_id IN ($format)
                   AND type = 'product_variation'
                   AND sku = %s";
		} elseif ( $this->field === 'global_unique_id' ) {
			$rawSql = "SELECT *
                   FROM $this->tableName
                   WHERE post_or_parent_id IN ($format)
                   AND type = 'product_variation'
                   AND global_unique_id = %s";
		} else {
			return;
		}

		if ( ! empty( $this->lang ) ) {
			$rawSql .= ' AND lang = %s';
		}

		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare( $rawSql, $pieces );
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$data = $wpdb->get_results( $sql );

		if ( ! empty( $data ) && is_array( $data ) && ! empty( $data[0] ) ) {
			$this->result = $data[0];
		}
	}

	/**
	 * Check if SKU exact match exist
	 *
	 * @return bool
	 */
	public function hasResults() {
		return ! empty( $this->result );
	}

	/**
	 * Get suggestion body
	 *
	 * @return array
	 */
	public function getSuggestionBody() {
		$body = [];

		if ( $this->hasResults() ) {
			$body = [
				'post_id'      => $this->result->post_or_parent_id,
				'variation_id' => $this->result->post_id,
				'value'        => $this->result->name,
				'url'          => apply_filters( 'dgwt/wcas/search_results/product_variation/url', $this->result->url, $this->phrase, $this->result ),
				'thumb_html'   => '<img src="' . $this->result->image . '">',
				'price'        => $this->result->html_price,
				'desc'         => $this->result->description,
				'sku'          => $this->result->sku,
				'on_sale'      => false,
				'featured'     => false,
				'type'         => 'product_variation',
			];
		}

		return $body;
	}
}
