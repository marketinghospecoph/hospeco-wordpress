<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\B2BKing;

use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with B2BKing
 *
 * Plugin URL: https://webwizards.dev/
 * Author: WebWizards
 */
class B2BKing extends AbstractPluginIntegration {
	protected const LABEL = 'B2BKing';

	private $visibleIds = false;

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}

		if ( ! defined( 'B2BKING_DIR' ) ) {
			return false;
		}

		return true;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'storeInTransientIncludedProducts' ], 20 );

		add_filter( 'dgwt/wcas/suggestion_details/taxonomy/products_query_args', [ $this, 'excludeHiddenProductsFromDetailsPanel' ] );

		add_filter( 'dgwt/wcas/troubleshooting/renamed_plugins', [ $this, 'getFolderRenameInfo' ] );
	}

	/**
	 * Store visible product ids in transient
	 */
	public function storeInTransientIncludedProducts() {
		if ( intval( get_option( 'b2bking_all_products_visible_all_users_setting', 1 ) ) !== 1 ) {
			if ( get_option( 'b2bking_plugin_status_setting', 'disabled' ) !== 'disabled' ) {
				if ( ! defined( 'ICL_LANGUAGE_NAME_EN' ) ) {
					$this->visibleIds = get_transient( 'b2bking_user_' . get_current_user_id() . '_ajax_visibility' );
				} else {
					$this->visibleIds = get_transient( 'b2bking_user_' . get_current_user_id() . '_ajax_visibility' . ICL_LANGUAGE_NAME_EN );
				}

				set_transient( 'dgwt_wcas_b2bking_visible_products_' . get_current_user_id(), empty( $this->visibleIds ) ? [] : $this->visibleIds, HOUR_IN_SECONDS );

				$visible_terms = DGWT_WCAS()->settings->getOption( 'show_product_tax_product_cat' ) === 'on' ? $this->getVisibleCategories() : [];
				$visible_terms = apply_filters( 'dgwt/wcas/integrations/b2bking/visible_terms', $visible_terms );

				set_transient( 'dgwt_wcas_b2bking_visible_terms_' . get_current_user_id(), $visible_terms );
			}
		}
	}

	/**
	 * Allow only visible products in: Details Panel >> Category products list
	 */
	public function excludeHiddenProductsFromDetailsPanel( $args ) {
		if ( $this->visibleIds !== false ) {
			$args['post__in'] = $this->visibleIds;
		}

		return $args;
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

		$result = Helpers::getFolderRenameInfo__premium_only( 'B2BKing', $filters->plugin_names );
		if ( $result ) {
			$plugins[] = $result;
		}

		return $plugins;
	}

	/**
	 * Get visible categories for current user
	 *
	 * This is almost not modified code from B2BKing plugin.
	 *
	 * @return array
	 * @see \B2bking::get_visibility_set_transient
	 */
	private function getVisibleCategories() {
		$user_is_b2b = get_user_meta( get_current_user_id(), 'b2bking_b2buser', true );

		// if user logged in and is b2b
		if ( is_user_logged_in() && ( $user_is_b2b === 'yes' ) ) {
			// Get current user's data: group, id, login, etc
			$currentuserid        = get_current_user_id();
			$currentuserid        = b2bking()->get_top_parent_account( $currentuserid );
			$currentuser          = get_user_by( 'id', $currentuserid );
			$currentuserlogin     = $currentuser->user_login;
			$currentusergroupidnr = b2bking()->get_user_group( $currentuserid );
			// if user is b2c
		} elseif ( is_user_logged_in() && ( $user_is_b2b !== 'yes' ) ) {
			$currentuserid        = get_current_user_id();
			$currentuserid        = b2bking()->get_top_parent_account( $currentuserid );
			$currentuser          = get_user_by( 'id', $currentuserid );
			$currentuserlogin     = $currentuser->user_login;
			$currentusergroupidnr = 'b2c';
		} else {
			$currentuserlogin     = 0;
			$currentusergroupidnr = 0;
		}

		/*
		*
		*	There are 2 separate queries that need to be made:
		* 	1. Query of all Categories visible to the USER AND all Categories visible to the USER'S GROUP
		*	2. Query of all Products set to Manual visibility mode, visible to the user or the user's group
		*
		*/

		// Build Visible Categories for the 1st Query
		$visiblecategories = [];
		$hiddencategories  = [];

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'fields'     => 'ids',
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $term ) {
			/*
			* If category is visible to GROUP OR category is visible to USER
			* Push category into visible categories array
			*/

			// first check group
			$group_meta = get_term_meta( $term, 'b2bking_group_' . $currentusergroupidnr, true );
			if ( intval( $group_meta ) === 1 ) {
				array_push( $visiblecategories, $term );
				// else check user
			} else {
				$userlistcommas = get_term_meta( $term, 'b2bking_category_users_textarea', true );
				$userarray      = explode( ',', $userlistcommas ?? '' );
				foreach ( $userarray as $user ) {
					if ( trim( $user ) === $currentuserlogin ) {
						array_push( $visiblecategories, $term );
						continue 2;
					}
				}

				// has reached this point, therefore category is not visible
				array_push( $hiddencategories, $term );
			}
		}

		return $visiblecategories;
	}
}
