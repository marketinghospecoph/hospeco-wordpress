<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\SearchQuery;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Utils;
use DgoraWcas\Helpers;
use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomPost {

	private $ids       = [];
	private $documents = [];
	private $postType  = '';
	private $phrase    = '';
	private $lang      = '';
	private $settings  = [];

	/**
	 * CustomPost constructor.
	 *
	 * @param $ids
	 */
	public function __construct( $ids, $postType, $phrase ) {
		$this->ids      = $ids;
		$this->postType = $postType;
		$this->phrase   = $phrase;

		$this->setSettings();
	}

	/**
	 * Get language code
	 *
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	public function getResults() {
		$this->setResults();
		$this->orderByWeight();

		return $this->documents;
	}

	/**
	 * Set language
	 *
	 * @param string $lang
	 *
	 * @return void
	 */
	public function setLang( $lang ) {
		if ( Multilingual::isLangCode( $lang ) ) {
			$this->lang = $lang;
		}
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
	 * Set post and page search results
	 *
	 * @return void
	 */
	public function setResults() {
		global $wpdb;

		$placeholders = array_fill( 0, count( $this->ids ), '%d' );
		$format       = implode( ', ', $placeholders );
		$documents    = [];

		$ids = $this->ids;
		foreach ( $this->ids as $id ) {
			$ids[] = $id;
		}

		$tableName = Utils::getTableName( 'readable' );

		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"
                SELECT *
                FROM $tableName
                WHERE post_id IN ($format)
                AND name != ''
                ORDER BY FIELD(post_id, $format)
                ",
			$ids
		);

		$r = $wpdb->get_results( $sql );
		// phpcs:enable

		if ( ! empty( $r ) && is_array( $r ) && ! empty( $r[0] ) && ! empty( $r[0]->post_id ) ) {

			foreach ( $r as $item ) {
				$item->meta = maybe_unserialize( $item->meta );

				$score     = Helpers::calcScore( $this->phrase, $item->name );
				$showImage = $this->getOption( 'show_post_type_' . $this->postType . '_images' ) === 'on';
				$showImage = apply_filters( "dgwt/wcas/search_results/{$this->postType}/show_image", $showImage, $this->phrase, $item );
				$name      = html_entity_decode( $item->name );

				$itemData = [
					'post_id'   => $item->post_id,
					'value'     => $name,
					'name'      => $name,
					'image'     => $showImage && ! empty( $item->image ) ? $item->image : '',
					'url'       => apply_filters( "dgwt/wcas/search_results/{$this->postType}/url", $item->url, $this->phrase, $item ),
					'type'      => 'post',
					'post_type' => $this->postType,
					'score'     => $score,
					'meta'      => $item->meta,
				];

				// Add the post language only if the query is multilingual
				if ( ! empty( $this->lang ) ) {
					$itemData['lang'] = $item->lang;
				}

				$documents[] = $itemData;
			}
		}

		$this->documents = apply_filters( 'dgwt/wcas/tnt/search_results/' . $this->postType, $documents, $this->phrase, $this->getLang() );
	}

	/**
	 * Order found items by weights
	 *
	 * @return void
	 */
	private function orderByWeight() {
		$i = 0;

		foreach ( $this->documents as $post ) {
			$score  = 0;
			$score += Helpers::calcScore( $this->phrase, $post['name'] );

			$this->documents[ $i ]['score'] = apply_filters( "dgwt/wcas/tnt/{$this->postType}/score", (float) $score, $post['post_id'], $post, $this );

			$i ++;
		}

		usort( $this->documents, [ 'DgoraWcas\Helpers', 'cmpSimilarity' ] );
	}
}
