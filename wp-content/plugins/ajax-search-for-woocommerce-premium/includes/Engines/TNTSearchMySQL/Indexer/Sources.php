<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

class Sources {

	/**
	 * Insert or update source record
	 *
	 * @param $data
	 *
	 * @return int source_id
	 */
	public static function saveSource( $data ) {
		global $wpdb;

		$table = self::getTableName();

		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"
              SELECT *
              FROM $table
              WHERE name = %s
              AND lang = %s",
			$data['name'],
			$data['lang']
		);

		$source = $wpdb->get_row( $sql );
		//phpcs:enable

		if ( ! empty( $source ) ) {

			$sourceID = (int) $source->source_id;
			$update   = [];

			$data = [];
			// Update if necessary
			if ( isset( $data['filtering'] ) && $data['filtering'] !== $source->filtering ) {
				$update = [ 'filtering' => $data['filtering'] ];
			}

			if ( isset( $data['searching'] ) && $data['searching'] !== $source->searching ) {
				$update = [ 'searching' => $data['searching'] ];
			}

			$format = [ '%d' ];

			$where       = [
				'source_id' => $source->source_id,
				'lang'      => $source->lang,
			];
			$whereFormat = [
				'%d',
				'%s',
			];

			if ( ! empty( $update ) ) {
				$wpdb->update( $table, $update, $where, $format, $whereFormat );
			}
		} else { // Insert

			$data = [
				'name'      => $data['name'],
				'label'     => $data['label'],
				'filtering' => isset( $data['filtering'] ) ? $data['filtering'] : 0,
				'searching' => isset( $data['searching'] ) ? $data['searching'] : 0,
				'lang'      => $data['lang'],
			];

			$format = [
				'%s', // name
				'%s', // label
				'%d', // filtering
				'%d', // searching
				'%s', // lang
			];

			$wpdb->insert( $table, $data, $format );
			$sourceID = (int) $wpdb->insert_id;
		}

		return empty( $sourceID ) ? 0 : $sourceID;
	}

	/**
	 * Get source ID based on the source name
	 *
	 * @param array $sourceName
	 *
	 * @return int
	 */
	public static function getSourceIdByName( $sourceName ) {
		global $wpdb;

		$table = self::getTableName();

		$id = $wpdb->get_var(
			//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT source_id
		                                      FROM $table
		                                      WHERE name = %s",
				$sourceName
			)
		);

		return ! empty( $id ) && is_numeric( $id ) ? (int) $id : 0;
	}

	public static function getTableName() {
		return Utils::getTableName( 'sources' );
	}
}
