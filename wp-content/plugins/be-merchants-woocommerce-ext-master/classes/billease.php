<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$fileDir = dirname(__FILE__);
include_once $fileDir . '/billease-client.php';

/** Error identifiers */
define('BILLEASE_PROCESS_PAYMENT_BLOCK', 'Process Payment');
define('BILLEASE_CREATE_CHECKOUT_EVENT', 'createCheckout');


class BillEase_Gateway extends WC_Payment_Gateway
{
    private static $_instance;

    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct()
    {
        $this->id = 'billease';
        $this->has_fields = true;
        $this->method_title = 'Pay with BillEase';
        $this->method_description = 'Buy Now, Pay Later! with BillEase Installments';

        $this->supports = array(
            'products'
        );

        $this->initFormFields();

        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->title = 'Pay with BillEase';
        $this->description = $this->get_option('description');
        $this->sandbox = $this->get_option('sandbox');
        $this->merchant_code = $this->get_option('merchant_code');
        $this->shop_code = $this->get_option('shop_code');
        $this->merchant_jwt = $this->get_option('merchant_jwt');
        $this->installments_widget_product_page = $this->get_option('installments_widget_product_page');
        $this->installments_widget_is_pay_in_four = $this->get_option('installments_widget_is_pay_in_four');
        $this->installments_widget_tenor = $this->get_option('installments_widget_tenor');
        $this->installments_widget_dp_pct = $this->get_option('installments_widget_dp_pct');
        $this->installments_widget_is_zero_interest = $this->get_option('installments_widget_is_zero_interest');
        $this->installments_widget_marketing_link = $this->get_option('installments_widget_marketing_link');

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        $this->client = new BillEaseClient($this->sandbox === 'yes', $this->merchant_code, $this->shop_code, $this->merchant_jwt);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_resources']);
    }

    public function enqueue_resources()
    {
        wp_enqueue_style('billease', BILLEASE_BASEURL . '/assets/css/billease.css');
    }

