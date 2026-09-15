<?php

if ( ! class_exists( 'ET_Builder_Element' ) && ! class_exists( 'ET_Builder_Module' ) ) {
	return;
}

$module_files = glob( __DIR__ . '/modules/*/*.php' );

// Load custom Divi Builder modules. Use basename checks so this works on Windows and Unix paths.
foreach ( (array) $module_files as $module_file ) {
	if ( ! $module_file ) {
		continue;
	}

	$module_dir  = basename( dirname( $module_file ) );
	$module_name = basename( $module_file, '.php' );

	if ( $module_dir === $module_name ) {
		require_once $module_file;
	}
}
