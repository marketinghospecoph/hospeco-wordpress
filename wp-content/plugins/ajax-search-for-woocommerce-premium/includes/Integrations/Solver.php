<?php

namespace DgoraWcas\Integrations;

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Solver
 *
 * Solve conflicts with other plugins
 */
class Solver {

	public function __construct() {
		$this->solveSearchWPWooCommerceIntegration();

		$this->solveDiviWithBuilderWC();

		$this->solveMedicorCoreScrips();

		$this->solveGeoTargetingWPScripts();

		$this->solveEmptyImages();

		$this->solveAntiSpamCleanTalk();

		if ( dgoraAsfwFs()->is__premium_only() ) {
			$this->solveLongHtmlPrices__premium_only();
		}
	}

	/**
	 * Solves conflict with SearchWP WooCommerce Integration by SearchWP, LLC
	 * Tested version: plugin SearchWP WooCommerce Integration by SearchWP v1.2.1
	 *
	 * Reason: Empty search page (no results). The plugin removes query_vars['s']
	 *
	 * @return void
	 */
	public function solveSearchWPWooCommerceIntegration() {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['dgwt_wcas'] ) ) {
			add_filter( 'searchwp_woocommerce_forced', '__return_false', PHP_INT_MAX );
			add_filter( 'searchwp_short_circuit', '__return_true', PHP_INT_MAX );
		}
	}

	/**
	 * Solves conflict with the DIVI builder
	 * Tested version: theme DIVI v3.19.18
	 *
	 * Reason: WP Query for search results was overwritten ih the hook pre_get_posts
	 */
	public function solveDiviWithBuilderWC() {
		add_action(
			'init',
			function () {
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['dgwt_wcas'] ) ) {
					remove_action( 'pre_get_posts', 'et_builder_wc_pre_get_posts', 10 );
				}
			}
		);
	}

	/**
	 * Medicor plugin by WpOpal uses wp_dequeue_style( 'dgwt-wcas-style' ); in their code.
	 * I don't know why they block my CSS, but I have to force to restore it.
	 */
	private function solveMedicorCoreScrips() {
		if ( class_exists( 'MedicorCore' ) ) {
			add_action(
				'wp_print_styles',
				function () {
					wp_enqueue_style( 'dgwt-wcas-style' );
				},
				PHP_INT_MAX
			);
		}
	}

	/**
	 * Check if prices in autocomplete should be load independent in extra AJAX request.
	 * The price is saved in the search index statically. Sometimes prices of products are conditional
	 * e.g. when the online store uses multi-currency or price based on country solutions.
	 */
	public static function shouldLoadPriceDynamically__premium_only() {

		$loadDynamically = false;

		// Prices are hidden. No action needed
		if ( DGWT_WCAS()->settings->getOption( 'show_product_price' ) !== 'on' ) {
			return false;
		}

		if ( defined( 'DGWT_WCAS_DYNAMIC_PRICES' ) ) {
			return DGWT_WCAS_DYNAMIC_PRICES ? true : false;
		}

		// WooCommerce Price Based on Country
		// https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/
		if ( defined( 'WCPBC_PLUGIN_FILE' ) ) {
			$loadDynamically = true;
		}

		// WOOCS - WooCommerce Currency Switcher
		// https://wordpress.org/plugins/woocommerce-currency-switcher/
		if ( defined( 'WOOCS_VERSION' ) ) {
			$loadDynamically = true;
		}

		// WooCommerce Multilingual
		// http://wpml.org/documentation/related-projects/woocommerce-multilingual/
		if ( defined( 'WCML_VERSION' ) ) {
			$loadDynamically = function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on();
		}

		// WooCommerce Wholesale Prices
		// https://wordpress.org/plugins/woocommerce-wholesale-prices/
		if ( class_exists( 'WooCommerceWholeSalePrices' ) ) {
			$loadDynamically = true;
		}

		// YITH Multi Currency Switcher for WooCommerce
		// https://yithemes.com/themes/plugins/yith-multi-currency-switcher-for-woocommerce/
		if ( defined( 'YITH_WCMCS_VERSION' ) ) {
			$loadDynamically = true;
		}

		// CURCY – Multi Currency for WooCommerce
		// https://wordpress.org/plugins/woo-multi-currency/
		if ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) ) {
			$loadDynamically = true;
		}

		// CURCY - WooCommerce Multi Currency Premium
		// https://villatheme.com/extensions/woo-multi-currency/
		if ( defined( 'WOOMULTI_CURRENCY_VERSION' ) ) {
			$loadDynamically = true;
		}

		return apply_filters( 'dgwt/wcas/tnt/dynamic_prices', $loadDynamically );
	}

	/**
	 * Disabling HTML price indexing when dynamic prices are enabled,
	 * or we detect a plugin that generates too long HTML prices
	 */
	private function solveLongHtmlPrices__premium_only() {
		$indexHtmlPrices = true;

		if ( defined( 'DGWT_WCAS_DYNAMIC_PRICES' ) && DGWT_WCAS_DYNAMIC_PRICES ) {
			$indexHtmlPrices = false;
		}

		// CURCY - WooCommerce Multi Currency Premium
		// https://villatheme.com/extensions/woo-multi-currency/
		if ( defined( 'WOOMULTI_CURRENCY_VERSION' ) ) {
			$indexHtmlPrices = false;
		}

		if ( ! $indexHtmlPrices ) {
			add_filter( 'dgwt/wcas/indexer/readable/index_html_price', '__return_false' );
			add_filter( 'dgwt/wcas/indexer/variation/index_html_price', '__return_false' );
		}
	}

	/**
	 * Preventing the GeoTargetingWP plugin from loading scripts in the settings page
	 * because the Selectize.js script is loaded twice
	 *
	 * @return void
	 */
	public function solveGeoTargetingWPScripts() {
		if ( ! Helpers::isSettingsPage() ) {
			return;
		}

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_dequeue_script( 'geot' );
				wp_dequeue_script( 'geot-chosen' );
				wp_dequeue_script( 'geot-selectize' );
			},
			999
		);
	}

	/**
	 * Preventing empty image URLs (null) from being passed to the indexer
	 *
	 * @return void
	 */
	public function solveEmptyImages() {
		add_filter(
			'dgwt/wcas/product/thumbnail_src',
			function ( $url, $id, $product ) {
				return empty( $url ) ? wc_placeholder_img_src() : $url;
			},
			PHP_INT_MAX - 5,
			3
		);

		add_filter(
			'dgwt/wcas/variation/thumbnail_src',
			function ( $url, $parentID, $variationID ) {
				return empty( $url ) ? wc_placeholder_img_src() : $url;
			},
			PHP_INT_MAX - 5,
			3
		);

		add_filter(
			'dgwt/wcas/term/thumbnail_src',
			function ( $url, $termID, $size, $term ) {
				return empty( $url ) ? wc_placeholder_img_src() : $url;
			},
			PHP_INT_MAX - 5,
			4
		);
	}

	/**
	 * Preventing the Anti-Spam by CleanTalk plugin from securing our search form
	 *
	 * Plugin URL: https://wordpress.org/plugins/cleantalk-spam-protect/
	 *
	 * @return void
	 */
	public function solveAntiSpamCleanTalk() {
		global $apbct;

		if ( ! defined( 'APBCT_VERSION' ) ) {
			return;
		}
		// The problem occurs when the "Test default WordPress search form for spam" option is "on".
		if ( isset( $apbct->settings['forms__search_test'] ) && ! $apbct->settings['forms__search_test'] ) {
			return;
		}

		/**
		 * In the cleantalk-spam-protect/js/apbct-public-bundle.min.js file, the plugin skips protection
		 * of the form when it has the "proinput" class (this is the class of another search plugin).
		 * We use this to make it applicable to our search engine as well.
		 */
		add_action(
			'wp_footer',
			function () { ?>
			<script>
				var dgwtWsasForms = document.querySelectorAll('.dgwt-wcas-search-wrapp');
				if (dgwtWsasForms.length > 0) {
					dgwtWsasForms.forEach(function (form) {
						form.classList.add('proinput');
					});
				}
			</script>
				<?php
			}
		);
	}
}
