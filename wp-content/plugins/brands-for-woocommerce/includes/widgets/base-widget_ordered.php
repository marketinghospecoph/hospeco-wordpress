<?php
class BeRocket_Brand_Base_Ordered_Widget extends BeRocket_Brand_Base_Widget {

	public function __construct( $widget_name, $widget_title, $args ) {
        parent::__construct( $widget_name, $widget_title, $args );

	    $this->defaults += array(
            'use_name'         => 1,
	        'orderby'          => 'alphabet',
            'order'            => 'ASC',
	        'featured_first'   => 1,
	        'count'            => 1,
	        'img_display'      => 1,
	        'img_width'        => '',
	        'img_width_units'  => '%',
	        'img_height'       => 64,
	        'img_height_units' => 'px',
	        'img_fit'          => 'cover',
	        'img_align'        => 'above',
	        'hide_empty'       => 1,
            'out_of_stock'     => '',
            'hierarchy'        => 'all',
            'category_only'    => 0,
            'include'          => '',
            'exclude'          => '',
            'cache_key'        => '',
            'brands_include'   => '',
	    );

	    $this->form_fields += array(
            'use_name' => array(
                "title" => __( 'Display text', 'brands-for-woocommerce' ),
                'type'  => 'checkbox',
                'class' => 'width100',
            ),
	        'img' => array(
	        	"title" => __( 'Image:', 'brands-for-woocommerce' ),
	        	'type'  => 'image',
                'class' => 'width100',
                'align_options' => array(
                    "above" => array( 'name' => __( 'Above name', 'brands-for-woocommerce' ) ),
                    "left"  => array( 'name' => __( 'Left to name', 'brands-for-woocommerce' ) ),
                    "right" => array( 'name' => __( 'Right to name', 'brands-for-woocommerce' ) ),
                    "under" => array( 'name' => __( 'Under name', 'brands-for-woocommerce' ) ),
                )
	        ),
            'orderby' => array(
                "title"   => __( 'Order by:', 'brands-for-woocommerce' ),
                'type'    => 'select',
                'class'   => 'br_brands_orderby',
                'options' => array(
                    'alphabet' => array( 'name' => __( 'Alphabet', 'brands-for-woocommerce' ) ),
                    'products' => array( 'name' => __( 'Number of products', 'brands-for-woocommerce' ) ),
                    'order'    => array( 'name' => __( 'Order', 'brands-for-woocommerce' ) ),
                    'random'   => array( 'name' => __( 'Random', 'brands-for-woocommerce' ) ),
                ),
            ),
            'order' => array(
                "title"   => __( '&nbsp;', 'brands-for-woocommerce' ),
                'type'    => 'select',
                'options' => array(
                    'ASC'  => array( 'name' => __( 'Asc', 'brands-for-woocommerce' ) ),
                    'DESC' => array( 'name' => __( 'Desc', 'brands-for-woocommerce' ) ),
                ),
                'class' => 'br_brands_order',
            ),
            'count' => array(
                "title" => __( 'Show number of products', 'brands-for-woocommerce' ),
                'type'  => 'checkbox',
                'class' => 'br_brands_checkbox_block',
            ),
	        'hide_empty' => array(
	        	"title" => __( 'Hide brands with no products', 'brands-for-woocommerce' ),
	        	'type'  => 'checkbox',
                'class' => 'br_brands_checkbox_block',
	        ),
            'out_of_stock' => array(
                "title" => __( 'Hide brands with products out of stock', 'brands-for-woocommerce' ),
                'type'  => 'checkbox',
                'class' => 'br_brands_checkbox_block',
            ),
            'featured_first' => array(
                "title" => __( 'Featured first', 'brands-for-woocommerce' ),
                'type'  => 'checkbox',
                'class' => 'br_brands_checkbox_block',
            ),
            'category_only' => array(
                "title" => __( 'Only brands of this category (on category page)', 'brands-for-woocommerce' ),
                'type'  => 'checkbox',
                'class' => 'width100',
            ),
            'hierarchy' => array(
                "title"   => __( 'Show brands hierarchy:', 'brands-for-woocommerce' ),
                'type'    => 'select',
                'class'   => 'width100',
                'options' => array(
                    'top'      => array( 'name' => __( 'Only top level', 'brands-for-woocommerce' ) ),
                    'children' => array( 'name' => __( 'Only children (without hierarchy)', 'brands-for-woocommerce' ) ),
                    'expand'   => array( 'name' => __( 'Show full hierarchy', 'brands-for-woocommerce' ) ),
                    'by_click' => array( 'name' => __( 'Expand by click', 'brands-for-woocommerce' ) ),
                    'all'      => array( 'name' => __( 'All brands without hierarchy', 'brands-for-woocommerce' ) ),
                ),
            ),
            'brands_include' => array(
                "title"       => __( 'Show only selected brand(s)', 'brands-for-woocommerce' ),
                'type'        => 'autocomplete',
                'multiselect' => 'multiselect',
                'class'       => 'width100',
                'callback'    => 'br_get_brands',
            ),
	    );

        $this->shortcode_args = array(
            'image'     => 'img_display',
            'img'       => 'img_display',
            'imgh'      => 'img_height',
            'imgw'      => 'img_width',
            'text'      => 'use_name',
            'use_image' => 'img',
        );
    }

