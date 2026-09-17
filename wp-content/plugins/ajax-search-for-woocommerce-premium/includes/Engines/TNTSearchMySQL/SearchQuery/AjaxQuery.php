<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\SearchQuery;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder;
use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Marketplace\WCMarketplace;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AjaxQuery extends MainQuery {

	/**
	 * Total autocomplete limit
	 * int
	 */
	private $totalLimit;

	/**
	 * Autocomplete groups
	 * array
	 */
	private $groups = [];

	/**
	 * Flexible lmits
	 * bool
	 */
	private $flexibleLimits = true;

	/**
	 * Show heading in autocomplete
	 * bool
	 */
	private $showHeadings = false;

	/**
	 * Final Sugestions
	 * array
	 */
	private $suggestions = [];

	/**
	 * AjaxQuery constructor.
	 *
	 * @param bool $debug
	 */
	public function __construct( $debug = false ) {

		parent::__construct( $debug );

		// Add "No results" suggestion if all results have been removed in earlier filters.
		add_filter(
			'dgwt/wcas/search_results/output',
			[
				'DgoraWcas\Helpers',
				'noResultsSuggestion',
			],
			PHP_INT_MAX - 10
		);
	}

	/**
	 * Send empty response in correct JSON format.
	 * Equivalent "No Results"
	 *
	 * @param string $engine
	 *
	 * @return void
	 */
	public static function sendEmptyResponse( $engine = 'pro' ) {
		$output  = [];
		$results = [];

		$results[] = [
			'value' => '',
			'type'  => 'no-results',
		];

		$output['suggestions'] = $results;

		$output['total'] = 0;

		if ( $engine === 'pro' ) {
			$output['tntTime'] = '0.0000 s';
		}
		$output['time'] = number_format( microtime( true ) - DGWT_SEARCH_START, 4, '.', '' );

		$output['engine'] = $engine;
		$output['v']      = Helpers::getPluginVersion__premium_only();

		echo json_encode( apply_filters( 'dgwt/wcas/search_results/output', $output ) );
		exit();
	}

	public static function sendEmptyResponseProductIdsOnly( $engine = 'pro' ) {
		$output = [
			'total'   => 0,
			'results' => [],
			'engine'  => $engine,
		];

		echo json_encode( apply_filters( 'dgwt/wcas/search_results/output/product_ids_only', $output ) );
		exit();
	}

	/**
	 * Send search results as a JSON response
	 *
	 * @param bool $exit
	 *
	 * @return void
	 */
	public function sendResults( $exit = true ) {
		$output               = [];
		$hits                 = 0;
		$this->groups         = $this->searchResultsGroups();
		$this->flexibleLimits = apply_filters( 'dgwt/wcas/flexible_limits', true );
		$this->showHeadings   = Helpers::canGroupSuggestions();
		$totalProducts        = 0;

		if ( $this->flexibleLimits ) {
			$totalLimit       = $this->getOption( 'suggestions_limit', 'int', 7 );
			$this->totalLimit = $totalLimit === - 1 ? $this->calcFreeSlots() : $totalLimit;
		}

		// Taxonomies
		$hits += count( $this->foundTax );
		foreach ( $this->foundTax as $suggestion ) {

			$key = ! empty( $suggestion['taxonomy'] ) ? 'tax_' . $suggestion['taxonomy'] : '';

			if ( array_key_exists( $key, $this->groups ) && empty( $this->groups[ $key ]['full'] ) ) {
				$limit = $this->flexibleLimits ? $this->totalLimit : $this->groups[ $key ]['limit'];

				$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/taxonomy', $suggestion, $suggestion['taxonomy'] );
				$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/' . $key, $outputData );

				if ( isset( $outputData['meta'] ) ) {
					unset( $outputData['meta'] );
				}

				$this->groups[ $key ]['results'][] = $outputData;

				if ( count( $this->groups[ $key ]['results'] ) === $limit ) {
					$this->groups[ $key ]['full'] = true;
				}
			}
		}

		// Vendors
		$hits += count( $this->foundVendors );
		foreach ( $this->foundVendors as $suggestion ) {

			$key = 'vendor';

			if ( empty( $this->groups[ $key ]['full'] ) ) {
				$limit = $this->flexibleLimits ? $this->totalLimit : $this->groups[ $key ]['limit'];

				$this->groups[ $key ]['results'][] = $suggestion;

				if ( count( $this->groups[ $key ]['results'] ) === $limit ) {
					$this->groups[ $key ]['full'] = true;
				}
			}
		}

		// Post types.
		foreach ( $this->foundPosts as $postType => $items ) {
			$hits += count( $items );
			foreach ( $items as $suggestion ) {

				$key = ! empty( $suggestion['post_type'] ) ? 'post_type_' . $suggestion['post_type'] : '';

				if ( array_key_exists( $key, $this->groups ) && empty( $this->groups[ $key ]['full'] ) ) {
					$limit = $this->flexibleLimits ? $this->totalLimit : $this->groups[ $key ]['limit'];

					/**
					 * In products $outputData could be different from the $suggestion.
					 * We keep these variable separately also here for consistency.
					 */
					$outputData = $suggestion;

					if ( isset( $outputData['meta'] ) ) {
						unset( $outputData['meta'] );
					}

					$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/' . $postType, $outputData, $suggestion );
					$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/' . $key, $outputData, $suggestion );

					$this->groups[ $key ]['results'][] = $outputData;

					if ( count( $this->groups[ $key ]['results'] ) - 1 === $limit ) {
						$this->groups[ $key ]['full'] = true;
					}
				}
			}
		}

		// Products
		if ( apply_filters( 'dgwt/wcas/search_in_products', true ) ) {
			$products      = $this->getProducts();
			$productsSlots = $this->flexibleLimits ? $this->totalLimit : $this->groups['product']['limit'];

			$ids = [];

			foreach ( $products as $suggestion ) {

				$outputData = [
					'post_id'    => $suggestion->post_id,
					'value'      => html_entity_decode( $suggestion->name ),
					'url'        => apply_filters( 'dgwt/wcas/search_results/product/url', $suggestion->url, $this->getPhrase(), $suggestion ),
					'thumb_html' => '<img src="' . $suggestion->image . '">',
					'price'      => $suggestion->html_price,
					'desc'       => $suggestion->description,
					'sku'        => $suggestion->sku,
					'on_sale'    => false,
					'featured'   => false,
					'type'       => $suggestion->type,
				];

				if ( $suggestion->type === 'product_variation' ) {
					$outputData['post_id']      = $suggestion->post_or_parent_id;
					$outputData['variation_id'] = $suggestion->post_id;
				}

				if ( $this->ShowVendorInProduct() ) {
					$vendor          = $this->getProductVendorData( $suggestion->post_id );
					$p['vendor']     = $vendor['shop_name'];
					$p['vendor_url'] = $vendor['vendor_url'];
				}

				if ( Helpers::isFiboDebugEnabled( 'score' ) ) {
					$outputData['score'] = round( $suggestion->score, 2 );
				}

				$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/product', $outputData, $suggestion );

				$this->groups['product']['results'][] = $outputData;

				$ids[] = $suggestion->post_id;

				$productsSlots --;
				if ( $productsSlots === 0 ) {
					break;
				}
			}

			// SKU exact match.
			$variationSupportModes = Builder::getInfo( 'variation_support_modes' );
			if ( is_array( $variationSupportModes ) && in_array( 'exact_match', $variationSupportModes ) ) {
				$varQuery = new ProductVariationQuery( $this->getPhrase(), $ids, 'sku', $this->getLang() );

				if ( $varQuery->hasResults() ) {
					$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/product_variation', $varQuery->getSuggestionBody() );
					array_unshift( $this->groups['product']['results'], $outputData );
					// phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found
					$hits += 1;
				} else {
					$varQuery = new ProductVariationQuery( $this->getPhrase(), $ids, 'global_unique_id', $this->getLang() );
					if ( $varQuery->hasResults() ) {
						$outputData = apply_filters( 'dgwt/wcas/tnt/search_results/suggestion/product_variation', $varQuery->getSuggestionBody() );
						array_unshift( $this->groups['product']['results'], $outputData );
						// phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found
						$hits += 1;
					}
				}
			}

			$totalProducts = count( $products );
			$hits         += $totalProducts;
		}

		// Analytics
		do_action( 'dgwt/wcas/analytics/after_searching', $this->getPhrase(), $hits, $this->getLang() );

		$this->orderGroups();

		if ( $this->flexibleLimits ) {
			$this->applyFlexibleLimits();
		}

		$this->convertGroupsToSuggestions();

		$this->maybeApplyMoreProductsLink( $totalProducts );

		$output['suggestions'] = $this->suggestions;
		$output['total']       = $hits;

		$output['tntTime'] = number_format( $this->tntTime / 1000, 4, '.', '' ) . ' s';
		$output['time']    = number_format( microtime( true ) - DGWT_SEARCH_START, 4, '.', '' ) . ' s';

		$output['engine'] = 'pro';
		$output['v']      = Helpers::getPluginVersion__premium_only();

		$output = apply_filters( 'dgwt/wcas/search_results/output', $output );
		$output = apply_filters( 'dgwt/wcas/tnt/search_results/output', $output );

		echo json_encode( $output );

		if ( $exit ) {
			exit();
		}
	}

	/**
	 * Send search results containing product IDs only as a JSON response
	 *
	 * @return void
	 */
	public function sendResultsProductIdsOnly() {
		$products = $this->getProducts();
		$results  = array_map( 'absint', wp_list_pluck( $products, 'post_id' ) );

		$output = [
			'total'   => $this->getTotalFound(),
			'results' => $results,
			'tntTime' => number_format( $this->tntTime / 1000, 4, '.', '' ) . ' s',
			'time'    => number_format( microtime( true ) - DGWT_SEARCH_START, 4, '.', '' ) . ' s',
			'engine'  => 'pro',
			'v'       => Helpers::getPluginVersion__premium_only(),
		];

		$output = apply_filters( 'dgwt/wcas/search_results/output/product_ids_only', $output );

		echo json_encode( $output );
		die();
	}

	/**
	 * Headline output structure
	 *
	 * @return array
	 */
	private function headlineBody( $headline ) {
		return [
			'value' => $headline,
			'type'  => 'headline',
		];
	}

	/**
	 * Sort group by order
	 *
	 * @return void
	 */
	private function orderGroups() {
		uasort( $this->groups, [ 'DgoraWcas\Helpers', 'sortAjaxResutlsGroups' ] );
	}

	/**
	 * Add more products link to the suggestion if neccessary
	 *
	 * @param int $totalProducts
	 *
	 * @return void
	 */
	private function maybeApplyMoreProductsLink( $totalProducts ) {
		$showMoreProductsLink = apply_filters( 'dgwt/wcas/search_results/show_more_products_link', false );

		if (
			! empty( $this->groups['product']['results'] ) &&
			( $showMoreProductsLink || count( $this->groups['product']['results'] ) < $totalProducts )
		) {
			$this->suggestions[] = [
				'total' => $totalProducts,
				'type'  => 'more_products',
			];
		}
	}

	/**
	 * Prepare suggestions based on groups
	 *
	 * @return void
	 */
	private function convertGroupsToSuggestions() {
		$this->suggestions = [];

		$totalHeadlines = 0;

		foreach ( $this->groups as $key => $group ) {

			if ( ! empty( $group['results'] ) ) {

				if ( $this->showHeadings ) {
					$this->suggestions[] = $this->headlineBody( $key );
					$totalHeadlines ++;
				}

				foreach ( $group['results'] as $result ) {
					$this->suggestions[] = $result;
				}
			}
		}

		// Remove products headline when there are only product type suggestion
		$alwaysShowProductHeadline = apply_filters( 'dgwt/wcas/search_results/always_show_product_headline', false );

		if ( ! $alwaysShowProductHeadline && $totalHeadlines === 1 ) {
			$i     = 0;
			$unset = false;
			foreach ( $this->suggestions as $key => $suggestion ) {
				if (
					! empty( $suggestion['type'] )
					&& $suggestion['type'] === 'headline'
					&& $suggestion['value'] === 'product'
				) {
					unset( $this->suggestions[ $i ] );
					$unset = true;
					break;
				}

				$i ++;
			}

			if ( $unset ) {
				$this->suggestions = array_values( $this->suggestions );
			}
		}

	}

	/**
	 * Apply flexible limits
	 *
	 * @return void
	 */
	private function applyFlexibleLimits() {

		$slots  = $this->totalLimit;
		$total  = 0;
		$groups = 0;

		foreach ( $this->groups as $key => $group ) {
			if ( ! empty( $this->groups[ $key ]['results'] ) ) {
				$total = $total + count( $this->groups[ $key ]['results'] );
				$groups ++;
			}
		}

		$toRemove = $total >= $slots ? $total - $slots : 0;

		if ( $toRemove > 0 ) {
			for ( $i = 0; $i < $toRemove; $i ++ ) {

				$largestGroupCount = 0;
				$largestGroupKey   = 'product';

				foreach ( $this->groups as $key => $group ) {
					if ( ! empty( $this->groups[ $key ]['results'] ) ) {

						$thisGroupTotal = count( $this->groups[ $key ]['results'] );
						if ( $thisGroupTotal > $largestGroupCount ) {
							$largestGroupCount = $thisGroupTotal;
							$largestGroupKey   = $key;
						}
					}
				}

				$last = count( $this->groups[ $largestGroupKey ]['results'] ) - 1;
				if ( isset( $this->groups[ $largestGroupKey ]['results'][ $last ] ) ) {
					unset( $this->groups[ $largestGroupKey ]['results'][ $last ] );
				}
			}
		}

	}

	/**
	 * Calc total free slots
	 *
	 * @return int
	 */
	public function calcFreeSlots() {
		$slots = 0;

		foreach ( $this->groups as $key => $group ) {
			if ( ! empty( $group['limit'] ) ) {
				$slots = $slots + absint( $group['limit'] );
			}
		}

		return $slots;
	}

	/**
	 * Can show vendors next to products
	 *
	 * @return int
	 */
	private function showVendorInProduct() {
		$show = false;

		// WC Marketplace
		if (
			Config::isPluginActive( 'dc-woocommerce-multi-vendor/dc_product_vendor.php' )
			&& is_array( $this->settings )
			&& ! empty( $this->settings['marketplace_show_vendors_in_products'] )
			&& $this->settings['marketplace_show_vendors_in_products'] === 'on'
		) {
			$show = true;
		}

		return $show;
	}

	/**
	 * Get Vendor data
	 *
	 * @param int $productID
	 *
	 * @return array
	 */
	private function getProductVendorData( $productID ) {

		$data = [
			'shop_name'  => '',
			'vendor_url' => '',
		];

		// WC Marketplace
		if ( Config::isPluginActive( 'dc-woocommerce-multi-vendor/dc_product_vendor.php' ) ) {
			$data = array_intersect_key( WCMarketplace::getVendorDataDirectly( $productID ), $data );

		}

		return $data;
	}

	/**
	 * Order of the search resutls groups
	 *
	 * @return array
	 */
	private function searchResultsGroups() {

		$groups = [
			'product' => [
				'limit' => 7,
				'order' => 100,
			],
		];

		// Taxonomies
		$taxonomies = $this->taxQuery->getActiveTaxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			switch ( $taxonomy ) {
				case 'product_cat':
					$order = 10;
					break;
				case 'product_tag':
					$order = 20;
					break;
				default:
					$order = 5;
					break;
			}

			$groups[ 'tax_' . $taxonomy ] = [
				'limit' => 3,
				'order' => $order,
			];
		}

		// Post types
		$activePostTypes = Builder::getInfo( 'active_post_types' );
		foreach ( $activePostTypes as $postType ) {
			switch ( $postType ) {
				case 'post':
					$order = 40;
					break;
				case 'page':
					$order = 50;
					break;
				default:
					$order = 60;
					break;
			}

			$groups[ $postType ] = [
				'limit' => 5,
				'order' => $order,
			];
		}

		// Vendors
		if ( $this->getOption( 'marketplace_enable_search', 'string', 'off' ) === 'on' ) {
			$groups['vendor'] = [
				'limit' => 5,
				'order' => 15,
			];
		}

		$groups = apply_filters( 'dgwt/wcas/search_groups', $groups );

		// Change post type keys to post_type_{$postType}.
		$activePostTypes = Builder::getInfo( 'active_post_types' );
		foreach ( $activePostTypes as $postType ) {
			if ( isset( $groups[ $postType ] ) ) {
				$groups[ 'post_type_' . $postType ] = $groups[ $postType ];
				unset( $groups[ $postType ] );
			}
		}

		return $groups;
	}
}
