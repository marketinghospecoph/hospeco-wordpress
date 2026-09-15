<?php
if ( ! isset( $brand_term ) || ! $brand_term instanceof WP_Term ) return;
$options = is_array( $options ) ? $options : array();

foreach ( array( 'thumbnail_width', 'thumbnail_height', 'banner_width', 'banner_height' ) as $size_key ) {
    $unit_key = $size_key . '_units';
    $parsed_size = brfr_parse_css_size(
        isset( $options[$size_key] ) ? $options[$size_key] : '',
        isset( $options[$unit_key] ) ? $options[$unit_key] : 'px'
    );
    $options[$size_key] = $parsed_size['value'];
    $options[$unit_key] = $parsed_size['units'];
}

$options['thumbnail_fit'] = isset( $options['thumbnail_fit'] ) && in_array( $options['thumbnail_fit'], array( 'cover', 'contain', 'fill', 'none' ), true ) ? $options['thumbnail_fit'] : 'cover';
$options['banner_fit'] = isset( $options['banner_fit'] ) && in_array( $options['banner_fit'], array( 'cover', 'contain', 'fill', 'none' ), true ) ? $options['banner_fit'] : 'cover';
$options['thumbnail_align'] = isset( $options['thumbnail_align'] ) && in_array( $options['thumbnail_align'], array( 'left', 'right', 'none' ), true ) ? $options['thumbnail_align'] : 'none';
$options['banner_align'] = isset( $options['banner_align'] ) && in_array( $options['banner_align'], array( 'left', 'right', 'center', 'none' ), true ) ? $options['banner_align'] : 'center';
foreach ( array(
    'display_title', 'display_categories', 'display_description', 'banner_display',
    'thumbnail_display', 'display_link', 'link_open_in_new_tab', 'related_products_display',
    'related_products_slider', 'related_products_hide_brands'
) as $boolean_key ) {
    $options[$boolean_key] = brfr_sanitize_shortcode_bool( isset( $options[$boolean_key] ) ? $options[$boolean_key] : 0 );
}

echo '<div class="brand_description_block">';
if ( ! empty( $options['display_title'] ) ) {
    echo '<h2>' . esc_html( $brand_term->name ) . '</h2>';
}

if ( ! empty( $options['banner_display'] ) ) {
    $brand_banner = get_term_meta( $brand_term->term_id, 'brand_banner_url', true );
    if ( ! empty( $brand_banner ) ) {
        $banner_width = empty( $options['banner_width'] ) ? '' : "width:{$options['banner_width']}{$options['banner_width_units']};";
        $banner_height = empty( $options['banner_height'] ) ? '' : "height:{$options['banner_height']}{$options['banner_height_units']};";
        $banner_fit = "object-fit:{$options['banner_fit']};";
        $banner_align = '';
        if ( 'center' === $options['banner_align'] ) {
            $banner_align = 'display:block;margin-left:auto;margin-right:auto;';
        } elseif ( in_array( $options['banner_align'], array( 'left', 'right' ), true ) ) {
            $banner_align = "float:{$options['banner_align']};";
        }
        $banner_style = safecss_filter_attr( trim( "$banner_width $banner_height $banner_align $banner_fit" ) );
        echo '<img class="br_brand_image br_brand_banner" src="' . esc_url( $brand_banner ) . '" alt="' . esc_attr( $brand_term->name ) . '" style="' . esc_attr( $banner_style ) . '">';
    }
}

if ( ! empty( $options['display_categories'] ) ) {
    $categories = get_term_meta( $brand_term->term_id, 'br_brand_category', true );
    if ( is_array( $categories ) && ! empty( $categories ) ) {
        echo '<div class="br_brand_info_categories"><span>' . esc_html__( 'Categories', 'brands-for-woocommerce' ) . ': </span><ul>';
        foreach ( $categories as $category_name ) {
            $category = get_term_by( 'name', sanitize_text_field( $category_name ), 'product_cat' );
            if ( ! $category instanceof WP_Term ) continue;
            $category_url = get_term_link( $category );
            if ( is_wp_error( $category_url ) ) continue;
            echo '<li><a href="' . esc_url( $category_url ) . '">' . esc_html( $category->name ) . '</a></li>';
        }
        echo '</ul></div>';
    }
}

echo '<div class="berocket_brand_description">';