    public function widget($args, $instance) {
        $instance = (array) $instance;
        if ( empty( $args['template'] ) ) return;
        $instance = $this->replace_shortcode_keys( $instance );

        $instance = shortcode_atts( $this->defaults, $instance );
        $instance['title'] = apply_filters( 'widget_title', sanitize_text_field($instance['title']), $instance );
        $instance = $this->sanitize_ordered_instance( $instance );
        
        $instance = $this->set_cache_key( $instance );
        $ordered_terms = $this->get_brands( $instance );

        if ( $instance['category_only'] && is_product_category() ) {
            global $wp_query;
            $category = $wp_query->get_queried_object()->name;
            $ordered_terms = $this->filter_for_category( $category, $ordered_terms );
        }

        if ( empty( $ordered_terms ) ) return;

        set_query_var( 'atts', $instance );
        set_query_var( 'ordered_terms', $ordered_terms );

        parent::widget( $args, $instance );
	}

    protected function sanitize_ordered_instance( $instance ) {
        $instance = $this->get_size( 'img_width', $instance );
        $instance = $this->get_size( 'img_height', $instance );

        $instance['orderby'] = $this->sanitize_choice(
            isset( $instance['orderby'] ) ? $instance['orderby'] : 'alphabet',
            array( 'alphabet', 'name', 'products', 'count', 'order', 'random', 'slug', 'description' ),
            'alphabet'
        );
        $instance['order'] = strtoupper( $this->sanitize_choice(
            isset( $instance['order'] ) ? $instance['order'] : 'asc',
            array( 'asc', 'desc' ),
            'asc'
        ) );
        $instance['img_fit'] = $this->sanitize_choice(
            isset( $instance['img_fit'] ) ? $instance['img_fit'] : 'cover',
            array( 'cover', 'contain', 'fill', 'none' ),
            'cover'
        );
        $instance['img_align'] = $this->sanitize_choice(
            isset( $instance['img_align'] ) ? $instance['img_align'] : 'above',
            array( 'above', 'left', 'right', 'under', 'none' ),
            'above'
        );
        $instance['hierarchy'] = $this->sanitize_choice(
            isset( $instance['hierarchy'] ) ? $instance['hierarchy'] : 'all',
            array( 'top', 'children', 'expand', 'by_click', 'all', 'current_child' ),
            'all'
        );
        $instance['include'] = $this->sanitize_id_list( isset( $instance['include'] ) ? $instance['include'] : '' );
        $instance['exclude'] = $this->sanitize_id_list( isset( $instance['exclude'] ) ? $instance['exclude'] : '' );
        $instance['brands_include'] = isset( $instance['brands_include'] ) && is_scalar( $instance['brands_include'] )
            ? sanitize_text_field( $instance['brands_include'] )
            : '';

        foreach ( array(
            'use_name', 'featured_first', 'count', 'img_display', 'hide_empty',
            'out_of_stock', 'category_only', 'show_all', 'slider'
        ) as $key ) {
            if ( array_key_exists( $key, $instance ) ) {
                $instance[$key] = brfr_sanitize_shortcode_bool( $instance[$key] );
            }
        }

        if ( array_key_exists( 'style', $instance ) ) {
            $instance['style'] = $this->sanitize_choice( $instance['style'], array( 'vertical', 'horizontal' ), 'vertical' );
        }
        if ( array_key_exists( 'groupby', $instance ) ) {
            $instance['groupby'] = $this->sanitize_choice( $instance['groupby'], array( 'alphabet', 'category', 'none' ), 'alphabet' );
        }
        if ( array_key_exists( 'column', $instance ) ) {
            $instance['column'] = max( 1, min( 100, absint( $instance['column'] ) ) );
        }
        if ( array_key_exists( 'per_row', $instance ) ) {
            $instance['per_row'] = max( 1, min( 100, absint( $instance['per_row'] ) ) );
        }
        if ( array_key_exists( 'brands_number', $instance ) ) {
            $instance['brands_number'] = empty( $instance['brands_number'] ) ? '' : min( 10000, absint( $instance['brands_number'] ) );
        }
        foreach ( array( 'padding', 'margin', 'border_width', 'slides_to_scroll' ) as $key ) {
            if ( array_key_exists( $key, $instance ) ) {
                $instance[$key] = empty( $instance[$key] ) ? '' : min( 10000, absint( $instance[$key] ) );
            }
        }
        if ( array_key_exists( 'border_color', $instance ) ) {
            $instance['border_color'] = sanitize_hex_color( $instance['border_color'] );
            if ( empty( $instance['border_color'] ) ) {
                $instance['border_color'] = '#000000';
            }
        }

        return $instance;
    }

