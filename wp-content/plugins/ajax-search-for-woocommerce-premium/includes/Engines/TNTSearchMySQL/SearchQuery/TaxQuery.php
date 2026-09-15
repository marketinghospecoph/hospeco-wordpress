<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\SearchQuery;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaxQuery {

	/**
	 * @var array
	 */
	private $taxonomies = [];
	private $settings   = [];
	private $taxTable   = '';
	private $lang       = '';

	public function __construct() {
		$this->setTable();
		$this->setSettings();
		$this->setTaxonomies();
	}

	/**
	 * Check if can search matching taxonomies
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return ! empty( $this->taxonomies );
	}

	/**
	 * Set taxonomy table name
	 *
	 * @return void
	 */
	private function setTable() {
		global $wpdb;

		$this->taxTable = $wpdb->prefix . Config::READABLE_TAX_INDEX;
	}

	/**
	 * Load settings
	 *
	 * @return void
	 */
	protected function setSettings() {
		$this->settings = Settings::getSettings();
	}

	/**
	 * Get option
	 *
	 * @param $option
	 *
	 * @return string
	 */
	private function getOption( $option ) {
		$value = '';
		if ( array_key_exists( $option, $this->settings ) ) {
			$value = $this->settings[ $option ];
		}

		return $value;
	}

	/**
	 * Set allowed product taxonomies
	 *
	 * @return void
	 */
	private function setTaxonomies() {
		global $wpdb;

		if ( ! Helpers::isTableExists( $this->taxTable ) ) {
			return;
		}
		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$taxonomies = $wpdb->get_col( "SELECT DISTINCT(taxonomy) FROM $this->taxTable" );

		$this->taxonomies = apply_filters( 'dgwt/wcas/tnt/search_taxonomies', $taxonomies );
	}

	/**
	 * Get allowed product taxonomies
	 *
	 * @return array
	 */
	public function getActiveTaxonomies() {
		return $this->taxonomies;
	}

	/**
	 * Set language
	 *
	 * @param $lang
	 *
	 * @return void
	 */
	public function setLang( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * @param array $termIds
	 * @param string $phrase
	 *
	 * @return array
	 */
	public function getResults( $termIds, $phrase ) {
		global $wpdb;

		$results = [];

		$placeholders = array_fill( 0, count( $termIds ), '%d' );
		$format       = implode( ', ', $placeholders );

		$where = " AND term_id IN ($format) ";

		if ( ! empty( $this->lang ) ) {
			$where .= $wpdb->prepare( ' AND lang = %s ', $this->lang );
		}

		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			'SELECT *
                                      FROM ' . $this->taxTable . "
                                      WHERE 1 = 1
                                      $where
                                      ORDER BY taxonomy, total_products DESC",
			$termIds
		);

		$r = $wpdb->get_results( $sql );
		//phpcs:enable

		$groups = [];

		if ( ! empty( $r ) && is_array( $r ) ) {
			foreach ( $r as $item ) {

				$score     = Helpers::calcScore( $phrase, $item->term_name );
				$showImage = $this->getOption( 'show_product_tax_' . $item->taxonomy . '_images' ) === 'on';

				$data = [
					'term_id'     => $item->term_id,
					'taxonomy'    => $item->taxonomy,
					'value'       => html_entity_decode( $item->term_name ),
					'url'         => apply_filters( 'dgwt/wcas/search_results/term/url', $item->term_link, $phrase, $item ),
					'image_src'   => $showImage && ! empty( $item->image ) ? $item->image : '',
					'breadcrumbs' => $item->breadcrumbs,
					'count'       => $item->total_products,
					'type'        => 'taxonomy',
					'score'       => apply_filters( 'dgwt/wcas/search_results/term/score', $score, $phrase, $item ),
					'meta'        => isset( $item->meta ) ? maybe_unserialize( $item->meta ) : [],
				];

				$groups[ $item->taxonomy ][] = apply_filters( 'dgwt/wcas/search_results/term', $data, $phrase, $this->lang );
			}
		}

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $key => $group ) {
				usort( $groups[ $key ], [ 'DgoraWcas\Helpers', 'cmpSimilarity' ] );
				$results = array_merge( $results, $groups[ $key ] );
			}
		}

		return apply_filters( 'dgwt/wcas/tnt/search_results/taxonomies', $results, $phrase, $this->lang );
	}
}
