<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WPML;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	const PLUGIN_NAME = 'sitepress-multilingual-cms/sitepress.php';

	public function init() {
		if ( ! Config::isPluginActive( self::PLUGIN_NAME ) ) {
			return;
		}

		add_filter( 'dgwt/wcas/tnt/search_results/products', [ $this, 'filterResultsByLang' ], 10, 3 );

		$postTypes = Builder::getInfo( 'active_post_types' );
		if ( ! empty( $postTypes && is_array( $postTypes ) ) ) {
			foreach ( $postTypes as $postType ) {
				add_filter( 'dgwt/wcas/tnt/search_results/' . $postType, [ $this, 'filterResultsByLang' ], 10, 3 );
			}
		}
	}

	/**
	 * Filter search results by language
	 *
	 * This filter is required only if in WPML settings it is checked: "Translatable - use translation if available
	 * or fallback to default language", but has no effect on search when only translated objects are indexed.
	 *
	 * @param $rows
	 * @param $phrase
	 * @param $lang
	 *
	 * @return array
	 */
	public function filterResultsByLang( $rows, $phrase, $lang ) {
		// If WPML is active but only with one language then
		// $lang is empty and there is no need to filter the results
		if ( empty( $lang ) ) {
			return $rows;
		}

		$rows = array_filter(
			$rows,
			function ( $row ) use ( $lang ) {
				$rowLang = '';
				if ( isset( $row->lang ) ) {
					$rowLang = $row->lang;
				} elseif ( isset( $row['lang'] ) ) {
					$rowLang = $row['lang'];
				}

				return $rowLang === $lang;
			}
		);

		$rows = array_values( $rows );

		return $rows;
	}
}
