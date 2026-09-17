<?php
/**
 * Cost of Goods for WooCommerce - Pro Class
 *
 * @version 2.4.0
 * @since   1.4.0
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Cost_of_Goods_Pro' ) ) :

class Alg_WC_Cost_of_Goods_Pro {

	/**
	 * @var Alg_WC_Cost_of_Goods_Recalculate_Orders_Bkg_Process
	 */
	protected $recalculate_orders_bkg_process;

	/**
	 * Constructor.
	 *
	 * @version 2.4.0
	 * @since   1.4.0
	 * @todo    [next] better "Analytics" support
	 * @todo    [next] add more information to our "Import tool"
	 */
	function __construct() {
		add_filter( 'alg_wc_cog_settings',                           array( $this, 'settings' ), 10, 3 );
		add_filter( 'alg_wc_cog_recalculate_orders_cost_and_profit', array( $this, 'recalculate_orders_cost_and_profit' ) );
		add_filter( 'alg_wc_cog_core_loaded',                        array( $this, 'core_loaded' ) );
		add_action( 'admin_init',                                    array( $this, 'admin_actions' ) );

		// Bkg Process
		add_action( 'plugins_loaded', array( $this, 'init_bkg_process' ) );

		// WP All Import
		add_filter( 'pmxi_custom_field', array( $this, 'fix_cost_from_wp_all_import' ), 10, 6 );

		// Sanitize restrict by user role option
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . 'alg_wc_cog_allowed_user_roles', array( $this, 'append_administrator_to_restrict_by_user_role_option' ), 10, 3 );

		// Do not show COG data for disallowed users
		add_filter( 'alg_wc_cog_create_import_tool_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_edit_costs_tool_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_orders_columns_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_product_columns_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_wc_settings_tab_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_report_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_analytics_orders_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_order_meta_box_validation', array( $this, 'block_disallowed_users' ) );
		add_filter( 'alg_wc_cog_create_product_meta_box_validation', array( $this, 'block_disallowed_users' ) );

		// Fix cost meta query for stock report
		add_filter( 'alg_wc_cog_stock_report_args', array( $this, 'stock_report_meta_query' ) );

		// Compatibility with WPC Product Bundles for WooCommerce
		add_filter( 'alg_wc_cog_stock_report_args', array( $this, 'exclude_wpc_bundle_from_stock_report' ) );
		add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'exclude_wpc_bundle_from_orders_report' ) );

		// Compatibility with Openpos - WooCommerce Point Of Sale(POS)
		add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'setup_openpos_order_reports_query' ) );
	}

	/**
	 * Changes '_price' and '_alg_wc_cog_cost' meta keys 'type' from 'DECUNAL to 'CHAR'
	 *
	 * @version 2.4.0
	 * @since   2.4.0
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function stock_report_meta_query( $args ) {
		if (
			'currency_as_char_and_not_empty' == get_option( 'alg_wc_cog_report_stock_meta_query', 'default' )
			&& isset( $args['meta_query'] )
			&& ! empty( $meta_query = $args['meta_query'] )
			&& ! empty( $decimal_params = wp_list_filter( $meta_query, array( 'type' => 'DECIMAL' ) ) )
		) {
			foreach ( $decimal_params as $key => $query ) {
				if ( in_array( $query['key'], array( '_price', '_alg_wc_cog_cost' ) ) ) {
					$args['meta_query'][ $key ]['compare'] = 'NOT IN';
					$args['meta_query'][ $key ]['value']   = array( '', '0' );
					$args['meta_query'][ $key ]['type']    = 'CHAR';
				}
			}
		}
		return $args;
	}

	/**
	 * Setup orders reports adding compatibility with Openpos - WooCommerce Point Of Sale(POS).
	 *
	 * @version 2.4.0
	 * @since   2.3.9
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	function setup_openpos_order_reports_query( $query ) {
		global $wpdb;
		if (
			'yes' === get_option( 'alg_wc_cog_openpos_anhvnit', 'no' )
			&& isset( $_GET['report'] )
			&& 'alg_cost_of_goods' === $_GET['report']
		) {
			$order_types = get_option( 'alg_wc_cog_openpos_anhvnit_report_order_type', array() );
			if ( in_array( 'common_orders', $order_types ) && ! in_array( 'openpos_orders', $order_types ) ) {
				$query['join']  .= " LEFT JOIN {$wpdb->postmeta} AS alg_wc_cog__op_order_source
				ON ( posts.ID = alg_wc_cog__op_order_source.post_id
				AND alg_wc_cog__op_order_source.meta_key = '_op_order_source' )";
				$query['where'] .= " AND alg_wc_cog__op_order_source.meta_value IS NULL OR( alg_wc_cog__op_order_source.post_id IS NOT NULL && alg_wc_cog__op_order_source.meta_value != 'openpos')";
			} elseif ( ! in_array( 'common_orders', $order_types ) && in_array( 'openpos_orders', $order_types ) ) {
				$query['join'] .= " INNER JOIN {$wpdb->postmeta} AS alg_wc_cog__op_order_source
				ON ( posts.ID = alg_wc_cog__op_order_source.post_id
				AND alg_wc_cog__op_order_source.meta_key = '_op_order_source' )";
			}
		}
		return $query;
	}

	/**
	 * exclude_wpc_bundle_from_orders_report.
	 *
	 * @version 2.3.8
	 * @since   2.3.8
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function exclude_wpc_bundle_from_orders_report( $args ) {
		if (
			'yes' === get_option( 'alg_wc_cog_wpc_product_bundle_for_wc', 'no' )
			&& isset( $_GET['page'] )
			&& 'wc-reports' == $_GET['page']
			&& isset( $_GET['tab'] )
			&& 'orders' == $_GET['tab']
			&& isset( $_GET['report'] )
			&& 'alg_cost_of_goods' == $_GET['report']
			&& isset( $args['join'] )
			&& false !== strpos( strtolower( $args['join'] ), strtolower( 'wp_woocommerce_order_itemmeta AS order_item_meta__qty' ) )
			&& isset( $args['select'] )
			&& false !== strpos( strtolower( $args['select'] ), strtolower( 'SUM( order_item_meta__qty.meta_value)' ) )
		) {
			global $wpdb;
			$where = "AND order_item_meta__qty.order_item_id NOT IN(
				SELECT DISTINCT wcoim.order_item_id
				FROM {$wpdb->prefix}woocommerce_order_itemmeta AS wcoim
				WHERE wcoim.meta_key = '_woosb_ids'
			)";
			if ( isset( $args['where'] ) ) {
				$args['where'] .= " {$where}";
			} else {
				$args['where'] = "{$where}";
			}
		}
		return $args;
	}

	/**
	 * exclude_wpc_bundle_from_stock_report.
	 *
	 * @version 2.3.8
	 * @since   2.3.8
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function exclude_wpc_bundle_from_stock_report( $args ) {
		if ( 'yes' === get_option( 'alg_wc_cog_wpc_product_bundle_for_wc', 'no' ) ) {
			$tax_query         = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
			$tax_query[]       = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'woosb',
				'operator' => 'NOT IN'
			);
			$args['tax_query'] = $tax_query;
		}
		return $args;
	}

	/**
	 * append_administrator_to_restrict_by_user_role_option.
	 *
	 * @version 2.3.4
	 * @since   2.3.4
	 *
	 * @param $value
	 * @param $option
	 * @param $raw_value
	 *
	 * @return array
	 */
	function append_administrator_to_restrict_by_user_role_option( $value, $option, $raw_value ) {
		if ( ! empty( $value ) && ! in_array( 'administrator', $value ) ) {
			$value[] = 'administrator';
		}
		return $value;
	}

	/**
	 * block_disallowed_users.
	 *
	 * @version 2.3.4
	 * @since   2.3.4
	 *
	 * @param $validation
	 *
	 * @return bool
	 */
	function block_disallowed_users( $validation ) {
		if ( alg_wc_cog_is_user_allowed() ) {
			return $validation;
		}
		$validation = false;
		return $validation;
	}

	/**
	 * fix_cost_from_wp_all_import.
	 *
	 * @version 2.3.5
	 * @since   2.3.4
	 *
	 * @param $value
	 * @param $post_id
	 * @param $key
	 * @param $original_value
	 * @param $existing_meta_keys
	 * @param $import_id
	 *
	 * @return mixed
	 */
	function fix_cost_from_wp_all_import( $value, $post_id, $key, $original_value, $existing_meta_keys, $import_id ) {
		if (
			'yes' !== get_option( 'alg_wc_cog_wp_all_import', 'no' )
			|| '_alg_wc_cog_cost' != $key
		) {
			return $value;
		}
		if ( 'yes' === get_option( 'alg_wc_cog_wp_all_import_convert_to_float', 'no' ) ) {
			$value = str_replace( ',', '.', $value );
		}
		if ( 'yes' === get_option( 'alg_wc_cog_wp_all_import_sanitize_float', 'no' ) ) {
			$value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		}
		return $value;
	}

	/**
	 * init_bkg_process.
	 *
	 * @version 2.3.0
	 * @since   2.3.0
	 */
	function init_bkg_process() {
		require_once( alg_wc_cog()->plugin_path() . '/includes/background-process/class-alg-wc-cog-recalculate-orders-bkg-process.php' );
		$this->recalculate_orders_bkg_process = new Alg_WC_Cost_of_Goods_Recalculate_Orders_Bkg_Process();
	}

	/**
	 * strip_tags_walker.
	 *
	 * @version 1.5.2
	 * @since   1.5.2
	 */
	function strip_tags_walker( &$item, $key ) {
		$item = strip_tags( $item );
	}

	/**
	 * admin_actions.
	 *
	 * @version 2.1.0
	 * @since   1.5.2
	 * @todo    [next] nonce
	 * @todo    [next] export: replace double quotes
	 * @todo    [next] check if `$data` is not empty
	 */
	function admin_actions() {
		if ( ! empty( $_GET['alg_wc_cog_stock_report_action'] ) && ! empty( $_GET['alg_wc_cog_stock_report_action_param'] ) && current_user_can( 'manage_woocommerce' ) ) {
			$action = sanitize_key( $_GET['alg_wc_cog_stock_report_action'] );
			$param  = sanitize_key( $_GET['alg_wc_cog_stock_report_action_param'] );
			if ( in_array( $param, array( 'totals', 'products' ) ) ) {
				require_once( 'reports/class-wc-report-alg-cog-stock.php' );
				$report = new WC_Report_Alg_Cost_Of_Goods_Stock();
				$data   = $report->output_report( $param, ( 'print' === $action ) );
				array_walk_recursive( $data, array( $this, 'strip_tags_walker' ) );
				switch ( $action ) {
					case 'print':
						echo '<pre>' . alg_wc_cog_get_table_html( $data, array( 'table_heading_type' => 'none' ) ) . '</pre>';
						die();
					case 'export':
						$csv = '';
						foreach ( $data as $record ) {
							$csv .= '"' . implode( '","', $record ) . '"' . PHP_EOL;
						}
						header( 'Content-Description: File Transfer' );
						header( 'Content-Type: application/octet-stream' );
						header( 'Content-Disposition: attachment; filename=cost-of-goods-stock-report-' . $param . '.csv' );
						header( 'Content-Transfer-Encoding: binary' );
						header( 'Expires: 0' );
						header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
						header( 'Pragma: public' );
						header( 'Content-Length: ' . strlen( $csv ) );
						echo $csv;
						die();
				}
			}
		}
	}

	/**
	 * core_loaded.
	 *
	 * @version 2.3.5
	 * @since   1.4.0
	 * @todo    [next] `wmc_admin_reports`
	 * @todo    [later] `$this->admin_quick_bulk_edit_position`: optional 'start'
	 */
	function core_loaded( $core ) {
		$this->core = $core;
		// Recalculate orders cost and profit
		add_filter( 'alg_wc_cog_save_settings',  array( $this, 'recalculate_orders_cost_and_profit' ), PHP_INT_MAX );
		// Reports
		add_filter( 'woocommerce_admin_reports', array( $this, 'add_cog_reports' ), PHP_INT_MAX );
		// Quick and bulk edit
		$this->do_add_admin_quick_edit        = ( 'yes' === get_option( 'alg_wc_cog_products_quick_edit', 'no' ) );
		$this->do_add_admin_bulk_edit         = ( 'yes' === get_option( 'alg_wc_cog_products_bulk_edit',  'no' ) );
		$this->admin_quick_bulk_edit_position = 'end';
		if ( $this->do_add_admin_quick_edit || $this->do_add_admin_bulk_edit ) {
			if ( $this->do_add_admin_quick_edit ) {
				add_action( 'woocommerce_product_quick_edit_' . $this->admin_quick_bulk_edit_position, array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
			}
			if ( $this->do_add_admin_bulk_edit ) {
				add_action( 'woocommerce_product_bulk_edit_'  . $this->admin_quick_bulk_edit_position, array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
			}
			add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
		}
		// Multicurrency
		if ( 'yes' === get_option( 'alg_wc_cog_currencies_enabled', 'no' ) ) {
			add_filter( 'alg_wc_cog_order_total_for_pecentage_fees', array( $this, 'convert_currency' ), 10, 2 );
			add_filter( 'alg_wc_cog_order_line_total',               array( $this, 'convert_currency' ), 10, 2 );
			add_filter( 'alg_wc_cog_order_extra_cost_from_meta',     array( $this, 'convert_currency' ), 10, 2 );
			add_filter( 'alg_wc_cog_order_shipping_total',           array( $this, 'convert_currency' ), 10, 2 );
			add_filter( 'alg_wc_cog_order_total_fees',               array( $this, 'convert_currency' ), 10, 2 );
			if ( 'yes' === get_option( 'alg_wc_cog_currencies_wmc', 'no' ) ) {
				add_filter( 'alg_wc_cog_currency_rate', array( $this, 'wmc_get_currency_rate' ), 10, 3 );
			}
			add_filter( 'wmc_admin_reports', array( $this, 'wmc_add_cog_report' ), 10, 2 );
		}
		// "Add stock" fields in quick and bulk edit
		add_action( 'woocommerce_product_bulk_edit_' . 'end', array( $this, 'handle_addstock_fields_to_bulk_and_quick_edit' ), PHP_INT_MAX );
		add_action( 'woocommerce_product_quick_edit_' . 'end', array( $this, 'handle_addstock_fields_to_bulk_and_quick_edit' ), PHP_INT_MAX );
		add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_addstock_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
	}

	/**
	 * save_addstock_bulk_and_quick_edit_fields.
	 *
	 * @version 2.3.5
	 * @since   2.3.5
	 */
	function save_addstock_bulk_and_quick_edit_fields( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}
		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) && 'no' == get_option( 'alg_wc_cog_products_add_stock_quick_edit', 'no' ) ) {
			return $post_id;
		} else {
			if ( 'no' == get_option( 'alg_wc_cog_products_add_stock_bulk_edit', 'no' ) ) {
				return $post_id;
			}
		}
		// Save
		if (
			isset( $_REQUEST['_alg_wc_cog_add_stock_qb'] ) && ! empty( $stock = $_REQUEST['_alg_wc_cog_add_stock_qb'] )
			&& isset( $_REQUEST['_alg_wc_cog_add_stock_cost_qb'] ) && ! empty( $cost = $_REQUEST['_alg_wc_cog_add_stock_cost_qb'] )
		) {
			alg_wc_cog()->core->products->product_add_stock( $post_id, wc_clean( wp_unslash( $stock ) ), wc_clean( wp_unslash( $cost ) ) );
		}
		return $post_id;
	}

	/**
	 * handle_addstock_fields_to_bulk_and_quick_edit.
	 *
	 * @version 2.3.5
	 * @since   2.3.5
	 */
	function handle_addstock_fields_to_bulk_and_quick_edit() {
		if (
			(
				false !== strpos( current_filter(), 'woocommerce_product_bulk_edit' )
				&& 'yes' == get_option( 'alg_wc_cog_products_add_stock_bulk_edit', 'no' )
			)
			||
			( false !== strpos( current_filter(), 'woocommerce_product_quick_edit' )
			  && 'yes' == get_option( 'alg_wc_cog_products_add_stock_quick_edit', 'no' )
			)
		) {
			$html = alg_wc_cog()->core->products->get_add_stock_bulk_and_quick_edit_fields();
			echo $html;
		}
	}

	/**
	 * wmc_add_cog_report.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 * @see     https://wordpress.org/plugins/woo-multi-currency/
	 */
	function wmc_add_cog_report( $value, $report ) {
		return ( 'alg_cost_of_goods' === $report ? true : $value );
	}

	/**
	 * wmc_get_currency_rate.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 * @see     https://wordpress.org/plugins/woo-multi-currency/
	 */
	function wmc_get_currency_rate( $rate, $order, $wc_currency ) {
		$order_currency = $order->get_currency();
		if ( ( ! is_admin() || is_ajax() ) && function_exists( 'wmc_get_exchange_rate' ) ) {
			// From function
			return wmc_get_exchange_rate( $order_currency );
		} else {
			// From order meta
			$wmc_order_info = get_post_meta( $order->get_id(), 'wmc_order_info', true );
			return ( ! empty( $wmc_order_info[ $order_currency ]['rate'] ) && is_numeric( $wmc_order_info[ $order_currency ]['rate'] ) ? $wmc_order_info[ $order_currency ]['rate'] : $rate );
		}
	}

	/**
	 * get_wc_currency.
	 *
	 * @version 2.3.0
	 * @since   2.2.0
	 */
	function get_wc_currency() {
		if ( ! isset( $this->wc_currency ) ) {
			$this->wc_currency = $this->core->get_default_shop_currency();
		}
		return $this->wc_currency;
	}

	/**
	 * get_currencies.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function get_currencies() {
		if ( ! isset( $this->currencies ) ) {
			$this->currencies = get_option( 'alg_wc_cog_currencies', array() );
		}
		return $this->currencies;
	}

	/**
	 * get_currencies_rates.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 */
	function get_currencies_rates() {
		if ( ! isset( $this->currencies_rates ) ) {
			$this->currencies_rates = get_option( 'alg_wc_cog_currencies_rates', array() );
		}
		return $this->currencies_rates;
	}

	/**
	 * convert_currency.
	 *
	 * @version 2.2.0
	 * @since   2.2.0
	 * @todo    [now] get rates from "Booster for WooCommerce" plugin
	 * @todo    [next] add "From order meta": a) same key for all currencies, and b) different meta key per currency
	 * @todo    [next] add automatic exchange rates (e.g. ECB)
	 * @todo    [maybe] check for `is_a( $order, 'WC_Order' )`?
	 */
	function convert_currency( $value, $order ) {
		if ( $value && ( $order_currency = $order->get_currency() ) != ( $wc_currency = $this->get_wc_currency() ) ) {
			// Filter
			if ( ( $rate = apply_filters( 'alg_wc_cog_currency_rate', 0, $order, $wc_currency ) ) ) {
				return ( $value / $rate );
			}
			// From options
			$currencies = $this->get_currencies();
			if ( in_array( $order_currency, $currencies ) ) {
				$rates = $this->get_currencies_rates();
				$rate  = apply_filters( 'alg_wc_cog_currency_rate_option', ( isset( $rates[ $wc_currency . $order_currency ] ) ? $rates[ $wc_currency . $order_currency ] : 0 ),
					$order, $wc_currency );
				if ( $rate ) {
					return ( $value / $rate );
				}
			}
		}
		return $value;
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 1.7.0
	 * @since   1.7.0
	 * @todo    [maybe] reposition this (to the `price_fields` section)
	 * @todo    [maybe] actual value (instead of "No change" placeholder) (probably need to add value to `woocommerce_inline_`)
	 */
	function add_bulk_and_quick_edit_fields() {
		$current_filter = current_filter();
		if ( 'end' === $this->admin_quick_bulk_edit_position && 'woocommerce_product_quick_edit_end' === $current_filter ) {
			echo '<br class="clear" />';
		}
		?><label>
			<span class="title"><?php echo __( 'Cost', 'cost-of-goods-for-woocommerce' ); ?></span>
			<span class="input-text-wrap">
				<input type="text" name="_alg_wc_cog_cost_qb" class="text wc_input_price" placeholder="<?php echo __( '- No change -', 'cost-of-goods-for-woocommerce' ); ?>" value="">
			</span>
		</label><?php
		if ( 'start' === $this->admin_quick_bulk_edit_position && 'woocommerce_product_quick_edit_start' === $current_filter ) {
			echo '<br class="clear" />';
		}
	}

	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 2.3.4
	 * @since   1.7.0
	 */
	function save_bulk_and_quick_edit_fields( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}
		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) { // WPCS: input var ok.
			if ( ! $this->do_add_admin_quick_edit ) {
				return $post_id;
			}
		} else {
			if ( ! $this->do_add_admin_bulk_edit ) {
				return $post_id;
			}
		}
		// Save.
		if ( isset( $_REQUEST['_alg_wc_cog_cost_qb'] ) && '' !== $_REQUEST['_alg_wc_cog_cost_qb'] ) {
			update_post_meta( $post_id, '_alg_wc_cog_cost', wc_clean( $_REQUEST['_alg_wc_cog_cost_qb'] ) );
			// Update all variations
			if (
				'yes' === get_option( 'alg_wc_cog_products_quick_edit_replace_variations', 'no' )
				&& ! empty( $product = wc_get_product( $post_id ) )
				&& $product->is_type( 'variable' )
			) {
				foreach ( $product->get_available_variations() as $variation ) {
					update_post_meta( $variation['variation_id'], '_alg_wc_cog_cost', wc_clean( $_REQUEST['_alg_wc_cog_cost_qb'] ) );
				}
			}
		}
		return $post_id;
	}

	/**
	 * recalculate_orders_cost_and_profit.
	 *
	 * @version 2.3.0
	 * @since   1.1.0
	 * @todo    [next] maybe skip "refunded" orders?
	 * @todo    [next] add to bulk actions
	 */
	function recalculate_orders_cost_and_profit( $current_section ) {
		if (
			'yes' === get_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_all',      'no' ) ||
			'yes' === get_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_no_costs', 'no' )
		) {
			if ( 'tools' === $current_section && current_user_can( 'manage_woocommerce' ) ) {
				if ( 0 != ( $memory_limit = get_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_memory_limit', 0 ) ) ) {
					ini_set( 'memory_limit', $memory_limit . 'M' );
				}
				$recalculate_for_orders_with_no_costs = get_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_no_costs', 'no' );
				$orders = wc_get_orders( array( 'limit' => -1, 'return' => 'ids' ) );
				if ( count( $orders ) < get_option( 'alg_wc_cog_bkg_process_min_amount', 100 ) ) {
					foreach ( $orders as $order_id ) {
						$this->core->orders->update_order_items_costs( $order_id, true, ( 'yes' === $recalculate_for_orders_with_no_costs ) );
					}
					$this->add_admin_notice( 'success', __( 'Orders cost and profit successfully recalculated.', 'cost-of-goods-for-woocommerce' ) );
				} else {
					$this->recalculate_orders_bkg_process->cancel_process();
					foreach ( $orders as $order_id ) {
						$this->recalculate_orders_bkg_process->push_to_queue( array( 'id' => $order_id, 'recalculate_for_orders_with_no_costs' => ( 'yes' === $recalculate_for_orders_with_no_costs ) ) );
					}
					$this->recalculate_orders_bkg_process->save()->dispatch();
					$this->add_admin_notice( 'success', __( 'Orders cost and profit recalculating via background processing. You should receive an e-mail when it\'s complete if the "Advanced > Background processing > Send email" option is enabled.', 'cost-of-goods-for-woocommerce' ) );
				}
			} else {
				$this->add_admin_notice( 'error',   __( 'Something went wrong...', 'cost-of-goods-for-woocommerce' ) );
			}
			update_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_all',      'no' );
			update_option( 'alg_wc_cog_recalculate_orders_cost_and_profit_no_costs', 'no' );
			wc_delete_shop_order_transients();
		}
	}

	/**
	 * add_admin_notice.
	 *
	 * @version 1.4.7
	 * @since   1.4.0
	 */
	function add_admin_notice( $type, $message ) {
		if ( method_exists( 'WC_Admin_Settings', 'add_message' ) ) {
			WC_Admin_Settings::add_message( $message );
		} else {
			$this->admin_notice = array( 'type' => $type, 'message' => $message );
			add_action( 'admin_notices', array( $this, 'print_admin_notice' ), PHP_INT_MAX );
		}
	}

	/**
	 * print_admin_notice.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function print_admin_notice() {
		echo '<div class="notice notice-' . $this->admin_notice['type'] . ' is-dismissible"><p><strong>' . $this->admin_notice['message'] . '</strong></p></div>';
	}

	/**
	 * add_cog_reports.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function add_cog_reports( $reports ) {
		if ( ! apply_filters( 'alg_wc_cog_create_report_validation', true ) ) {
			return $reports;
		}
		// Orders
		$orders_report = array(
			'title'       => __( 'Cost of goods', 'cost-of-goods-for-woocommerce' ),
			'description' => '',
			'hide_title'  => true,
			'callback'    => array( $this, 'get_cog_report' ),
		);
		if ( isset( $reports['orders']['reports'] ) ) {
			$orders_reports = array();
			foreach ( $reports['orders']['reports'] as $report_id => $report_data ) {
				$orders_reports[ $report_id ] = $report_data;
				if ( 'sales_by_date' === $report_id ) {
					$orders_reports['alg_cost_of_goods'] = $orders_report;
				}
			}
			$reports['orders']['reports'] = $orders_reports;
		} else {
			$reports['orders']['reports']['alg_cost_of_goods'] = $orders_report;
		}
		// Stock
		$reports['stock']['reports']['alg_cost_of_goods_stock'] = array(
			'title'       => __( 'Cost of goods', 'cost-of-goods-for-woocommerce' ),
			'description' => '',
			'hide_title'  => true,
			'callback'    => array( $this, 'get_cog_report_stock' ),
		);
		return $reports;
	}

	/**
	 * get_cog_report.
	 *
	 * @version 2.3.4
	 * @since   1.3.0
	 * @todo    [next] `order_status` and transients?
	 */
	function get_cog_report() {
		require_once( 'reports/class-wc-report-alg-cog.php' );
		$report = new WC_Report_Alg_Cost_Of_Goods();
		$report->order_status = get_option( 'alg_wc_cog_report_orders_order_status', array( 'completed', 'processing', 'on-hold' ) );
		if ( empty( $report->order_status ) ) {
			$report->order_status = array( 'completed', 'processing', 'on-hold' );
		}
		add_filter( 'woocommerce_reports_order_statuses', function ( $order_status ) use($report){
			if (
				! isset( $_GET['report'] )
				|| 'alg_cost_of_goods' !== $_GET['report']
			) {
				return $order_status;
			}
			return $report->order_status;
		} );
		$report->output_report();
	}

	/**
	 * get_cog_report_stock.
	 *
	 * @version 2.1.0
	 * @since   1.3.2
	 */
	function get_cog_report_stock() {
		require_once( 'reports/class-wc-report-alg-cog-stock.php' );
		$report = new WC_Report_Alg_Cost_Of_Goods_Stock();
		$report->output_report();
	}

	/**
	 * settings.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function settings( $value, $type = '', $args = array() ) {
		if ( 'report' === $type ) {
			return sprintf( __( 'Cost of Goods reports are in %s and in %s.', 'cost-of-goods-for-woocommerce' ),
					'<a href="' . admin_url( 'admin.php?page=wc-reports&tab=orders&report=alg_cost_of_goods' ) . '">' .
						__( 'Reports > Orders > Cost of Goods', 'cost-of-goods-for-woocommerce' ) . '</a>',
					'<a href="' . admin_url( 'admin.php?page=wc-reports&tab=stock&report=alg_cost_of_goods_stock' ) . '">' .
						__( 'Reports > Stock > Cost of Goods', 'cost-of-goods-for-woocommerce' ) . '</a>'
				);
		}
		return '';
	}

}

endif;

return new Alg_WC_Cost_of_Goods_Pro();
