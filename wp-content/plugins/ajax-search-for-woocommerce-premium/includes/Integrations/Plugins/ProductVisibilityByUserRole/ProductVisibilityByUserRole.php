<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\ProductVisibilityByUserRole;

use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with Product Visibility by User Role for WooCommerce Pro WP Wham
 *
 * Plugin URL: https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/
 * Author: WP Wham
 */
class ProductVisibilityByUserRole extends AbstractPluginIntegration {
	protected const LABEL       = 'Product Visibility by User Role for WooCommerce';
	protected const MIN_VERSION = '1.7.2';

	private $user_role;

	public static function isActive(): bool {
		if ( ! dgoraAsfwFs()->is_premium() ) {
			return false;
		}

		if ( parent::isActive() === false ) {
			return false;
		}

		return class_exists( 'Alg_WC_PVBUR' );
	}

	public static function pluginVersion(): string {
		return function_exists( 'Alg_WC_PVBUR' )
			? (string) \Alg_WC_PVBUR()->version
			: '';
	}

	public function init(): void {
		if ( get_option( 'alg_wc_pvbur_enabled' ) === 'no' ) {
			return;
		}

		$current_user    = wp_get_current_user();
		$this->user_role = ! empty( $current_user->roles ) ? $current_user->roles : [ 'guest' ];

		add_filter( 'alg_wc_pvbur_post__not_in', [ $this, 'collectVisibleProducts' ] );

		$this->collectVisibleCategory();
	}

	/**
	 * Collect visible products and store them in transient
	 *
	 * @param array $ids
	 *
	 * @return array
	 */
	public function collectVisibleProducts( array $ids ): array {
		$transient_key = 'pvbur_visible_products_' . implode( '-', $this->user_role );
		$cached_ids    = get_transient( $transient_key );

		if ( $cached_ids === false || array_diff( $ids, $cached_ids ) ) {
			set_transient( $transient_key, $ids, HOUR_IN_SECONDS );
		}

		remove_filter( 'alg_wc_pvbur_post__not_in', [ $this, 'collectVisibleProducts' ] );

		return $ids;
	}

	private function collectVisibleCategory() {
		$transient_key       = 'pvbur_visible_category_' . implode( '-', $this->user_role );
		$cached_category_ids = get_transient( $transient_key );

		if ( $cached_category_ids ) {
			return;
		}

		$visible_cat_ids = function_exists( 'alg_wc_pvbur_get_visible_product_terms' ) ? alg_wc_pvbur_get_visible_product_terms() : [];

		if ( empty( $visible_cat_ids ) ) {
			return;
		}

		set_transient( $transient_key, $visible_cat_ids, HOUR_IN_SECONDS );
	}
}
