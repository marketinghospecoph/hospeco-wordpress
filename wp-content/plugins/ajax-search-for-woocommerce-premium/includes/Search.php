<?php

namespace DgoraWcas;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder;
use DgoraWcas\Engines\TNTSearchMySQL\SearchQuery\SearchResultsPageQuery;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Search {
	/**
	 * Search posts (products, posts or pages)
	 *
	 * @param string $phrase Search phrase.
	 * @param array $args {
	 *     Search arguments.
	 *
	 * @type string $post_type Post type.
	 * @type string $fields Format of returned data. Accepts: 'ids', 'all'.
	 * @type int $per_page The number of results to search for. Use -1 to request all results.
	 * @type int $page The number of the current page.
	 * @type int $offset The number of results to offset before retrieval.
	 * @type string $orderby Sort retrieved results by parameter. Only in Pro version. Accepts: 'relevance', 'price', 'date', 'rating', 'popularity'.
	 * @type string $order Designates ascending or descending order of results. Only in Pro version. Accepts: 'desc', 'asc'.
	 * @type string $lang Language.
	 * }
	 *
	 * @return array|\WP_Error
	 */
	public function searchPosts( $phrase, $args ) {
		$args = wp_parse_args(
			$args,
			[
				'post_type' => 'product',
				'fields'    => 'ids',
				'per_page'  => 10,
				'page'      => 1,
				'offset'    => 0,
				'orderby'   => 'relevance',
				'order'     => '',
				'lang'      => '',
			]
		);

		$args = $this->validateArgs( $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		if ( dgoraAsfwFs()->is__premium_only() ) {
			list( $results, $totalResults, $totalPages ) = $this->doSearch__premium_only( $phrase, $args );
		}

		if ( ! dgoraAsfwFs()->is_premium() ) {
			list( $results, $totalResults, $totalPages ) = $this->doSearch( $phrase, $args );
		}

		$response = [
			'results'     => $results,
			'total'       => $totalResults,
			'total_pages' => $totalPages,
		];
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		return json_decode( json_encode( $response ), true );
	}

	/**
	 * Do a search via WordPress.
	 *
	 * @param string $phrase Search phrase.
	 * @param array $args Search arguments.
	 *
	 * @return array Array with results.
	 */
	private function doSearch( $phrase, $args ): array {
		$searchResults = DGWT_WCAS()->nativeSearch->getSearchResults( $phrase, true, 'product-ids' );
		$results       = [];

		if (
			isset( $searchResults['suggestions'] ) &&
			is_array( $searchResults['suggestions'] )
		) {
			$results = wp_list_pluck( $searchResults['suggestions'], 'ID' );
		}

		/*
		 * If there are no results, native search returns a dummy product ID = 0 to force integrations to have no results.
		 * However, here, when there are no results, we just want to return an empty array.
		 */
		if ( isset( $results[0] ) && $results[0] === 0 ) {
			$results = [];
		}

		$totalResults = count( $results );
		$totalPages   = (int) ceil( $totalResults / $args['per_page'] );

		if ( $args['page'] > $totalPages ) {
			$args['page'] = $totalPages;
		}

		if ( $args['per_page'] > 0 ) {
			$offset  = ( ( $args['page'] - 1 ) * $args['per_page'] ) + $args['offset'];
			$results = array_slice( $results, $offset, $args['per_page'] );
		} else {
			$totalPages = 1;
		}

		if ( $totalResults === 0 ) {
			$totalPages = 0;
		}

		if ( $args['fields'] === 'ids' ) {
			return [ $results, $totalResults, $totalPages ];
		}

		$results = DGWT_WCAS()->nativeSearch->getProductsData(
			$results,
			- 1,
			[
				'price',
				'sku',
				'thumb_html',
			]
		);

		return [ $results, $totalResults, $totalPages ];
	}

	/**
	 * Da a search via our engine.
	 *
	 * @param string $phrase Search phrase.
	 * @param array $args Search arguments.
	 *
	 * @return array
	 */
	private function doSearch__premium_only( $phrase, $args ) {
		// Break early if keyword contains blacklisted phrase.
		if ( Helpers::phraseContainsBlacklistedTerm( $phrase ) ) {
			return [ [], 0, 0 ];
		}

		$search = new SearchResultsPageQuery();
		$search->setPhrase( $phrase );

		$lang = '';
		if ( Multilingual::isMultilingual() ) {
			$lang = Multilingual::isLangCode( $args['lang'] ) ? $args['lang'] : Multilingual::getCurrentLanguage();
		}

		if ( ! Builder::isIndexValid( $lang ) ) {
			return [ [], 0, 0 ];
		}

		$search = new SearchResultsPageQuery();
		$search->setPhrase( $phrase );

		if ( ! empty( $lang ) ) {
			$search->setLang( $lang );
		}

		if ( $args['post_type'] === 'product' || $args['post_type'] === 'product-variation' ) {
			$search->searchProducts();
			$results = $search->getProducts( $args['orderby'], $args['order'] );
		} else {
			$search->searchPosts( [ $args['post_type'] ] );
			$results = $search->getPosts( $args['post_type'] );
		}

		if ( $args['fields'] === 'ids' ) {
			$results = array_map( 'intval', wp_list_pluck( $results, 'post_id' ) );
		}

		$totalResults = count( $results );
		$totalPages   = (int) ceil( $totalResults / $args['per_page'] );

		if ( $args['page'] > $totalPages ) {
			$args['page'] = $totalPages;
		}

		if ( $args['per_page'] > 0 ) {
			$offset  = ( ( $args['page'] - 1 ) * $args['per_page'] ) + $args['offset'];
			$results = array_slice( $results, $offset, $args['per_page'] );
		} else {
			$totalPages = 1;
		}

		if ( $totalResults === 0 ) {
			$totalPages = 0;
		}

		return [ $results, $totalResults, $totalPages ];
	}

	/**
	 * Validate search arguments
	 *
	 * @param array $args Search arguments.
	 *
	 * @return array|\WP_Error
	 */
	private function validateArgs( $args ) {
		$error = new \WP_Error();

		$args['per_page'] = (int) $args['per_page'];
		if ( $args['per_page'] < - 1 ) {
			$args['per_page'] = abs( $args['per_page'] );
		} elseif ( $args['per_page'] === 0 ) {
			$args['per_page'] = 1;
		}

		$args['offset'] = (int) $args['offset'];
		if ( $args['offset'] < 0 ) {
			$args['offset'] = 0;
		}

		$allowedPostTypes = [ 'product' ];
		if ( dgoraAsfwFs()->is__premium_only() ) {
			$allowedPostTypes = Helpers::getAllowedPostTypes();
		}
		if ( ! in_array( $args['post_type'], $allowedPostTypes, true ) ) {
			$error->add( 'dgwt-wcas-invalid-arg-post-type', 'Invalid argument: post_type' );
		}

		if ( ! in_array( $args['fields'], [ 'all', 'ids' ], true ) ) {
			$error->add( 'dgwt-wcas-invalid-arg-fields', 'Invalid argument: fields' );
		}

		if ( dgoraAsfwFs()->is__premium_only() ) {
			if ( ! in_array( $args['orderby'], [ 'relevance', 'date', 'price', 'rating', 'popularity' ], true ) ) {
				$error->add( 'dgwt-wcas-invalid-arg-orderby', 'Invalid argument: orderby' );
			}

			if ( ! in_array( $args['order'], [ '', 'desc', 'asc' ], true ) ) {
				$error->add( 'dgwt-wcas-invalid-arg-order', 'Invalid argument: order' );
			}
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		return $args;
	}
}
