<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paypal_V4_Fix {

	/**
	 * Instance.
	 *
	 * @var Paypal_V4_Fix|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Paypal_V4_Fix
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		add_filter(
			'woocommerce_paypal_payments_simulate_cart_enabled',
			'__return_false',
			999
		);

		add_filter(
			'ppcp_ditch_items_breakdown',
			'__return_true',
			999
		);

		add_filter(
			'ppcp_create_order_request_body_data',
			array( $this, 'override_create_order_amount' ),
			999
		);

		add_filter(
			'ppcp_patch_order_request_body_data',
			array( $this, 'override_patch_order_amount' ),
			999
		);
	}

	/**
	 * Get partial payment amount.
	 *
	 * @return string|false
	 */
	private function get_partial_amount() {

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		$partial_selected = WC()->session->get( 'pi_partial_payment' );

		if ( empty( $partial_selected ) ) {
			return false;
		}

		$advance_amount = WC()->session->get( 'pi_advance_amount' );

		if ( empty( $advance_amount ) || (float) $advance_amount <= 0 ) {
			return false;
		}

		return number_format(
			(float) $advance_amount,
			2,
			'.',
			''
		);
	}

	/**
	 * Override PayPal create order amount.
	 *
	 * @param array $data Request data.
	 *
	 * @return array
	 */
	public function override_create_order_amount( $data ) {

		$partial_amount = $this->get_partial_amount();

		if ( ! $partial_amount ) {
			return $data;
		}

		if ( isset( $data['purchase_units'][0]['amount'] ) ) {

			$currency = $data['purchase_units'][0]['amount']['currency_code'] ?? get_woocommerce_currency();

			$data['purchase_units'][0]['amount'] = array(
				'currency_code' => $currency,
				'value'         => $partial_amount,
			);
		}

		if ( isset( $data['purchase_units'][0]['items'] ) ) {
			unset( $data['purchase_units'][0]['items'] );
		}

		return $data;
	}

	/**
	 * Override PayPal patch order amount.
	 *
	 * @param array $patches Patch data.
	 *
	 * @return array
	 */
	public function override_patch_order_amount( $patches ) {

		$partial_amount = $this->get_partial_amount();

		if ( ! $partial_amount ) {
			return $patches;
		}

		foreach ( $patches as $key => $patch ) {

			if ( isset( $patch['value']['amount'] ) ) {

				$currency = $patch['value']['amount']['currency_code'] ?? get_woocommerce_currency();

				$patches[ $key ]['value']['amount'] = array(
					'currency_code' => $currency,
					'value'         => $partial_amount,
				);
			}

			if ( isset( $patch['value']['items'] ) ) {
				unset( $patches[ $key ]['value']['items'] );
			}
		}

		return $patches;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton.' );
	}
}

Paypal_V4_Fix::get_instance();