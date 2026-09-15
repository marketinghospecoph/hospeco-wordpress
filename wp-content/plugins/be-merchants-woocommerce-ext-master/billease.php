<?php
/**
 * PHP version 7
 * Plugin Name: BillEase
 * Description: Buy Now, Pay Later! with BillEase Installments
 * Author: First Digital Finance Corporation
 * Author URI: https://www.firstdigitalfinance.com
 * Version: 1.1.9
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BILLEASE_BASEPATH', WP_PLUGIN_DIR . '/' . basename(__DIR__));
define('BILLEASE_BASEURL', WP_PLUGIN_URL . '/' . basename(__DIR__));

function BillEase_Woocommerce_Missing_notice()
{
    echo '<div class="error"><p><strong>' . sprintf(
            esc_html__(
                'BillEase requires WooCommerce to be '
                . 'installed and active. You can download %s here.',
                'woocommerce-gateway-billease'
            ),
            '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
        ) . '</strong></p></div>';
}

function BillEase_Init_Gateway_class()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'BillEase_Woocommerce_Missing_notice');
        return;
    }

    define('BILLEASE_MAIN_FILE', __FILE__);
    define('BILLEASE_VERSION', '1.1.9');
    define('BILLEASE_BASE_SANDBOX_URL', 'https://pub.staging.fdfc.io/be-transactions-api');
    define('BILLEASE_BASE_PRODUCTION_URL', 'https://pub.fdfc.io/be-transactions-api');

    if (!class_exists('BillEase')) :
        class BillEase
        {
            private static $_instance;

            public static function getInstance()
            {
                if (null === self::$_instance) {
                    self::$_instance = new self();
                }

                return self::$_instance;
            }

            private function __clone()
            {
                // empty
            }

            public function __wakeup()
            {
                // empty
            }

            private function __construct()
            {
                add_action('admin_init', array($this, 'install'));
                $this->init();
            }

            public function init()
            {
                $fileDir = dirname(__FILE__);
                include_once $fileDir . '/classes/billease.php';

                add_filter(
                    'woocommerce_payment_gateways',
                    array($this, 'addGateways')
                );

                if (version_compare(WC_VERSION, '3.4', '<')) {
                    add_filter(
                        'woocommerce_get_sections_checkout',
                        array($this, 'filterGatewayOrderAdmin')
                    );
                }

                $gateway_options = get_option('woocommerce_billease_settings');

                if ($gateway_options['installments_widget_product_page'] === 'yes') {
                    add_action('woocommerce_before_add_to_cart_form', [$this, 'showInstallmentsWidgetOnProductPage']);
                }
            }

            public function addGateways($methods)
            {
                $methods[] = 'BillEase_Gateway';

                return $methods;
            }

            public function filterGatewayOrderAdmin($sections)
            {
                unset($sections['billease']);

                $gatewayName = 'woocommerce-gateway-billease';
                $sections['billease'] = __(
                    'Pay with BillEase',
                    $gatewayName
                );

                $sections = [];

                return $sections;
            }

            public function showInstallmentsWidgetOnProductPage()
            {
                include BILLEASE_BASEPATH . '/partials/installments-widget-product-page.php';
            }

            public function install()
            {
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }

                if (!defined('IFRAME_REQUEST')
                    && (BILLEASE_VERSION !== get_option(
                            'billease_version'
                        ))
                ) {
                    do_action('woocommerce_billease_updated');

                    if (!defined('BILLEASE_INSTALLING')) {
                        define('BILLEASE_INSTALLING', true);
                    }

                    $this->updatePluginVersion();
                }
            }

            public function updatePluginVersion()
            {
                delete_option('billease_version');
                update_option('billease_version', BILLEASE_VERSION);
            }

        }

        BillEase::getInstance();
    endif;
}

add_action('plugins_loaded', 'billease_init_gateway_class');