	public function update( $new_instance, $old_instance ) {
        $instance = parent::update( $new_instance, $old_instance );
        // $instance['brands_include'] = empty( $_REQUEST['berocket_brand'] ) ? '' : serialize( $_REQUEST['berocket_brand'] );
        $BeRocket_product_brand = BeRocket_product_brand::getInstance();
        $options = $BeRocket_product_brand->get_option();
        if ( !empty( $options['use_cache'] ) ) {
            delete_transient( $this->id );
        }
		return $instance;
	}

    protected function form_query( $atts ) {
        // raw query to order taxonomy terms by multiple meta values
        global $wpdb, $wp_query;

        $wc_term_ids = wc_get_product_visibility_term_ids();
        $hide_empty = empty( $atts['hide_empty'] ) ? '' : " AND tt.count <> '' AND tm_hidden.object_id NOT IN (
            SELECT object_id
            FROM {$wpdb->prefix}term_relationships
            WHERE term_taxonomy_id IN (" . $wc_term_ids['exclude-from-catalog'] . ")
        )";
        $hide_empty_join = empty( $atts['hide_empty'] ) ? '' : " LEFT JOIN {$wpdb->prefix}term_relationships AS tm_hidden ON tt.term_taxonomy_id = tm_hidden.term_taxonomy_id";
        $match_id_list = '/^[0-9, .]*$/';
        
        $exclude = empty( $atts['exclude'] ) || !preg_match( $match_id_list, $atts['exclude'] ) ? '' : "AND t.term_id NOT IN ({$atts['exclude']})";
        $include = empty( $atts['include'] ) || !preg_match( $match_id_list, $atts['include'] ) ? '' : "AND t.term_id IN ({$atts['include']})";
        $order = strtoupper( $atts['order'] ) == 'DESC' ? 'DESC' : 'ASC';

