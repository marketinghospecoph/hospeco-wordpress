<?php

namespace DgoraWcas;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Product {
	protected $productID = 0;
	/**
	 * @var bool|false|\WC_Product|null
	 */
	protected $wcProduct           = null;
	protected $langCode            = 'en';
	private $variations            = [];
	private $indexingFailureReason = '';

	public function __construct( $product ) {
		if ( ! empty( $product ) && is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			$this->productID = $product->get_id();
			$this->wcProduct = $product;
		}

		if ( ! empty( $product ) && is_object( $product ) && is_a( $product, 'WP_Post' ) ) {
			$this->productID = absint( $product->ID );
			$this->wcProduct = wc_get_product( $product );
		}

		if ( is_numeric( $product ) && 'product' === get_post_type( $product ) ) {
			$this->productID = absint( $product );
			$this->wcProduct = wc_get_product( $product );
		}

		$this->setLanguage();
	}

	/**
	 * Set info about product language
	 *
	 * @return void
	 */
	public function setLanguage() {
		$this->langCode = Multilingual::getPostLang( $this->getID() );
	}

	/**
	 * Get product ID (post_id)
	 *
	 * @return INT
	 */
	public function getID() {
		return $this->productID;
	}

	/**
	 * Get created date
	 *
	 * @return mixed
	 */
	public function getCreatedDate() {
		$date = $this->wcProduct->get_date_created();
		if ( ! $date ) {
			$date = '0000-00-00 00:00:00';
		}

		return $date;
	}

	/**
	 * Get product name
	 *
	 * @return string
	 */
	public function getName() {
		return apply_filters( 'dgwt/wcas/product/name', $this->wcProduct->get_name() );
	}

	/**
	 * Get prepared product description
	 *
	 * @param string $type full|short|suggestions|details-panel
	 * @param int $forceWordsLimit
	 *
	 * @return string
	 */
	public function getDescription( $type = 'full', $forceWordsLimit = 0 ) {

		$output = '';

		if ( $type === 'full' ) {
			$output = $this->wcProduct->get_description();
		}

		if ( $type === 'short' ) {
			$output = $this->wcProduct->get_short_description();
		}

		if ( $type === 'suggestions' || $type === 'details-panel' ) {

			$desc = $this->wcProduct->get_short_description();

			if ( empty( $desc ) ) {
				$desc = $this->wcProduct->get_description();
			}

			if ( ! empty( $desc ) ) {

				if ( $type === 'details-panel' ) {
					$wordsLimit = $forceWordsLimit > 0 ? $forceWordsLimit : 20;
					$output     = Helpers::makeShortDescription( $desc, $wordsLimit, '<br><b><strong>', false );
				} elseif ( $type === 'suggestions' ) {
					$wordsLimit = $forceWordsLimit > 0 ? $forceWordsLimit : 30;
					$output     = Helpers::makeShortDescription( $desc, $wordsLimit, '' );
				}
			}
		}

		return apply_filters( 'dgwt/wcas/product/description', $output, $type, $this->productID, $this );
	}

	/**
	 * Get product permalink
	 *
	 * @return string
	 */
	public function getPermalink() {
		$permalink = $this->wcProduct->get_permalink();

		return apply_filters( 'dgwt/wcas/product/permalink', $permalink, $this->productID, $this );
	}

