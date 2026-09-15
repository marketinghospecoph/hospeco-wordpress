<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable;

use DgoraWcas\Helpers;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Utils;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cache {
	/** @var bool */
	private $enabled;

	/** @var string */
	private $lang = '';

	/** @var string Post type or taxonomy */
	private $type = '';

	/**
	 * Cache constructor
	 */
	public function __construct() {
		$this->setStatus();
	}

	/**
	 * Set cache status
	 */
	private function setStatus() {
		$this->enabled = Helpers::doesDbSupportJson__premium_only() && Helpers::isCacheEnabled__premium_only();
	}

	/**
	 * @param $lang
	 *
	 * @return void
	 */
	public function setLang( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	public function setType( $type ) {
		$this->type = $type;
	}

	/**
	 * Get cache status
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return (bool) $this->enabled;
	}

	/**
	 * Set value into cache
	 *
	 * @param string $key
	 * @param string $value JSON
	 *
	 * @return bool
	 */
	public function set( $key, $value ) {
		global $wpdb;

		if ( ! $this->enabled ) {
			return true;
		}

		$cacheTable = Utils::getTableName( 'searchable_cache', $this->lang );

		$type  = empty( $this->type ) ? 'product' : $this->type;
		$query = "INSERT INTO $cacheTable (`cache_key`, `cache_value`, `cache_type`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `cache_key` = VALUES(`cache_key`), `cache_value` = VALUES(`cache_value`), `cache_type` = VALUES(`cache_type`)";
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$res = $wpdb->query( $wpdb->prepare( $query, $key, $value, $type ) );

		return $res !== false;
	}

	/**
	 * Get value from cache
	 *
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public function get( $key ) {
		global $wpdb;

		if ( ! $this->enabled ) {
			return false;
		}

		$cacheTable = Utils::getTableName( 'searchable_cache', $this->lang );
		$type       = empty( $this->type ) ? 'product' : $this->type;

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $cacheTable WHERE cache_key = %s AND cache_type = %s LIMIT 1", $key, $type ), ARRAY_A );

		if ( ! empty( $result['cache_value'] ) ) {
			$value = json_decode( $result['cache_value'] );

			return json_last_error() === JSON_ERROR_NONE ? $value : false;
		}

		return false;
	}

	/**
	 * Delete cache entry by part of it's value
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function deleteByValue( $value ) {
		global $wpdb;

		if ( ! $this->enabled ) {
			return true;
		}

		$cacheTable = Utils::getTableName( 'searchable_cache', $this->lang );
		$type       = empty( $this->type ) ? 'product' : $this->type;

		if ( ! Helpers::isTableExists( $cacheTable ) ) {
			return false;
		}

		$query = "DELETE FROM $cacheTable WHERE JSON_CONTAINS(`cache_value`, %s) = 1 AND cache_type = %s";
		$res   = $wpdb->query( $wpdb->prepare( $query, $value, $type ) );

		return $res !== false;
	}
}
