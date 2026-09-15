<?php

namespace DgoraWcas\Admin;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Storage;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Install {

	const SETTINGS_VERSION = 2;

	/**
	 * Call installation callback
	 *
	 * @return void
	 */
	public static function maybeInstall() {
		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			add_action( 'admin_init', [ __CLASS__, 'checkVersion' ], 5 );
		}
	}

	/**
	 * Install process
	 *
	 * @return void
	 */
	public static function install() {

		if ( ! defined( 'DGWT_WCAS_INSTALLING' ) ) {
			define( 'DGWT_WCAS_INSTALLING', true );
		}

		self::saveActivationDate();

		self::createOptions();

		self::maybeUpgradeOptions();

		// Update plugin version
		update_option( 'dgwt_wcas_version', DGWT_WCAS_VERSION );

	}

	/**
	 * Save default options
	 *
	 * @return void
	 */
	public static function createOptions() {

		global $dgwtWcasSettings;

		$sections = DGWT_WCAS()->settings->settingsFields();

		$settings = [];

		if ( is_array( $sections ) && ! empty( $sections ) ) {
			foreach ( $sections as $options ) {

				if ( is_array( $options ) && ! empty( $options ) ) {

					foreach ( $options as $option ) {

						if ( isset( $option['name'] ) && ! isset( $dgwtWcasSettings[ $option['name'] ] ) ) {

							$settings[ $option['name'] ] = isset( $option['default'] ) ? $option['default'] : '';
						}
					}
				}
			}
		}

		$updateOptions = array_merge( $settings, $dgwtWcasSettings );

		update_option( DGWT_WCAS_SETTINGS_KEY, $updateOptions );
	}

	/**
	 * Maybe update settings structure or values
	 *
	 * @param string $version free | pro
	 */
	private static function maybeUpgradeOptions( $version = 'free' ) {
		$freeSettingsVersion = (int) get_option( 'dgwt_wcas_settings_version', 0 );
		$proSettingsVersion  = (int) get_option( 'dgwt_wcas_settings_version_pro', 0 );
		$mainVersion         = $freeSettingsVersion;
		$mainKey             = 'dgwt_wcas_settings_version';
		$upgraded            = false;

		if ( $version === 'pro' ) {
			$mainVersion = $proSettingsVersion;
			$mainKey     = 'dgwt_wcas_settings_version_pro';
		}

		if ( $freeSettingsVersion === 0 && $proSettingsVersion === 0 ) {
			self::upgradeOptionsTo1();
			self::upgradeOptionsTo2();
			$upgraded = true;
		}

		if ( ! $upgraded ) {
			if (
				( $version === 'free' && $freeSettingsVersion < 2 && $proSettingsVersion !== 2 )
				|| ( $version === 'pro' && $proSettingsVersion < 2 && $freeSettingsVersion !== 2 )
			) {
				self::upgradeOptionsTo2();
			}
		}

		if ( $mainVersion < self::SETTINGS_VERSION ) {
			update_option( $mainKey, self::SETTINGS_VERSION );
			DGWT_WCAS()->settings->clearCache();
		}
	}

	private static function upgradeOptionsTo1() {
		$settings = get_option( DGWT_WCAS_SETTINGS_KEY );

		if ( empty( $settings ) ) {
			return;
		}

		// Product categories
		if ( isset( $settings['show_matching_categories'] ) ) {
			$settings['show_product_tax_product_cat'] = $settings['show_matching_categories'];
			unset( $settings['show_matching_categories'] );
		}
		if ( isset( $settings['show_categories_images'] ) ) {
			$settings['show_product_tax_product_cat_images'] = $settings['show_categories_images'];
			unset( $settings['show_categories_images'] );
		}
		if ( isset( $settings['search_in_product_categories'] ) ) {
			$settings['search_in_product_tax_product_cat'] = $settings['search_in_product_categories'];
			unset( $settings['search_in_product_categories'] );
		}

		// Product tags
		if ( isset( $settings['show_matching_tags'] ) ) {
			$settings['show_product_tax_product_tag'] = $settings['show_matching_tags'];
			unset( $settings['show_matching_tags'] );
		}
		if ( isset( $settings['search_in_product_tags'] ) ) {
			$settings['search_in_product_tax_product_tag'] = $settings['search_in_product_tags'];
			unset( $settings['search_in_product_tags'] );
		}

		// Product brands
		if ( DGWT_WCAS()->brands->hasBrands() ) {
			if ( isset( $settings['show_matching_brands'] ) ) {
				$settings[ 'show_product_tax_' . DGWT_WCAS()->brands->getBrandTaxonomy() ] = $settings['show_matching_brands'];
				unset( $settings['show_matching_brands'] );
			}
			if ( isset( $settings['search_in_brands'] ) ) {
				$settings[ 'search_in_product_tax_' . DGWT_WCAS()->brands->getBrandTaxonomy() ] = $settings['search_in_brands'];
				unset( $settings['search_in_brands'] );
			}
			if ( isset( $settings['show_brands_images'] ) ) {
				$settings[ 'show_product_tax_' . DGWT_WCAS()->brands->getBrandTaxonomy() . '_images' ] = $settings['show_brands_images'];
				unset( $settings['show_brands_images'] );
			}
		}

		update_option( DGWT_WCAS_SETTINGS_KEY, $settings );
	}

	/**
	 * Since v1.19.0 we've started to use two separated breakpoints in the search bar layout settings:
	 *
	 *   1. "mobile_breakpoint" - set the breakpoint for switching between icon and search bar layout
	 *
	 *   2. "mobile_overlay_breakpoint" - new option added since v1.19.0.
	 *   Set the breakpoint after which the mobile search overlay is activated
	 *
	 *  Before v1.19.0 "mobile_breakpoint" was in charge of these two things.
	 *  We don't know if users set "mobile_breakpoint" to handle overlay on mobile or icon/bar toggle.
	 *  So we have to copy value of "mobile_breakpoint" to "mobile_overlay_breakpoint" to keep backwards compatibility
	 */
	private static function upgradeOptionsTo2() {
		$settings = get_option( DGWT_WCAS_SETTINGS_KEY );

		if ( empty( $settings ) ) {
			return;
		}

		if ( isset( $settings['mobile_breakpoint'] ) && isset( $settings['mobile_overlay_breakpoint'] )
		) {
			$settings['mobile_overlay_breakpoint'] = $settings['mobile_breakpoint'];
			update_option( DGWT_WCAS_SETTINGS_KEY, $settings );
		}
	}

	/**
	 * Save activation timestamp
	 * Used to display notice, asking for a feedback
	 *
	 * @return void
	 */
	private static function saveActivationDate() {
		$date = get_option( 'dgwt_wcas_activation_date' );

		if ( empty( $date ) ) {
			update_option( 'dgwt_wcas_activation_date', time() );
		}
	}

	/**
	 * Check if SQL server support JSON data type
	 */
	private static function checkIfDbSupportJson() {
		global $wpdb;

		$suppress_errors = $wpdb->suppress_errors;
		$wpdb->suppress_errors();
		$result = $wpdb->get_var( "SELECT JSON_CONTAINS('[1,2,3]', '2')" );
		$wpdb->suppress_errors( $suppress_errors );

		update_option( 'dgwt_wcas_db_json_support', ( $result === '1' && empty( $wpdb->last_error ) ) ? 'yes' : 'no' );
	}

	/**
	 * Check if SQL server support locking mechanism
	 */
	private static function checkIfDbSupportLocks() {
		global $wpdb;

		$lockName = 'fibosearch_lock_test';

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT GET_LOCK(%s,%d) as set_lock', $lockName, 5 )
		);

		if ( intval( $row->set_lock ) === 1 ) {
			$wpdb->get_row(
				$wpdb->prepare( 'SELECT RELEASE_LOCK(%s) as lock_released', $lockName )
			);
			update_option( 'dgwt_wcas_db_locking_support', 'yes' );
		} else {
			update_option( 'dgwt_wcas_db_locking_support', 'no' );
		}
	}

	/**
	 * Compare plugin version and install if a new version is available
	 *
	 * @return void
	 */
	public static function checkVersion() {
		if ( ! defined( 'IFRAME_REQUEST' ) ) {

			if ( ! dgoraAsfwFs()->is_premium() && get_option( 'dgwt_wcas_version' ) != DGWT_WCAS_VERSION ) {
				self::install();
			}

			if ( dgoraAsfwFs()->is__premium_only() ) {
				$pluginVersion = Helpers::getPluginVersion__premium_only();

				if ( $pluginVersion != DGWT_WCAS_VERSION ) {
					Builder::flushOptionsCache();
					update_option( 'dgwt_wcas_version_pro', DGWT_WCAS_VERSION );

					/**
					 * Temporarily removing the index rebuild action when plugin settings change to prevent it from
					 * occurring in self::createOptions(), as the index rebuild will be triggered manually a few lines later.
					 */
					remove_action( 'update_option_' . DGWT_WCAS_SETTINGS_KEY, [ DGWT_WCAS()->tntsearchMySql, 'buildIndexOnchangeSettings' ], 10 );

					self::createOptions();

					self::maybeUpgradeOptions( 'pro' );

					self::checkIfDbSupportJson();

					self::checkIfDbSupportLocks();

					Storage::maybeCreate();

					// Remove deprecated options for storing indexer logs.
					delete_option( Builder::LAST_BUILD_OPTION_KEY . '_logs' );
					delete_option( Builder::LAST_BUILD_OPTION_KEY . '_logs_tmp' );

					if ( self::shouldRebuildIndex__premium_only( $pluginVersion, DGWT_WCAS_VERSION ) ) {
						// Whenever a plugin version is changed, we always remove the old index
						// when parallel building is enabled, to avoid compatibility problems.
						if ( Config::isParallelBuildingEnabled() ) {
							Builder::wipeIndexTables( 'main' );
							Builder::addInfo( 'status', 'not-exist', 'main' );
						}

						Builder::buildIndex();
					}

					Builder::wipeSQLiteAfterEffects();
				}
			}
		}
	}

	/**
	 * Check if index should be rebuilt based on plugin version changes
	 *
	 * If both saved and current plugin versions are within the specified ranges,
	 * then we do not rebuild the index.
	 *
	 * @param string $savedPluginVersion
	 * @param string $currentPluginVersion
	 *
	 * @return bool
	 */
	public static function shouldRebuildIndex__premium_only( $savedPluginVersion, $currentPluginVersion ) {
		$versionsWithoutIndexChanges = [
			[
				'min' => '1.32.0',
				'max' => '1.33.0',
			],
		];

		foreach ( $versionsWithoutIndexChanges as $versionRange ) {
			if (
				version_compare( $savedPluginVersion, $versionRange['min'], '>=' ) &&
				version_compare( $savedPluginVersion, $versionRange['max'], '<=' ) &&
				version_compare( $currentPluginVersion, $versionRange['min'], '>=' ) &&
				version_compare( $currentPluginVersion, $versionRange['max'], '<=' )
			) {
				return false;
			}
		}

		return true;
	}
}
