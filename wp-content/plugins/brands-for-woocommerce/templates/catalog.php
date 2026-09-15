<?php
$cache_key = sanitize_key( isset( $atts['cache_key'] ) ? $atts['cache_key'] : '' );
$random_class = sanitize_html_class( 'berocket_letter_block_' . $cache_key );
$style_class = in_array( $atts['style'], array( 'vertical', 'horizontal' ), true ) ? $atts['style'] : 'vertical';
$img_align = in_array( $atts['img_align'], array( 'above', 'left', 'right', 'under', 'none' ), true ) ? $atts['img_align'] : 'above';
$show_count = ! empty( $atts['count'] );
$is_grouped = 'none' !== $atts['groupby'];
$column = max( 1, min( 100, absint( $atts['column'] ) ) );

if ( ! wp_script_is( 'br_brands_catalog' ) ) {
    wp_enqueue_script( 'br_brands_catalog' );
}

$width = empty( $atts['img_width'] ) ? '' : "width:{$atts['img_width']}{$atts['img_width_units']};";
$fit = 'none' === $atts['img_fit'] ? '' : "object-fit:{$atts['img_fit']};";
$hierarchy_class = empty( $atts['hierarchy'] ) ? '' : sanitize_html_class( 'br_brands_hierarchy_' . $atts['hierarchy'] );
$sorted_id = $ordered_terms['sorted_id'];
$all_terms = $ordered_terms['all_terms'];

echo '<div class="' . esc_attr( trim( 'berocket_brand_list ' . $hierarchy_class ) ) . '">';

$keys = array_keys( $sorted_id );
if ( $is_grouped ) {
    echo '<div class="' . esc_attr( 'berocket_brand_name_letters ' . $style_class ) . '">';
    if ( ! empty( $atts['show_all'] ) && count( $keys ) > 1 ) {
        echo '<a data-href="#all" class="button">' . esc_html__( 'All', 'brands-for-woocommerce' ) . '</a>';
    }

    foreach ( $keys as $index => $key ) {
        $block_id = $random_class . '_' . absint( $index );
        echo '<a data-href="#' . esc_attr( $block_id ) . '" class="button">' . esc_html( $key ) . '</a>';
    }
    echo '</div>';
}

echo '<div class="' . esc_attr( 'berocket_letter_blocks ' . $random_class ) . '">';
foreach ( $keys as $index => $key ) {
    $block_id = $random_class . '_' . absint( $index );
    echo '<div id="' . esc_attr( $block_id ) . '" class="' . esc_attr( 'br_brand_letter_block ' . $style_class ) . '">';

    if ( $is_grouped ) {
        echo '<h3>' . esc_html( $key ) . '</h3>';
    }
    foreach ( $sorted_id[$key] as $term_id ) {
        if ( empty( $all_terms[$term_id] ) ) continue;
        $term = $all_terms[$term_id];
        if ( ! empty( $term->parent ) && 0 != $term->parent ) continue;

        $count = $show_count ? " <span class='br_brand_count'>(" . absint( $term->count_posts ) . ')</span>' : '';
        $brand_link = $term->link;
        if ( is_wp_error( $brand_link ) ) {
            echo '<div id="message" class="error"><p>' . esc_html( $brand_link->get_error_message() ) . '</p></div>';
            $brand_link = '#error_link';
        }

        $has_children = brfr_add_children_arrow( $term );
        $tooltip_class = empty( $term->tooltip['class'] ) ? '' : $term->tooltip['class'];
        $tooltip_attr = empty( $term->tooltip['text'] ) ? '' : ' data-tippy="' . esc_attr( $term->tooltip['text'] ) . '"';
        $element_class = trim( 'br_brand_letter_element ' . $has_children['class'] . ' ' . $style_class . ' ' . $tooltip_class );

        echo '<div class="' . esc_attr( $element_class ) . '"' . $tooltip_attr . '>';
        echo '<div class="brand_info"><a href="' . esc_url( $brand_link ) . '">';

        $brand_name = empty( $atts['use_name'] )
            ? ''
            : '<span class="br_brand_name"> ' . esc_html( $term->name ) . $count . '</span>';
        $brand_image = '';
        if ( ! empty( $atts['img_display'] ) && ! empty( $term->image ) ) {
            $height = empty( $atts['img_height'] ) ? '' : "height:{$atts['img_height']}{$atts['img_height_units']};";
            $image_style = safecss_filter_attr( trim( "$width $height $fit" ) );
            $brand_image = '<img src="' . esc_url( $term->image ) . '" class="' . esc_attr( 'align_' . $img_align ) . '" alt="' . esc_attr( $term->name ) . '" style="' . esc_attr( $image_style ) . '" />';
        }

        if ( 'under' === $img_align || 'right' === $img_align ) {
            echo $brand_name . $brand_image;
        } else {
            echo $brand_image . $brand_name;
        }
        echo '</a>' . $has_children['arrow'] . '</div>';
        if ( ! empty( $term->children ) ) BeRocket_Brand_Base_Ordered_Widget::show_children( $all_terms, $term );
        echo '</div>';
    }
    echo '</div>';
}
echo '</div></div>';

$column_width = rtrim( rtrim( number_format( 100 / $column, 4, '.', '' ), '0' ), '.' );
?>
<style>
    .<?php echo $random_class; ?> .br_brand_letter_block.horizontal {
        width: <?php echo $column_width; ?>%;
        float: left;
    }
    .<?php echo $random_class; ?> .br_brand_letter_element.vertical {
        width: <?php echo $column_width; ?>%;
        float: left;
    }
    .<?php echo $random_class; ?> .br_brand_letter_block.horizontal:nth-child(<?php echo $column; ?>n + 1) {
        clear: both;
    }
</style>
