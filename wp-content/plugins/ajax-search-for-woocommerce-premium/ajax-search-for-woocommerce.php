<?php
/**
 * Plugin Name: FiboSearch - AJAX Search for WooCommerce (Pro)
 * Plugin URI: https://fibosearch.com?utm_source=wp-admin&utm_medium=referral&utm_campaign=author_uri&utm_gen=utmdc
 * Description: The most popular WooCommerce product search. Gives your users a well-designed advanced AJAX search bar with live search suggestions.
 * Version: 1.33.0
 * Update URI: https://api.freemius.com
 * Author: FiboSearch Team
 * Author URI: https://fibosearch.com?utm_source=wp-admin&utm_medium=referral&utm_campaign=author_uri&utm_gen=utmdc
 * Text Domain: ajax-search-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC requires at least: 5.5
 * WC tested up to: 10.7
 *
 * @fs_premium_only /includes/Engines/TNTSearchMySQL/
 * @fs_premium_only /vendor-pro/
 * @fs_premium_only /composer-pro/
 * @fs_premium_only /languages/
 * @fs_premium_only /fs/placeholder.php
 * @fs_premium_only /partials/admin/indexer-header.php, /partials/admin/indexer-notices.php, /partials/admin/indexer-body.php, /partials/admin/system-status.php, /partials/admin/pro-starter.php
 * @fs_premium_only /assets/css/selectize.css, /assets/js/selectize.min.js, /assets/js/vue.js, /assets/js/vue.min.js, /assets/js/vue2-selectize.js
 * @fs_premium_only /partials/admin/debug/body-indexer.php, /partials/admin/debug/body-product.php, /partials/admin/debug/body-search.php, /partials/admin/debug/body-source-query.php
 * @fs_premium_only /partials/details-panel/page.php, /partials/details-panel/post.php
 * @fs_premium_only /partials/emails
 * @fs_premium_only /partials/search-results-page/
 * @fs_premium_only /includes/Post.php
 * @fs_premium_only /includes/Integrations/WpCliFiboSearchIndex.php
 * @fs_premium_only /includes/Integrations/Marketplace/
 * @fs_premium_only /includes/Integrations/Plugins/BoosterIO/
 * @fs_premium_only /includes/Integrations/Plugins/B2BKing/
 * @fs_premium_only /includes/Integrations/Plugins/MemberPress/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceProductsVisibility/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceWholeSalePricesIntegration/Filters.php
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceCatalogVisibilityOptions/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceProductFilters/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceProtectedCategories/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommercePrivateStore/Filters.php
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceShowSingleVariations/
 * @fs_premium_only /includes/Integrations/Plugins/TranslatePress/
 * @fs_premium_only /includes/Integrations/Plugins/WPML/
 * @fs_premium_only /includes/Integrations/Plugins/AdvancedCustomFields/
 * @fs_premium_only /includes/Integrations/Plugins/AdvancedCustomFieldsTable/
 * @fs_premium_only /includes/Integrations/Plugins/QtranslateXt/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceMemberships/
 * @fs_premium_only /includes/Integrations/Plugins/CustomProductTabsForWooCommerce/
 * @fs_premium_only /includes/Integrations/Plugins/ProductSpecifications/
 * @fs_premium_only /includes/Integrations/Plugins/Elementor/PostsWidgetsSkins
 * @fs_premium_only /includes/Integrations/Plugins/Elementor/PostsWidget.php
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceB2B/
 * @fs_premium_only /includes/Integrations/Plugins/AddifyB2B/
 * @fs_premium_only /includes/Integrations/Plugins/ProductVisibilityByUserRole/
 * @fs_premium_only /includes/Integrations/Plugins/WooCommerceSingleVariations/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DGWT_WC_Ajax_Search' ) && ! function_exists( 'dgoraAsfwFs' ) ) {


	$fspath = dirname( __FILE__ ) . '/fs/config.php';
	if ( file_exists( $fspath ) ) {
		require_once $fspath;
	}

	final class DGWT_WC_Ajax_Search {


		private static $instance;
		private $tnow;
		public $engine = 'native';
		/**
		 * @var \DgoraWcas\Settings
		 */
		public $settings;
		/**
		 * @var \DgoraWcas\Setup
		 */
		public $setup;
		public $multilingual;
		/**
		 * @var \DgoraWcas\Integrations\Themes\ThemesCompatibility
		 */
		public $themeCompatibility;
		/**
		 * @var \DgoraWcas\Integrations\Brands
		 */
		public $brands;
		/**
		 * @var \DgoraWcas\Integrations\Marketplace\Marketplace
		 */
		public $marketplace;
		/**
		 * @var \DgoraWcas\Engines\WordPressNative\Search
		 */
		public $nativeSearch;
		/**
		 * @var \DgoraWcas\Personalization
		 */
		public $personalization;
		/**
		 * @var \DgoraWcas\Engines\TNTSearchMySQL\TNTSearch
		 */
		public $tntsearchMySql;
		/**
		 * @var \DgoraWcas\Search
		 */
		protected $search;

		public $tntsearchMySqlValid = false;
		public $searchInstances     = 0;

		public static function getInstance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DGWT_WC_Ajax_Search ) ) {

				self::$instance = new DGWT_WC_Ajax_Search();

				self::$instance->constants();
				self::$instance->loadTextdomain();

				if ( ! self::$instance->checkRequirements() ) {
					return;
				}

				self::$instance->systemHooks();

				self::$instance->autoload();

				self::$instance->setup = new \DgoraWcas\Setup();
				self::$instance->setup->init();

				self::$instance->settings = new \DgoraWcas\Settings();

				self::$instance->hooks();

				new \DgoraWcas\Integrations\Plugins\PluginsCompatibility();

				self::$instance->multilingual = new \DgoraWcas\Multilingual();

				if ( dgoraAsfwFs()->is__premium_only() ) {
					self::$instance->tntsearchMySqlValid = \DgoraWcas\Engines\TNTSearchMySQL\Indexer\Builder::isIndexValid();

					self::$instance->engine = 'native';

					if ( self::$instance->tntsearchMySqlValid ) {
						self::$instance->engine = 'tntsearchMySql';
					}

					self::$instance->tntsearchMySql = new \DgoraWcas\Engines\TNTSearchMySQL\TNTSearch();

					self::$instance->marketplace = new \DgoraWcas\Integrations\Marketplace\Marketplace();
					self::$instance->marketplace->init();

					// WP-CLI integration.
					if ( defined( 'WP_CLI' ) && WP_CLI ) {
						WP_CLI::add_command( 'fibosearch index', '\DgoraWcas\Integrations\WpCliFiboSearchIndex' );
					}
				}

				self::$instance->nativeSearch = new \DgoraWcas\Engines\WordPressNative\Search();

				// @TODO Temporary always use native WordPress DetailsBox engine.
				// Replace with details.php and shortinit in future releases
				new \DgoraWcas\Engines\WordPressNative\DetailsBox();

				self::$instance->personalization = new \DgoraWcas\Personalization();
				new \DgoraWcas\Scripts();

				$embeddingViaMenu = new \DgoraWcas\EmbeddingViaMenu();
				$embeddingViaMenu->init();

				self::$instance->themeCompatibility = new \DgoraWcas\Integrations\Themes\ThemesCompatibility();

				self::$instance->brands = new \DgoraWcas\Integrations\Brands();
				self::$instance->brands->init();

				\DgoraWcas\Shortcode::register();

				if ( is_admin() ) {
					\DgoraWcas\Admin\Install::maybeInstall();
					new \DgoraWcas\Admin\AdminMenu();
					new \DgoraWcas\Admin\Promo\FiboSearch2Teaser();
					new \DgoraWcas\Admin\Promo\FeedbackNotice();
					new \DgoraWcas\Admin\Promo\FiboFiltersTeaser();
					new \DgoraWcas\Admin\Promo\Upgrade();
					new \DgoraWcas\Admin\Troubleshooting();
				}

				if ( is_admin() || wp_doing_cron() ) {
					$analytics = new \DgoraWcas\Analytics\Analytics();
					$analytics->init();
				}

				new \DgoraWcas\Integrations\Solver();

				global $wp_version;

				if ( version_compare( $wp_version, '5.9' ) >= 0 ) {
					$blocks = new \DgoraWcas\Blocks();
					$blocks->init();
				}
			}
			self::$instance->tnow = time();

			return self::$instance;
		}

		/**
		 * Constructor Function
		 */
		private function __construct() {
			self::$instance = $this;
		}

		/**
		 * Uninstall, Activate, Deactivate hooks
		 *
		 * @return void
		 */
		private function systemHooks() {

			register_deactivation_hook(
				__FILE__,
				function () {

					if ( dgoraAsfwFs()->is__premium_only() ) {
						\DgoraWcas\Helpers::removeBatchOptions__premium_only();
						$maintenance = new \DgoraWcas\Analytics\Maintenance();
						$maintenance->unschedule();
					}
				}
			);

		}

		/**
		 * Check requirements
		 *
		 * @return bool
		 */
		private function checkRequirements() {
			if ( version_compare( PHP_VERSION, '7.0' ) < 0 ) {
				add_action( 'admin_notices', [ $this, 'adminNoticeReqPhp70' ] );

				return false;
			}

			if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_AJAX' ) ) {
				add_action( 'admin_notices', [ $this, 'adminNoticeNoWoocommerce' ] );

				return false;
			}

			if ( ! file_exists( DGWT_WCAS_DIR . 'vendor/autoload.php' ) ) {
				add_action( 'admin_notices', [ $this, 'adminNoticeNoVendor' ] );

				return false;
			}

			return true;
		}

		/**
		 * Notice: Minimum required PHP version is 7.0
		 *
		 * @return void
		 */
		public function adminNoticeReqPhp70() {

			if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES ) {
				return;
			}

			?>
			<div class="notice notice-error dgwt-wcas-notice">
				<p>
					<?php
					printf(
						__(
							'%1$s: You need PHP version at least 7.0 to run this plugin. You are currently using PHP version %2$s.',
							'ajax-search-for-woocommerce'
						),
						'<b>' . DGWT_WCAS_NAME . '</b>',
						PHP_VERSION
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Notice: requires WooCommerce
		 *
		 * @return void
		 */
		public function adminNoticeNoWoocommerce() {
			?>
			<div class="notice notice-error dgwt-wcas-notice">
				<p>
					<?php
					printf(
						__( '%1$s is enabled but not effective. It requires %2$s in order to work.', 'ajax-search-for-woocommerce' ),
						'<b>' . DGWT_WCAS_FULL_NAME . '</b>',
						'<a href="https://wordpress.org/plugins/woocommerce/"  target="_blank">WooCommerce</a>'
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Notice: requires /vendor
		 *
		 * @return void
		 */
		public function adminNoticeNoVendor() {
			?>
			<div class="notice notice-error dgwt-wcas-notice">
				<p>
					<?php
					printf(
						__( '%s is enabled but not effective. It is missing core files. Please reinstall the plugin.', 'ajax-search-for-woocommerce' ),
						'<b>' . DGWT_WCAS_FULL_NAME . '</b>'
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Setup plugin constants
		 *
		 * @return void
		 */
		private function constants() {

			$v = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );

			if ( dgoraAsfwFs()->is__premium_only() ) {
				$this->define( 'DGWT_WCAS_NAME', 'FiboSearch Pro' );
				$this->define( 'DGWT_WCAS_FULL_NAME', 'FiboSearch Pro - AJAX Search for WooCommerce' );
			}

			$this->define( 'DGWT_WCAS_NAME', 'FiboSearch' );
			$this->define( 'DGWT_WCAS_FULL_NAME', 'FiboSearch - AJAX Search for WooCommerce' );

			$this->define( 'DGWT_WCAS_VERSION', $v['Version'] );
			$this->define( 'DGWT_WCAS_FILE', __FILE__ );
			$this->define( 'DGWT_WCAS_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'DGWT_WCAS_URL', plugin_dir_url( __FILE__ ) );

			$this->define( 'DGWT_WCAS_SETTINGS_KEY', 'dgwt_wcas_settings' );

			$this->define( 'DGWT_WCAS_SEARCH_ACTION', 'dgwt_wcas_ajax_search' );
			$this->define( 'DGWT_WCAS_RESULT_DETAILS_ACTION', 'dgwt_wcas_result_details' );
			$this->define( 'DGWT_WCAS_GET_PRICES_ACTION', 'dgwt_wcas_get_prices' );
			if ( dgoraAsfwFs()->is__premium_only() ) {
				$this->define( 'DGWT_WCAS_SEARCH_PRO_ACTION', 'dgwt_wcas_ajax_search_pro' );
			}

			$this->define( 'DGWT_WCAS_WC_AJAX_ENDPOINT', true );

		}

		/**
		 * Define constant if not already set
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 *
		 * @return void
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * PSR-4 autoload
		 *
		 * @return void
		 */
		public function autoload() {
			require_once DGWT_WCAS_DIR . 'vendor/autoload.php';
			require_once DGWT_WCAS_DIR . 'widget.php';
		}

		/**
		 * Actions and filters
		 *
		 * @return void
		 */
		private function hooks() {
			if ( dgoraAsfwFs()->is__premium_only() ) {
				\DgoraWcas\Engines\TNTSearchMySQL\Indexer\Readable\Database::registerTables();
				\DgoraWcas\Engines\TNTSearchMySQL\Indexer\Taxonomy\Database::registerTables();
				\DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable\Database::registerTables();
				\DgoraWcas\Engines\TNTSearchMySQL\Indexer\Vendor\Database::registerTables();
				\DgoraWcas\Engines\TNTSearchMySQL\Indexer\Storage::registerTables();
			}

			add_action( 'admin_init', [ $this, 'adminScripts' ], 8 );
		}


		/**
		 * Enqueue admin sripts
		 *
		 * @return void
		 */
		public function adminScripts() {
			$min = SCRIPT_DEBUG ? '' : '.min';

			// Register CSS
			wp_register_style(
				'dgwt-wcas-admin-style',
				DGWT_WCAS_URL . 'assets/css/admin-style' . $min . '.css',
				[],
				DGWT_WCAS_VERSION
			);
			// Register front styles for block editor
			wp_register_style( 'dgwt-wcas-style', apply_filters( 'dgwt/wcas/scripts/css_style_url', DGWT_WCAS_URL . 'assets/css/style' . $min . '.css' ), [], DGWT_WCAS_VERSION );

			// Register JS
			//phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_register_script(
				'dgwt-wcas-admin-js',
				DGWT_WCAS_URL . 'assets/js/admin' . $min . '.js',
				[
					'jquery',
					'wp-url',
				],
				DGWT_WCAS_VERSION
			);

			if ( \DgoraWcas\Helpers::isSettingsPage() ) {
				$localize = [
					'adminurl'    => admin_url( 'admin-ajax.php' ), // This adminurl is not relative.
					'labels'      => \DgoraWcas\Helpers::getLabels(),
					'adminLabels' => [
						'preview' => __( 'No interaction! This is only a preview.', 'ajax-search-for-woocommerce' ),
					],
					'nonces'      => [
						'build_index'             => wp_create_nonce( 'dgwt_wcas_build_index' ),
						'stop_build_index'        => wp_create_nonce( 'dgwt_wcas_stop_build_index' ),
						'build_index_heartbeat'   => wp_create_nonce( 'dgwt_wcas_build_index_heartbeat' ),
						'advanced_options_switch' => wp_create_nonce( 'dgwt_wcas_advanced_options_switch' ),
						'index_details_toggle'    => wp_create_nonce( 'dgwt_wcas_index_details_toggle' ),
					],
					'images'      => [
						'admin_preloader_url' => DGWT_WCAS_URL . 'assets/img/preloader.gif',
					],
					'postTypes'   => [ 'post', 'page' ],
				];
				if ( dgoraAsfwFs()->is__premium_only() ) {
					$localize['is_premium'] = true;
					$localize['postTypes']  = $this->tntsearchMySql->postTypes->getPostTypesSlugs();
					//phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found
					$localize['adminLabels']['custom_fields_loading_error'] = __( 'Error loading custom fields.', 'ajax-search-for-woocommerce' ) . ' ' . '<a target="_blank" href="https://fibosearch.com/documentation/troubleshooting/unable-to-select-custom-fields-in-fibosearch-settings/">' . __( 'Learn more', 'ajax-search-for-woocommerce' ) . '.</a>';
				}
				$localize = apply_filters( 'dgwt/wcas/scripts/admin/localize', $localize );
				wp_localize_script( 'dgwt-wcas-admin-js', 'dgwt_wcas', $localize );

				// Enqueue CSS
				wp_enqueue_style( 'dgwt-wcas-admin-style' );

				if ( ! dgoraAsfwFs()->is_activation_mode() ) {
					add_thickbox();
					wp_enqueue_style( 'wp-color-picker' );
					wp_enqueue_script( 'dgwt-wcas-admin-js' );
					wp_enqueue_script( 'wp-color-picker' );
					// TODO
					//phpcs:disable WordPress.WP.EnqueuedResourceParameters.NotInFooter
					wp_enqueue_script( 'dgwt-wcas-admin-popper-js', DGWT_WCAS_URL . 'assets/js/popper.min.js', [ 'jquery' ], DGWT_WCAS_VERSION );
					wp_enqueue_script( 'dgwt-wcas-admin-tooltip-js', DGWT_WCAS_URL . 'assets/js/tooltip.min.js', [ 'jquery' ], DGWT_WCAS_VERSION );
					wp_enqueue_style( 'dgwt-wcas-style', apply_filters( 'dgwt/wcas/scripts/css_style_url', DGWT_WCAS_URL . 'assets/css/style' . $min . '.css' ), [], DGWT_WCAS_VERSION );

					if ( dgoraAsfwFs()->is__premium_only() ) {
						wp_enqueue_style( 'dgwt-wcas-admin-selectize', DGWT_WCAS_URL . 'assets/css/selectize' . $min . '.css', [], DGWT_WCAS_VERSION );
						wp_enqueue_script( 'dgwt-wcas-admin-selectize-js', DGWT_WCAS_URL . 'assets/js/selectize.min.js', [ 'jquery' ], DGWT_WCAS_VERSION );
						wp_enqueue_script( 'dgwt-wcas-admin-vue', DGWT_WCAS_URL . 'assets/js/vue' . $min . '.js', [], DGWT_WCAS_VERSION );
						wp_enqueue_script( 'dgwt-wcas-admin-vue-selectize', DGWT_WCAS_URL . 'assets/js/vue2-selectize.js', [ 'dgwt-wcas-admin-selectize-js' ], DGWT_WCAS_VERSION );
					}
					//phpcs:enable
				}
			}

			if ( \DgoraWcas\Helpers::isCheckoutPage() ) {
				wp_enqueue_style( 'dgwt-wcas-admin-style' );
			}

			if ( \DgoraWcas\Helpers::isDebugPage() ) {
				wp_enqueue_style( 'dgwt-wcas-admin-style' );
			}
		}

		/**
		 * Register text domain
		 *
		 * @return void
		 */
		private function loadTextdomain() {
			$lang_dir = dirname( plugin_basename( DGWT_WCAS_FILE ) ) . '/languages/';
			load_plugin_textdomain( 'ajax-search-for-woocommerce', false, $lang_dir );
		}

		/**
		 * @see \DgoraWcas\Search
		 */
		public function searchPosts( $phrase, $args = [] ) {
			if ( is_null( $this->search ) ) {
				$this->search = new \DgoraWcas\Search();
			}

			return $this->search->searchPosts( $phrase, $args );
		}

		public function getInfo( $type = '' ) {
			switch ( $type ) {
				case 'search_endpoint':
					return \DgoraWcas\Helpers::getEndpointUrl( 'search' );
				case 'current_language':
					return \DgoraWcas\Multilingual::isMultilingual() ? \DgoraWcas\Multilingual::getCurrentLanguage() : '';
				default:
					return '';
			}
		}
	}

	// Init the plugin
	function DGWT_WCAS() {
		return DGWT_WC_Ajax_Search::getInstance();
	}

	add_action( 'plugins_loaded', 'DGWT_WCAS', 15 );
}
