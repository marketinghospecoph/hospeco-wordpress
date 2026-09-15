<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable\Indexer as IndexerS;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Readable\Indexer as IndexerR;
use DgoraWcas\Helpers;
use DgoraWcas\Product;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BackgroundProductUpdater {

	public function init() {
		add_action( 'dgwt/wcas/tnt/background_product_update', [ __CLASS__, 'handle' ], 10, 2 );
		add_action( 'dgwt/wcas/tnt/init_variations_update', [ __CLASS__, 'handleInitVariationsUpdate' ] );
	}

	/**
	 * Update product in index
	 *
	 * @param string $action Action
	 * @param int $postID Product ID
	 */
	public static function handle( $action, $postID ) {
		if ( empty( $postID ) ) {
			return;
		}
		if ( intval( $postID ) <= 0 ) {
			return;
		}

		$indexerR = new IndexerR();
		// We always update the product in the "main" index.
		$indexerS = new IndexerS( [ 'index_role' => 'main' ] );

		switch ( $action ) {
			case 'update':
				$indexerR->update( $postID, true );
				$indexerS->update( $postID );
				break;
			case 'delete':
				try {
					$indexerR->delete( $postID );
				} catch ( \Error $e ) {
					Logger::handleUpdaterThrowableError( $e, '[Readable index] ' );
				} catch ( \Exception $e ) {
					Logger::handleUpdaterThrowableError( $e, '[Readable index] ' );
				}
				$indexerS->delete( $postID );
				break;
		}

		sleep( 1 );
	}

	/**
	 * Schedule to update or delete product in background
	 *
	 * @param string $action Action
	 * @param int $postID Product ID
	 */
	public static function schedule( $action, $postID ) {
		$queue = Utils::getQueue();
		if ( empty( $queue ) ) {
			return;
		}
		// Skip if index isn't yet completed
		if ( Builder::getInfo( 'status' ) !== 'completed' ) {
			$queue->cancel_all( 'dgwt/wcas/tnt/background_product_update' );

			return;
		}
		// Skip if triggered from order
		if ( Helpers::is_running_inside_class( 'WC_Order', 20 ) && $action !== 'delete' ) {
			return;
		}

		// Check if there is task scheduled for this product
		$scheduledUpdates = $queue->search(
			[
				'hook'   => 'dgwt/wcas/tnt/background_product_update',
				'args'   => [
					'action' => $action,
					'postID' => $postID,
				],
				'status' => 'pending',
			]
		);

		if ( empty( $scheduledUpdates ) ) {
			// Preventing creation of too large queue of products to update in the index
			$allScheduledUpdates = $queue->search(
				[
					'hook'     => 'dgwt/wcas/tnt/background_product_update',
					'status'   => 'pending',
					'per_page' => - 1,
				]
			);
			$maxScheduledUpdates = apply_filters( 'dgwt/wcas/tnt/max_scheduled_updates', 30 );
			if ( count( $allScheduledUpdates ) < $maxScheduledUpdates ) {
				$queue->add(
					'dgwt/wcas/tnt/background_product_update',
					[
						'action' => $action,
						'postID' => $postID,
					]
				);
			}
		}
	}

	/**
	 * Schedule init variations update
	 */
	public static function scheduleInitVariationsUpdate( $delayed = false ) {
		$variableProductsToUpdate = get_option( 'dgwt_wcas_variable_products_to_update', [] );
		if ( ! is_array( $variableProductsToUpdate ) ) {
			$variableProductsToUpdate = [];
		}
		if ( empty( $variableProductsToUpdate ) ) {
			return;
		}

		$queue = Utils::getQueue();
		if ( empty( $queue ) ) {
			return;
		}

		// Skip if index isn't yet completed
		if ( Builder::getInfo( 'status' ) !== 'completed' ) {
			$queue->cancel_all( 'dgwt/wcas/tnt/init_variations_update' );

			return;
		}

		$scheduledUpdates = $queue->search(
			[
				'hook'   => 'dgwt/wcas/tnt/init_variations_update',
				'status' => 'pending',
			]
		);

		if ( empty( $scheduledUpdates ) ) {
			if ( $delayed ) {
				$queue->schedule_single( time() + 60, 'dgwt/wcas/tnt/init_variations_update' );
			} else {
				$queue->add( 'dgwt/wcas/tnt/init_variations_update' );
			}
		}
	}

	/**
	 * Prepare and init variations update for latest edited product
	 *
	 * @return void
	 */
	public static function handleInitVariationsUpdate() {
		global $wpdb;

		$variableProductsToUpdate = get_option( 'dgwt_wcas_variable_products_to_update', [] );
		if ( ! is_array( $variableProductsToUpdate ) ) {
			$variableProductsToUpdate = [];
		}

		if ( ! empty( $variableProductsToUpdate ) && DGWT_WCAS()->tntsearchMySql->asyncVariationsUpdater->is_process_running() ) {
			self::scheduleInitVariationsUpdate( true );

			return;
		}

		if ( ! empty( $variableProductsToUpdate ) ) {
			$productID = array_shift( $variableProductsToUpdate );
			update_option( 'dgwt_wcas_variable_products_to_update', $variableProductsToUpdate );

			$product            = new Product( $productID );
			$variations         = $product->getAvailableVariations();
			$variationIds       = wp_list_pluck( $variations, 'variation_id' );
			$variationIds       = array_map( fn( $id ) => (string) $id, $variationIds );
			$variationsSetCount = apply_filters( 'dgwt/wcas/indexer/readable_set_items_count', 25 );

			// Integrations can remove some variations from the list.
			$variationIds = apply_filters( 'dgwt/wcas/variations_update/variation_ids', $variationIds );

			// Before running background update, we need to remove all variations from the index.
			$wpdb->delete(
				$wpdb->dgwt_wcas_index,
				[
					'post_or_parent_id' => $productID,
					'type'              => 'product_variation',
				],
				[ '%d', '%s' ]
			);

			if ( ! empty( $variationIds ) ) {
				$i = 0;
				foreach ( $variationIds as $variationId ) {
					$variationsSet[] = $variationId;

					if ( count( $variationsSet ) === $variationsSetCount || $i + 1 === count( $variationIds ) ) {
						DGWT_WCAS()->tntsearchMySql->asyncVariationsUpdater->push_to_queue(
							[
								'parent_id' => $productID,
								'items'     => $variationsSet,
							]
						);
						$variationsSet = [];
					}

					$i ++;
				}

				DGWT_WCAS()->tntsearchMySql->asyncVariationsUpdater->save()->maybe_dispatch();
			}
		}
	}
}
