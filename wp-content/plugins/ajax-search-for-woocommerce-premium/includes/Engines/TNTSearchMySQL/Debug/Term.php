<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Debug;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Taxonomy\Indexer as IndexerT;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Term {

	private $termID;
	private $taxonomy;
	public $term;
	private $indexerT;
	private $lang;

	public function __construct( $termID, $taxonomy, $lang ) {

		$termID = absint( $termID );

		$this->term     = new \DgoraWcas\Term( $termID, $taxonomy );
		$this->termID   = $termID;
		$this->taxonomy = $taxonomy;
		$this->indexerT = new IndexerT();
		$this->lang     = $lang;
	}

	/**
	 * Get data that are saved in a readable index
	 *
	 * @return array|null
	 */
	public function getReadableIndexData() {
		global $wpdb;

		$taxTable = $wpdb->prefix . Config::READABLE_TAX_INDEX;

		$lang = '';
		if ( ! empty( $this->lang ) ) {
			$lang .= $wpdb->prepare( ' AND lang = %s ', $this->lang );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			'SELECT *
                                      FROM ' . $taxTable . "
                                      WHERE term_id = %d
                                      AND taxonomy = %s
                                      $lang
                                      ",
			$this->termID,
			$this->taxonomy
		);

		return $wpdb->get_row( $sql, ARRAY_A );
		// phpcs:enable
	}

	/**
	 * Get searchable index terms that belong to product
	 *
	 * @return array
	 */
	public function getSearchableIndexData() {
		$terms = [];
		foreach ( $this->indexerT->getWordList( $this->termID, $this->taxonomy, $this->lang ) as $term ) {
			$terms[] = $term['term'];
		}

		return $terms;
	}
}
