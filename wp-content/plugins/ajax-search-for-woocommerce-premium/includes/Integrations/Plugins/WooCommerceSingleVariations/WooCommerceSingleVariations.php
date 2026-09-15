<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceSingleVariations;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\SourceQuery;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with WooCommerce Single Variations by weLaunch
 *
 * Plugin URL: https://welaunch.io/plugins/woocommerce-single-variations/
 * Author: weLaunch
 */
class WooCommerceSingleVariations extends AbstractPluginIntegration {
	protected const LABEL       = 'WooCommerce Single Variations';
	protected const MIN_VERSION = '1.4.7';

	/**
	 * @var VariationVisibilityResolver|null
	 */
	private $visibilityResolver;

	/**
	 * @var VariationTitleBuilder|null
	 */
	private $variationTitleBuilder;

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() || ! class_exists( 'WooCommerce_Single_Variations' ) ) {
			return false;
		}

		return parent::isActive()
			&& ! empty( static::getPluginOption( 'enable' ) )
			&& ! empty( static::getPluginOption( 'showVariationsInSearch' ) );
	}

	public static function pluginVersion(): string {
		global $WooCommerce_Single_Variations;

		if ( is_object( $WooCommerce_Single_Variations ) && method_exists( $WooCommerce_Single_Variations, 'get_version' ) ) {
			$version = $WooCommerce_Single_Variations->get_version();
			if ( is_string( $version ) || is_numeric( $version ) ) {
				return (string) $version;
			}
		}

		return '';
	}

	public function init(): void {
		add_filter( 'dgwt/wcas/variation_support_modes', [ $this, 'variation_support_modes' ] );
		add_filter( 'dgwt/wcas/indexer/variation_parent_ids', [ $this, 'variation_parent_ids' ] );
		add_filter( 'dgwt/wcas/indexer/variation_ids', [ $this, 'filter_variation_ids' ] );
		add_filter( 'dgwt/wcas/variations_update/variation_ids', [ $this, 'filter_variation_ids' ] );

		add_filter( 'dgwt/wcas/indexer/source_query/where', [ $this, 'exclude_variable_parents_from_source_query' ], 10, 3 );
		add_filter( 'dgwt/wcas/readable_index/insert', [ $this, 'exclude_variable_parents_from_readable_index' ], 10, 3 );
		add_filter( 'dgwt/wcas/indexer/updater/can_index', [ $this, 'can_index_after_update' ], 10, 3 );

		add_filter( 'dgwt/wcas/tnt/variation_source_query/data', [ $this, 'filter_variation_source_data' ], 10, 3 );
		add_filter( 'dgwt/wcas/tnt/variation_source_query/data', [ $this, 'apply_variation_titles_to_source_rows' ], 20, 3 );
		add_filter( 'dgwt/wcas/variation/insert', [ $this, 'apply_variation_title_to_readable_index' ], 5, 2 );
	}

	public function variation_support_modes( $modes ): array {
		$modes   = array_diff( (array) $modes, [ 'exact_match' ] );
		$modes[] = 'as_single_product';

		return array_values( array_unique( $modes ) );
	}

	public function variation_parent_ids(): array {
		remove_filter( 'dgwt/wcas/indexer/source_query/where', [ $this, 'exclude_variable_parents_from_source_query' ], 10 );
		add_filter( 'dgwt/wcas/indexer/source_query/where/exclude_from_search', '__return_empty_string', 5 );

		$source   = new SourceQuery( [ 'ids' => true ] );
		$ids      = $source->getData();
		$resolver = $this->visibilityResolver();

		remove_filter( 'dgwt/wcas/indexer/source_query/where/exclude_from_search', '__return_empty_string', 5 );
		add_filter( 'dgwt/wcas/indexer/source_query/where', [ $this, 'exclude_variable_parents_from_source_query' ], 10, 3 );

		return $resolver->filterParentIds( $resolver->filterVariableProductIds( $ids ) );
	}

	public function filter_variation_ids( $variationIds ): array {
		return $this->visibilityResolver()->filterVariationIds( (array) $variationIds );
	}

	public function filter_variation_source_data( $data, $_sourceQuery, bool $onlyIds ) {
		return $this->visibilityResolver()->filterSourceData( $data, $onlyIds );
	}

	public function exclude_variable_parents_from_source_query( string $where ): string {
		global $wpdb;

		$termTaxonomyId = $this->get_variable_product_term_taxonomy_id();
		if ( $termTaxonomyId === 0 ) {
			return $where;
		}

		$where .= $wpdb->prepare(
			" AND posts.ID NOT IN (
				SELECT object_id
				FROM $wpdb->term_relationships
				WHERE term_taxonomy_id = %d
			)",
			$termTaxonomyId
		);

		return $where;
	}

	public function exclude_variable_parents_from_readable_index( $data, int $postId, string $postType ) {
		if ( $postType !== 'product' ) {
			return $data;
		}

		$product = wc_get_product( $postId );
		if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_type( 'variable' ) ) {
			return $data;
		}

		return [];
	}

	/**
	 * Allow to index variable product after update
	 *
	 * We need allow to index variable products after update, because during the update we will retrieve the variations.
	 * Without this there is no way to index the variations.
	 * As side effect, the variable product will be in readable index, but not in the searchable.
	 */
	public function can_index_after_update( bool $canIndex, int $_productId, $wcProduct ): bool {
		if ( is_a( $wcProduct, 'WC_Product' ) && $wcProduct->get_type() === 'variable' ) {
			return true;
		}

		return $canIndex;
	}

	public function apply_variation_title_to_readable_index( $data, $product ) {
		$titleBuilder = $this->variationTitleBuilder();
		if ( ! $titleBuilder->isEnabled() ) {
			return $data;
		}

		if ( ! is_a( $product, 'WC_Product_Variation' ) ) {
			return $data;
		}

		$title = $titleBuilder->build( $product );
		if ( $title !== '' ) {
			$data['title'] = $title;
		}

		return $data;
	}

	public function apply_variation_titles_to_source_rows( $data, $_sourceQuery, bool $onlyIds ) {
		$titleBuilder = $this->variationTitleBuilder();
		if ( $onlyIds || ! $titleBuilder->isEnabled() || ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $index => $row ) {
			$variationId = isset( $row['ID'] ) ? (int) $row['ID'] : 0;
			if ( $variationId < 1 ) {
				continue;
			}

			$product = wc_get_product( $variationId );
			if ( ! is_a( $product, 'WC_Product_Variation' ) ) {
				continue;
			}

			$title = $titleBuilder->build( $product );
			if ( $title !== '' ) {
				$data[ $index ]['name'] = $title;
			}
		}

		return $data;
	}

	private static function getPluginOption( string $key, $default = null ) {
		$options = get_option( 'woocommerce_single_variations_options', [] );
		if ( ! is_array( $options ) ) {
			return $default;
		}

		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	private function visibilityResolver(): VariationVisibilityResolver {
		if ( ! $this->visibilityResolver instanceof VariationVisibilityResolver ) {
			$this->visibilityResolver = new VariationVisibilityResolver();
		}

		return $this->visibilityResolver;
	}

	private function variationTitleBuilder(): VariationTitleBuilder {
		if ( ! $this->variationTitleBuilder instanceof VariationTitleBuilder ) {
			$this->variationTitleBuilder = new VariationTitleBuilder();
		}

		return $this->variationTitleBuilder;
	}

	private function get_variable_product_term_taxonomy_id(): int {
		$term = get_term_by( 'slug', 'variable', 'product_type' );

		return ( $term && ! is_wp_error( $term ) && ! empty( $term->term_taxonomy_id ) ) ? (int) $term->term_taxonomy_id : 0;
	}
}