	/**
	 * Get product thumbnail url
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function getThumbnailSrc( $size = '' ) {
		$src  = '';
		$size = empty( $size ) ? DGWT_WCAS()->setup->getThumbnailSize() : $size;

		$imageID = $this->wcProduct->get_image_id();

		if ( ! empty( $imageID ) ) {
			$imageSrc = wp_get_attachment_image_src( $imageID, $size );

			if ( is_array( $imageSrc ) && ! empty( $imageSrc[0] ) ) {
				$src = $imageSrc[0];
			}
		}

		if ( empty( $src ) ) {
			$src = wc_placeholder_img_src();
		}

		return apply_filters( 'dgwt/wcas/product/thumbnail_src', $src, $this->productID, $this );
	}

	/**
	 * Get product thumbnail
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function getThumbnail( $size = '' ) {
		return '<img src="' . $this->getThumbnailSrc( $size ) . '" alt="' . wp_strip_all_tags( $this->getName(), true ) . '" />';
	}

	/**
	 * Get product thumbnail srcset
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function getThumbnailSrcset( $size = '' ) {
		$size    = empty( $size ) ? DGWT_WCAS()->setup->getThumbnailSize() : $size;
		$imageID = $this->wcProduct->get_image_id();

		$srcset = function_exists( 'wp_get_attachment_image_srcset' ) ? (string) wp_get_attachment_image_srcset( $imageID, $size ) : '';

		return apply_filters( 'dgwt/wcas/product/thumbnail_srcset', $srcset, $this->productID, $this );
	}

	/**
	 * Get product thumbnail sizes (for srcset)
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function getThumbnailSizes( $size = '' ) {
		$size    = empty( $size ) ? DGWT_WCAS()->setup->getThumbnailSize() : $size;
		$imageID = $this->wcProduct->get_image_id();

		$sizes = function_exists( 'wp_get_attachment_image_sizes' ) ? (string) wp_get_attachment_image_sizes( $imageID, $size ) : false;

		return apply_filters( 'dgwt/wcas/product/thumbnail_sizes', $sizes, $this->productID, $this );
	}

	/**
	 * Get HTML code with the product price
	 *
	 * @return string
	 */
	public function getPriceHTML() {

		$price = $this->wcProduct->get_price_html();

		if ( ! empty( $price ) && is_string( $price ) ) {
			$price = preg_replace( '/(?<=\d)\s+(?=\d|\-)/', '&nbsp;', $price );
		}

		return (string) apply_filters( 'dgwt/wcas/product/html_price', $price, $this->productID, $this );
	}

	/**
	 * Get price
	 *
	 * @return string
	 */
	public function getPrice() {
		return (string) $this->wcProduct->get_price();
	}

	/**
	 * Get average rating
	 *
	 * @return float
	 */
	public function getAverageRating() {
		return (float) $this->wcProduct->get_average_rating();
	}

	/**
	 * Get review count
	 *
	 * @return int
	 */
	public function getReviewCount() {
		return (int) $this->wcProduct->get_review_count();
	}

	/**
	 * Get rating HTML
	 *
	 * @return string
	 */
	public function getRatingHtml() {
		return (string) wc_get_rating_html( $this->getAverageRating() );
	}


	/**
	 * Get total sales
	 *
	 * @return int
	 */
	public function getTotalSales() {
		return (int) $this->wcProduct->get_total_sales();
	}

	/**
	 * Get SKU
	 *
	 * @return string
	 */
	public function getSKU() {
		return (string) apply_filters( 'dgwt/wcas/product/sku', $this->wcProduct->get_sku(), $this->productID, $this );
	}

	/**
	 * Get Global Unique ID
	 *
	 * @return string
	 */
	public function getGlobalUniqueId() {
		return (string) apply_filters( 'dgwt/wcas/product/global_unique_id', method_exists( $this->wcProduct, 'get_global_unique_id' ) ? $this->wcProduct->get_global_unique_id() : '', $this->productID, $this );
	}

	/**
	 * Get available variations
	 *
	 * @return array
	 */
	public function getAvailableVariations() {
		global $wpdb;

		if ( empty( $this->variations ) && is_a( $this->wcProduct, 'WC_Product_Variable' ) ) {
			$sql = $wpdb->prepare(
				"
				SELECT {$wpdb->posts}.ID AS variation_id, postmeta.meta_value AS variation_description, wc_product_meta_lookup.sku
				FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id
				LEFT JOIN {$wpdb->postmeta} postmeta ON {$wpdb->posts}.ID = postmeta.post_id AND postmeta.meta_key = '_variation_description'
				WHERE {$wpdb->posts}.post_parent = %d
				AND {$wpdb->posts}.post_status = 'publish'
			",
				$this->getID()
			);

			if ( apply_filters( 'dgwt/wcas/indexer/include_variations_with_zero_price', true ) === false ) {
				$sql .= ' AND wc_product_meta_lookup.min_price > 0';
			}

			if ( DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on' ) {
				$sql .= " AND wc_product_meta_lookup.stock_status <> 'outofstock'";
			}
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->get_results( $sql, ARRAY_A );
			if ( is_array( $result ) ) {
				$result = array_map(
					function ( $item ) {
						$item['variation_id'] = intval( $item['variation_id'] );

						return $item;
					},
					$result
				);
			}

			$this->variations = $result;

			return $this->variations;
		}

		return $this->variations;
	}

