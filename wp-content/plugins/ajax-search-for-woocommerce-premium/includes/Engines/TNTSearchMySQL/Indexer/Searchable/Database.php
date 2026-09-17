<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Utils;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDBException;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDB;
use DgoraWcas\Engines\TNTSearchMySQL\Support\Cache;
use DgoraWcas\Helpers;
use DgoraWcas\Multilingual;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	/**
	 * Add tables names to the $wpdb object
	 *
	 * @return void
	 */
	public static function registerTables() {
		global $wpdb;

		$wpdb->dgwt_wcas_si_wordlist = $wpdb->prefix . Config::SEARCHABLE_INDEX_WORDLIST;
		if ( Helpers::isTableExists( $wpdb->dgwt_wcas_si_wordlist ) ) {
			$wpdb->tables[] = Config::SEARCHABLE_INDEX_WORDLIST;
		}

		$wpdb->dgwt_wcas_si_doclist = $wpdb->prefix . Config::SEARCHABLE_INDEX_DOCLIST;
		if ( Helpers::isTableExists( $wpdb->dgwt_wcas_si_doclist ) ) {
			$wpdb->tables[] = Config::SEARCHABLE_INDEX_DOCLIST;
		}

		$wpdb->dgwt_wcas_si_cache = $wpdb->prefix . Config::SEARCHABLE_INDEX_CACHE;
		if ( Helpers::isTableExists( $wpdb->dgwt_wcas_si_cache ) ) {
			$wpdb->tables[] = Config::SEARCHABLE_INDEX_CACHE;
		}
	}

	/**
	 * Install DB tables
	 *
	 * @return void
	 * @throws WPDBException
	 */
	private static function install( $indexRoleSuffix ) {
		$langs = Multilingual::isMultilingual() ? Multilingual::getLanguages() : [ '' ];

		foreach ( $langs as $lang ) {
			WPDB::get_instance()->query( self::wordListTableStruct( $lang, $indexRoleSuffix ) );
			WPDB::get_instance()->query( self::docListTableStruct( $lang, $indexRoleSuffix ) );

			if ( Helpers::isCacheEnabled__premium_only() && Helpers::doesDbSupportJson__premium_only() ) {
				WPDB::get_instance()->query( self::cacheTableStruct( $lang, $indexRoleSuffix ) );
			}
		}

		// MySQL Index
		foreach ( $langs as $lang ) {
			$doclistTableName = Utils::getTableName( 'searchable_doclist', $lang ) . Config::getIndexRoleSuffix();
			WPDB::get_instance()->query( "CREATE INDEX main_term_id_index ON $doclistTableName(term_id);" );
			WPDB::get_instance()->query( "CREATE INDEX main_doc_id_index ON $doclistTableName(doc_id);" );
			if ( defined( 'DGWT_WCAS_INDEXER_EXTENDED_DOCLIST_INDEX' ) && DGWT_WCAS_INDEXER_EXTENDED_DOCLIST_INDEX ) {
				WPDB::get_instance()->query( "CREATE INDEX main_doc_id_term_id_index ON $doclistTableName(doc_id,term_id);" );
			}

			$wordlistTableName = Utils::getTableName( 'searchable_wordlist', $lang ) . Config::getIndexRoleSuffix();
			WPDB::get_instance()->query( "CREATE INDEX main_term_type_index ON $wordlistTableName(term,type);" );
			WPDB::get_instance()->query( "CREATE INDEX main_term_type_num_hits_index ON $wordlistTableName(type,term,num_hits);" );
		}
	}

	/**
	 * Get real tables belong to the searchable index
	 *
	 * @return array
	 */
	public static function getSearchableIndexTables( $indexRoleSuffix = '' ) {
		$searchableTables = [];

		$tables = Utils::getAllPluginTables();

		if ( ! empty( $tables ) ) {
			foreach ( $tables as $table ) {
				if (
					(
						strpos( $table, 'dgwt_wcas_invindex_doclist' ) !== false
						|| strpos( $table, 'dgwt_wcas_invindex_wordlist' ) !== false
						|| strpos( $table, 'dgwt_wcas_invindex_cache' ) !== false
					)
					&& Helpers::endsWith( $table, $indexRoleSuffix )
				) {
					$searchableTables[] = $table;
				}
			}
		}

		return $searchableTables;
	}

	/**
	 *  DB structure for Wordlist table
	 *
	 * @param string $lang Language.
	 * @param string $indexRoleSuffix Index role suffix.
	 *
	 * @return string
	 */
	public static function wordListTableStruct( $lang, $indexRoleSuffix = '' ) {
		$suffix         = trim( Utils::getTableSuffix( $lang ), '_' );
		$collateContext = empty( $suffix ) ? '' : '/' . $suffix;
		$tableName      = Utils::getTableName( 'searchable_wordlist', $lang ) . $indexRoleSuffix;
		$collate        = Utils::getCollate( 'searchable/wordlist' . $collateContext );

		$sql = "CREATE TABLE $tableName (
				id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
				term         VARCHAR(127) NOT NULL,
				type         VARCHAR(55) NOT NULL,
				num_hits     MEDIUMINT NOT NULL DEFAULT 1,
				/* num_docs     MEDIUMINT NOT NULL DEFAULT 1, */
				PRIMARY KEY  (id)
			    ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC $collate;";

		return $sql;
	}

	/**
	 *  DB structure for Doclist table
	 *
	 * @param string $lang Language.
	 * @param string $indexRoleSuffix Index role suffix.
	 *
	 * @return string
	 */
	public static function docListTableStruct( $lang, $indexRoleSuffix = '' ) {
		$tableName = Utils::getTableName( 'searchable_doclist', $lang ) . $indexRoleSuffix;

		$sql = "CREATE TABLE $tableName (
				id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                term_id      INT UNSIGNED NOT NULL,
				doc_id       BIGINT NOT NULL,
				/* hit_count    MEDIUMINT NOT NULL DEFAULT 1, */
				PRIMARY KEY  (id)
			    ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE ascii_bin";

		return $sql;
	}

	/**
	 *  DB structure for Cache table
	 *
	 * @param string $lang Language.
	 * @param string $indexRoleSuffix Index role suffix.
	 *
	 * @return string
	 */
	public static function cacheTableStruct( $lang, $indexRoleSuffix = '' ) {
		$tableName = Utils::getTableName( 'searchable_cache', $lang ) . $indexRoleSuffix;

		$collate = Utils::getCollate( 'searchable/cache' );

		$sql = "CREATE TABLE $tableName (
				cache_id     MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
                cache_key    VARCHAR(255) NOT NULL,
				cache_value  JSON NOT NULL,
				cache_type   VARCHAR(55) NOT NULL,
				PRIMARY KEY  (cache_id),
				UNIQUE KEY cache_key_type (cache_key,cache_type)
			    ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC $collate;";

		return $sql;
	}

	/**
	 * Create database structure from the scratch
	 *
	 * @return void
	 * @throws WPDBException
	 */
	public static function create( $indexRoleSuffix ) {
		self::install( $indexRoleSuffix );
	}

	/**
	 * Remove searchable index
	 *
	 * @return void
	 */
	public static function remove( $indexRoleSuffix = '' ) {
		global $wpdb;

		$wpdb->hide_errors();

		foreach ( self::getSearchableIndexTables( $indexRoleSuffix ) as $table ) {
			//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		Cache::delete( 'table_exists', 'database' );
	}
}
