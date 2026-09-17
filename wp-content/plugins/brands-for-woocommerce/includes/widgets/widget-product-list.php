<?php
class BeRocket_product_list_Widget extends BeRocket_Base_Product_List_Widget {
	public function __construct() {
        parent::__construct( 
            "berocket_product_list_widget", 
            __( "BeRocket Brands Product List", 'brands-for-woocommerce' ),
            array( "description" => __( 'Product list for given brands (by ids or slugs)', 'brands-for-woocommerce' ) ) 
        );
        // $this->template = 'list';

        $this->defaults += array(
            'brands'          => '',
            'brand_field'     => 'name',
            'hide_pagination' => false,
            'hide_labels'     => false,
        );

        $this->form_fields += array(
            'hide_pagination' => array(
                'title' => __( 'Hide pagination', 'brands-for-woocommerce' ),
                'type' => 'checkbox',
                'class' => 'br_brands_checkbox_block'
            ),
            'hide_labels' => array(
                'title' => __( 'Hide <a href="https://berocket.com/product/woocommerce-advanced-product-labels?utm_source=free_plugin&utm_medium=brands" target="_blank" style="color: #7f54b3;" title="BeRocket labels">BeRocket labels</a>', 'brands-for-woocommerce' ),
                'type' => 'checkbox',
                'class' => 'br_brands_checkbox_block'
            ),
            'brands' => array(
                "title"  => __( 'Brand', 'brands-for-woocommerce' ),
                'type'   => 'autocomplete',
                'class'  => 'width100',
            ),
        );
    }

    public function update( $new_instance, $old_instance ) {
        $new_instance['brands'] = empty( $new_instance['brands'] ) ? '' : sanitize_text_field( $new_instance['brands'] );
        parent::update( $new_instance, $old_instance );
        return $new_instance;
    }

    public function widget( $args, $instance ) {
        $instance = array_merge( $this->defaults, (array) $instance );
        $instance = $this->sanitize_product_list_instance( $instance );

        $BeRocket_product_brand = BeRocket_product_brand::getInstance();
        //$products = $BeRocket_product_brand->get_from_cache( $instance['cache_key'] );
        $products = array();

        if ( empty( $instance['per_page'] ) ) {
            $instance['per_page'] = -1;
        } else {
            $instance['per_page'] = intval($instance['per_page']);
        }

        if ( empty( $instance['slider'] ) ) {
            $instance['paged'] = isset( $_GET[ $instance['cache_key'] ] ) ? (int) $_GET[ $instance['cache_key'] ] : 1; 
        } else {
            $instance['paged'] = false;
            $instance['per_page'] = -1;
        }

        if ( empty( $products ) && empty( $products->products ) ) {
            $brands        = $instance['brands'];
            $ordering_args = WC()->query->get_catalog_ordering_args( $instance['orderby'], $instance['order'] );
            $meta_query    = WC()->query->get_meta_query();
            $field         = empty( $instance['brand_field'] ) ? $this->defaults['brand_field'] : $instance['brand_field'];
            $field         = in_array( $field, array( 'term_id', 'id', 'slug', 'name' ), true ) ? $field : $this->defaults['brand_field'];
            $brands        = empty( $brands ) ? array() : ( is_array( $brands ) ? $brands : explode( ',', $brands ) );
            $brands        = array_map( 'trim', $brands );

            if( empty( $brands ) && function_exists( 'is_product' ) && is_product() ) {
                global $product;
                $product_id = 0;
                $field = 'term_id';
                if( is_a( $product, 'WC_Product' ) ) {
                    $product_id = $product->get_id();
                } else {
                    $product_id = get_queried_object_id();
                }
                if( ! empty( $product_id ) ) {
                    $product_brands = wp_get_post_terms( $product_id, BeRocket_product_brand::$taxonomy_name, array( 'fields' => 'ids' ) );
                    if( ! empty( $product_brands ) && ! is_wp_error( $product_brands ) ) {
                        $brands = $product_brands;
                    }
                }
            } elseif( empty( $brands ) && is_tax( BeRocket_product_brand::$taxonomy_name ) ) {
                $term = get_queried_object();
                if( ! empty( $term->term_id ) && ! is_wp_error( $term ) && $term->taxonomy == BeRocket_product_brand::$taxonomy_name ) {
                    $brands = array( $term->term_id );
                    $field = 'term_id';
                }
            }

            $brands = array_unique( array_filter( $brands ) );
            if ( empty( $brands ) ) return;

            if( ! in_array($instance['orderby'], array('title', 'name', 'date', 'modified', 'rand')) ) {
                $instance['orderby'] = 'title';
            }
            if( ! in_array($instance['order'], array('asc', 'desc')) ) {
                $instance['order'] = 'asc';
            }

            $products = wc_get_products(array(
                'meta_key'   => '_price',
                'status'     => 'publish',
                'limit'      => $instance['per_page'],
                'page'       => intval($instance['paged']),
                'paginate'   => true,
                'return'     => 'ids',
                'orderby'    => $instance['orderby'],
                'order'      => $instance['order'],
                'meta_query' => $meta_query,
                'tax_query'  => array(
                    'relation' => 'AND',
                    array(
                        'taxonomy' => BeRocket_product_brand::$taxonomy_name,
                        'terms'    => $brands,
                        'field'    => $field,
                        'operator' => 'IN',
                    ),
                    array(
                        'taxonomy'  => 'product_visibility',
                        'terms'     => array( 'exclude-from-catalog' ),
                        'field'     => 'name',
                        'operator'  => 'NOT IN',
                    ),
                ),
            ));
            //$BeRocket_product_brand->set_to_cache( $instance['cache_key'], json_encode($products) );
        }
        ob_start();
        echo $args['before_widget'];
        if ( !empty( $instance['title'] ) ) echo $args['before_title'], wp_kses_post( $instance['title'] ), $args['after_title'];
        brfr_product_loop( $products, $instance );
        echo $args['after_widget'];
        $return = ob_get_clean();

        WC()->query->remove_ordering_args();

        echo $return;
	}
}
