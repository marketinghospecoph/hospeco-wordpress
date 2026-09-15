<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function brbrand_divi5_get_modules( $module_name = '' ) {
    $modules = array(
        'brbrand/brand-catalog' => array(
            'name'         => 'brbrand/brand-catalog',
            'module_dir'   => 'brand-catalog',
            'module_class' => 'brbrand_divi5_brand_catalog',
            'widget_class' => 'berocket_alphabet_brand_widget',
            'defaults'     => array(
                'use_name'           => 'on',
                'img_display'        => 'on',
                'img_width'          => '',
                'img_width_units'    => '%',
                'img_height'         => '64',
                'img_height_units'   => 'px',
                'img_fit'            => 'cover',
                'img_align'          => 'above',
                'orderby'            => 'alphabet',
                'order'              => 'ASC',
                'count'              => 'on',
                'hide_empty'         => 'on',
                'out_of_stock'       => 'off',
                'featured_first'     => 'on',
                'show_all'           => 'on',
                'category_only'      => 'off',
                'hierarchy'          => 'all',
                'brands_include'     => '',
                'groupby'            => 'alphabet',
                'style'              => 'vertical',
                'column'             => '2',
            ),
        ),
        'brbrand/brands-list' => array(
            'name'         => 'brbrand/brands-list',
            'module_dir'   => 'brands-list',
            'module_class' => 'brbrand_divi5_brands_list',
            'widget_class' => 'berocket_product_brand_widget',
            'defaults'     => array(
                'use_name'           => 'on',
                'img_display'        => 'on',
                'img_width'          => '100',
                'img_width_units'    => '%',
                'img_height'         => '100',
                'img_height_units'   => '%',
                'img_fit'            => 'cover',
                'img_align'          => 'none',
                'orderby'            => 'title',
                'order'              => 'asc',
                'count'              => 'on',
                'hide_empty'         => 'on',
                'out_of_stock'       => 'on',
                'featured_first'     => 'on',
                'slider'             => 'on',
                'show_all'           => 'on',
                'category_only'      => 'on',
                'hierarchy'          => 'all',
                'brands_include'     => '',
                'per_row'            => '3',
                'brands_number'      => 'All',
                'padding'            => '3',
                'margin'             => '3',
                'border_width'       => '',
                'border_color'       => '',
            ),
        ),
        'brbrand/brands-products' => array(
            'name'         => 'brbrand/brands-products',
            'module_dir'   => 'brands-products',
            'module_class' => 'brbrand_divi5_brands_products',
            'widget_class' => 'berocket_product_list_widget',
            'defaults'     => array(
                'brands'          => '',
                'per_page'        => '',
                'columns'         => '4',
                'orderby'         => 'title',
                'order'           => 'asc',
                'slider'          => 'off',
                'hide_brands'     => 'off',
                'hide_pagination' => 'off',
                'hide_labels'     => 'off',
                'brand_field'     => 'name',
            ),
        ),
        'brbrand/brands-description' => array(
            'name'         => 'brbrand/brands-description',
            'module_dir'   => 'brands-description',
            'module_class' => 'brbrand_divi5_brands_description',
            'widget_class' => 'berocket_product_brand_description_widget',
            'defaults'     => brbrand_divi5_description_defaults(),
        ),
        'brbrand/product-brands-info' => array(
            'name'         => 'brbrand/product-brands-info',
            'module_dir'   => 'product-brands-info',
            'module_class' => 'brbrand_divi5_product_brands_info',
            'widget_class' => 'berocket_product_brands_info_widget',
            'defaults'     => array_merge(
                brbrand_divi5_description_defaults(),
                array(
                    'product'  => '',
                    'limit'    => '1',
                    'featured' => 'off',
                )
            ),
        ),
    );

    if ( '' !== $module_name ) {
        return isset( $modules[ $module_name ] ) ? $modules[ $module_name ] : array();
    }

    return $modules;
}

function brbrand_divi5_description_defaults() {
    return array(
        'brand_id'                 => '',
        'display_title'            => 'off',
        'display_categories'       => 'off',
        'display_description'      => 'off',
        'thumbnail_display'        => 'off',
        'thumbnail_width'          => '100',
        'thumbnail_width_units'    => '%',
        'thumbnail_height'         => '',
        'thumbnail_height_units'   => 'px',
        'thumbnail_fit'            => 'cover',
        'thumbnail_align'          => 'none',
        'banner_display'           => 'off',
        'banner_width'             => '100',
        'banner_width_units'       => '%',
        'banner_height'            => '',
        'banner_height_units'      => 'px',
        'banner_fit'               => 'cover',
        'banner_align'             => 'center',
        'related_products_display' => 'off',
        'per_page'                 => '',
        'columns'                  => '4',
        'orderby'                  => 'title',
        'order'                    => 'asc',
        'slider'                   => 'off',
        'hide_brands'              => 'off',
        'display_link'             => 'off',
        'featured'                 => 'off',
    );
}
