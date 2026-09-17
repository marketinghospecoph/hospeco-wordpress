<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceSingleVariations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VariationVisibilityResolver {

	public function filterParentIds( array $parentIds ): array {
		$parentIds = $this->normalizeIds( $parentIds );
		if ( empty( $parentIds ) ) {
			return [];
		}

		$excludedVariableProducts = $this->normalizeIds( (array) static::getPluginOption( 'excludeVariableProducts', [] ) );
		if ( ! empty( $excludedVariableProducts ) ) {
			$parentIds = array_values( array_diff( $parentIds, $excludedVariableProducts ) );
		}

		// The original plugin uses current archive category context. In search we do not have that context,
		// so we map category rules to the categories assigned to the variable parent product.
		$excludeProductCategories = $this->normalizeIds( (array) static::getPluginOption( 'excludeProductCategories', [] ) );
		if ( ! empty( $excludeProductCategories ) ) {
			$excludedParents = $this->getObjectIdsInCategories( $parentIds, $excludeProductCategories );
			if ( ! empty( $excludedParents ) ) {
				$parentIds = array_values( array_diff( $parentIds, $excludedParents ) );
			}
		}

		$includeProductCategories = $this->normalizeIds( (array) static::getPluginOption( 'includeProductCategories', [] ) );
		if ( ! empty( $includeProductCategories ) ) {
			$includedParents = $this->getObjectIdsInCategories( $parentIds, $includeProductCategories );
			$parentIds       = array_values( array_intersect( $parentIds, $includedParents ) );
		}

		return $parentIds;
	}

	public function filterVariationIds( array $variationIds ): array {
		$variationIds = $this->normalizeIds( $variationIds );
		if ( empty( $variationIds ) ) {
			return [];
		}

		$excludedIds = $this->getExcludedVariationIds( $variationIds );
		if ( empty( $excludedIds ) ) {
			return $variationIds;
		}

		return array_values( array_diff( $variationIds, $excludedIds ) );
	}

	public function filterSourceData( $data, bool $onlyIds ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return $data;
		}

		if ( $onlyIds ) {
			return $this->filterVariationIds( $data );
		}

		$variationIds = $this->normalizeIds( wp_list_pluck( $data, 'ID' ) );
		if ( empty( $variationIds ) ) {
			return $data;
		}

		$allowedVariationIds = array_fill_keys( $this->filterVariationIds( $variationIds ), true );

		return array_values(
			array_filter(
				$data,
				static function ( $row ) use ( $allowedVariationIds ) {
					$variationId = isset( $row['ID'] ) ? (int) $row['ID'] : 0;

					return $variationId > 0 && isset( $allowedVariationIds[ $variationId ] );
				}
			)
		);
	}

	public function filterVariableProductIds( array $productIds ): array {
		global $wpdb;

		$productIds = $this->normalizeIds( $productIds );
		if ( empty( $productIds ) ) {
			return [];
		}

		$termTaxonomyId = $this->getVariableProductTermTaxonomyId();
		if ( $termTaxonomyId === 0 ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $productIds ), '%d' ) );
		$args         = array_merge( [ $termTaxonomyId ], $productIds );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"SELECT object_id
			FROM $wpdb->term_relationships
			WHERE term_taxonomy_id = %d
			AND object_id IN ($placeholders)",
			$args
		);

		$ids = $wpdb->get_col( $sql );
		// phpcs:enable

		return $this->normalizeIds( $ids );
	}

	private static function getPluginOption( string $key, $default = null ) {
		$options = get_option( 'woocommerce_single_variations_options', [] );
		if ( ! is_array( $options ) ) {
			return $default;
		}

		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	private function getExcludedVariationIds( array $variationIds ): array {
		$variationIds = $this->normalizeIds( $variationIds );
		if ( empty( $variationIds ) ) {
			return [];
		}

		$parentMap = $this->getVariationParentMap( $variationIds );
		if ( empty( $parentMap ) ) {
			return [];
		}

		$excludedIds    = [];
		$allowedParents = array_fill_keys( $this->filterParentIds( array_values( $parentMap ) ), true );

		foreach ( $parentMap as $variationId => $parentId ) {
			if ( ! isset( $allowedParents[ $parentId ] ) ) {
				$excludedIds[] = $variationId;
			}
		}

		$excludedIds = array_merge(
			$excludedIds,
			array_intersect( $variationIds, $this->normalizeIds( (array) static::getPluginOption( 'excludeVariationProducts', [] ) ) ),
			$this->getVariationIdsExcludedByPostMetaKey( $variationIds ),
			$this->getVariationIdsExcludedByAttributes( $variationIds, $parentMap )
		);

		$excludedIds = $this->normalizeIds( $excludedIds );

		$includedVariationIds = array_intersect( $variationIds, $this->normalizeIds( (array) static::getPluginOption( 'includeVariationProducts', [] ) ) );
		if ( ! empty( $includedVariationIds ) ) {
			$excludedIds = array_values( array_diff( $excludedIds, $includedVariationIds ) );
		}

		return $excludedIds;
	}

	private function getVariationIdsExcludedByPostMetaKey( array $variationIds ): array {
		global $wpdb;

		$metaKey = (string) static::getPluginOption( 'excludeVariationsByPostMetaKey', '' );
		if ( $metaKey === '' ) {
			return [];
		}

		$excludedIds = [];
		foreach ( array_chunk( $variationIds, 500 ) as $variationIdsChunk ) {
			$placeholders = implode( ', ', array_fill( 0, count( $variationIdsChunk ), '%d' ) );
			$args         = array_merge( [ $metaKey ], $variationIdsChunk );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare(
				"SELECT DISTINCT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND post_id IN ($placeholders)",
				$args
			);

			$excludedIds = array_merge( $excludedIds, $wpdb->get_col( $sql ) );
			// phpcs:enable
		}

		return $this->normalizeIds( $excludedIds );
	}

	private function getVariationIdsExcludedByAttributes( array $variationIds, array $parentMap ): array {
		$excludedAttributes = $this->getExcludedAttributes();
		if ( empty( $excludedAttributes ) ) {
			return [];
		}

		$relevantParentIds = $this->normalizeIds( array_values( $parentMap ) );
		if ( empty( $relevantParentIds ) ) {
			return [];
		}

		$parentIdsMatchingAttributes = $this->getParentIdsMatchingExcludedAttributes( $relevantParentIds, $excludedAttributes );
		if ( empty( $parentIdsMatchingAttributes ) ) {
			return [];
		}

		$relevantVariationIds = array_fill_keys( $variationIds, true );
		$excludedVariationIds = [];

		foreach ( $parentIdsMatchingAttributes as $parentId ) {
			$orderedVariationIds = array_values(
				array_filter(
					$this->getOrderedVariationIdsForParent( $parentId ),
					static function ( $variationId ) use ( $relevantVariationIds ) {
						return isset( $relevantVariationIds[ $variationId ] );
					}
				)
			);

			if ( empty( $orderedVariationIds ) ) {
				continue;
			}

			$excludedVariationIds = array_merge( $excludedVariationIds, $orderedVariationIds );

			if ( ! empty( static::getPluginOption( 'excludedAttributesKeepFirstVariation' ) ) ) {
				$keptVariationIds     = $this->getVariationIdsKeptForExcludedAttributes( $orderedVariationIds, $excludedAttributes );
				$excludedVariationIds = array_values( array_diff( $excludedVariationIds, $keptVariationIds ) );
			}
		}

		return $this->normalizeIds( $excludedVariationIds );
	}

	private function getParentIdsMatchingExcludedAttributes( array $parentIds, array $excludedAttributes ): array {
		$attributeTaxQuery = [
			'relation' => (string) static::getPluginOption( 'excludedAttributesRelation', 'OR' ),
		];

		foreach ( $excludedAttributes as $excludedAttribute ) {
			if ( ! taxonomy_exists( $excludedAttribute ) ) {
				continue;
			}

			$termIds = get_terms(
				[
					'taxonomy'   => $excludedAttribute,
					'fields'     => 'ids',
					'hide_empty' => false,
				]
			);

			if ( is_wp_error( $termIds ) || empty( $termIds ) ) {
				continue;
			}

			$attributeTaxQuery[] = [
				'taxonomy' => $excludedAttribute,
				'field'    => 'term_id',
				'terms'    => $termIds,
				'operator' => 'IN',
			];
		}

		if ( count( $attributeTaxQuery ) === 1 ) {
			return [];
		}

		$query = new \WP_Query(
			[
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__in'       => $parentIds,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => [
					[
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => [ 'variable' ],
					],
					$attributeTaxQuery,
				],
			]
		);

		return $this->normalizeIds( $query->posts );
	}

	private function getOrderedVariationIdsForParent( int $parentId ): array {
		$order   = (string) static::getPluginOption( 'order', 'DESC' );
		$orderby = (string) static::getPluginOption( 'orderby', '_price' );

		$args = [
			'post_parent'    => $parentId,
			'post_status'    => 'publish',
			'post_type'      => 'product_variation',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'order'          => $order === 'ASC' ? 'ASC' : 'DESC',
			'orderby'        => $orderby,
		];

		if ( $orderby === '_price' ) {
			$args['meta_key'] = '_price';
			$args['orderby']  = 'meta_value_num';
		}

		return $this->normalizeIds( get_posts( $args ) );
	}

	private function getVariationIdsKeptForExcludedAttributes( array $variationIds, array $excludedAttributes ): array {
		$keptVariationIds       = [];
		$keptVariationIdsByHash = [];
		$foundOneAttribute      = false;
		$maxVariations          = max( 1, (int) static::getPluginOption( 'excludedMaxVariations', 999 ) );
		$keepOneAttribute       = ! empty( static::getPluginOption( 'excludedAttributesKeepOneAttributeProducts' ) );
		$keepOnStock            = ! empty( static::getPluginOption( 'excludedAttributesKeepOnStock' ) );
		$i                      = 0;

		foreach ( $variationIds as $variationId ) {
			$variationProduct = wc_get_product( $variationId );
			if ( ! is_a( $variationProduct, 'WC_Product_Variation' ) ) {
				continue;
			}

			if ( $keepOnStock && ! $variationProduct->is_in_stock() ) {
				continue;
			}

			$variationAttributes = $variationProduct->get_attributes();
			if ( empty( $variationAttributes ) ) {
				continue;
			}

			$variationIdentifier = '';
			foreach ( $variationAttributes as $variationAttributeKey => $variationAttributeValue ) {
				if ( in_array( $variationAttributeKey, $excludedAttributes, true ) ) {
					if ( $keepOneAttribute && count( $variationAttributes ) === 1 && ! $foundOneAttribute ) {
						$keptVariationIds[] = $variationId;
						$foundOneAttribute  = true;
					}

					continue;
				}

				$variationIdentifier .= $variationAttributeKey . $variationAttributeValue;
			}

			if ( $i >= $maxVariations ) {
				break;
			}

			$keptVariationIdsByHash[ $variationIdentifier ] = $variationId;
			$i++;
		}

		return $this->normalizeIds( array_merge( $keptVariationIds, array_values( $keptVariationIdsByHash ) ) );
	}

	private function getObjectIdsInCategories( array $objectIds, array $categoryIds ): array {
		global $wpdb;

		$objectIds   = $this->normalizeIds( $objectIds );
		$categoryIds = $this->normalizeIds( $categoryIds );

		if ( empty( $objectIds ) || empty( $categoryIds ) ) {
			return [];
		}

		$matchedIds           = [];
		$categoryPlaceholders = implode( ', ', array_fill( 0, count( $categoryIds ), '%d' ) );

		foreach ( array_chunk( $objectIds, 500 ) as $objectIdsChunk ) {
			$objectPlaceholders = implode( ', ', array_fill( 0, count( $objectIdsChunk ), '%d' ) );
			$args               = array_merge( $categoryIds, $objectIdsChunk );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare(
				"SELECT DISTINCT tr.object_id
				FROM $wpdb->term_relationships tr
				INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tt.taxonomy = 'product_cat'
				AND tt.term_id IN ($categoryPlaceholders)
				AND tr.object_id IN ($objectPlaceholders)",
				$args
			);

			$matchedIds = array_merge( $matchedIds, $wpdb->get_col( $sql ) );
			// phpcs:enable
		}

		return $this->normalizeIds( $matchedIds );
	}

	private function getVariationParentMap( array $variationIds ): array {
		global $wpdb;

		$variationIds = $this->normalizeIds( $variationIds );
		if ( empty( $variationIds ) ) {
			return [];
		}

		$map = [];
		foreach ( array_chunk( $variationIds, 500 ) as $variationIdsChunk ) {
			$placeholders = implode( ', ', array_fill( 0, count( $variationIdsChunk ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare(
				"SELECT ID, post_parent
				FROM $wpdb->posts
				WHERE post_type = 'product_variation'
				AND post_parent > 0
				AND ID IN ($placeholders)",
				$variationIdsChunk
			);

			$rows = $wpdb->get_results( $sql, ARRAY_A );
			// phpcs:enable
			if ( empty( $rows ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				$variationId = isset( $row['ID'] ) ? (int) $row['ID'] : 0;
				$parentId    = isset( $row['post_parent'] ) ? (int) $row['post_parent'] : 0;

				if ( $variationId > 0 && $parentId > 0 ) {
					$map[ $variationId ] = $parentId;
				}
			}
		}

		return $map;
	}

	private function getExcludedAttributes(): array {
		$excludedAttributes = static::getPluginOption( 'excludedAttributes', [] );
		$excludedAttributes = is_array( $excludedAttributes ) ? $excludedAttributes : [];
		$excludedAttributes = apply_filters( 'woocommerce_single_variations_excluded_attributes', $excludedAttributes );

		return array_values(
			array_filter(
				array_map(
					static function ( $taxonomy ) {
						return sanitize_key( (string) $taxonomy );
					},
					$excludedAttributes
				)
			)
		);
	}

	private function normalizeIds( array $ids ): array {
		return array_values( array_unique( wp_parse_id_list( $ids ) ) );
	}

	private function getVariableProductTermTaxonomyId(): int {
		$term = get_term_by( 'slug', 'variable', 'product_type' );

		return ( $term && ! is_wp_error( $term ) && ! empty( $term->term_taxonomy_id ) ) ? (int) $term->term_taxonomy_id : 0;
	}
}
