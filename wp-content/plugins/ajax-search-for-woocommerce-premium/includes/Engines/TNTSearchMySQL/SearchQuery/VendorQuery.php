<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\SearchQuery;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VendorQuery {

	private $vendorsTable = '';

	private $settings = [];

	public function init() {
		$this->setTable();
		$this->setSettings();
	}

	/**
	 * Check if can search in vendors
	 *
	 * @return bool
	 */
	public function isEnabled() {
		$enabled = false;
		if ( $this->getOption( 'marketplace_enable_search' ) === 'on' ) {
			$enabled = true;
		}

		return $enabled;
	}

	/**
	 * Set taxonomy table name
	 *
	 * @return void
	 */
	private function setTable() {
		global $wpdb;

		$this->vendorsTable = $wpdb->prefix . Config::VENDORS_INDEX;
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

	public function search( $phrase ) {
		global $wpdb;

		$results = [];

		$term = $wpdb->esc_like( strtolower( $phrase ) );

		$where = ' AND (';

		$searchIn = apply_filters(
			'dgwt/wcas/vendors/search_in',
			[
				'shop_name',
			]
		);

		$whereParts = [];
		if ( is_array( $searchIn ) ) {
			foreach ( $searchIn as $col ) {
				if ( ! in_array( $col, [ 'shop_name', 'shop_city', 'shop_description' ] ) ) {
					continue;
				}
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$whereParts[] = $wpdb->prepare( ' ' . $col . ' LIKE LOWER(%s) ', '%' . $term . '%' );
			}
		}

		if ( empty( $whereParts ) ) {
			$where .= '1=2';
		} else {
			$where .= join( ' OR ', $whereParts );
		}

		$where .= ')';

		$sql = 'SELECT *
                FROM ' . $this->vendorsTable . "
                WHERE 1 = 1
                $where";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$r = $wpdb->get_results( $sql );

		$groups = [];

		if ( ! empty( $r ) && is_array( $r ) ) {
			foreach ( $r as $item ) {

				$score = Helpers::calcScore( $phrase, $item->shop_name );

				$groups['vendors'][] = [
					'vendor_id' => $item->vendor_id,
					'value'     => html_entity_decode( $item->shop_name ),
					'shop_city' => $item->shop_city,
					'desc'      => $item->shop_description,
					'url'       => $item->shop_url,
					'image_src' => $item->shop_image,
					'type'      => 'vendor',
					'score'     => $score,
				];
			}
		}

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $key => $group ) {
				usort( $groups[ $key ], [ 'DgoraWcas\Helpers', 'cmpSimilarity' ] );
				$results = array_merge( $results, $groups[ $key ] );
			}
		}

		return $results;
	}

}