	/**
	 * Get all SKUs for variations
	 *
	 * @return array
	 */
	public function getVariationsSKUs() {
		$skus = [];

		$variations = $this->getAvailableVariations();

		foreach ( $variations as $variation ) {

			if ( is_array( $variation ) && ! empty( $variation['sku'] ) ) {
				$skus[] = sanitize_text_field( $variation['sku'] );
			}
		}

		return apply_filters( 'dgwt/wcas/product/variations_skus', $skus, $this->productID, $this );
	}

	/**
	 * Get description of all product variations
	 *
	 * @return array
	 */
	public function getVariationsDescriptions() {

		$descriptions = [];

		$variations = $this->getAvailableVariations();

		foreach ( $variations as $variation ) {

			if ( is_array( $variation ) && ! empty( $variation['variation_description'] ) ) {
				$descriptions[] = sanitize_text_field( $variation['variation_description'] );
			}
		}

		return $descriptions;
	}


	/**
	 * Get attributes
	 *
	 * @param bool $onlyNames
	 *
	 * @return array
	 */
	public function getAttributes( $onlyNames = false ) {
		$terms      = [];
		$attributes = apply_filters( 'dgwt/wcas/product/attributes', $this->wcProduct->get_attributes(), $this->productID, $this );

		foreach ( $attributes as $attribute ) {

			if ( $onlyNames ) {

				if ( $attribute->is_taxonomy() ) {

					// Global attributes
					$attrTerms = $attribute->get_terms();
					if ( ! empty( $attrTerms ) && is_array( $attrTerms ) ) {
						foreach ( $attrTerms as $attrTerm ) {
							$terms[] = $attrTerm->name;
						}
					}
				} else {

					// Custom attributes
					$attrOptions = $attribute->get_options();
					if ( ! empty( $attrOptions ) && is_array( $attrOptions ) ) {
						// Use external static method for keeping always the same filters = self::getCustomAttributes($productID);
						$customAttributesValues = self::getCustomAttributes( $this->productID );
						if ( ! empty( $customAttributesValues ) ) {
							$terms = array_merge( $terms, $customAttributesValues );
						}
					}
				}
				//phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
			} else {
				// @TODO future use
			}
		}

		return apply_filters( 'dgwt/wcas/product/attribute_terms', $terms, $this->productID, $this );
	}

	/**
	 * Get product language
	 *
	 * @return string
	 */
	public function getLanguage() {
		return $this->langCode;
	}

	/**
	 * Get custom field value
	 *
	 * @param string $metaKey
	 *
	 * @return string
	 */
	public function getCustomField( $metaKey ) {
		$result = apply_filters( 'dgwt/wcas/product/custom_field', false, $metaKey, $this->productID );
		if ( $result !== false ) {
			return $result;
		}

		return get_post_meta( $this->productID, sanitize_key( $metaKey ), true );
	}

	/**
	 * Get brand name
	 *
	 * @return string
	 */
	public function getBrand() {
		$brand    = '';
		$taxonomy = DGWT_WCAS()->brands->getBrandTaxonomy();

		if ( ! empty( $taxonomy ) ) {

			$terms = get_the_terms( $this->productID, $taxonomy );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$brand = ! empty( $terms[0]->name ) ? $terms[0]->name : '';
			}
		}

