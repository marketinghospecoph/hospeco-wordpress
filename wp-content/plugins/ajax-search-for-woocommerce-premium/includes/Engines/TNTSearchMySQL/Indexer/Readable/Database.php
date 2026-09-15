<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Readable;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Utils;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDBException;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDB;
use DgoraWcas\Engines\TNTSearchMySQL\Support\Cache;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {
	/**
	 * Add table names to the $wpdb object
	 *
	 * @return void
	 */
	public static function registerTables() {
		global $wpdb;

		$wpdb->dgwt_wcas_index = $wpdb->prefix . Config::READABLE_INDEX;
		if ( Helpers::isTableExists( $wpdb->dgwt_wcas_index ) ) {
			$wpdb->tables[] = Config::READABLE_INDEX;
		}
	}

	/**
	 * Install DB table
	 *
	 * @return void
	 * @throws WPDBException
	 */
	private static function install( $indexRoleSuffix = '' ) {
		global $wpdb;

		$collate = Utils::getCollate( 'readable/main' );

		/**
		 * We use 'id' column because 'post_id' is not always unique.
		 * This happens, for example, with the TranslatePress plugin, when records of different
		 * languages have the same 'post_id'.
		 */
		$tableName = $wpdb->dgwt_wcas_index . $indexRoleSuffix;
		$table     = "CREATE TABLE $tableName (
			id         		  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id           BIGINT(20) UNSIGNED NOT NULL,
			post_or_parent_id BIGINT(20) UNSIGNED NOT NULL,
			created_date      DATETIME NOT NULL DEFAULT '1970-01-01 00:00:01',
			name              TEXT NOT NULL,
			description       MEDIUMTEXT NOT NULL,
			type              VARCHAR(55) NOT NULL,
			sku               TEXT NOT NULL,
			global_unique_id  TEXT NOT NULL,
			sku_variations    TEXT NOT NULL,
			attributes        LONGTEXT NOT NULL,
			meta              LONGTEXT NOT NULL,
			image             TEXT NOT NULL,
			url               TEXT NOT NULL,
			html_price        TEXT NOT NULL,
			price             DECIMAL(10,2) NOT NULL,
			average_rating    DECIMAL(3,2) NOT NULL,
            review_count      SMALLINT(5) NOT NULL DEFAULT '0',
            total_sales       SMALLINT(5) NOT NULL DEFAULT '0',
            lang              VARCHAR(20) NOT NULL,
			PRIMARY KEY       (id)
		) ENGINE=InnoDB ROW_FORMAT=DYNAMIC $collate;";

		WPDB::get_instance()->query( $table );

		WPDB::get_instance()->query( "CREATE INDEX main_post_id ON $tableName(post_id);" );
		WPDB::get_instance()->query( "CREATE INDEX main_post_or_parent_id ON $tableName(post_or_parent_id);" );
		WPDB::get_instance()->query( "CREATE INDEX main_sku ON $tableName(sku(150));" );
		WPDB::get_instance()->query( "CREATE INDEX main_global_unique_id ON $tableName(global_unique_id(150));" );
		WPDB::get_instance()->query( "CREATE INDEX main_lang ON $tableName(lang);" );
	}

	/**
	 * Create database structure from the scratch
	 *
	 * @return void
	 * @throws WPDBException
	 */
	public static function create( $indexRoleSuffix = '' ) {
		self::install( $indexRoleSuffix );
	}

	/**
	 * Remove DB table
	 *
	 * @return void
	 */
	public static function remove( $indexRoleSuffix ) {
		global $wpdb;

		$wpdb->hide_errors();

		$tableName = $wpdb->dgwt_wcas_index . $indexRoleSuffix;
		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $tableName" );

		Cache::delete( 'table_exists', 'database' );
	}

	/**
	 * Remove duplicates from the table
	 *
	 * @param string $indexRoleSuffix
	 *
	 * return int
	 */
	public static function removeDuplicates( $indexRoleSuffix = '' ) {
		global $wpdb;

		$tableName = $wpdb->dgwt_wcas_index . $indexRoleSuffix;
		//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "DELETE t1 FROM $tableName t1, $tableName t2 WHERE t1.id < t2.id AND t1.post_id = t2.post_id AND t1.lang = t2.lang" );

		return intval( $result );
	}
}