if ( ! empty( $options['thumbnail_display'] ) ) {
    $brand_thumbnail = get_term_meta( $brand_term->term_id, 'brand_image_url', true );
    if ( ! empty( $brand_thumbnail ) ) {
        $thumbnail_width = empty( $options['thumbnail_width'] ) ? '' : "width:{$options['thumbnail_width']}{$options['thumbnail_width_units']};";
        $thumbnail_height = empty( $options['thumbnail_height'] ) ? '' : "height:{$options['thumbnail_height']}{$options['thumbnail_height_units']};";
        $thumbnail_fit = "object-fit:{$options['thumbnail_fit']};";
        $thumbnail_align = in_array( $options['thumbnail_align'], array( 'left', 'right' ), true ) ? "float:{$options['thumbnail_align']};" : '';
        $thumbnail_style = safecss_filter_attr( trim( "$thumbnail_width $thumbnail_height $thumbnail_align $thumbnail_fit" ) );
        $tooltip = BeRocket_product_brand::get_tooltip( $brand_term->term_id );
        $tooltip_class = empty( $tooltip['class'] ) ? '' : $tooltip['class'];
        $tooltip_attr = empty( $tooltip['text'] ) ? '' : ' data-tippy="' . esc_attr( $tooltip['text'] ) . '"';
        echo '<img class="' . esc_attr( trim( 'br_brand_image br_brand_thumbnail ' . $tooltip_class ) ) . '" src="' . esc_url( $brand_thumbnail ) . '" alt="' . esc_attr( $brand_term->name ) . '" style="' . esc_attr( $thumbnail_style ) . '"' . $tooltip_attr . ' />';
    }
}

if ( ! empty( $options['display_description'] ) ) {
    $brand_description = wp_kses_post( term_description( $brand_term ) );
    echo '<div class="text">' . do_shortcode( $brand_description ) . '</div>';
}

if ( ! empty( $options['display_link'] ) ) {
    $brand_url = get_term_meta( $brand_term->term_id, 'br_brand_url', true );
    if ( ! empty( $brand_url ) ) {
        $target = empty( $options['link_open_in_new_tab'] ) ? '' : ' target="_blank" rel="noopener noreferrer"';
        $link_text = isset( $options['link_text'] ) ? sanitize_text_field( $options['link_text'] ) : '';
        echo '<a class="br_brand_link" href="' . esc_url( $brand_url ) . '"' . $target . '>' . esc_html( $link_text ) . '</a>';
    }
}

if ( ! empty( $options['related_products_display'] ) ) {
    $related_products = get_term_meta( $brand_term->term_id, 'br_brand_related', true );
    $related_products = array_values( array_unique( array_filter( array_map( 'absint', (array) $related_products ) ) ) );
    if ( ! empty( $related_products ) ) {
        echo '<h4>' . esc_html__( 'Related products', 'brands-for-woocommerce' ) . '</h4>';
        $get_argument = 'br_brands_related';
        $is_slider = ! empty( $options['related_products_slider'] );
        $per_page = empty( $options['related_products_per_page'] ) || -1 === intval( $options['related_products_per_page'] )
            ? -1
            : min( 1000, absint( $options['related_products_per_page'] ) );
        $paged = $is_slider ? false : max( 1, isset( $_GET[$get_argument] ) ? absint( $_GET[$get_argument] ) : 1 );
        if ( $is_slider ) $per_page = -1;

        $orderby = isset( $options['related_products_orderby'] ) && in_array( $options['related_products_orderby'], array( 'title', 'name', 'date', 'modified', 'rand' ), true ) ? $options['related_products_orderby'] : 'title';
        $order = isset( $options['related_products_order'] ) && in_array( strtolower( $options['related_products_order'] ), array( 'asc', 'desc' ), true ) ? strtolower( $options['related_products_order'] ) : 'asc';
        $columns = max( 1, min( 12, isset( $options['related_products_columns'] ) ? absint( $options['related_products_columns'] ) : 3 ) );
        $slides_to_scroll = empty( $options['slides_to_scroll'] ) ? '' : max( 1, min( 1000, absint( $options['slides_to_scroll'] ) ) );

        $products = wc_get_products( array(
            'status' => 'publish',
            'limit' => $per_page,
            'page' => $paged,
            'paginate' => true,
            'return' => 'ids',
            'orderby' => $orderby,
            'order' => $order,
            'include' => $related_products,
        ) );

        brfr_product_loop( $products, array(
            'columns' => $columns,
            'cache_key' => $get_argument,
            'slider' => $is_slider ? 1 : 0,
            'slides_to_scroll' => $slides_to_scroll,
            'hide_brands' => empty( $options['related_products_hide_brands'] ) ? 0 : 1,
            'paged' => $paged,
        ) );
    }
}

echo '</div></div>';
