<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Engines\TNTSearchMySQL\Support\Cache;
use DgoraWcas\Helpers;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage handler for plugin data that shouldn't be stored in the wp_options table because of length.
 *
 * Creates, updates, deletes and retrieves records from the dgwt_wcas_storage table
 * with optional object‑cache integration.
 */
class Storage {

	public const STORAGE_DB_NAME = 'dgwt_wcas_storage';
	public const DB_VERSION      = 1;

	/**
	 * List of keys allowed to be saved / retrieved.
	 *
	 * @return array<string> Whitelisted keys.
	 */
	private static function getWhitelistKeys(): array {
		return [
			'indexer_last_build_logs',
			'indexer_last_build_logs_tmp',
		];
	}

	/**
	 * Checks whether a key is on the allow‑list.
	 *
	 * @param string $key Key to check.
	 *
	 * @return bool True when key is whitelisted.
	 */
	public static function isKeyWhitelisted( string $key ): bool {
		return in_array( $key, self::getWhitelistKeys(), true );
	}

	/**
	 * Returns cache group name.
	 *
	 * @return string Cache group.
	 */
	public static function getCacheGroup(): string {
		return 'storage';
	}

	/**
	 * Returns the fully‑qualified table name (with `$wpdb->prefix`).
	 *
	 * @return string Fully‑qualified table name.
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public static function getTableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::STORAGE_DB_NAME;
	}

	/**
	 * Adds table references to the global `$wpdb` object.
	 *
	 * @return void
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public static function registerTables(): void {
		global $wpdb;

		$wpdb->dgwt_wcas_storage = self::getTableName();
		$wpdb->tables[]          = self::STORAGE_DB_NAME;
	}

	/**
	 * Ensures the custom table exists and is up‑to‑date.
	 *
	 * If the stored DB version differs from the expected version, the table
	 * will be (re)created via {@see self::create()}.
	 *
	 * @return void
	 */
	public static function maybeCreate(): void {
		if ( get_option( 'dgwt_wcas_storage_db_version' ) !== self::DB_VERSION ) {
			update_option( 'dgwt_wcas_storage_db_version', self::DB_VERSION );
			self::create();
		}
	}

	/**
	 * DB structure for the storage table.
	 *
	 * @return string SQL.
	 */
	public static function tableStruct(): string {
		$tableName = self::getTableName();
		$collate   = Helpers::getCollate( $tableName );

		return "CREATE TABLE $tableName (
                name            VARCHAR(255)   NOT NULL DEFAULT '',
                content         LONGTEXT       NOT NULL,
                PRIMARY KEY (name)
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC $collate;";
	}

	/**
	 * Creates the database table.
	 * Includes WordPress's upgrade functions (dbDelta) for schema changes.
	 *
	 * @return void
	 */
	public static function create(): void {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( self::tableStruct() );
	}

	/**
	 * Drops the Storage database.
	 *
	 * @return void
	 */
	public static function drop(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				'DROP TABLE IF EXISTS %i',
				self::getTableName()
			)
		);
	}

	/**
	 * Saves a record in the storage table.
	 *
	 * Inserts a row identified by the given key.
	 * Returns `true` when the operation was successful, otherwise `false`.
	 *
	 * @param string $key Unique content identifier.
	 * @param string $content Content (JSON‐encoded or plain string).
	 *
	 * @return bool           Whether the row was written successfully.
	 */
	public static function save( string $key, string $content ): bool {
		global $wpdb;

		// Break early if the key is not on the allow-list.
		if ( ! self::isKeyWhitelisted( $key ) ) {
			return false;
		}

		$table = self::getTableName();
		if ( ! Helpers::isTableExists( $table ) ) {
			return false;
		}

		$dataToInsert = [
			'name'    => $key,
			'content' => $content,
		];

		$format = [
			'%s', // name.
			'%s', // content.
		];

		$insertedRows = $wpdb->insert( $table, $dataToInsert, $format );

		return is_int( $insertedRows ) && $insertedRows > 0;
	}

	/**
	 * Updates an existing Storage entry by deleting the old record and saving the new one.
	 *
	 * @param string $key Unique content identifier.
	 * @param string $content Content (JSON‐encoded or plain string).
	 *
	 * @return bool         True on success, false on failure.
	 */
	public static function update( string $key, string $content ): bool {
		// Break early if the key is not on the allow-list.
		if ( ! self::isKeyWhitelisted( $key ) ) {
			return false;
		}

		self::delete( $key );

		return self::save( $key, $content );
	}

	/**
	 * Deletes a Storage row by name.
	 *
	 * @param string $key Entry key to delete.
	 *
	 * @return bool   True when at least one row is removed, false on failure or when
	 *                no matching row exists.
	 */
	public static function delete( string $key ): bool {
		global $wpdb;

		// Break early if the key is not on the allow-list.
		if ( ! self::isKeyWhitelisted( $key ) ) {
			return false;
		}

		$table = self::getTableName();

		if ( ! Helpers::isTableExists( $table ) ) {
			return false;
		}

		$result = $wpdb->delete(
			$table,
			[
				'name' => $key,
			],
			[ '%s' ]
		);

		return is_int( $result ) && $result > 0;
	}

	/**
	 * Retrieves a cached or database-stored string.
	 *
	 * @param string $key Name of the content.
	 * @param bool $skipCache Whether to skip the cache lookup.
	 *
	 * @return string The stored content, or an empty string on failure.
	 */
	public static function get( string $key, bool $skipCache = false ): string {
		global $wpdb;

		// Break early if the key is not on the allow-list.
		if ( ! self::isKeyWhitelisted( $key ) ) {
			return '';
		}

		$table = self::getTableName();

		if ( ! Helpers::isTableExists( $table ) ) {
			return '';
		}

		$cached = $skipCache ? false : Cache::get( $key, self::getCacheGroup() );

		if ( $cached !== false ) {
			return $cached;
		}

		$where = $wpdb->prepare( ' AND name = %s', $key );

		$sql = "
		SELECT SQL_NO_CACHE content
		FROM {$table}
		WHERE 1 = 1
			{$where}";

		$result = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$content = '';
		if ( is_string( $result ) && $result !== '' ) {
			$content = $result;
			Cache::set( $key, $content, self::getCacheGroup() );
		}

		return $content;
	}
}
