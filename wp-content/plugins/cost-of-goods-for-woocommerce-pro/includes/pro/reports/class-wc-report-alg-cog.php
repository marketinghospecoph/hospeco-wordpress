<?php
/**
 * WC_Report_Alg_Cost_Of_Goods
 *
 * @version     2.3.5
 * @package     WooCommerce/Admin/Reports
 * @wc_version  2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Report_Alg_Cost_Of_Goods
 */
class WC_Report_Alg_Cost_Of_Goods extends WC_Admin_Report {

	/**
	 * Chart colors.
	 *
	 * @var array
	 */
	public $chart_colours = array();

	/**
	 * The report data.
	 *
	 * @var stdClass
	 */
	private $report_data;

	/**
	 * Get report data.
	 *
	 * @return stdClass
	 */
	public function get_report_data() {
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}
		return $this->report_data;
	}

	/**
	 * get_cog_report_fields.
	 *
	 * Everything related to adding new columns to the report should be in this function.
	 *
	 * @version 2.3.5
	 * @since   2.0.0
	 * @todo    [next] `profit_percent`, `profit_margin`
	 */
	private function get_cog_report_fields() {
		if ( ! isset( $this->cog_fields ) ) {
			$this->cog_fields = array(
				array(
					'meta_key'     => '_alg_wc_cog_order_cost',
					'legend_title' => __( '%s costs', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff9800',
					'chart_title'  => __( 'Costs amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_profit',
					'legend_title' => __( '%s profit', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#4caf50',
					'chart_title'  => __( 'Profit amount', 'cost-of-goods-for-woocommerce' ),
				),
				// Extra fields
				array(
					'meta_key'     => '_alg_wc_cog_order_items_cost',
					'legend_title' => __( '%s item costs', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff0000',
					'chart_title'  => __( 'Item costs amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_fees',
					'legend_title' => __( '%s fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff1000',
					'chart_title'  => __( 'Fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_shipping_cost',
					'legend_title' => __( '%s shipping method fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff2000',
					'chart_title'  => __( 'Shipping method fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_shipping_cost_fixed',
					'legend_title' => __( '%s shipping method fees (fixed)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff3000',
					'chart_title'  => __( 'Shipping method fees amount (fixed)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_shipping_cost_percent',
					'legend_title' => __( '%s shipping method fees (percent)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff4000',
					'chart_title'  => __( 'Shipping method fees amount (percent)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_gateway_cost',
					'legend_title' => __( '%s gateway fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff5000',
					'chart_title'  => __( 'Gateway fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_gateway_cost_fixed',
					'legend_title' => __( '%s gateway fees (fixed)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff6000',
					'chart_title'  => __( 'Gateway fees amount (fixed)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_gateway_cost_percent',
					'legend_title' => __( '%s gateway fees (percent)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff7000',
					'chart_title'  => __( 'Gateway fees amount (percent)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_extra_cost',
					'legend_title' => __( '%s order fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff8000',
					'chart_title'  => __( 'Order fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_extra_cost_fixed',
					'legend_title' => __( '%s order fees (fixed)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ff9000',
					'chart_title'  => __( 'Order fees amount (fixed)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_extra_cost_percent',
					'legend_title' => __( '%s order fees (percent)', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ffa000',
					'chart_title'  => __( 'Order fees amount (percent)', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_extra_cost_per_order',
					'legend_title' => __( '%s per order fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ffb000',
					'chart_title'  => __( 'Per order fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_' . 'handling' . '_fee',
					'legend_title' => __( '%s handling fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ffc000',
					'chart_title'  => __( 'Handling fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_' . 'shipping' . '_fee',
					'legend_title' => __( '%s shipping fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#ffe000',
					'chart_title'  => __( 'Shipping fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_' . 'payment' . '_fee',
					'legend_title' => __( '%s payment fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#fff000',
					'chart_title'  => __( 'Payment fees amount', 'cost-of-goods-for-woocommerce' ),
				),
				array(
					'meta_key'     => '_alg_wc_cog_order_extra_cost_from_meta',
					'legend_title' => __( '%s meta fees', 'cost-of-goods-for-woocommerce' ),
					'chart_color'  => '#fff100',
					'chart_title'  => __( 'Meta fees amount', 'cost-of-goods-for-woocommerce' ),
				),
			);
			// Prepare fields
			$this->enabled_fields = array_merge( array( '_alg_wc_cog_order_cost', '_alg_wc_cog_order_profit' ), get_option( 'alg_wc_cog_report_orders_extra_fields', array() ) );
			$i = 0;
			foreach ( $this->get_cog_report_fields() as $key => $field ) {
				if ( ! in_array( $field['meta_key'], $this->enabled_fields ) ) {
					unset( $this->cog_fields[ $key ] );
				} else {
					$this->cog_fields[ $key ]['data_totals_name']      = $field['meta_key'] . '_data_totals';
					$this->cog_fields[ $key ]['data_name']             = $field['meta_key'] . '_data';
					$this->cog_fields[ $key ]['legend_chart_color_id'] = $field['meta_key'] . '_color_id';
					$this->cog_fields[ $key ]['legend_series']         = 6 + $i;
					$i++;
				}
			}
		}
		return $this->cog_fields;
	}

	/**
	 * get_cog_report_data.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	private function get_cog_report_data() {
		$data_totals = array();
		$data        = array();
		foreach ( $this->get_cog_report_fields() as $field ) {
			$data_totals[ $field['meta_key'] ] = array(
				'type'     => 'meta',
				'function' => 'SUM',
				'name'     => $field['data_totals_name'],
			);
			$data[ $field['meta_key'] ] = array(
				'type'     => 'meta',
				'function' => 'SUM',
				'name'     => $field['data_name'],
			);
		}
		$data['post_date'] = array(
			'type'     => 'post_data',
			'function' => '',
			'name'     => 'post_date',
		);
		$this->report_data->alg_wc_cog_totals = (array) $this->get_order_report_data(
			array(
				'data'         => $data_totals,
				'filter_range' => true,
				'order_status' => $this->get_order_status(),
			)
		);
		$this->report_data->alg_wc_cog_average_profit = wc_format_decimal( $this->report_data->alg_wc_cog_totals['_alg_wc_cog_order_profit_data_totals'] /
			( $this->chart_interval + 1 ), 2 );
		$this->report_data->alg_wc_cog_data = (array) $this->get_order_report_data(
			array(
				'data'         => $data,
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => $this->get_order_status(),
			)
		);
	}

	/**
	 * get_cog_legend.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	private function get_cog_legend( $legend ) {
		foreach ( $this->get_cog_report_fields() as $field ) {
			$legend[] = array(
				'title'            => sprintf(
					$field['legend_title'],
					'<strong>' . wc_price( $this->report_data->alg_wc_cog_totals[ $field['data_totals_name'] ] ) . '</strong>'
				),
				'color'            => $this->chart_colours[ $field['legend_chart_color_id'] ],
				'highlight_series' => $field['legend_series'],
			);
		}
		switch ( $this->chart_groupby ) {
			case 'day':
				$alg_wc_cog_average_profit_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average daily profit', 'cost-of-goods-for-woocommerce' ),
					'<strong>' . wc_price( $this->report_data->alg_wc_cog_average_profit ) . '</strong>'
				);
				break;
			case 'month':
			default:
				$alg_wc_cog_average_profit_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average monthly profit', 'cost-of-goods-for-woocommerce' ),
					'<strong>' . wc_price( $this->report_data->alg_wc_cog_average_profit ) . '</strong>'
				);
				break;
		}
		if ( $this->report_data->alg_wc_cog_average_profit > 0 ) {
			$legend[] = array(
				'title'            => $alg_wc_cog_average_profit_title,
				'color'            => $this->chart_colours['alg_wc_cog_average_profit'],
				'highlight_series' => 8,
			);
		}
		return $legend;
	}

	/**
	 * get_cog_chart_colours.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	private function get_cog_chart_colours() {
		foreach ( $this->get_cog_report_fields() as $field ) {
			$this->chart_colours[ $field['legend_chart_color_id'] ] = $field['chart_color'];
		}
		$this->chart_colours['alg_wc_cog_average_profit'] = '#8bc34a';
	}

	/**
	 * get_cog_main_chart_data.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	private function get_cog_main_chart_data( $data ) {
		foreach ( $this->get_cog_report_fields() as $field ) {
			$data[ $field['data_name'] ]   = $this->prepare_chart_data( $this->report_data->alg_wc_cog_data,
				'post_date', $field['data_name'], $this->chart_interval, $this->start_date, $this->chart_groupby );
		}
		return $data;
	}

	/**
	 * get_cog_main_chart_data_json.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	private function get_cog_main_chart_data_json( $data ) {
		$result = array();
		foreach ( $this->get_cog_report_fields() as $field ) {
			$result[ $field['data_name'] ] = array_map( array( $this, 'round_chart_totals' ), array_values( $data[ $field['data_name'] ] ) );
		}
		return $result;
	}

	/**
	 * get_cog_main_chart_jquery.
	 *
	 * @version 2.3.0
	 * @since   2.0.0
	 */
	private function get_cog_main_chart_jquery( $data ) {
		foreach ( $this->get_cog_report_fields() as $field ) {
			?>
			{
			label: "<?php echo esc_js( $field['chart_title'] ); ?>",
			data: order_data.<?php echo $field['data_name']; ?>,
			yaxis: 2,
			color: '<?php echo esc_js( $this->chart_colours[ $field['legend_chart_color_id'] ] ); ?>',
			points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
			lines: { show: true, lineWidth: 2, fill: false },
			shadowSize: 0,
			prepend_tooltip: "<?php echo alg_wc_cog()->core->get_default_shop_currency_symbol(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>"
			},
			<?php
		}
		?>
		{
			label: "<?php echo esc_js( __( 'Average profit', 'cost-of-goods-for-woocommerce' ) ); ?>",
			data: [ [ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->alg_wc_cog_average_profit ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->alg_wc_cog_average_profit ); ?> ] ],
			yaxis: 2,
			color: '<?php echo esc_js( $this->chart_colours['alg_wc_cog_average_profit'] ); ?>',
			points: { show: false },
			lines: { show: true, lineWidth: 2, fill: false },
			shadowSize: 0,
			hoverable: false
		},
		<?php
	}

	/**
	 * get_order_status.
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 */
	private function get_order_status( $extra_order_status = false ) {
		$order_status = $this->order_status;
		if ( $extra_order_status ) {
			if ( is_array( $extra_order_status ) ) {
				$order_status = array_merge( $order_status, $extra_order_status );
			} else {
				$order_status[] = $extra_order_status;
			}
		}
		return $order_status;
	}

	/**
	 * Get all data needed for this report and store in the class.
	 *
	 * @version 2.0.0
	 */
	private function query_report_data() {
		$this->report_data = new stdClass();

		$this->report_data->order_counts = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'ID'        => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'count',
						'distinct' => true,
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => $this->get_order_status( 'refunded' ),
			)
		);

		$this->report_data->coupons = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'order_item_name' => array(
						'type'     => 'order_item',
						'function' => '',
						'name'     => 'order_item_name',
					),
					'discount_amount' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'coupon',
						'function'        => 'SUM',
						'name'            => 'discount_amount',
					),
					'post_date'       => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'where'        => array(
					array(
						'key'      => 'order_items.order_item_type',
						'value'    => 'coupon',
						'operator' => '=',
					),
				),
				'group_by'     => $this->group_by_query . ', order_item_name',
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => $this->get_order_status( 'refunded' ),
			)
		);

		// All items from orders - even those refunded.
		$this->report_data->order_items = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'_qty'      => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_count',
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'where'        => array(
					array(
						'key'      => 'order_items.order_item_type',
						'value'    => 'line_item',
						'operator' => '=',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'order-count' ),
				'order_status' => $this->get_order_status( 'refunded' ),
			)
		);

		/**
		 * Get total of fully refunded items.
		 */
		$this->report_data->refunded_order_items = absint(
			$this->get_order_report_data(
				array(
					'data'         => array(
						'_qty' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_count',
						),
					),
					'where'        => array(
						array(
							'key'      => 'order_items.order_item_type',
							'value'    => 'line_item',
							'operator' => '=',
						),
					),
					'query_type'   => 'get_var',
					'filter_range' => true,
					'order_types'  => wc_get_order_types( 'order-count' ),
					'order_status' => array( 'refunded' ),
				)
			)
		);

		/**
		 * Order totals by date. Charts should show GROSS amounts to avoid going -ve.
		 */
		$this->report_data->orders = (array) $this->get_order_report_data(
			array(
				'data'         => array(
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_shipping',
					),
					'_order_tax'          => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_tax',
					),
					'_order_shipping_tax' => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_shipping_tax',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true,
				'order_types'  => wc_get_order_types( 'sales-reports' ),
				'order_status' => $this->get_order_status( 'refunded' ),
			)
		);

		/**
		 * If an order is 100% refunded we should look at the parent's totals, but the refunds dates.
		 * We also need to ensure each parent order's values are only counted/summed once.
		 */
		$this->report_data->full_refunds = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'_order_total'        => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'_order_shipping'     => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_shipping',
					),
					'_order_tax'          => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_tax',
					),
					'_order_shipping_tax' => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_shipping_tax',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'            => 'posts.post_parent',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => array( 'refunded' ),
			)
		);

		foreach ( $this->report_data->full_refunds as $key => $order ) {
			$this->report_data->full_refunds[ $key ]->net_refund = $order->total_refund - ( $order->total_shipping + $order->total_tax + $order->total_shipping_tax );
		}

		/**
		 * Partial refunds. This includes line items, shipping and taxes. Not grouped by date.
		 */
		$this->report_data->partial_refunds = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'ID'                  => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'refund_id',
					),
					'_refund_amount'      => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
					'order_item_type'     => array(
						'type'      => 'order_item',
						'function'  => '',
						'name'      => 'item_type',
						'join_type' => 'LEFT',
					),
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping',
						'join_type' => 'LEFT',
					),
					'_order_tax'          => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_tax',
						'join_type' => 'LEFT',
					),
					'_order_shipping_tax' => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping_tax',
						'join_type' => 'LEFT',
					),
					'_qty'                => array(
						'type'      => 'order_item_meta',
						'function'  => 'SUM',
						'name'      => 'order_item_count',
						'join_type' => 'LEFT',
					),
				),
				'group_by'            => 'refund_id',
				'order_by'            => 'post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => $this->get_order_status(),
			)
		);

		foreach ( $this->report_data->partial_refunds as $key => $order ) {
			$this->report_data->partial_refunds[ $key ]->net_refund = $order->total_refund - ( $order->total_shipping + $order->total_tax + $order->total_shipping_tax );
		}

		/**
		 * Refund lines - all partial refunds on all order types so we can plot full AND partial refunds on the chart.
		 */
		$this->report_data->refund_lines = (array) $this->get_order_report_data(
			array(
				'data'                => array(
					'ID'                  => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'refund_id',
					),
					'_refund_amount'      => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_refund',
					),
					'post_date'           => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
					'order_item_type'     => array(
						'type'      => 'order_item',
						'function'  => '',
						'name'      => 'item_type',
						'join_type' => 'LEFT',
					),
					'_order_total'        => array(
						'type'     => 'meta',
						'function' => '',
						'name'     => 'total_sales',
					),
					'_order_shipping'     => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping',
						'join_type' => 'LEFT',
					),
					'_order_tax'          => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_tax',
						'join_type' => 'LEFT',
					),
					'_order_shipping_tax' => array(
						'type'      => 'meta',
						'function'  => '',
						'name'      => 'total_shipping_tax',
						'join_type' => 'LEFT',
					),
					'_qty'                => array(
						'type'      => 'order_item_meta',
						'function'  => 'SUM',
						'name'      => 'order_item_count',
						'join_type' => 'LEFT',
					),
				),
				'group_by'            => 'refund_id',
				'order_by'            => 'post_date ASC',
				'query_type'          => 'get_results',
				'filter_range'        => true,
				'order_status'        => false,
				'parent_order_status' => $this->get_order_status( 'refunded' ),
			)
		);

		/**
		 * Total up refunds. Note: when an order is fully refunded, a refund line will be added.
		 */
		$this->report_data->total_tax_refunded          = 0;
		$this->report_data->total_shipping_refunded     = 0;
		$this->report_data->total_shipping_tax_refunded = 0;
		$this->report_data->total_refunds               = 0;

		$this->report_data->refunded_orders = array_merge( $this->report_data->partial_refunds, $this->report_data->full_refunds );

		foreach ( $this->report_data->refunded_orders as $key => $value ) {
			$this->report_data->total_tax_refunded          += floatval( $value->total_tax < 0 ? $value->total_tax * -1 : $value->total_tax );
			$this->report_data->total_refunds               += floatval( $value->total_refund );
			$this->report_data->total_shipping_tax_refunded += floatval( $value->total_shipping_tax < 0 ? $value->total_shipping_tax * -1 : $value->total_shipping_tax );
			$this->report_data->total_shipping_refunded     += floatval( $value->total_shipping < 0 ? $value->total_shipping * -1 : $value->total_shipping );

			// Only applies to parial.
			if ( isset( $value->order_item_count ) ) {
				$this->report_data->refunded_order_items += floatval( $value->order_item_count < 0 ? $value->order_item_count * -1 : $value->order_item_count );
			}
		}

		// Totals from all orders - including those refunded. Subtract refunded amounts.
		$this->report_data->total_tax          = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_tax' ) ) - $this->report_data->total_tax_refunded, 2 );
		$this->report_data->total_shipping     = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping' ) ) - $this->report_data->total_shipping_refunded, 2 );
		$this->report_data->total_shipping_tax = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_shipping_tax' ) ) - $this->report_data->total_shipping_tax_refunded, 2 );

		// Total the refunds and sales amounts. Sales subract refunds. Note - total_sales also includes shipping costs.
		$this->report_data->total_sales = wc_format_decimal( array_sum( wp_list_pluck( $this->report_data->orders, 'total_sales' ) ) - $this->report_data->total_refunds, 2 );
		$this->report_data->net_sales   = wc_format_decimal( $this->report_data->total_sales - $this->report_data->total_shipping - max( 0, $this->report_data->total_tax ) - max( 0, $this->report_data->total_shipping_tax ), 2 );

		// Calculate average based on net.
		$this->report_data->average_sales       = wc_format_decimal( $this->report_data->net_sales / ( $this->chart_interval + 1 ), 2 );
		$this->report_data->average_total_sales = wc_format_decimal( $this->report_data->total_sales / ( $this->chart_interval + 1 ), 2 );

		// Total orders and discounts also includes those which have been refunded at some point.
		$this->report_data->total_coupons         = number_format( array_sum( wp_list_pluck( $this->report_data->coupons, 'discount_amount' ) ), 2, '.', '' );
		$this->report_data->total_refunded_orders = absint( count( $this->report_data->full_refunds ) );

		// Total orders in this period, even if refunded.
		$this->report_data->total_orders = absint( array_sum( wp_list_pluck( $this->report_data->order_counts, 'count' ) ) );

		// Item items ordered in this period, even if refunded.
		$this->report_data->total_items = absint( array_sum( wp_list_pluck( $this->report_data->order_items, 'order_item_count' ) ) );

		// COG
		$this->get_cog_report_data();

		// 3rd party filtering of report data
		$this->report_data = apply_filters( 'woocommerce_admin_report_data', $this->report_data );
	}

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @version 2.0.0
	 * @return  array
	 */
	public function get_chart_legend() {
		$legend = array();
		$data   = $this->get_report_data();

		switch ( $this->chart_groupby ) {
			case 'day':
				$average_total_sales_title = sprintf(
					/* translators: %s: average total sales */
					__( '%s average gross daily sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
				);
				$average_sales_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average net daily sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_sales ) . '</strong>'
				);
				break;
			case 'month':
			default:
				$average_total_sales_title = sprintf(
					/* translators: %s: average total sales */
					__( '%s average gross monthly sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_total_sales ) . '</strong>'
				);
				$average_sales_title = sprintf(
					/* translators: %s: average sales */
					__( '%s average net monthly sales', 'woocommerce' ),
					'<strong>' . wc_price( $data->average_sales ) . '</strong>'
				);
				break;
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total sales */
				__( '%s gross sales in this period', 'woocommerce' ),
				'<strong>' . wc_price( $data->total_sales ) . '</strong>'
			),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and including shipping and taxes.', 'woocommerce' ),
			'color'            => $this->chart_colours['sales_amount'],
			'highlight_series' => 4, // WPFactory
		);
		if ( $data->average_total_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_total_sales_title,
				'color'            => $this->chart_colours['average'],
				'highlight_series' => 2,
			);
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: net sales */
				__( '%s net sales in this period', 'woocommerce' ),
				'<strong>' . wc_price( $data->net_sales ) . '</strong>'
			),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and excluding shipping and taxes.', 'woocommerce' ),
			'color'            => $this->chart_colours['net_sales_amount'],
			'highlight_series' => 5, // WPFactory
		);
		if ( $data->average_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_sales_title,
				'color'            => $this->chart_colours['net_average'],
				'highlight_series' => 3,
			);
		}

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total orders */
				__( '%s orders placed', 'woocommerce' ),
				'<strong>' . $data->total_orders . '</strong>'
			),
			'color'            => $this->chart_colours['order_count'],
			'highlight_series' => 1,
		);

		$legend[] = array(
			'title'            => sprintf(
				/* translators: %s: total items */
				__( '%s items purchased', 'woocommerce' ),
				'<strong>' . $data->total_items . '</strong>'
			),
			'color'            => $this->chart_colours['item_count'],
			'highlight_series' => 0,
		);

		// COG
		$legend = $this->get_cog_legend( $legend );

		return $legend;
	}

	/**
	 * Output the report.
	 *
	 * @version 2.0.0
	 */
	public function output_report() {
		$ranges = array(
			'year'       => __( 'Year', 'woocommerce' ),
			'last_month' => __( 'Last month', 'woocommerce' ),
			'month'      => __( 'This month', 'woocommerce' ),
			'7day'       => __( 'Last 7 days', 'woocommerce' ),
		);

		$this->chart_colours = array(
			'sales_amount'     => '#b1d4ea',
			'net_sales_amount' => '#3498db',
			'average'          => '#b1d4ea',
			'net_average'      => '#3498db',
			'order_count'      => '#dbe1e3',
			'item_count'       => '#ecf0f1',
		);

		// COG
		$this->get_cog_chart_colours();

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.NoNonceVerification

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ), true ) ) {
			$current_range = '7day';
		}

		$this->check_current_range_nonce( $current_range );
		$this->calculate_current_range( $current_range );

		include WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php';
	}

	/**
	 * Output an export link.
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.NoNonceVerification
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_attr( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
			data-exclude_series="2"
			data-groupby="<?php echo esc_attr( $this->chart_groupby ); ?>"
		>
			<?php esc_html_e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Round our totals correctly.
	 *
	 * @param array|string $amount Chart total.
	 *
	 * @return array|string
	 */
	private function round_chart_totals( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}

	/**
	 * Get the main chart.
	 *
	 * @version 2.0.0
	 */
	public function get_main_chart() {
		global $wp_locale;

		// Prepare data for report.
		$data = array(
			'order_counts'         => $this->prepare_chart_data( $this->report_data->order_counts, 'post_date', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'order_item_counts'    => $this->prepare_chart_data( $this->report_data->order_items, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'order_amounts'        => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'coupon_amounts'       => $this->prepare_chart_data( $this->report_data->coupons, 'post_date', 'discount_amount', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'shipping_amounts'     => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'refund_amounts'       => $this->prepare_chart_data( $this->report_data->refund_lines, 'post_date', 'total_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'net_refund_amounts'   => $this->prepare_chart_data( $this->report_data->refunded_orders, 'post_date', 'net_refund', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'shipping_tax_amounts' => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_shipping_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'tax_amounts'          => $this->prepare_chart_data( $this->report_data->orders, 'post_date', 'total_tax', $this->chart_interval, $this->start_date, $this->chart_groupby ),
			'net_order_amounts'    => array(),
			'gross_order_amounts'  => array(),
		);
		// COG
		$data = $this->get_cog_main_chart_data( $data );

		foreach ( $data['order_amounts'] as $order_amount_key => $order_amount_value ) {
			$data['gross_order_amounts'][ $order_amount_key ]     = $order_amount_value;
			$data['gross_order_amounts'][ $order_amount_key ][1] -= $data['refund_amounts'][ $order_amount_key ][1];

			$data['net_order_amounts'][ $order_amount_key ] = $order_amount_value;
			// Subtract the sum of the values from net order amounts.
			$data['net_order_amounts'][ $order_amount_key ][1] -=
				$data['net_refund_amounts'][ $order_amount_key ][1] +
				$data['shipping_amounts'][ $order_amount_key ][1] +
				$data['shipping_tax_amounts'][ $order_amount_key ][1] +
				$data['tax_amounts'][ $order_amount_key ][1];
		}

		// 3rd party filtering of report data.
		$data = apply_filters( 'woocommerce_admin_report_chart_data', $data );

		// Encode in json format.
		$chart_data = wp_json_encode(
			array_merge( array(
				'order_counts'        => array_values( $data['order_counts'] ),
				'order_item_counts'   => array_values( $data['order_item_counts'] ),
				'order_amounts'       => array_map( array( $this, 'round_chart_totals' ), array_values( $data['order_amounts'] ) ),
				'gross_order_amounts' => array_map( array( $this, 'round_chart_totals' ), array_values( $data['gross_order_amounts'] ) ),
				'net_order_amounts'   => array_map( array( $this, 'round_chart_totals' ), array_values( $data['net_order_amounts'] ) ),
				'shipping_amounts'    => array_map( array( $this, 'round_chart_totals' ), array_values( $data['shipping_amounts'] ) ),
				'coupon_amounts'      => array_map( array( $this, 'round_chart_totals' ), array_values( $data['coupon_amounts'] ) ),
				'refund_amounts'      => array_map( array( $this, 'round_chart_totals' ), array_values( $data['refund_amounts'] ) ),
			), $this->get_cog_main_chart_data_json( $data ) ) // COG
		);
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( $chart_data ); ?>' ) );
				var drawGraph = function( highlight ) {
					var series = [
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ); ?>",
							data: order_data.order_item_counts,
							color: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>',
							bars: { fillColor: '<?php echo esc_js( $this->chart_colours['item_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce' ) ); ?>",
							data: order_data.order_counts,
							color: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>',
							bars: { fillColor: '<?php echo esc_js( $this->chart_colours['order_count'] ); ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo esc_js( $this->barwidth ); ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average gross sales amount', 'woocommerce' ) ); ?>",
							data: [ [ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_total_sales ); ?> ] ],
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['average'] ); ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average net sales amount', 'woocommerce' ) ); ?>",
							data: [ [ <?php echo esc_js( min( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ], [ <?php echo esc_js( max( array_keys( $data['order_amounts'] ) ) ); ?>, <?php echo esc_js( $this->report_data->average_sales ); ?> ] ],
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['net_average'] ); ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Gross sales amount', 'woocommerce' ) ); ?>",
							data: order_data.gross_order_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['sales_amount'] ); ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
						},
						{
							label: "<?php echo esc_js( __( 'Net sales amount', 'woocommerce' ) ); ?>",
							data: order_data.net_order_amounts,
							yaxis: 2,
							color: '<?php echo esc_js( $this->chart_colours['net_sales_amount'] ); ?>',
							points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 5, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
						},
						<?php $this->get_cog_main_chart_jquery( $data ); // COG ?>
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars ) {
							highlight_series.bars.fillColor = '#9c5d90';
						}

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
							grid: {
								color: '#aaa',
								borderColor: 'transparent',
								borderWidth: 0,
								hoverable: true
							},
							xaxes: [ {
								color: '#aaa',
								position: "bottom",
								tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php echo ( 'day' === $this->chart_groupby ) ? '%d %b' : '%b'; ?>",
								monthNames: JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( array_values( $wp_locale->month_abbrev ) ) ); ?>' ) ),
								tickLength: 1,
								minTickSize: [1, "<?php echo esc_js( $this->chart_groupby ); ?>"],
								font: {
									color: "#aaa"
								}
							} ],
							yaxes: [
								{
									min: 0,
									minTickSize: 1,
									tickDecimals: 0,
									color: '#d4d9dc',
									font: { color: "#aaa" }
								},
								{
									position: "right",
									min: 0,
									tickDecimals: 2,
									alignTicksWithAxis: 1,
									color: 'transparent',
									font: { color: "#aaa" }
								}
							],
						}
					);

					jQuery('.chart-placeholder').resize();
				}

				drawGraph();

				jQuery('.highlight_series').hover(
					function() {
						drawGraph( jQuery(this).data('series') );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}
}
