<?php

namespace DgoraWcas;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Uninstall {

	public static function afterUninstall() {
		self::wipeOptions();
		self::wipeTransients();

		/**
		 * Multisite.
		 */
		if ( is_multisite() ) {
			$currentSiteId = get_current_blog_id();

			foreach ( get_sites() as $site ) {
				if ( is_numeric( $site->blog_id ) && $site->blog_id > 1 ) {
					switch_to_blog( $site->blog_id );

					self::wipeOptions();
					self::wipeTransients();
				}
			}

			// Switch back to the original site.
			switch_to_blog( ( $currentSiteId ) );
		}
	}

	/**
	 * Wipe options.
	 */
	private static function wipeOptions() {
		$options = [
			'dgwt_wcas_schedule_single',
			'dgwt_wcas_settings_show_advanced',
			'dgwt_wcas_images_regenerated',
			'dgwt_wcas_activation_date',
			'dgwt_wcas_dismiss_review_notice',
			'dgwt_wcas_stats_db_version',
			'widget_dgwt_wcas_ajax_search',
		];

		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Wipe transients.
	 */
	private static function wipeTransients() {
		$transients = [
			'dgwt_wcas_troubleshooting_async_results',
		];

		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}
	}

	public static function afterUninstall__premium_only() {
		/**
		 * Single site.
		 */
		self::wipeTables__premium_only();
		self::wipeOptions__premium_only();
		self::wipeTransients__premium_only();
		self::wipeActionSchedulerLogsAndActions__premium_only();
		self::wipeLogs__premium_only();

		/**
		 * Multisite.
		 */
		if ( is_multisite() ) {
			$currentSiteId = get_current_blog_id();

			foreach ( get_sites() as $site ) {
				if ( is_numeric( $site->blog_id ) && $site->blog_id > 1 ) {
					switch_to_blog( $site->blog_id );

					self::wipeTables__premium_only();
					self::wipeOptions__premium_only();
					self::wipeTransients__premium_only();
					self::wipeActionSchedulerLogsAndActions__premium_only();
					self::wipeLogs__premium_only();
				}
			}

			// Switch back to the original site.
			switch_to_blog( ( $currentSiteId ) );
		}
	}

	/**
	 * Wipe database tables.
	 */
	private static function wipeTables__premium_only() {
		global $wpdb;

		$pluginTables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'dgwt_wcas_' ) . '%' ) );
		foreach ( $pluginTables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * Wipe options.
	 */
	private static function wipeOptions__premium_only() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'dgwt_wcas_indexer_last_build%'" ) );

		$options = [
			'dgwt_wcas_schedule_single',
			'dgwt_wcas_schedule_last_data',
			'dgwt_wcas_inv_index_db_version',
			'dgwt_wcas_index_db_version',
			'dgwt_wcas_tax_index_db_version',
			'dgwt_wcas_var_index_db_version',
			'dgwt_wcas_ven_index_db_version',
			'dgwt_wcas_settings_show_advanced',
			'dgwt_wcas_db_json_support',
			'dgwt_wcas_images_regenerated',
			'dgwt_wcas_debug_search_logs',
			'dgwt_wcas_indexer_prepare_process_exist',
			'dgwt_wcas_activation_date',
			'dgwt_wcas_dismiss_review_notice',
			'dgwt_wcas_indexer_last_failure_data',
			'dgwt_wcas_auto_send_indexer_failure_reports',
			'dgwt_wcas_dismiss_indexer_failure_notices',
			'dgwt_wcas_db_locking_support',
			'dgwt_wcas_stats_db_version',
			'widget_dgwt_wcas_ajax_search',
			'dgwt_wcas_storage_db_version',
		];

		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Wipe transients.
	 */
	private static function wipeTransients__premium_only() {
		$transients = [
			'dgwt_wcas_indexer_details_display',
			'dgwt_wcas_searchable_custom_fields',
			'dgwt_wcas_troubleshooting_async_results',
			'dgwt_wcas_indexer_debug',
			'dgwt_wcas_indexer_debug_scope',
		];

		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}
	}

	/**
	 * Wipe Action Scheduler actions and its logs.
	 */
	private static function wipeActionSchedulerLogsAndActions__premium_only() {
		global $wpdb;

		$logIds = $wpdb->get_col( "SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = 'dgwt/wcas/tnt/background_product_update'" );

		if ( ! empty( $logIds ) ) {
			$placeholders = array_fill( 0, count( $logIds ), '%d' );
			$format       = implode( ', ', $placeholders );
			// TODO
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}actionscheduler_logs WHERE action_id IN ($format)", $logIds ) );
		}
		$wpdb->query( "DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = 'dgwt/wcas/tnt/background_product_update'" );
	}

	/**
	 * Wipe logs.
	 */
	private static function wipeLogs__premium_only() {
		if ( class_exists( 'WC_Log_Handler_File' ) ) {
			$wcLogHandler = new \WC_Log_Handler_File();
			foreach ( [ 'fibosearch-updater', 'fibosearch-fail-indexer', 'fibosearch-indexer' ] as $source ) {
				$files = $wcLogHandler->get_log_files();
				foreach ( $files as $handle => $filename ) {
					if ( strpos( $handle, $source ) !== false ) {
						$wcLogHandler->remove( $handle );
					}
				}
			}
		}
	}
}
