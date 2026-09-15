<?php

namespace DgoraWcas\Integrations\Plugins\MemberPress;

use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MemberPress extends AbstractPluginIntegration {
	protected const LABEL         = 'MemberPress';
	protected const MIN_VERSION   = '1.9.23';
	protected const VERSION_CONST = 'MEPR_VERSION';

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}

		return parent::isActive();
	}

	public function init(): void {
		add_action( 'template_redirect', [ $this, 'runIntegration' ] );

		// WooCommerce
		add_action( 'woocommerce_update_product', [ $this, 'clearProductIDsTransients' ] );
		add_action( 'edited_product_cat', [ $this, 'clearProductIDsTransients' ] );

		// Membership, Rules
		add_action(
			'clean_post_cache',
			function ( $postID, $post ) {
				if ( in_array( $post->post_type, [ 'memberpressrule', 'memberpressproduct' ] ) ) {
					$this->clearProductIDsTransients();
				}
			},
			10,
			2
		);

		// User
		add_action( 'profile_update', [ $this, 'clearProductIDsTransients' ], 100 );

		// Save Membership settings
		add_action( 'mepr-process-options', [ $this, 'clearProductIDsTransients' ] );

		add_filter( 'dgwt/wcas/troubleshooting/renamed_plugins', [ $this, 'getFolderRenameInfo' ] );
	}

	/**
	 * Run integration
	 *
	 * Store in transient and session IDs of locked product IDs for current user.
	 */
	public function runIntegration() {
		$transientName = 'dgwt_wcas_woocommerce_memberpress_pids_' . get_current_user_id();
		$productIDs    = get_transient( $transientName );

		if ( $productIDs === false ) {
			$productIDs = [];

			$query = new \WP_Query(
				[
					'posts_per_page' => - 1,
					'post_type'      => 'product',
				]
			);

			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post ) {
					if ( \MeprRule::is_locked( $post ) ) {
						$productIDs[] = $post->ID;
					}
				}
			}

			set_transient( $transientName, $productIDs, 6 * HOUR_IN_SECONDS );
		}

		if ( ! session_id() ) {
			session_start();
		}

		$_SESSION['dgwt-wcas-woocommerce-memberpress-locked-products'] = $productIDs;
	}

	/**
	 * Clear transients
	 */
	public function clearProductIDsTransients() {
		global $wpdb;

		$prefix = $wpdb->esc_like( '_transient_dgwt_wcas_woocommerce_memberpress_pids_' );
		$sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE %s";
		//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$keys = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

		if ( ! is_wp_error( $keys ) ) {
			foreach ( $keys as $key ) {
				delete_transient( ltrim( $key['option_name'], '_transient_' ) );
			}
		}
	}

	/**
	 * Get info about renamed plugin folder
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function getFolderRenameInfo( $plugins ) {
		$filters = new Filters();

		$result = Helpers::getFolderRenameInfo__premium_only( 'MemberPress Basic', $filters->plugin_names );
		if ( $result ) {
			$plugins[] = $result;
		}

		return $plugins;
	}
}
