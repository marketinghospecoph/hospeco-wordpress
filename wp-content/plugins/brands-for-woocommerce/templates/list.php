<?php
$widget_number = sanitize_html_class( isset( $atts['cache_key'] ) ? $atts['cache_key'] : '' );
$per_row = max( 1, min( 100, absint( $atts['per_row'] ) ) );
$slider_class = '';
$slider_data = '';

echo '<style>';
if ( ! empty( $atts['slider'] ) ) {
    $slider_class = 'br_slick_slider';
    $BeRocket_product_brand = BeRocket_product_brand::getInstance();
    $options = $BeRocket_product_brand->get_option();
    $slider_data .= ' data-slides_to_show="' . esc_attr( $per_row ) . '"';
    if ( ! empty( $atts['slides_to_scroll'] ) ) {
        $slider_data .= ' data-slides_to_scroll="' . esc_attr( absint( $atts['slides_to_scroll'] ) ) . '"';
    }
    echo brfr_add_slider_script( $options, '.brcs_slider_brands' );
    $list_style = 'slider';
} else {
    $brand_width = 100 / $per_row;
    $brand_width = rtrim( rtrim( number_format( $brand_width, 4, '.', '' ), '0' ), '.' ) . '%';
    if ( ! empty( $atts['margin'] ) ) {
        $brand_margin = absint( $atts['margin'] ) * 2;
        $brand_width = "calc($brand_width - {$brand_margin}px)";
    }
    echo '.br_brand_' . $widget_number . ' .br_widget_brand_element_slider {
            width: ' . $brand_width . ';
        }
        .widget_berocket_product_brand_widget .brcs_slider_brands_container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .widget_berocket_product_brand_widget .brcs_slider_brands_container .brand_slider_image a {
            display: block;
            text-align: center;
        }
        .widget_berocket_product_brand_widget .brcs_slider_brands_container .brand_slider_image a img {
            margin: 0 auto;
        }';
    $list_style = 'list';
}

$border_style = ! empty( $atts['border_color'] ) && ! empty( $atts['border_width'] )
    ? 'border: ' . absint( $atts['border_width'] ) . 'px solid ' . $atts['border_color'] . ' !important;'
    : '';
$padding = empty( $atts['padding'] ) ? '' : 'padding: ' . absint( $atts['padding'] ) . 'px;';
$margin = empty( $atts['margin'] ) ? '' : 'margin: ' . absint( $atts['margin'] ) . 'px;';
$hierarchy_class = empty( $atts['hierarchy'] ) ? '' : sanitize_html_class( 'br_brands_hierarchy_' . $atts['hierarchy'] );

echo '.br_brand_' . $widget_number . ' .br_widget_brand_element_slider {
        box-sizing: border-box;
        ' . $padding . '
        ' . $margin . '
        ' . $border_style . '
    }';
echo '</style>';

$container_class = trim( "brcs_slider_brands brcs_slider_brands_container br_brand_$widget_number $slider_class $hierarchy_class" );
echo '<div class="' . esc_attr( $container_class ) . '" data-columns="' . esc_attr( $per_row ) . '"' . $slider_data . '>';

$img_align = in_array( $atts['img_align'], array( 'above', 'left', 'right', 'under', 'none' ), true ) ? $atts['img_align'] : 'above';
$height = empty( $atts['img_height'] ) ? '' : "height:{$atts['img_height']}{$atts['img_height_units']};";
$width = empty( $atts['img_width'] ) ? '' : "width:{$atts['img_width']}{$atts['img_width_units']};";
$fit = 'none' === $atts['img_fit'] ? '' : "object-fit:{$atts['img_fit']};";

$align = $line_height = '';
$display_span = 'display: block;';
if ( 'left' === $img_align || 'right' === $img_align ) {
    $align = "float:$img_align;";
    $line_height = empty( $atts['img_height'] ) ? '' : "line-height:{$atts['img_height']}{$atts['img_height_units']};";
    $display_span = 'display: inline-block;';
}

if ( isset( $ordered_terms['all_terms'] ) ) $ordered_terms = $ordered_terms['all_terms'];

foreach ( $ordered_terms as $id => $term ) {
    if ( ! empty( $term->parent ) && 0 != $term->parent ) continue;

    $count = ! empty( $atts['count'] ) ? " <span class='br_brand_count'>(" . absint( $term->count_posts ) . ')</span>' : '';
    $brand_link = $term->link;
    if ( is_wp_error( $brand_link ) ) {
        echo '<div id="message" class="error"><p>' . esc_html( $brand_link->get_error_message() ) . '</p></div>';
        $brand_link = '#error_link';
    }

    $name_style = safecss_filter_attr( trim( "$line_height $display_span" ) );
    $brand_name = ! empty( $atts['use_name'] )
        ? '<span style="' . esc_attr( $name_style ) . '">' . esc_html( $term->name ) . $count . '</span>'
        : '';
    $image_style = safecss_filter_attr( trim( "$width $height $fit $align" ) );
    $brand_image = ! empty( $atts['img_display'] ) && ! empty( $term->image )
        ? '<img src="' . esc_url( $term->image ) . '" alt="' . esc_attr( $term->name ) . '" style="' . esc_attr( $image_style ) . '" />'
        : '';

    $has_children = brfr_add_children_arrow( $term );
    $tooltip_class = empty( $term->tooltip['class'] ) ? '' : $term->tooltip['class'];
    $tooltip_attr = empty( $term->tooltip['text'] ) ? '' : ' data-tippy="' . esc_attr( $term->tooltip['text'] ) . '"';
    $element_class = trim( "br_widget_brand_element_slider $list_style $tooltip_class {$has_children['class']}" );
    echo '<div class="' . esc_attr( $element_class ) . '"' . $tooltip_attr . '>';
    echo '<div class="brand_info brand_slider_image"><a href="' . esc_url( $brand_link ) . '">';
    if ( 'under' === $img_align ) {
        echo $brand_name . $brand_image;
    } else {
        echo $brand_image . $brand_name;
    }
    echo '</a>' . $has_children['arrow'] . '</div>';

    if ( ! empty( $term->children ) ) BeRocket_Brand_Base_Ordered_Widget::show_children( $ordered_terms, $term );
    echo '</div>';
}

echo '</div>';