		return $brand;
	}

	/**
	 * Get terms form specific taxonomy
	 *
	 * @param string $taxonomy
	 * @param string $format Output format
	 *
	 * @return string|array
	 */
	public function getTerms( $taxonomy = 'product_cat', $format = 'array' ) {
		$items = [];

		if ( ! empty( $taxonomy ) ) {

			$terms = get_the_terms( $this->productID, $taxonomy );

			if ( ! empty( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! empty( $term->name ) ) {

						if ( apply_filters( 'dgwt/wcas/tnt/source_query/term_description', false ) && ! empty( $term->description ) ) {
							$items[] = $term->name . ' | ' . $term->description;
						} else {
							$items[] = $term->name;
						}
					}
				}
			}
		}

		return $format === 'string' ? implode( ' | ', $items ) : $items;
	}

	/**
	 * Check, if class is initialized correctly
	 *
	 * @return bool
	 */
	public function isValid() {
		$isValid = false;

		if ( is_a( $this->wcProduct, 'WC_Product' ) ) {
			$isValid = true;
		}

		return $isValid;
	}

	/**
	 * WooCommerce raw product object
	 *
	 * @return \WC_Product
	 */
	public function getWooObject() {
		return $this->wcProduct;
	}

	/**
	 * Get custom attributes
	 *
	 * For external use
	 *
	 * @param int productID
	 *
	 * @return array
	 */
	public static function getCustomAttributes( $productID ) {
		global $wpdb;

		$terms = [];

		$sql = $wpdb->prepare(
			"SELECT meta_value
                                      FROM $wpdb->postmeta
                                      WHERE post_id = %d
                                      AND meta_key = '_product_attributes'
                                     ",
			$productID
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$optValue = $wpdb->get_var( $sql );

		if ( ! empty( $optValue ) && strpos( $optValue, 'a:' ) === 0 ) {
			// TODO
			//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$rawAttributes = unserialize( $optValue );

			if ( is_array( $rawAttributes ) && ! empty( $rawAttributes ) ) {

				$rawAttributes = apply_filters( 'dgwt/wcas/product/custom_attributes', $rawAttributes, $productID );

				foreach ( $rawAttributes as $rawAttribute ) {
					if ( is_array( $rawAttribute ) && array_key_exists( 'is_taxonomy', $rawAttribute ) && $rawAttribute['is_taxonomy'] == 0 && ! empty( $rawAttribute['value'] ) ) {
						$partTerms = explode( ' | ', $rawAttribute['value'] );

						$terms = array_merge( $terms, $partTerms );
					}
				}
			}
		}

		return $terms;
	}

	/**
	 * Get product currency
	 *
	 * @return string
	 */
	public function getCurrency() {

		$currency = get_woocommerce_currency();

		if ( Multilingual::isMultilingual() ) {
			$currencyByLang = Multilingual::getCurrencyForLang( $this->getLanguage() );
			if ( ! empty( $currencyByLang ) ) {
				$currency = $currencyByLang;
			}
		}

		return $currency;
	}

	/**
	 * Stock quantity message
	 *
	 * @return string
	 */
	public function getStockAvailability() {
		$html = '';

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && $this->wcProduct->get_manage_stock() ) {
			$data = $this->wcProduct->get_availability();
			if ( ! empty( $data ) && is_array( $data ) ) {
				$text  = ! empty( $data['availability'] ) ? $data['availability'] : '';
				$class = ! empty( $data['class'] ) ? ' ' . $data['class'] : '';

				if ( $text ) {
					$html .= '<span class="dgwt-wcas-stock' . $class . '">';
					$html .= $text;
					$html .= '</span>';
				}
			}
		}

		return $html;
	}

	/**
	 * Check product type
	 *
	 * @return bool
	 */
	public function isType( $type ) {
		return $this->wcProduct->is_type( $type );
	}

	/**
	 * Check if the object was initialized properly
	 *
	 * @return bool
	 */
	public function isCorrect() {
		$correct = true;

		if ( empty( $this->wcProduct ) ) {
			$correct = false;
		}

		return $correct;
	}

	/**
	 * Check if the product is published and visible in catalog/search.
	 *
	 * @param string $context Context name (e.g. 'indexer', 'details-panel').
	 *
	 * @return bool
	 */
	public function isPublishedAndVisible( string $context = 'indexer' ): bool {
		if ( ! $this->isValid() ) {
			return false;
		}

		$woo        = $this->getWooObject();
		$status     = $woo->get_status();
		$visibility = $woo->get_catalog_visibility();

		if ( $status !== 'publish' ) {
			if ( $context === 'indexer' ) {
				$this->indexingFailureReason = 'Product is not published.';
			}

			return false;
		}

		if ( ! in_array( $visibility, [ 'visible', 'search' ], true ) ) {
			if ( $context === 'indexer' ) {
				$this->indexingFailureReason = 'Product is not visible in catalog or search.';
			}

			return false;
		}

		return true;
	}

	/**
	 * Determine whether the current user can access product details in the details panel.
	 *
	 * Public users can only access published products visible in catalog/search.
	 * Non-public products (draft/private/pending/future) require 'read_post' capability.
	 * Hidden published products require stronger permissions ('edit_post').
	 *
	 * @param bool $checkStockStatus Whether to respect the "exclude out of stock" setting.
	 * @param string $context Method call context.
	 *
	 * @return bool
	 */
	public function canUserSee( bool $checkStockStatus = true, string $context = '' ): bool {
		if ( ! $this->isValid() ) {
			return false;
		}

		$woo    = $this->getWooObject();
		$postId = $this->getID();

		$canShow = $this->isPublishedAndVisible( $context );

		// Non-public products: allow only authorized users.
		if ( ! $canShow ) {
			$status = $woo->get_status();

			if ( $status !== 'publish' ) {
				$canShow = current_user_can( 'read_post', $postId );
			} else {
				// Published but hidden from catalog/search.
				$canShow = current_user_can( 'edit_post', $postId );
			}
		}

		// Respect "exclude out of stock" setting for public users.
		if (
			$canShow
			&& $checkStockStatus
			&& DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on'
			&& ! $woo->is_in_stock()
			&& ! current_user_can( 'edit_post', $postId )
		) {
			$canShow = false;
		}

		// Do not reveal password-protected content to public users.
		$post = get_post( $postId );

		if ( $canShow && $post instanceof \WP_Post && post_password_required( $post ) && ! current_user_can( 'edit_post', $postId ) ) {
			$canShow = false;
		}

		return (bool) apply_filters( 'dgwt/wcas/product/can_show_in_details_panel', $canShow, $this );
	}


	/**
	 * Check if the parent product (Variable product) can be indexed.
	 * If there aren't available variations, the product shouldn't be indexed.
	 *
	 * @return bool
	 */
	public function canIndexParent() {
		$canIndex = true;
		if ( $this->getWooObject()->get_type() === 'variable' && empty( $this->getAvailableVariations() ) ) {
			$canIndex = false;
		}

		return apply_filters( 'dgwt/wcas/can_index_variable_parent', $canIndex, $this );
	}

	/**
	 * Check if the product can be indexed
	 *
	 * @return bool
	 */
	public function canIndex__premium_only() {
		$canIndex          = true;
		$excludeOutOfStock = DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on';

		if ( ! $this->isValid() ) {
			$this->indexingFailureReason = 'Product is not valid.';

			return false;
		}

		// Don't index if a product is out of stock and should be excluded.
		if ( $excludeOutOfStock && ! $this->getWooObject()->is_in_stock() ) {
			$this->indexingFailureReason = 'Product is out of stock and "exclude out of stock" option is enabled.';

			return false;
		}

		// Don't index variable product if variations aren't available.
		if ( ! $this->canIndexParent() ) {
			$this->indexingFailureReason = 'Parent product cannot be indexed.';

			return false;
		}

		// Index or not index the product has depend on filters and filter mode
		$rules = Helpers::getFilterProductsRules__premium_only();
		if ( ! empty( $rules ) ) {
			$filterMode = DGWT_WCAS()->settings->getOption( 'filter_products_mode', 'exclude' );
			$langs      = [];

			// In "include" mode, products are excluded by default and will only be included if they are matched by term
			if ( $filterMode === 'include' ) {
				$this->indexingFailureReason = 'Filter mode is set to INCLUDE and product does not match any rule.';
				$canIndex                    = false;
			}

			if ( Multilingual::isMultilingual() ) {
				// Others languages than default
				$langs = array_values( array_diff( Multilingual::getLanguages(), [ Multilingual::getDefaultLanguage() ] ) );
			}

			foreach ( $rules as $group => $values ) {
				$matchedTerms = Helpers::getFilterGroupTerms__premium_only( $group, $values );
				if ( ! empty( $matchedTerms ) ) {
					$excludedTermIds = wp_list_pluck( $matchedTerms, 'term_taxonomy_id' );
					$taxonomy        = Helpers::getTaxonomyFromFilterGroup__premium_only( $group );
					$productTerms    = wc_get_product_terms( $this->getID(), $taxonomy );

					if ( empty( $productTerms ) ) {
						continue;
					}

					$productTermIds = wp_list_pluck( $productTerms, 'term_taxonomy_id' );
					if ( ! empty( array_intersect( $productTermIds, $excludedTermIds ) ) ) {
						if ( $filterMode === 'exclude' ) {
							$this->indexingFailureReason = "Product is assigned to an excluded term in taxonomy '$taxonomy'.";
							$canIndex                    = false;
						} else {
							$canIndex = true;
						}
						break;
					}

					// Check whether the product has assigned a translation of the excluded terms
					if ( ! empty( $langs ) ) {
						foreach ( $langs as $lang ) {
							foreach ( $matchedTerms as $term ) {
								$termTranslated = Multilingual::getTerm( $term->term_id, $taxonomy, $lang );
								if ( ! empty( $termTranslated ) && in_array( $termTranslated->term_taxonomy_id, $productTermIds ) ) {
									if ( $filterMode === 'exclude' ) {
										$this->indexingFailureReason = "Product is assigned to a translated excluded term in language '$lang'.";
										$canIndex                    = false;
									} else {
										$canIndex = true;
									}
									break 3;
								}
							}
						}
					}
				}
			}
		}

		return $canIndex;
	}

	/**
	 * Get indexing status and failure reason if product cannot be indexed.
	 *
	 * @return array{canIndex: bool, reason: string|null}
	 */
	public function checkIndexability__premium_only() {
		$canIndex              = $this->canIndex__premium_only();
		$isPublishedAndVisible = $this->isPublishedAndVisible();

		return [
			'canIndex'          => $canIndex && $isPublishedAndVisible,
			'notIndexingReason' => $this->indexingFailureReason,
		];
	}

	/**
	 * Get information about a catalag visibility based on WooCommerce CRUD.
	 *
	 * @return string
	 */
	public function getVisibilityByCRUD__premium_only() {
		$output  = [
			'label' => '',
			'key'   => '',
		];
		$options = [
			'visible' => 'Shop and search results',
			'catalog' => 'Shop only',
			'search'  => 'Search results only',
			'hidden'  => 'Hidden',
		];
		$key     = $this->getWooObject()->get_catalog_visibility();

		if ( array_key_exists( $key, $options ) ) {
			$output['label'] = $options[ $key ];
			$output['key']   = $key;
		}

		return $output;
	}

	/**
	 * Get terms IDs that are related to visibility and are assigned to this product.
	 *
	 * @param string $type Visibility terms for catalog visibility or "out of stock" visibility.
	 *                     catalog_visibility, stock_status
	 *
	 * @return array
	 */
	public function getVisibilityByTerms__premium_only( $type = 'catalog_visibility' ) {
		global $wpdb;
		$output           = [];
		$visibility_terms = wc_get_product_visibility_term_ids();
		$termIDs          = [];

		$fields = [ 'exclude-from-catalog', 'exclude-from-search' ];
		if ( $type === 'stock_status' ) {
			$fields = [ 'outofstock' ];
		}

		foreach ( $visibility_terms as $key => $term_id ) {
			if ( in_array( $key, $fields ) ) {
				$termIDs[] = $term_id;
			}
		}

		$placeholders = array_fill( 0, count( $termIDs ), '%d' );
		$format       = implode( ', ', $placeholders );

		$params = $termIDs;
		array_unshift( $params, $this->getID() );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"SELECT term_taxonomy_id
									  FROM $wpdb->term_relationships
									  WHERE object_id = %d
									  AND term_taxonomy_id IN($format)",
			$params
		);

		$res = $wpdb->get_col( $sql );
		// phpcs:enable

		if ( ! empty( $res ) ) {
			foreach ( $res as $termID ) {
				$termID = (int) $termID;
				$label  = array_search( $termID, $visibility_terms, true );
				if ( $label !== false ) {
					$output[ $label ] = $termID;
				}
			}
		}

		return $output;
	}
}
