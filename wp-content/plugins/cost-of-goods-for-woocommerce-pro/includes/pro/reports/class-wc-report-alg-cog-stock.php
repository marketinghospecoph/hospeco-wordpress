<?php
/**
 * Cost of Goods for WooCommerce - Report - Stock
 *
 * @version 2.4.0
 * @since   1.3.2
 * @author  WPFactory
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Report_Alg_Cost_Of_Goods_Stock' ) ) :

class WC_Report_Alg_Cost_Of_Goods_Stock {

	/**
	 * Constructor.
	 *
	 * @version 1.3.2
	 * @since   1.3.2
	 */
	function __construct() {
		return true;
	}

	/**
	 * get_action_link.
	 *
	 * @version 1.5.2
	 * @since   1.5.2
	 */
	function get_action_link( $action, $type ) {
		return add_query_arg( array( 'alg_wc_cog_stock_report_action' => $action, 'alg_wc_cog_stock_report_action_param' => $type ) );
	}

	/**
	 * get_actions.
	 *
	 * @version 1.5.2
	 * @since   1.5.2
	 */
	function get_actions( $type ) {
		return '<span style="float:right;">' .
			'<a target="_blank" href="' . $this->get_action_link( 'print', $type )  . '">[' . __( 'print', 'cost-of-goods-for-woocommerce' )  . ']' . '</a>' . ' ' .
			'<a target="_blank" href="' . $this->get_action_link( 'export', $type ) . '">[' . __( 'export', 'cost-of-goods-for-woocommerce' ) . ']' . '</a>' .
		'</span>';
	}

	/**
	 * format_price.
	 *
	 * @version 1.5.2
	 * @since   1.5.2
	 * @todo    [maybe] `round` on `! $do_format_price`?
	 */
	function format_price( $value, $do_format_price = true ) {
		return ( $do_format_price ? wc_price( $value ) : $value );
	}

	/**
	 * output_report.
	 *
	 * @version 2.4.0
	 * @since   1.3.2
	 * @todo    [later] maybe optimize (`get_post_meta( $product_id, '_price', true )`
	 * @todo    [later] save results in transients
	 * @todo    [later] use `wc_get_products`
	 */
	function output_report( $return = false, $do_format_price = true ) {
		$do_get_post_meta = get_option( 'alg_wc_cog_report_stock_price_method', 'default' );
		$args = array(
			'post_type'      => array( 'product', 'product_variation' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_alg_wc_cog_cost',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'DECIMAL',
				),
				array(
					'key'     => '_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'DECIMAL',
				),
				array(
					'key'     => '_stock',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'DECIMAL',
				),
			),
		);
		$loop = new WP_Query( apply_filters( 'alg_wc_cog_stock_report_args', $args ) );
		$html = '';
		if ( $loop->have_posts() ) {
			$total_cost   = 0;
			$total_price  = 0;
			$total_stock  = 0;
			$table_data   = array();
			$table_data[] = array(
				'#',
				__( 'Product ID', 'cost-of-goods-for-woocommerce' ),
				__( 'SKU', 'cost-of-goods-for-woocommerce' ),
				__( 'Title', 'cost-of-goods-for-woocommerce' ),
				__( 'Stock', 'cost-of-goods-for-woocommerce' ),
				__( 'Cost', 'cost-of-goods-for-woocommerce' ),
				__( 'Price', 'cost-of-goods-for-woocommerce' ),
				__( 'Profit', 'cost-of-goods-for-woocommerce' ),
				__( 'Total Cost', 'cost-of-goods-for-woocommerce' ),
				__( 'Total Price', 'cost-of-goods-for-woocommerce' ),
				__( 'Total Profit', 'cost-of-goods-for-woocommerce' ),
			);
			foreach ( $loop->posts as $i => $product_id ) {
				$cost           = (float) alg_wc_cog()->core->products->get_product_cost( $product_id );
				$product        = wc_get_product( $product_id );
				$price          = alg_wc_cog()->core->products->get_product_price( $product, 'excluding_tax_with_price_from_db' == $do_get_post_meta ? array( 'params' => array( array( 'price' => get_post_meta( $product_id, '_price', true ) ) ) ) : array() );
				$stock          = get_post_meta( $product_id, '_stock', true );
				$profit         = $price - $cost;
				$total_cost    += $cost  * $stock;
				$total_price   += $price * $stock;
				$total_stock   += $stock;
				$table_data[]   = array(
					$i + 1,
					'<a target="_blank" href="' . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) . '">' . $product_id . '</a>',
					$product->get_sku(),
					get_the_title( $product_id ),
					( float ) $stock,
					$do_format_price ? alg_wc_cog_format_cost( $cost ) : $cost,
					$this->format_price( $price,               $do_format_price ),
					$this->format_price( $profit,              $do_format_price ),
					$do_format_price ? alg_wc_cog_format_cost( $cost * $stock ) : $cost * $stock,
					$this->format_price( ( $price  * $stock ), $do_format_price ),
					$this->format_price( ( $profit * $stock ), $do_format_price ),
				);
			}
			$average_cost      = ( 0 != $total_stock ? ( $total_cost   / $total_stock ) : 0 );
			$average_price     = ( 0 != $total_stock ? ( $total_price  / $total_stock ) : 0 );
			$total_profit      = $total_price - $total_cost;
			$profit_percentage = ( 0 != $total_cost  ? round( ( $total_profit / $total_cost  * 100 ), 2 ) : 0 );
			$profit_margin     = ( 0 != $total_price ? round( ( $total_profit / $total_price * 100 ), 2 ) : 0 );
			$total_table_data  = array(
				array(
					__( 'Total Stock', 'cost-of-goods-for-woocommerce' ),
					$total_stock,
				),
				array(
					__( 'Total Products', 'cost-of-goods-for-woocommerce' ),
					$loop->found_posts,
				),
				array(
					__( 'Total Cost',  'cost-of-goods-for-woocommerce' ),
					$do_format_price ? alg_wc_cog_format_cost( $total_cost ) : $total_cost,
				),
				array(
					__( 'Total Price', 'cost-of-goods-for-woocommerce' ),
					$this->format_price( $total_price, $do_format_price ),
				),
				array(
					__( 'Total Profit', 'cost-of-goods-for-woocommerce' ),
					$this->format_price( $total_profit, $do_format_price ),
				),
				array(
					__( 'Average Cost', 'cost-of-goods-for-woocommerce' ),
					$do_format_price ? alg_wc_cog_format_cost( $average_cost ) : $average_cost,
				),
				array(
					__( 'Average Price', 'cost-of-goods-for-woocommerce' ),
					$this->format_price( $average_price, $do_format_price ),
				),
				array(
					__( 'Average Profit', 'cost-of-goods-for-woocommerce' ),
					$this->format_price( ( $average_price - $average_cost ), $do_format_price ),
				),
				array(
					__( 'Profit Percentage', 'cost-of-goods-for-woocommerce' ) .
						wc_help_tip( sprintf( '%s / %s', __( 'Total Profit', 'cost-of-goods-for-woocommerce' ), __( 'Total Cost', 'cost-of-goods-for-woocommerce' ) ) ),
					$profit_percentage . '%',
				),
				array( __( 'Profit Margin', 'cost-of-goods-for-woocommerce' ) .
						wc_help_tip( sprintf( '%s / %s', __( 'Total Profit', 'cost-of-goods-for-woocommerce' ), __( 'Total Price', 'cost-of-goods-for-woocommerce' ) ) ),
					$profit_margin . '%',
				),
			);
			if ( $return ) {
				switch ( $return ) {
					case 'totals':
						return $total_table_data;
					case 'products':
						return $table_data;
				}
			}
			$html .= '<p><em>' . __( 'Only products with cost, price and stock are counted in report.', 'cost-of-goods-for-woocommerce' ) . ' ' .
				__( 'All prices exclude tax.', 'cost-of-goods-for-woocommerce' ) . '</em></p>';
			$html .= '<h2>' . __( 'Totals', 'cost-of-goods-for-woocommerce' ) . $this->get_actions( 'totals' ) . '</h2>';
			$html .= alg_wc_cog_get_table_html( $total_table_data,
				array( 'table_heading_type' => 'vertical', 'table_class' => 'widefat striped' ) );
			$html .= '<h2>' . __( 'Products', 'cost-of-goods-for-woocommerce' ) . $this->get_actions( 'products' ) . '</h2>';
			$html .= alg_wc_cog_get_table_html( $table_data,
				array( 'table_heading_type' => 'horizontal', 'table_class' => 'wp-list-table widefat fixed striped stock' ) );
		} else {
			$html .= '<p><em>' . __( 'There are no products with cost, price and stock.', 'cost-of-goods-for-woocommerce' ) . '</em></p>';
		}
		echo '<div id="poststuff" class="woocommerce-reports-wide">' . $html . '</div>';
	}

}

endif;
