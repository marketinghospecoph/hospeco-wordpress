<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Create a helper function for easy SDK access.
function dgoraAsfwFs() {
    global $dgoraAsfwFs;
    if ( !isset( $dgoraAsfwFs ) ) {
        // Include Freemius SDK.
        // Freemius SDK could be loaded via Composer autoload, but we disabled that with tools/fix-freemius.php.
        require_once dirname( dirname( __FILE__ ) ) . '/vendor/freemius/wordpress-sdk/start.php';
        // Activate multisite network integration.
        if ( !defined( 'WP_FS__PRODUCT_700_MULTISITE' ) ) {
            define( 'WP_FS__PRODUCT_700_MULTISITE', true );
        }
        $dgoraAsfwFs = fs_dynamic_init( array(
            'id'                => '700',
            'slug'              => 'ajax-search-for-woocommerce',
            'type'              => 'plugin',
            'public_key'        => 'pk_f4f2a51dbe0aee43de0692db77a3e',
            'is_premium'        => true,
            'premium_suffix'    => 'Pro',
            'has_addons'        => false,
            'has_paid_plans'    => true,
            'wp_org_gatekeeper' => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
            'menu'              => array(
                'slug'        => 'dgwt_wcas_settings',
                'parent'      => array(
                    'slug' => 'woocommerce',
                ),
                'account'     => false,
                'contact'     => false,
                'support'     => false,
                'pricing'     => false,
                'affiliation' => false,
            ),
            'is_live'           => true,
        ) );
    }
    return $dgoraAsfwFs;
}

// Init Freemius.
dgoraAsfwFs();
// Signal that SDK was initiated.
do_action( 'dgoraAsfwFs_loaded' );
dgoraAsfwFs()->add_filter( 'plugin_icon', function () {
    return dirname( dirname( __FILE__ ) ) . '/assets/img/logo-128.png';
} );
// Uninstall
if ( dgoraAsfwFs()->is__premium_only() ) {
    dgoraAsfwFs()->add_action( 'after_uninstall', function () {
        if ( !class_exists( '\\DgoraWcas\\Uninstall' ) ) {
            require_once __DIR__ . '/../includes/Uninstall.php';
        }
        \DgoraWcas\Uninstall::afterUninstall__premium_only();
    } );
}
if ( !dgoraAsfwFs()->is_premium() ) {
    dgoraAsfwFs()->add_action( 'after_uninstall', function () {
        if ( !class_exists( '\\DgoraWcas\\Uninstall' ) ) {
            require_once __DIR__ . '/../includes/Uninstall.php';
        }
        \DgoraWcas\Uninstall::afterUninstall();
    } );
}