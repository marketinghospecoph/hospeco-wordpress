<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Pi_dpmw_Order_Blocker {

    public static function init() {
        add_action('woocommerce_after_checkout_validation', array(__CLASS__,'validate_order'),10,2);

        /**
         * this is for block based checkout page
         */
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'validate_order_block' ), 10, 2 );
    }

    public static function validate_order($request, $errors) {
        $email = isset($request['billing_email']) ? sanitize_email($request['billing_email']) : '';
        $ip    = self::get_client_ip();
        if ($email) {
            $email = self::normalize_email($email);
        }

        if ($email && Pi_dpmw_Blocklist_DB::is_blocked('email', $email)) {
            $errors->add('email_blocked', self::email_blocked_msg());
        }

        if ($ip && Pi_dpmw_Blocklist_DB::is_blocked('ip', $ip)) {
            $errors->add('ip_blocked', self::ip_blocked_msg());
        }

    }

    public static function validate_order_block($order, $request) {
        $email = $order->get_billing_email();
        $ip    = self::get_client_ip();
        if ($email) {
            $email = self::normalize_email($email);
        }

        if ($email && Pi_dpmw_Blocklist_DB::is_blocked('email', $email)) {
            throw new \WC_Data_Exception('invalid_checkout_data', self::email_blocked_msg() );
        }

        if ($ip && Pi_dpmw_Blocklist_DB::is_blocked('ip', $ip)) {
            throw new \WC_Data_Exception('invalid_checkout_data', self::ip_blocked_msg() );
        }

    }

    private static function normalize_email($email) {
        $email = strtolower(trim($email));
        if (strpos($email, '@') !== false) {
            list($local, $domain) = explode('@', $email, 2);
            $plus_supported_domains = ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'protonmail.com'];
            if (in_array($domain, $plus_supported_domains)) {
                $local = preg_replace('/\+.*/', '', $local);
            }
            return $local . '@' . $domain;
        }
        return $email;
    }

    static function email_blocked_msg() {
        return get_option('pisol_dpmw_email_blocked_msg', __('Your email address is blocked from placing orders.', 'disable-payment-method-for-woocommerce'));
    }

    static function ip_blocked_msg() {
        return get_option('pisol_dpmw_ip_blocked_msg', __('Your IP address is blocked from placing orders.', 'disable-payment-method-for-woocommerce'));
    }

    private static function get_client_ip() {
        foreach (
            [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ] as $key
        ) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return '';
    }
}

Pi_dpmw_Order_Blocker::init();
