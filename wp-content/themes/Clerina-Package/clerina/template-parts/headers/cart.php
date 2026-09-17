<?php

if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
	return;
}
global $woocommerce;
?>

<li class="extra-menu-item menu-item-cart mini-cart woocommerce">
    <a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
        <i class="icon-cart extra-icon"></i>
        <b class="text-cart hidden-lg"><?php echo esc_html__( 'Cart', 'clerina' ) ?></b>
		<?php if ( intval( $woocommerce->cart->cart_contents_count ) != 0 ) : ?>
            <span class="mini-item-counter"><?php echo intval( $woocommerce->cart->cart_contents_count ); ?></span>
		<?php endif; ?>
    </a>
    <div class="mini-cart-content">
        <div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
    </div>
</li>