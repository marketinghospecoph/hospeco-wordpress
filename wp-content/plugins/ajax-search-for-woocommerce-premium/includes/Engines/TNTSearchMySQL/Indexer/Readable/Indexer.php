<?php
namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Readable;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\BackgroundProductUpdater;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Logger;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDB;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\WPDBException;
use DgoraWcas\Multilingual;
use DgoraWcas\Post;
use DgoraWcas\Product;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder;
use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\ProductVariation;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Indexer {

	/**
	 * @var Product|ProductVariation
	 */
	private $product;

	/**
	 * @var Post
	 */
	private $post;

	/**
	 * Insert post to the index
	 *
	 * @param int $postID Post ID.
	 * @param bool $updateVariations Update variations.
	 * @param bool $directVariations Index variations directly.
	 * @param string $indexRole
	 *
	 * @return bool true on success
	 * @throws WPDBException
	 */
	public function insert( $postID, $updateVariations = false, $directVariations = false, $indexRole = '' ) {
		global $wpdb;

		if ( empty( $indexRole ) ) {
			$indexRole = Config::getIndexRole();
		}

		$success  = false;
		$postType = get_post_type( $postID );

		if ( $postType === 'product' ) {
			$this->product = new Product( $postID );

			// Abort if it's a variable product and it doesn't have visible variations.
			if ( ! $this->product->canIndexParent() ) {
				return false;
			}

			// Support for multilingual
			if ( Multilingual::isMultilingual() ) {
				$lang = $this->product->getLanguage();
				// Abort if the product hasn't a language.
				if ( empty( $lang ) ) {
					return false;
				}
				// Abort if the product has a language that is not present in the settings.
				if ( ! in_array( $lang, Multilingual::getLanguages() ) ) {
					return false;
				}

				if ( $lang !== Multilingual::getCurrentLanguage() ) {
					Multilingual::switchLanguage( $lang );
				}

				if ( Multilingual::isMultiCurrency() ) {
					Multilingual::setCurrentCurrency( $this->product->getCurrency() );
				}
			}

			$data = $this->getProductData();

			// This will be used only if product is updated in background process.
			$variationSupportModes = Builder::getInfo( 'variation_support_modes' );
			if (
				$updateVariations &&
				$this->product->isType( 'variable' ) &&
				is_array( $variationSupportModes ) &&
				(
					in_array( 'exact_match', $variationSupportModes ) ||
					in_array( 'as_single_product', $variationSupportModes )
				)
			) {
				$this->enqueueOrProcessVariationsUpdate( $directVariations );
			}
		} elseif ( $postType === 'product_variation' ) {
			$this->product = new ProductVariation( $postID );

			if ( ! $this->product->isValid() ) {
				return false;
			}

			$variationSupportModes = Builder::getInfo( 'variation_support_modes' );
			if ( is_array( $variationSupportModes ) && in_array( 'exact_match', $variationSupportModes ) && empty( $this->product->getSKU() ) ) {
				return false;
			}

			if ( ! $this->product->canIndex__premium_only() ) {
				return false;
			}

			// Empty variation SKU? return
			if ( is_array( $variationSupportModes ) && in_array( 'exact_match', $variationSupportModes ) && ! empty( $this->product->getParentSKU() ) && $this->product->getParentSKU() === $this->product->getSKU() ) {
				return false;
			}

			// Support for multilingual
			if ( Multilingual::isMultilingual() ) {
				$lang = $this->product->getLanguage();
				// Abort if the product hasn't a language.
				if ( empty( $lang ) ) {
					return false;
				}
				// Abort if the product has a language that is not present in the settings.
				if ( ! in_array( $lang, Multilingual::getLanguages() ) ) {
					return false;
				}

				if ( $lang !== Multilingual::getCurrentLanguage() ) {
					Multilingual::switchLanguage( $lang );
				}

				if ( Multilingual::isMultiCurrency() ) {
					Multilingual::setCurrentCurrency( $this->product->getCurrency() );
				}
			}

			$data = $this->getProductVariationData();
		} else {
			$this->post = new Post( $postID );

			// Support for multilingual
			if ( Multilingual::isMultilingual() ) {
				$lang = $this->post->getLanguage();
				// Abort if the post hasn't a language.
				if ( empty( $lang ) ) {
					return false;
				}
				// Abort if the post has a language that is not present in the settings.
				if ( ! in_array( $lang, Multilingual::getLanguages() ) ) {
					return false;
				}

				if ( $lang !== Multilingual::getCurrentLanguage() ) {
					Multilingual::switchLanguage( $lang );
				}
			}

			$data = $this->getNonProductData();
		}

		$dataFiltered = apply_filters( 'dgwt/wcas/readable_index/insert', $data, $postID, $postType );

		if ( isset( $dataFiltered['meta'] ) ) {
			$dataFiltered['meta'] = maybe_serialize( $dataFiltered['meta'] );
		}

		$indexRoleSuffix = $indexRole === 'main' ? '' : '_tmp';

		if ( ! empty( $dataFiltered ) ) {
			$rows = WPDB::get_instance()->insert(
				$wpdb->dgwt_wcas_index . $indexRoleSuffix,
				$dataFiltered,
				[
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%f',
					'%f',
					'%d',
					'%d',
					'%s',
				]
			);

			if ( is_numeric( $rows ) ) {
				$success = true;
			}
		}

		do_action( 'dgwt/wcas/readable_index/after_insert', $dataFiltered, $postID, $postType, $success, $data, $indexRole );

		return $success;
	}

	/**
	 * Get product data to save
	 *
	 * @return array
	 */
	private function getProductData() {
		$data = [];

		if ( is_object( $this->product ) && $this->product->isValid() ) {

			$wordsLimit = - 1;
			if ( DGWT_WCAS()->settings->getOption( 'show_details_box' ) === 'on' ) {
				$wordsLimit = 15;
			}

			$data = [
				'post_id'           => $this->product->getID(),
				'post_or_parent_id' => $this->product->getID(),
				'created_date'      => get_post_field( 'post_date', $this->product->getID(), 'raw' ),
				'name'              => $this->product->getName(),
				'description'       => $this->product->getDescription( 'suggestions', $wordsLimit ),
				'type'              => 'product',
				'sku'               => $this->product->getSKU(),
				'sku_variations'    => '',
				'global_unique_id'  => $this->product->getGlobalUniqueId(),
				'attributes'        => '',
				'meta'              => [],
				'image'             => (string) $this->product->getThumbnailSrc(),
				'url'               => $this->product->getPermalink(),
				'html_price'        => apply_filters( 'dgwt/wcas/indexer/readable/index_html_price', true ) ? $this->product->getPriceHTML() : '',
				'price'             => $this->product->getPrice(),
				'average_rating'    => $this->product->getAverageRating(),
				'review_count'      => $this->product->getReviewCount(),
				'total_sales'       => $this->product->getTotalSales(),
				'lang'              => $this->product->getLanguage(),
			];

			if ( apply_filters( 'dgwt/wcas/tnt/indexer/readable/process_sku_variations', true ) ) {
				$data['sku_variations'] = implode( '|', $this->product->getVariationsSKUs() );
			}

			if ( apply_filters( 'dgwt/wcas/tnt/indexer/readable/process_attributes', true ) ) {
				$data['attributes'] = implode( '|', $this->product->getAttributes( true ) );
			}

			$data = apply_filters( 'dgwt/wcas/tnt/indexer/readable/product/data', $data, $this->product->getID(), $this->product );
		}

		return $data;
	}

	/**
	 * Get product variation data to save
	 *
	 * @return array
	 */
	private function getProductVariationData() {
		// Title of variation = name of it's parent
		$variationAttrs = (string) wc_get_formatted_variation( $this->product->getWooObject(), true, false, false );
		$title          = (string) $this->product->getWooObject()->get_title();

		if ( ! empty( $variationAttrs ) ) {
			$title .= ', ' . $variationAttrs;
		}

		$lang = $this->product->getLanguage();
		$lang = Multilingual::isLangCode( $lang ) ? $lang : Multilingual::getDefaultLanguage();

		/**
		 * Get data for product variation in old format for compatibility.
		 */
		$dataLegacy = [
			'variation_id' => $this->product->getID(),
			'product_id'   => $this->product->getParentID(),
			'sku'          => (string) $this->product->getSKU(),
			'title'        => apply_filters( 'dgwt/wcas/variation/title', $title, $this->product->getWooObject() ),
			'description'  => (string) $this->product->getDescription(),
			'image'        => $this->product->getThumbnailSrc(),
			'url'          => apply_filters( 'dgwt/wcas/variation/permalink', $this->product->getPermalink(), $this->product->getWooObject() ),
			'html_price'   => apply_filters( 'dgwt/wcas/indexer/variation/index_html_price', true ) ? $this->product->getPriceHTML() : '',
			'lang'         => $lang,
		];

		$dataFiltered = apply_filters( 'dgwt/wcas/variation/insert', $dataLegacy, $this->product->getWooObject() );

		$data = [];
		/**
		 * Convert data to format used in readable index.
		 */
		if ( ! empty( $dataFiltered ) ) {
			$data = [
				'post_id'           => $dataFiltered['variation_id'],
				'post_or_parent_id' => $dataFiltered['product_id'],
				'created_date'      => get_post_field( 'post_date', $this->product->getID(), 'raw' ),
				'name'              => $dataFiltered['title'],
				'description'       => $dataFiltered['description'],
				'type'              => 'product_variation',
				'sku'               => $dataFiltered['sku'],
				'sku_variations'    => '',
				'global_unique_id'  => (string) $this->product->getGlobalUniqueId(),
				'attributes'        => '',
				'meta'              => [],
				'image'             => $dataFiltered['image'],
				'url'               => $dataFiltered['url'],
				'html_price'        => $dataFiltered['html_price'],
				'price'             => $this->product->getPrice(),
				'average_rating'    => $this->product->getAverageRating(),
				'review_count'      => $this->product->getReviewCount(),
				'total_sales'       => $this->product->getTotalSales(),
				'lang'              => $this->product->getLanguage(),
			];
		}

		return apply_filters( 'dgwt/wcas/tnt/indexer/readable/product_variation/data', $data, $this->product->getID(), $this->product );
	}


	/**
	 * Get post or pages data to save
	 *
	 * @return array
	 */
	private function getNonProductData() {
		$data = [];

		if ( is_object( $this->post ) && $this->post->isValid() ) {
			$thumbSize           = apply_filters( 'dgwt/wcas/indexer/readable/' . $this->post->getPostType() . '/thumbnail_size', 'medium' );
			$postTypesWithImages = apply_filters( 'dgwt/wcas/indexer/readable/post_types_with_images', [] );

			$data = [
				'post_id'           => $this->post->getID(),
				'post_or_parent_id' => $this->post->getID(),
				'created_date'      => get_post_field( 'post_date', $this->post->getID(), 'raw' ),
				'name'              => $this->post->getTitle(),
				'description'       => $this->post->getDescription( 'short' ),
				'type'              => $this->post->getPostType(),
				'sku'               => '',
				'sku_variations'    => '',
				'global_unique_id'  => '',
				'attributes'        => '',
				'meta'              => [],
				'image'             => is_array( $postTypesWithImages ) && in_array( $this->post->getPostType(), $postTypesWithImages ) ? $this->post->getThumbnailSrc( $thumbSize ) : '',
				'url'               => $this->post->getPermalink(),
				'html_price'        => '',
				'price'             => '',
				'average_rating'    => '',
				'review_count'      => '',
				'total_sales'       => '',
				'lang'              => $this->post->getLanguage(),
			];

			$data = apply_filters( 'dgwt/wcas/tnt/indexer/readable/' . $this->post->getPostType() . '/data', $data, $this->post->getID(), $this->post );
		}

		return $data;
	}

	/**
	 * Enqueue variations for indexing in separate background process or index them instantly
	 *
	 * @param bool $directVariations Index variations directly
	 */
	private function enqueueOrProcessVariationsUpdate( $directVariations = false ) {
		$variations = $this->product->getAvailableVariations();
		if ( empty( $variations ) ) {
			// No variations, so ensure that there are no variations in the index.
			$this->deleteVariations( $this->product->getID() );

			return;
		}

		if ( $directVariations ) {
			DGWT_WCAS()->tntsearchMySql->asyncVariationsUpdater->task(
				[
					'parent_id' => $this->product->getID(),
					'items'     => wp_list_pluck( $variations, 'variation_id' ),
				]
			);
		} else {
			$variableProductsToUpdate = get_option( 'dgwt_wcas_variable_products_to_update', [] );
			if ( ! is_array( $variableProductsToUpdate ) ) {
				$variableProductsToUpdate = [];
			}
			$variableProductsToUpdate[] = $this->product->getID();
			update_option( 'dgwt_wcas_variable_products_to_update', $variableProductsToUpdate, false );

			BackgroundProductUpdater::scheduleInitVariationsUpdate();
		}
	}

	/**
	 * Update product
	 *
	 * @param int $postID Post ID
	 * @param boolean $updateVariations Update variations.
	 * @param bool $directVariations
	 *
	 * @return void
	 */
	public function update( $postID, $updateVariations = false, $directVariations = false ) {
		try {
			$this->delete( $postID, false );
			// We always update the product in the "main" index.
			$this->insert( $postID, $updateVariations, $directVariations, 'main' );
			// Remove duplicates from the index.
			$result = Database::removeDuplicates();
			if ( $result > 0 ) {
				Builder::log( '[Readable index] Removed ' . $result . ' duplicate(s)', 'debug', 'file', 'readable', 'single_product_update' );
			}
		} catch ( \Error $e ) {
			Logger::handleUpdaterThrowableError( $e, '[Readable index] ' );
		} catch ( \Exception $e ) {
			Logger::handleUpdaterThrowableError( $e, '[Readable index] ' );
		}
	}

	/**
	 * Get data of an indexed product
	 *
	 * @param int $postID Post ID
	 * @param string $lang Post language
	 *
	 * @return array
	 */
	public function getSingle( $postID, $lang = '' ) {
		global $wpdb;
		$data = [];

		$postID = absint( $postID );

		if ( empty( $lang ) ) {
			$sql = $wpdb->prepare(
				"
                SELECT *
                FROM $wpdb->dgwt_wcas_index
                WHERE post_id = %d
                ",
				$postID
			);
		} else {
			$sql = $wpdb->prepare(
				"
                SELECT *
                FROM $wpdb->dgwt_wcas_index
                WHERE post_id = %d
                AND lang = %s
                ",
				$postID,
				$lang
			);
		}
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$r = $wpdb->get_results( $sql );
		if ( ! empty( $r ) && is_array( $r ) && ! empty( $r[0] ) && ! empty( $r[0]->post_id ) ) {
			$data = $r[0];
		}

		return $data;
	}

	/**
	 * Remove record from the index
	 *
	 * @param int postID
	 * @param bool $deleteVariations Delete variations.
	 *
	 * @return bool true on success
	 * @throws WPDBException
	 */
	public function delete( $postID, $deleteVariations = true ) {
		global $wpdb;

		$success = WPDB::get_instance()->delete(
			$wpdb->dgwt_wcas_index,
			[ 'post_id' => $postID ],
			[ '%d' ]
		);

		// During updating product, variations are updated (and deleted) separately in the background process.
		if ( $deleteVariations ) {
			$success = $success && $this->deleteVariations( $postID );
		}

		return (bool) $success;
	}

	protected function deleteVariations( $parentID ) {
		global $wpdb;

		return WPDB::get_instance()->delete(
			$wpdb->dgwt_wcas_index,
			[
				'post_or_parent_id' => $parentID,
				'type'              => 'product_variation',
			],
			[ '%d', '%s' ]
		);
	}

	/**
	 * Wipe index
	 *
	 * @return bool
	 */
	public function wipe( $indexRoleSuffix = '' ) {
		Database::remove( $indexRoleSuffix );
		Builder::log( '[Readable index] Cleared' );

		return true;
	}
}
