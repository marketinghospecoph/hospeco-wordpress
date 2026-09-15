<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BrBrand_Divi5_Widget_Renderer {
    protected $module_name;
    protected $widget_class;
    protected $defaults;

    public function __construct( $args ) {
        $this->module_name  = $args['name'];
        $this->widget_class = $args['widget_class'];
        $this->defaults     = $args['defaults'];
    }

    public function render_widget( $attrs ) {
        $atts = $this->attrs_to_widget_atts( $attrs );

        ob_start();
        the_widget( $this->widget_class, $atts );
        return ob_get_clean();
    }

    public function attrs_to_widget_atts( $attrs ) {
        $atts = $this->defaults;

        foreach ( array_keys( $this->defaults ) as $key ) {
            $value = $this->get_attr_value( $attrs, $key );
            if ( null !== $value ) {
                $atts[ $key ] = $value;
            }
        }

        $atts = $this->normalize_booleans( $atts );

        if ( isset( $atts['brands_number'] ) && ( '' === $atts['brands_number'] || 'All' === $atts['brands_number'] || 0 === intval( $atts['brands_number'] ) ) ) {
            $atts['brands_number'] = '';
        }

        if ( 'berocket_product_list_widget' === $this->widget_class && empty( $atts['brand_field'] ) ) {
            $atts['brand_field'] = 'name';
        }

        if ( 'berocket_product_brands_info_widget' === $this->widget_class && isset( $atts['product'] ) && 'current' === $atts['product'] ) {
            $atts['product'] = '';
        }

        return $atts;
    }

    protected function get_attr_value( $attrs, $key ) {
        if ( isset( $attrs[ $key ]['innerContent']['desktop']['value'] ) ) {
            return $this->normalize_attr_value( $attrs[ $key ]['innerContent']['desktop']['value'] );
        }

        if ( isset( $attrs[ $key ] ) && ! is_array( $attrs[ $key ] ) ) {
            return $attrs[ $key ];
        }

        return null;
    }

    protected function normalize_attr_value( $value ) {
        if ( is_array( $value ) ) {
            foreach ( array( 'value', 'number', 'amount' ) as $value_key ) {
                if ( isset( $value[ $value_key ] ) && '' !== $value[ $value_key ] && ! is_array( $value[ $value_key ] ) ) {
                    return $value[ $value_key ];
                }
            }

            if ( isset( $value['value']['value'] ) && '' !== $value['value']['value'] && ! is_array( $value['value']['value'] ) ) {
                return $value['value']['value'];
            }

            return '';
        }

        return $value;
    }

    protected function normalize_booleans( $atts ) {
        foreach ( $atts as $key => $value ) {
            if ( 'on' === $value || 'off' === $value ) {
                $atts[ $key ] = ( 'on' === $value ) ? 1 : '';
            } elseif ( is_bool( $value ) ) {
                $atts[ $key ] = $value ? 1 : '';
            }
        }

        return $atts;
    }
}