    public function get_icon()
    {
        ob_start();
        include BILLEASE_BASEPATH . '/partials/payment-gateway-icon.php';
        $icon_html = ob_get_clean();

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    public function initFormFields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable BillEase Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'description' => array(
                'title' => 'Payment method description',
                'type' => 'textarea',
                'default' => 'Split your payments in 3 equal installments or configure terms up to 12 months using this payment method. You can instantly sign up or login at checkout to complete your purchase.'
            ),
            'sandbox' => array(
                'title' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Enable sandbox mode to test payment transactions with BillEase.'
            ),
            'merchant_code' => array(
                'title' => 'Merchant Code',
                'type' => 'text',
            ),
            'shop_code' => array(
                'title' => 'Shop Code',
                'type' => 'text'
            ),
            'merchant_jwt' => array(
                'title' => 'Merchant JWT',
                'type' => 'textarea'
            ),
            'installments_widget_product_page' => array(
                'title' => 'Installments widget on product page',
                'type' => 'checkbox',
                'description' => 'Enable installments widget to show sample BillEase installments on product page.',
                'default' => 'no'
            ),
            'installments_widget_is_pay_in_four' => array(
                'title' => 'Whether to use pay in 4 in the installments widget',
                'type' => 'checkbox',
                'description' => 'When installments widget is enabled, use pay in 4.',
                'default' => 'no'
            ),
            'installments_widget_tenor' => array(
                'title' => 'Tenor (months) used in the installments widget (1-12)',
                'type' => 'text',
                'description' => 'When installments widget is enabled, this will be used for tenor/months.',
                'default' => '3'
            ),
            'installments_widget_dp_pct' => array(
                'title' => 'Down payment percentage used in the installments widget (0-100)',
                'type' => 'text',
                'description' => 'When installments widget is enabled, this will be used for calculating down payment.',
                'default' => '0'
            ),
            'installments_widget_is_zero_interest' => array(
                'title' => 'Whether to use zero-interest in the installments widget',
                'type' => 'checkbox',
                'description' => 'When installments widget is enabled, use zero interest instead of the default BillEase interest rate.',
                'default' => 'no'
            ),
            'installments_widget_marketing_link' => array(
                'title' => 'Installments widget marketing link',
                'type' => 'text',
                'description' => 'When installments widget is enabled, this URL will be used for Learn More link.',
                'default' => 'https://billease.ph'
            )
        );
    }

    public function process_payment($orderId)
    {
        global $order;
        $order = wc_get_order($orderId);

        function getItemPayload($items, $item)
        {
            global $order;
            $product = wc_get_product($item->get_product_id());

            $total_tax = array_sum($item->get_taxes()['total']) ?: 0;

            array_push(
                $items,
                array(
                    'code' => strval($product->get_sku() ? $product->get_sku() : $item->get_product_id()),
                    'item' => $item->get_name(),
                    'price' => ($item->get_total() + $total_tax) / $item->get_quantity(),
                    'quantity' => $item->get_quantity(),
                    'currency' => $order->get_currency(),
                    'url_item' => $product->get_permalink(),
                    'url_img' => wp_get_attachment_image_url($product->get_image_id(), 'full') ?: null,
                    'category' => strip_tags(wc_get_product_category_list($product->get_id())),
                    'item_type' => 'item'
                )
            );

            return $items;
        }

        $items = array_reduce($order->get_items(), 'getItemPayload', []);
        $total_shipping = floatval($order->get_shipping_total()) + floatval($order->get_shipping_tax());

        if (!!$total_shipping) {
            $items[] = array(
                'code' => 'shipping_fee',
                'item' => 'Shipping Fee',
                'price' => $total_shipping,
                'quantity' => 1,
                'currency' => $order->get_currency(),
                'item_type' => 'fee'
            );
        }

        foreach($order->get_items('fee') as $item_id => $item_fee ) {
            $fee_name = $item_fee->get_name();
            $fee_total = $item_fee->get_total();

            if (!!$fee_total) {
                $items[] = array(
                    'code' => $fee_name,
                    'item' => $fee_name,
                    'price' => $fee_total + $item_fee->get_total_tax(),
                    'quantity' => $item_fee->get_quantity(),
                    'currency' => $order->get_currency(),
                    'item_type' => 'fee'
                );
            }
        }

        $amount = floatval($order->get_total());
        $total_item_amount = array_sum(
            array_map(
                function($item) {
                    return $item['price'] * $item['quantity'];
                },
                $items
            )
        );
        $total_discount = floatval($order->get_discount_total()) + floatval($order->get_discount_tax());

        if (!!$total_discount && $total_item_amount > $amount) {
            $items[] = array(
                'code' => 'discount',
                'item' => 'Discount',
                'price' => -$total_discount,
                'quantity' => 1,
                'currency' => $order->get_currency(),
                'item_type' => 'fee'
            );
            $total_item_amount = $total_item_amount - $total_discount;
        }

        if ($total_item_amount > $amount) {
            foreach ($items as $key => $item) {
                if ($item['item_type'] === 'fee') {
                    $items[$key]['price'] = round($item['price']);
                }
            }
        }

        $payload = array(
            'merchant_code' => $this->merchant_code,
            'shop_code' => $this->shop_code,
            'amount' => $amount,
            'currency' => $order->get_currency(),
            'checkout_type' => 'standard',
            'is_async' => false,
            'order_id' => strval($orderId),
            'url_redirect' => $order->get_checkout_order_received_url(),
            'callbackapi_url' => get_home_url() . '/wp-json/wc/v3/orders/' . $orderId,
            'customer' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'internal_user_id' => $order->get_customer_id(),
                'adr_shipping' => array(
                    'addr_type' => 'shipping',
                    'country' => $order->get_shipping_country() ?: null,
                    'province' => (method_exists($order, 'get_shipping_province') ? $order->get_shipping_province() : $order->get_shipping_state()) ?: null,
                    'city' => $order->get_shipping_city() ?: null,
                    'barangay' => method_exists($order, 'get_shipping_barangay') ? $order->get_shipping_barangay() : null,
                    'street' => method_exists($order, 'get_shipping_street') ? $order->get_shipping_street() : null,
                    'address' => $order->get_shipping_address_1() ?: null
                ),
                'adr_billing' => array(
                    'addr_type' => 'billing',
                    'country' => $order->get_billing_country() ?: null,
                    'province' => (method_exists($order, 'get_billing_province') ? $order->get_billing_province() : $order->get_billing_state()) ?: null,
                    'city' => $order->get_billing_city() ?: null,
                    'barangay' => method_exists($order, 'get_billing_barangay') ? $order->get_billing_barangay() : null,
                    'street' => method_exists($order, 'get_billing_street') ? $order->get_billing_street() : null,
                    'address' => $order->get_billing_address_1() ?: null
                )
            ),
            'items' => $items
        );

        $encodedPayload = json_encode($payload);

        $response = $this->client->createCheckout($encodedPayload);

        if (array_key_exists('error', $response)) {
            wc_get_logger()->log('error', '[' . BILLEASE_PROCESS_PAYMENT_BLOCK . '][' . BILLEASE_CREATE_CHECKOUT_EVENT . '] ' . json_encode($response['error']));
            return null;
        }

        return array(
            'result' => 'success',
            'redirect' => $response['redirect_url']
        );
    }
}
