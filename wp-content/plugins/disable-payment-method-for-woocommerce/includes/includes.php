<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/review.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/help.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-common-cart.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/pisol.class.form.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-store-data-order.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-blacklist-db.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/dependency.php';

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-disable-payment-method-for-woocommerce-menu.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-payment-method-disable-rules.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-add-disable-payment-rule.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/selection_rules/includes.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/cod-deposit.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-third-party-support.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/advance-fees/admin.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/extra-setting.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-custom-field.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-analytics.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/blacklist.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-accesscontrol.php';

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-filter-payment-methods.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-apply-fees.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-partial-payment-ui.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-partial-payment.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-partial-payment-order-state.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-partial-payment-email.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-partial-payment-session.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-apply-order-fees.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-phone-pay.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-partial-order-pay.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-js.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-safety.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-gateway-specific.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-blacklist.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-paypal-v4-fix.php';

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'block/includes.php';

/**
 * load this file on wp_loaded as it has class defination that is extension of WC_order which will throw error if loaded before
 */
/**
 * we wont use require_once else woocommerce_init hook will not try to re include the file
 */
add_action('wp_loaded', function(){
    include plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shop-deposit-order.php';
});

/**
 * some time woocommerce has not completed loading of file in wp_loadd so we are loading this on woocommerce_init again to avoid any issue and we have added a check in class file to avoid re-declaration issue
 */
add_action('woocommerce_init', function(){
    include plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shop-deposit-order.php';
});