<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/modules.php';
require_once __DIR__ . '/WidgetRenderer.php';
require_once __DIR__ . '/WidgetModule.php';

function brbrand_divi5_register_modules( $dependency_tree ) {
    foreach ( brbrand_divi5_get_modules() as $module ) {
        $dependency_tree->add_dependency( new BrBrand_Divi5_Widget_Module( $module ) );
    }
}
