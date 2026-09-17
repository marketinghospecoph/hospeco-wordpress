<?php
/**
 * WooCommerce Compatibility File
 *
 * @link https://woocommerce.com/
 *
 * @package 'Clerina'
 */

/**
 * WooCommerce setup function.
 *
 * @link https://docs.woocommerce.com/document/third-party-custom-theme-compatibility/
 * @link https://github.com/woocommerce/woocommerce/wiki/Enabling-product-gallery-features-(zoom,-swipe,-lightbox)-in-3.0.0
 *
 * @return void
 */
function clerina_woocommerce_setup() {
	add_theme_support( 'woocommerce', array( 'thumbnail_image_width' => 600 ) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

add_action( 'after_setup_theme', 'clerina_woocommerce_setup' );


/**
 * WooCommerce specific scripts & stylesheets.
 *
 * @return void
 */
function clerina_woocommerce_scripts() {
	wp_enqueue_style( 'clerina-woocommerce-style', get_template_directory_uri() . '/woocommerce.css' );
}

add_action( 'wp_enqueue_scripts', 'clerina_woocommerce_scripts' );

/**
 * Disable the default WooCommerce stylesheet.
 *
 * Removing the default WooCommerce stylesheet and enqueing your own will
 * protect you during WooCommerce core updates.
 *
 * @link https://docs.woocommerce.com/document/disable-the-default-stylesheet/
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Add 'woocommerce-active' class to the body tag.
 *
 * @param  array $classes CSS classes applied to the body tag.
 *
 * @return array $classes modified to include 'woocommerce-active' class.
 */
function clerina_woocommerce_active_body_class( $classes ) {
	$classes[] = 'woocommerce-active';

	return $classes;
}

add_filter( 'body_class', 'clerina_woocommerce_active_body_class' );


/**
 * Remove default WooCommerce wrapper.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

if ( ! function_exists( 'clerina_woocommerce_wrapper_before' ) ) {
	/**
	 * Before Content.
	 *
	 * Wraps all WooCommerce content in wrappers which match the theme markup.
	 *
	 * @return void
	 */
	function clerina_woocommerce_wrapper_before() {
		?>
        <div id="primary" class="content-area <?php clerina_content_columns(); ?>">
        <main id="main" class="site-main" role="main">
		<?php
	}
}
add_action( 'woocommerce_before_main_content', 'clerina_woocommerce_wrapper_before' );

if ( ! function_exists( 'clerina_woocommerce_wrapper_after' ) ) {
	/**
	 * After Content.
	 *
	 * Closes the wrapping divs.
	 *
	 * @return void
	 */
	function clerina_woocommerce_wrapper_after() {
		?>
        </main><!-- #main -->
        </div><!-- #primary -->
		<?php
	}
}
add_action( 'woocommerce_after_main_content', 'clerina_woocommerce_wrapper_after' );


function clerina_custom_replace_sale_text( $html ) {
	return '<span class="onsale">' . esc_html__( 'Sale', 'clerina' ) . '</span>';
}

add_filter( 'woocommerce_sale_flash', 'clerina_custom_replace_sale_text' );

function clerina_customize_product_sorting( $sorting_options ) {
	$sorting_options = array(
		'default'    => esc_html__( 'Default sorting', 'clerina' ),
		'popularity' => esc_html__( 'Sort by popularity', 'clerina' ),
		'rating'     => esc_html__( 'Sort by average rating', 'clerina' ),
		'date'       => esc_html__( 'Sort by newness', 'clerina' ),
		'price'      => esc_html__( 'Sort by price: low to high', 'clerina' ),
		'price-desc' => esc_html__( 'Sort by price: high to low', 'clerina' ),
	);

	return $sorting_options;
}

add_filter( 'woocommerce_catalog_orderby', 'clerina_customize_product_sorting' );


add_action( 'init', 'woocommerce_clear_cart_url' );
function woocommerce_clear_cart_url() {
	global $woocommerce;
	if ( isset( $_REQUEST['clear-cart'] ) ) {
		$woocommerce->cart->empty_cart();
	}
}

//Need an early hook to ajaxify update mini shop cart
add_filter( 'woocommerce_add_to_cart_fragments', 'clerina_add_to_cart_fragments' );
function clerina_add_to_cart_fragments( $fragments ) {
	global $woocommerce;

	if ( empty( $woocommerce ) ) {
		return $fragments;
	}

	ob_start();
	?>

    <a class="cart-contents" href="<?php echo esc_url( $woocommerce->cart->get_cart_url() ) ?>"">
    <i class="icon-cart extra-icon"></i>
    <b class="text-cart hidden-lg"><?php echo esc_html__( 'Cart', 'clerina' ) ?></b>
	<?php if ( intval( $woocommerce->cart->cart_contents_count ) != 0 ) : ?>
        <span class="mini-item-counter"><?php echo intval( $woocommerce->cart->cart_contents_count ); ?></span>
	<?php endif; ?>
    </a>

	<?php
	$fragments['a.cart-contents'] = ob_get_clean();

	return $fragments;
}

// Addtocart Button
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_add_to_cart', 10 );

// Remove product link
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

/**
 * Add box content images
 */
function clerina_after_box_images() {
	echo "<div class='box-images'><a class='link-thumbnail' href='" . get_the_permalink() . "'>";
}

add_action( "woocommerce_before_shop_loop_item_title", 'clerina_after_box_images', 9 );


/**
 * Close box content images
 */
function clerina_before_box_images() {
	echo "</a></div>";
}

add_action( "woocommerce_before_shop_loop_item_title", 'clerina_before_box_images', 30 );

/**
 * Add box content images
 */
function clerina_after_box_content() {
	echo "<div class='box-content'>";
}

add_action( "woocommerce_shop_loop_item_title", 'clerina_after_box_content', 9 );

/**
 * Close box content images
 */
function clerina_before_box_content() {
	echo "</div>";
}

add_action( "woocommerce_after_shop_loop_item_title", 'clerina_before_box_content', 10 );

/**
 * Add link title
 */
function clerina_show_product_loop_title() {
	?><h2 class="woocommerce-loop-product__title"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h2><?php
}

// Add link to product title in shop loop
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title' );
add_action( 'woocommerce_shop_loop_item_title', 'clerina_show_product_loop_title' );


// Change possition cross sell
remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
add_action( 'woocommerce_after_cart', 'woocommerce_cross_sell_display' );

function clerina_cross_sells_columns( $cross_columns ) {
	return '4';
}

// Change columns and total of cross sell
add_filter( "woocommerce_cross_sells_columns", "clerina_cross_sells_columns" );

function clerina_cross_sells_numbers( $cross_columns ) {
	return '4';
}

// Change columns and total of cross sell
add_filter( "woocommerce_cross_sells_total", "clerina_cross_sells_numbers" );

// Remove Upsell Products
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );


// Customizer by Woocommerce
require get_template_directory() . '/inc/woocommerce/customizer.php';