        $taxonomy = BeRocket_product_brand::$taxonomy_name;
        //use get_terms to filter query with other plugins
        $brands_terms_id = get_terms( array('taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ) );
        if( !is_array( $brands_terms_id ) || empty( $brands_terms_id ) ) {
            $brands_terms_id = array(0);
        }
        $include = ( empty( $include ) ? '' : $include . ' ') . 'AND t.term_id IN ('.implode(',', $brands_terms_id).')';

        $query = array(
            'select' => "SELECT MIN(t.slug) as slug, MIN(tt.description) as description, 
                        MIN(t.term_id) as term_id, MIN(t.name) as name, MIN(tt.count) count, 
                        MIN(tm_image.meta_value) as image, MIN(tm_tooltip.meta_value) AS tooltip",
            'from' => "FROM {$wpdb->prefix}terms AS t
                LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
                LEFT JOIN {$wpdb->prefix}termmeta AS tm_image ON t.term_id = tm_image.term_id AND tm_image.meta_key='brand_image_url'
                LEFT JOIN {$wpdb->prefix}termmeta AS tm_tooltip ON t.term_id = tm_tooltip.term_id AND tm_tooltip.meta_key='br_brand_tooltip'
                {$hide_empty_join}",
            'orderby' => array(),
            'where' => "WHERE tt.taxonomy='$taxonomy' $include $exclude $hide_empty",
            'limit' => '',
            'group' => 'GROUP BY tt.term_taxonomy_id'
        );

        if ( !empty( $atts['hierarchy'] ) ) {
            switch ( $atts['hierarchy'] ) {
                case 'top':
                    $query['where'] .= ' AND tt.parent = 0';
                    break;

                case 'expand':
                case 'by_click':
                    if ( empty( $atts['slider'] ) ) {
                        $query['select'] .= ', tt.parent';
                    }
                    break;

                case 'children':
                    $query['where'] .= ' AND tt.parent <> 0';
                    break;

                case 'current_child':
                    $tax = $wp_query->get_queried_object();
                    $parent_id = 0;
                    if( ! empty($tax) && is_a($tax, 'WP_Term') && $tax->taxonomy == 'berocket_brand' ) {
                        $parent_id = $tax->term_id;
                    }
                    $query['where'] .= ' AND tt.parent = ' . $parent_id;
                    break;

                default:
                    break;
            }
        }

        if ( !empty( $atts['brands_include'] ) ) {
            $brands_include = explode(',', $atts['brands_include']);
            $brands_include_checked = array();
            foreach($brands_include as $brand_include) {
                if( ! empty($brand_include) ) {
                    $brands_include_checked[] = sanitize_title_for_query(trim($brand_include));
                }
            }
            if ( ! empty( $brands_include_checked ) ) {
                $query['where'] .= " AND t.slug IN ('" . implode( "','", array_map( 'esc_sql', $brands_include_checked ) ) . "')";
            }
            // $query['where'] .= " AND t.term_id IN ('" . implode( "', '", unserialize( $atts['brands_include'] ) ) . "')";
        }

        if ( !empty( $atts['featured_first'] ) ) {
            $query['select'] .= ', MIN(tm_featured.meta_value) as featured';
            $query['from'] .= " LEFT JOIN {$wpdb->prefix}termmeta AS tm_featured ON t.term_id = tm_featured.term_id AND tm_featured.meta_key='br_brand_featured'";
            $query['orderby'][] = "cast(MIN(tm_featured.meta_value) AS unsigned) DESC";
        }

        switch ( $atts['orderby'] ) {
            case 'random':
                $query['orderby'] = array( "RAND()" );
                break;

            case 'order':
                $query['select'] .= ', MIN(tm_order.meta_value) as order_val';
                $query['from'] .= " LEFT JOIN {$wpdb->prefix}termmeta AS tm_order ON t.term_id = tm_order.term_id AND tm_order.meta_key='br_brand_order'";
                $query['orderby'][] = "cast(MIN(tm_order.meta_value) as unsigned) $order, t.name ASC";
                break;

            case 'count':
            case 'products':
                $query['orderby'][] = "count $order, name ASC";
                break;

            case 'alphabet':
            case 'name':
                $query['orderby'][] = "name $order";
                break;

            case 'slug':
                $query['orderby'][] = "slug $order";
                break;

            case 'description':
                $query['orderby'][] = "description $order";
                break;

            default:
                break;
        }

        return $query;
    }

    protected function form_query_add_category( $query, $atts ) {
        global $wpdb;
        $query['select'] .= ", MIN(tm_cat.meta_value) as category";
        $query['from'] .= " LEFT JOIN {$wpdb->prefix}termmeta AS tm_cat ON t.term_id = tm_cat.term_id AND tm_cat.meta_key='br_brand_category'";
        return $query;
    }

    protected function add_attributes( $term ) {
        // $term->count = number of products not including child brands
        // if ( !empty( get_term_children( $term->term_id, BeRocket_product_brand::$taxonomy_name ) ) ) {
        //     $posts = get_posts( array(
        //         'post_type' => 'product',
        //         'numberposts' => -1,
        //         'tax_query' => array(
        //         array(
        //             'taxonomy' => BeRocket_product_brand::$taxonomy_name,
        //             'field' => 'term_id', 
        //             'terms' => $term->term_id,
        //             'include_children' => true
        //             )
        //     )));
        //     $term->count_posts = count( $posts );
        // } else {
        //     $term->count_posts = $term->count;
        // }

        $term->count_posts = $term->count;

        $term->link = get_term_link( (int)$term->term_id, BeRocket_product_brand::$taxonomy_name );
        $term->tooltip = BeRocket_product_brand::get_tooltip_data( $term->tooltip );
        return $term;
    }

    protected function sort_terms( $terms, $atts ) {
        $terms_by_id = array();
        foreach ( $terms as $term ) {
            $terms_by_id[$term->term_id] = $this->add_attributes( $term );
            $terms_by_id[$term->term_id]->children = array();
        }

        if ( in_array( $atts['hierarchy'], array( 'expand', 'by_click' ) ) ) {
            foreach ( $terms_by_id as $id => $term ) {
                if ( empty( $term->parent ) || $term->parent == 0 ) continue;
                if ( !empty( $terms_by_id[$term->parent] ) ) {
                    array_push( $terms_by_id[$term->parent]->children, $id );
                }
            }
        }

        return $terms_by_id;
    }

    protected function get_brands( $atts ) {
        $BeRocket_product_brand = BeRocket_product_brand::getInstance();
        $cached_terms = $BeRocket_product_brand->get_from_cache( $atts['cache_storage_key'] );
        if( !empty( $cached_terms ) ) {
            // bd('from cache');
            //return $cached_terms;
        } 
        // else {
        //     bd('from cache');            
        // }

        global $wpdb;
        $query = $this->form_query( $atts );
        $orderby = empty( $query['orderby'] ) ? '' : 'ORDER BY ' . implode( ', ', $query['orderby'] );
        // bd("{$query['select']} {$query['from']} {$query['where']} {$query['group']} $orderby {$query['limit']}");
        $terms = $wpdb->get_results( "{$query['select']} {$query['from']} {$query['where']} {$query['group']} $orderby {$query['limit']}" );
        if( empty( $terms ) || !is_array($terms) ) return array();
        if ( !empty( $atts['out_of_stock'] ) ) {
            foreach ( $terms as $i => $term ) {
                if ( empty($term) || ! is_object($term) || $term->count == 0 ) continue;

                $products = wc_get_products(array(
                    'status'     => 'publish',
                    'limit'      => -1,
                    'paginate'   => false,
                    'tax_query'  => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => BeRocket_product_brand::$taxonomy_name,
                            'terms'    => $term->term_id,
                            'field'    => 'term_id',
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

                $out_of_stock = 0;
                foreach ( $products as $product ) {
                    if ( !$product->is_in_stock() ) $out_of_stock++;
                }
                if ( $out_of_stock == $term->count ) {
                    unset( $terms[$i] );
                } else {
                    $terms[$i]->count -= $out_of_stock;
                }
            }
        }

        $terms = $this->sort_terms( $terms, $atts );

        $BeRocket_product_brand->set_to_cache( $atts['cache_storage_key'], $terms );
        return $terms;
    }

    protected function replace_shortcode_keys( $instance ) {
        $instance = parent::replace_shortcode_keys( $instance );

        if ( !empty( $instance['border_color'] ) ) {
            $instance['border_color'] = sanitize_text_field($instance['border_color']);
            $instance['border_color'] = str_replace('#', '', $instance['border_color']);
            $instance['border_color'] = "#{$instance['border_color']}";
        }

        return $instance;
    }

    public static function show_children( $all_terms, $term ) {
        echo "<ul class='br_brands_children'>";
        foreach ( $term->children as $id ) {
            $child_term = $all_terms[$id];
            $has_children = brfr_add_children_arrow( $child_term );
            echo "<li class='" . esc_attr( $has_children['class'] ) . "'><i class='fas fa-chevron-right br_brand_children_arrow_right'></i><span class='br_child_brand_name'><a href='" . esc_url( $child_term->link ) . "'>" . esc_html( $child_term->name ) . "</a><span class='br_brand_count'>(" . absint( $child_term->count_posts ) . ")</span></span>{$has_children['arrow']}";
            if ( !empty( $child_term->children ) ) self::show_children( $all_terms, $child_term );
            echo '</li>';
        }
        echo "</ul>";
    }
}
