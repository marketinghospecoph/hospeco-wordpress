<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable\Indexer as IndexerS;
use DgoraWcas\Engines\TNTSearchMySQL\Indexer\Readable\Indexer as IndexerR;
use DgoraWcas\Engines\TNTSearchMySQL\Libs\WPAsyncRequest;
use DgoraWcas\Engines\TNTSearchMySQL\Libs\WPBackgroundProcess;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AsyncVariationsUpdater extends WPBackgroundProcess {
	/**
	 * @var string
	 */
	protected $prefix = 'wcas';

	/**
	 * @var string
	 */
	protected $action = 'update_variation';

	/**
	 * @var string
	 */
	protected $name = '[Variation update]';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $dataPart Data with items to iterate over
	 *
	 * @return mixed
	 */
	public function task( $dataPart ) {
		if ( ! defined( 'DGWT_WCAS_VARIATIONS_UPDATE_TASK' ) ) {
			define( 'DGWT_WCAS_VARIATIONS_UPDATE_TASK', true );
		}

		Builder::log( '[Variation update] Starting async task. Variations set count: ' . count( $dataPart['items'] ) . '. Parent: #' . $dataPart['parent_id'], 'debug', 'file', 'variation', $this->action );

		$indexerR = new IndexerR();
		// We always update the product in the "main" index.
		$indexerS = new IndexerS( [ 'index_role' => 'main' ] );

		foreach ( $dataPart['items'] as $itemID ) {
			$status = Builder::getInfo( 'status', Config::getIndexRole() );
			if ( $status !== 'completed' ) {
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					return false;
				}
				Builder::log( '[Variation update] Breaking async task due to indexer status: ' . $status, 'debug', 'file', 'variation', $this->action );
				exit();
			}

			$indexerR->update( $itemID );
			$variationSupportModes = Builder::getInfo( 'variation_support_modes' );
			if ( is_array( $variationSupportModes ) && in_array( 'as_single_product', $variationSupportModes ) ) {
				$indexerS->update( $itemID );
			}
		}
		Builder::log( sprintf( '[Variation update] Finished processing items set for #%s', $dataPart['parent_id'] ), 'debug', 'file', 'variation', $this->action );

		return false;
	}

	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		if ( delete_site_option( $key ) ) {
			Builder::log( sprintf( '[Variation update] The queue <code>%s</code> was deleted ', $key ), 'debug', 'file', 'all', $this->action );
		};

		return $this;
	}

	/**
	 * Schedule event
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			if ( wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier ) !== false ) {
				Builder::log( sprintf( '[Variation update] Schedule <code>%s</code> was created ', $this->cron_hook_identifier ), 'debug', 'file', 'all', $this->action );
			}
		}
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		for ( $i = 0; $i < 2; $i ++ ) {
			if ( $this->is_process_running() ) {
				// Background process already running.
				exit;
			}
			sleep( 2 );
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			exit;
		}

		$this->handle();

		exit;
	}

	/**
	 * Save queue
	 *
	 * @return $this
	 */
	public function save() {
		$key = $this->generate_key();

		if ( ! empty( $this->data ) ) {
			update_site_option( $key, $this->data );
			Builder::log( sprintf( '[Variation update] The queue <code>%s</code> was created', $key ), 'debug', 'file', 'all', $this->action );
		}

		return $this;
	}

	/**
	 * Dispatch job is queue is not empty
	 */
	public function maybe_dispatch() {
		$status = Builder::getInfo( 'status', Config::getIndexRole() );
		if ( $status !== 'completed' ) {
			Builder::log( '[Variation update] Breaking async task dispatch due to indexer status: ' . $status, 'debug', 'file', 'variation', $this->action );
			exit();
		}

		if ( $this->is_queue_empty() ) {
			$this->complete();
		} else {
			$this->data( [] );
			$this->dispatch();
		}
	}

	/**
	 * Dispatch
	 *
	 * @access public
	 * @return void
	 */
	public function dispatch() {
		// Schedule the cron healthcheck.
		$this->schedule_event();

		// Perform remote post.
		WPAsyncRequest::dispatch();

		$status = Builder::getInfo( 'status', Config::getIndexRole() );
		if ( $status !== 'completed' ) {
			Builder::log( $this->name . ' Breaking background process due to indexer status: ' . $status, 'debug', 'file', 'bg-process', $this->action );
			exit();
		}

		// Wait 15s and redispatch process if queue is not empty and process is not running
		$redispatchProcess = apply_filters( 'dgwt/wcas/indexer/redispatch-not-running-process', true );
		if ( $redispatchProcess ) {
			for ( $i = 1; $i <= 10; $i ++ ) {
				$sleep = $i <= 5 ? 1 : 2;
				sleep( $sleep );

				$isEmpty   = $this->is_queue_empty();
				$isRunning = $this->is_process_running();
				if ( $isEmpty || ( ! $isEmpty && $isRunning ) ) {
					Builder::log( $this->name . ' Breaking redispatch process | Loop: ' . $i . ' | Empty: ' . ( $isEmpty ? 'true' : 'false' ) . ' | Is running: ' . ( $isRunning ? 'true' : 'false' ), 'debug', 'file', 'bg-process', $this->action );

					return;
				}

				if ( ! in_array( Builder::getInfo( 'status', Config::getIndexRole() ), [ 'completed' ] ) ) {
					return;
				}
			}

			if ( ! $this->is_queue_empty() && ! $this->is_process_running() ) {
				// Set default timeout and blocking, so we can get and check response
				add_filter(
					$this->identifier . '_post_args',
					function ( $args ) {
						if ( isset( $args['timeout'] ) ) {
							unset( $args['timeout'] );
						}
						if ( isset( $args['blocking'] ) ) {
							unset( $args['blocking'] );
						}
						// Prevent error: "400 Bad Request: Request Header Or Cookie Too Large"
						if ( isset( $args['cookies'] ) && is_array( $args['cookies'] ) ) {
							foreach ( $args['cookies'] as $index => $cookie ) {
								if ( strlen( $cookie ) > 250 ) {
									$args['cookies'][ $index ] = '';
								}
							}
						}

						return $args;
					}
				);

				Builder::log( $this->name . ' Redispatching process', 'debug', 'file', 'bg-process', $this->action );

				$response = WPAsyncRequest::dispatch();
				if ( wp_remote_retrieve_response_code( $response ) !== 200 || is_wp_error( $response ) ) {
					if ( is_wp_error( $response ) ) {
						Builder::log( $this->name . ' Redispatch process error: ' . $response->get_error_message() . ' | Code: ' . $response->get_error_code(), 'debug', 'file', 'bg-process', $this->action );
					} else {
						Builder::log( $this->name . ' Redispatch process error response | Code: ' . wp_remote_retrieve_response_code( $response ) . ' | Message: ' . wp_remote_retrieve_response_message( $response ) . ' | Body: ' . substr( wp_remote_retrieve_body( $response ), 0, 1000 ) . '', 'debug', 'file', 'bg-process', $this->action );
					}
				}
			}
		}
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	public function complete() {
		parent::complete();

		Builder::log( '[Variation update] Set completed', 'debug', 'file', 'all', $this->action );

		sleep( 1 );

		BackgroundProductUpdater::scheduleInitVariationsUpdate();
	}
}
