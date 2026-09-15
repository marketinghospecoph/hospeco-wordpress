<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceProductsVisibility;

use DgoraWcas\Helpers;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with WooCommerce Products Visibility
 *
 * Plugin URL: https://themeforest.net/user/codemine
 * Author: codemine
 */
class WooCommerceProductsVisibility extends AbstractPluginIntegration {
	protected const LABEL         = 'WooCommerce Products Visibility';
	protected const VERSION_CONST = 'WooCommerce_Products_Visibility_VERSION';

	public static function shouldInitLate(): bool {
		return dgoraAsfwFs()->is_premium();
	}

	public function init(): void {
		/**
		 * This plugin is hooked on plugins_loaded action with priority 100000, so we need
		 * wait for it, and try to load this integration in next hook.
		 */
		add_action( 'sanitize_comment_cookies', [ $this, 'late_init' ] );
	}

	public function late_init() {
		if ( class_exists( 'WCPV_BACKEND' ) || class_exists( 'CMWCV_Backend_Helpers' ) ) {
			add_filter( 'dgwt/wcas/troubleshooting/renamed_plugins', [ $this, 'getFolderRenameInfo' ] );
		}

		if ( ! class_exists( 'WCPV_FRONTEND' ) && ! class_exists( 'CMWCV_WCPV_FRONTEND' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'store_in_transient_included_products' ], 20 );
	}

	/**
	 * Store visible product ids in transient
	 */
	public function store_in_transient_included_products() {
		$wcpv_frontend = null;
		// Since 5.x, the plugin has changed classes.
		if ( class_exists( 'CMWCV_WCPV_FRONTEND' ) ) {
			$wcpv_frontend = \CMWCV_WCPV_FRONTEND::get_instance();
		} elseif ( 'WCPV_FRONTEND' ) {
			$wcpv_frontend = \WCPV_FRONTEND::get_instance();
		}

		if ( is_null( $wcpv_frontend ) ) {
			return;
		}

		if ( ! empty( $wcpv_frontend->include_products ) ) {
			set_transient( 'dgwt_wcas_wcpv_visible_products_' . get_current_user_id(), $wcpv_frontend->include_products, HOUR_IN_SECONDS );
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

		// Since 5.x, the plugin has changed its name.
		$pluginName = class_exists( 'CMWCV_Backend_Helpers' ) ? 'WooCommerce Visibility' : 'WooCommerce Products Visibility';
		$result     = Helpers::getFolderRenameInfo__premium_only( $pluginName, $filters->plugin_names );

		if ( $result ) {
			$plugins[] = $result;
		}

		return $plugins;
	}
}
