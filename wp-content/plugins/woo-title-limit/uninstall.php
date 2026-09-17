<?php
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_names = [
	'wtl_opt_shop',
	'wtl_opt_product',
	'wtl_opt_category',
	'wtl_opt_home',
	'wtl_opt_tag',
	'wtl_opt_general',
];

foreach ( $option_names as $option_name ) {
	delete_option( $option_name );
}
?>