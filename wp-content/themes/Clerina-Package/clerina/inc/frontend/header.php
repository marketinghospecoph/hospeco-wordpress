<?php
/**
 * Hooks for template header
 *
 * @package 'Clerina'
 */


/**
 * Enqueue scripts and styles.
 */
function clerina_scripts() {
	/**
	 * Register and enqueue styles
	 */
	wp_register_style( 'clerina-fonts', clerina_fonts_url(), array(), '20170801' );
	wp_register_style( 'bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '3.3.7' );
	wp_register_style( 'font-awesome', get_template_directory_uri() . '/css/font-awesome.min.css', array(), '4.7.0' );
	wp_register_style( 'eleganticons', get_template_directory_uri() . '/css/eleganticons.min.css', array(), '4.7.0' );
	wp_register_style( 'linearicons', get_template_directory_uri() . '/css/linearicons.min.css', array(), '4.7.0' );
	wp_register_style( 'slick', get_template_directory_uri() . '/css/slick.css', array(), '4.7.0' );
	wp_register_style( 'photoswipe', get_template_directory_uri() . '/css/photoswipe.min.css', array(), '4.7.0' );

	wp_enqueue_style( 'clerina', get_template_directory_uri() . '/style.css', array(
		'clerina-fonts',
		'eleganticons',
		'font-awesome',
		'linearicons',
		'bootstrap',
		'slick',
		'photoswipe',
	), '20170801' );

	wp_add_inline_style( 'clerina', clerina_customize_styles() );

	/**
	 * Register and enqueue scripts
	 */
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_script( 'html5shiv', get_template_directory_uri() . '/js/plugins/html5shiv.min.js', array(), '3.7.2' );
	wp_script_add_data( 'html5shiv', 'conditional', 'lt IE 9' );

	wp_enqueue_script( 'respond', get_template_directory_uri() . '/js/plugins/respond.min.js', array(), '1.4.2' );
	wp_script_add_data( 'respond', 'conditional', 'lt IE 9' );
	wp_register_script( 'sticky', get_template_directory_uri() . '/js/plugins/jquery.sticky.js', array(), '1.0', true );
	wp_register_script( 'isotope', get_template_directory_uri() . '/js/plugins/isotope.pkgd.min.js', array(), '2.2.2', true );
	wp_register_script( 'slick', get_template_directory_uri() . '/js/plugins/slick.min.js', array(), '1.0', true );
	wp_register_script( 'counterup', get_template_directory_uri() . '/js/plugins/jquery.counterup.min.js', array(), '1.0', true );
	wp_register_script( 'waypoints', get_template_directory_uri() . '/js/plugins/waypoints.min.js', array(), '1.0', true );
	wp_register_script( 'photoswipe', get_template_directory_uri() . '/js/plugins/photoswipe.min.js', array(), '4.1.1', true );
	wp_register_script( 'photoswipe-ui', get_template_directory_uri() . '/js/plugins/photoswipe-ui.min.js', array( 'photoswipe' ), '4.1.1', true );
	wp_register_script( 'flipclock', get_template_directory_uri() . '/js/plugins/flipclock.min.js', array(), '1.0', true );
	wp_register_script( 'parallax', get_template_directory_uri() . '/js/plugins/jquery.parallax.min.js', array(), '1.0', true );


	$photoswipe_skin = 'photoswipe-default-skin';
	if ( wp_style_is( $photoswipe_skin, 'registered' ) && ! wp_style_is( $photoswipe_skin, 'enqueued' ) ) {
		wp_enqueue_style( $photoswipe_skin );
	}

	wp_enqueue_script( 'clerina', get_template_directory_uri() . "/js/scripts$min.js", array(
		'jquery',
		'imagesloaded',
		'sticky',
		'isotope',
		'slick',
		'counterup',
		'waypoints',
		'flipclock',
		'photoswipe',
		'photoswipe-ui',
		'parallax',

	), '20170801', true );

	wp_localize_script(
		'clerina', 'clerinaData', array(
			'nl_days'    => intval( clerina_get_option( 'newsletter_reappear' ) ),
			'nl_seconds' => intval( clerina_get_option( 'newsletter_visible' ) ) == 2 ? intval( clerina_get_option( 'newsletter_seconds' ) ) : 0,
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'clerina_scripts' );

/**
 * Display show top bar
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'clerina_show_topbar' ) ) :
	function clerina_show_topbar() {
		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}
		if ( clerina_get_option( 'topbar' ) == 0 ) {
			return;
		}
		get_template_part( 'template-parts/headers/topbar' );
	}
endif;
add_action( 'clerina_before_header', 'clerina_show_topbar', 10, 1 );

/**
 * Display show top bar mobile
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'clerina_show_topbar_mobile' ) ) :
	function clerina_show_topbar_mobile() {
		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}
		if ( clerina_get_option( 'topbar-mobile' ) == 0 ) {
			return;
		}
		get_template_part( 'template-parts/headers/topbar-mobile' );
	}
endif;
add_action( 'clerina_before_header', 'clerina_show_topbar_mobile', 10, 1 );

/**
 * Display the site header
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'clerina_show_header' ) ) :
	function clerina_show_header() {
		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}
		$header_layout = clerina_get_option( 'header_layout' );
		get_template_part( 'template-parts/headers/layout', $header_layout );
	}
endif;
add_action( 'clerina_header', 'clerina_show_header' );

function clerina_customize_styles() {
	$inline_css = '';

	/* 404 background */

	if ( is_404() ) {
		$banner = clerina_get_option( 'not_found_bg' );

		if ( $banner ) {
			$inline_css .= '.error404 .site-content { background-image: url( ' . esc_url( $banner ) . '); }';
		}
	}

	// Logo
	$logo_size_width = intval( clerina_get_option( 'logo_width' ) );
	$logo_css        = $logo_size_width ? 'width:' . $logo_size_width . 'px; ' : '';

	$logo_size_height = intval( clerina_get_option( 'logo_height' ) );
	$logo_css         .= $logo_size_height ? 'height:' . $logo_size_height . 'px; ' : '';

	$logo_margin = clerina_get_option( 'logo_margins' );
	$logo_css    .= $logo_margin['top'] ? 'margin-top:' . $logo_margin['top'] . '; ' : '';
	$logo_css    .= $logo_margin['right'] ? 'margin-right:' . $logo_margin['right'] . '; ' : '';
	$logo_css    .= $logo_margin['bottom'] ? 'margin-bottom:' . $logo_margin['bottom'] . '; ' : '';
	$logo_css    .= $logo_margin['left'] ? 'margin-left:' . $logo_margin['left'] . '; ' : '';

	if ( ! empty( $logo_css ) ) {
		$inline_css .= '.site-header .header-logo img ' . ' {' . $logo_css . '}';
	}

	/* Color Scheme */
	$color_scheme_option = clerina_get_option( 'color_scheme' );
	if ( intval( clerina_get_option( 'custom_color_scheme' ) ) ) {
		$color_scheme_option = clerina_get_option( 'custom_color' );
	}
	// Don't do anything if the default color scheme is selected.
	if ( $color_scheme_option ) {
		$inline_css .= clerina_get_color_scheme_css( $color_scheme_option );
	}

	$inline_css .= clerina_typography_css();

	$inline_css .= clerina_get_heading_typography_css();

	$inline_css .= clerina_header_styles();

	return $inline_css;

}

function clerina_header_styles() {
	if ( ! intval( clerina_get_option( 'menu_custom_height' ) ) ) {
		return;
	}

	$inline_css = '';

	$line_height = clerina_get_option( 'menu_height' );

	if ( empty( $line_height ) ) {
		return;
	}

	$inline_css .= '.primary-nav > ul > li, .site-header .menu-extras li{line-height:' . $line_height . 'px}';

	return $inline_css;
}

/**
 * Display page header
 */
function clerina_page_header() {
	get_template_part( 'template-parts/page-headers/page-header' );
}

add_action( 'clerina_after_header', 'clerina_page_header' );

/**
 * Filter to archive title and add page title for singular pages
 *
 * @param string $title
 *
 * @return string
 */
function clerina_the_archive_title( $title ) {
	if ( is_search() ) {
		$title = sprintf( esc_html__( 'Search Results', 'clerina' ) );

	} elseif ( is_404() ) {
		$title = sprintf( esc_html__( 'Page Not Found', 'clerina' ) );

	} elseif ( is_page() ) {
		$title = get_the_title();

	} elseif ( is_home() && is_front_page() ) {
		$title = esc_html__( 'The Latest Posts', 'clerina' );

	} elseif ( is_home() && ! is_front_page() ) {
		$title = get_the_title( get_option( 'page_for_posts' ) );

	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$title = get_the_title( get_option( 'woocommerce_shop_page_id' ) );

	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$cats = get_the_terms( get_the_ID(), 'product_cat' );
		if ( ! is_wp_error( $cats ) && $cats ) {
			$title = $cats[0]->name;
		} else {
			$title = get_the_title( get_option( 'woocommerce_shop_page_id' ) );
		}

	} elseif ( is_post_type_archive( 'portfolio' ) ) {

		if ( get_option( 'clerina_portfolio_page_id' ) ) {
			$title = get_the_title( get_option( 'clerina_portfolio_page_id' ) );
		} else {
			$title = esc_html__( 'Portfolio', 'clerina' );
		}

	} elseif ( is_post_type_archive( 'service' ) ) {
		if ( get_option( 'clerina_service_page_id' ) ) {
			$title = get_the_title( get_option( 'clerina_service_page_id' ) );
		} else {
			$title = esc_html__( 'Services', 'clerina' );
		}

	} elseif ( is_post_type_archive( 'testimonial' ) ) {
		if ( get_option( 'clerina_testimonial_page_id' ) ) {
			$title = get_the_title( get_option( 'clerina_testimonial_page_id' ) );
		} else {
			$title = esc_html__( 'Testimonial', 'clerina' );
		}

	} elseif ( is_tax() || is_category() ) {
		$title = single_term_title( '', false );

	} elseif ( is_singular() ) {
		$title = get_the_title( get_the_ID() );
	}

	return $title;
}

add_filter( 'get_the_archive_title', 'clerina_the_archive_title' );

/**
 * Returns CSS for the color schemes.
 *
 *
 * @param array $colors Color scheme colors.
 *
 * @return string Color scheme CSS.
 */
function clerina_get_color_scheme_css( $colors ) {
	return <<<CSS
	/* Color Scheme */
	/* Background Color */
	.clerina-button-group .clerina-button,
	.clerina-service-carousel .clerina-service-grid-wrapter .hover .entry-readmore .read-more:hover,
	.clerina-button-group .clerina-button,
	.clerina-service-carousel .clerina-service-grid-wrapter .hover .entry-readmore .read-more:hover,
	.cl-contact-form-7.style-2 div.wpcf7 input[type=submit],
	.cl-price-table.style-2:hover a,
	.cl-team-carousel .slick-arrow,
	.cl-image-carousel .slick-arrow,
	.cl-address-box .social li a:hover,
	.clerina-image-box-carousel .clerina-image-box-item .image-box-carousel-wrapper .image-box-readmore .read-more:hover,
	.clerina-booking-form .cpb-radio-buttons li input:checked + span,
	.clerina-booking-form .calculated .cpb-pricing_table-field button:hover,
	button,input[type=button],input[type=reset],input[type=submit],
	.paging-navigation .page-numbers:hover, .paging-navigation .page-numbers.current,
	.btn-primary,
	.primary-sidebar .widget .tagcloud a:hover,
	.site-header .menu-extras li .mini-item-counter,
	.header-v3 .site-header .header-menu-wrapper,
	.woocommerce ul.cart_list .woocommerce-mini-cart__buttons .button,.woocommerce .widget_shopping_cart_content .woocommerce-mini-cart__buttons .button:not(.checkout),
	.error-404 .page-content a,
	.pag-2 .pagination .next,
	.clerina-service .content-item .entry-read-more a:hover,
	.single-service .banner-style-2 a,
	.service-sidebar .widget_categories ul li:hover,
	.service-sidebar .widget_categories ul .current-menu-item,
	.cl-testimonial-carousel.style-2 .entry-thumbnail:before,
	.woocommerce .widget .price_slider_wrapper .ui-slider-range,
	.woocommerce .widget .price_slider_wrapper .ui-slider-handle,
	.single-product .site .site-content .entry-summary .cart .variations tr td .reset_variations:hover,
	.single-product .site .site-content .entry-summary .cart .button,
	.single-product .site .site-content .woocommerce-tabs .wc-tabs li:after,
	.single-product .woocommerce-Reviews .bar-rating .star-item .bar-content span,
	.woocommerce-cart .woocommerce .cart-collaterals .wc-proceed-to-checkout .button,
	.woocommerce-cart .woocommerce .return-to-shop .button, .cart .woocommerce .return-to-shop .button,
	.cart .woocommerce .cart-collaterals .wc-proceed-to-checkout .button,
	.footer-navigation .content .menu-item-cart .mini-item-counter,
	.slick-prev:hover, .slick-prev:focus, .slick-next:hover, .slick-next:focus,
	.cl-team-carousel .box-img .overlay-link,
	.cl-testimonial-carousel.style-2 .slick-prev:hover, 
	.cl-testimonial-carousel.style-2 .slick-prev:focus, 
	.cl-testimonial-carousel.style-2 .slick-next:hover, 
	.cl-testimonial-carousel.style-2 .slick-next:focus,
	.wpb-js-composer .vc_tta.vc_general .vc_active .vc_tta-panel-title
	{background-color: $colors;}

	/* Color */
	a:hover, 
	.clerina-service-carousel .entry-wrapter:hover a, .clerina-service-carousel .entry-wrapter:hover h3, 
	.clerina-service-carousel .clerina-service-grid-wrapter .hover .entry-readmore .read-more, 
	.clerina-service-carousel .entry-wrapter:hover a, .clerina-service-carousel .entry-wrapter:hover h3, 
	.clerina-service-carousel .clerina-service-grid-wrapter .hover .entry-readmore .read-more, 
	.clerina-icon-box .iconbox-title a:hover,
	.cl-price-table.style-2 .top-table, .cl-price-table.style-2 h2, 
	.cl-team-carousel .box-img .overlay-link a:hover, 
	.clerina-image-box-carousel .clerina-image-box-item .image-box-carousel-wrapper .image-box-readmore .read-more, 
	.clerina-booking-form .cpb-price, 
	.clerina-booking-form .calculated .cpb-pricing_table-field .cpb-pricing-table-unit, 
	a:hover, a:focus, a:active, 
	.primary-mobile-nav ul.menu li > a:hover, 
	.primary-mobile-nav ul.menu li.active > a, 
	.primary-mobile-nav ul.menu li.current_page_parent > a, .primary-mobile-nav ul.menu li.current-menu-item > a, .primary-mobile-nav ul.menu li.current-menu-ancestor > a, .primary-mobile-nav ul.menu li.current-menu-parent > a, 
	.primary-mobile-nav a:hover, .primary-mobile-nav a:focus, .primary-mobile-nav a:active, 
	.main-color, 
	.primary-sidebar .widget ul li a:hover, 
	.primary-sidebar .widget_archive ul li:hover,.primary-sidebar .widget_categories ul li:hover,.primary-sidebar .widget_product_categories ul li:hover,.primary-sidebar .widget_pages ul li:hover, 
	.primary-sidebar .social-links-widget .share-social:hover, 
	.topbar .social-links-widget .share-social:hover, 
	.primary-nav a:hover, .primary-nav a:focus, .primary-nav a:active, 
	.primary-nav > ul > li.current-menu-parent > a, .primary-nav > ul > li.current-menu-item > a, .primary-nav > ul > li.current-menu-ancestor > a, 
	.primary-nav li li a:hover, 
	.page-header .site-breadcrumb li > span, 
	.woocommerce ul.cart_list ul.product_list_widget li .product-content .woocommerce-Price-amount,.woocommerce .widget_shopping_cart_content ul.product_list_widget li .product-content .woocommerce-Price-amount, 
	.woocommerce ul.cart_list .woocommerce-mini-cart__total .woocommerce-Price-amount,.woocommerce .widget_shopping_cart_content .woocommerce-mini-cart__total .woocommerce-Price-amount, 
	.footer-widgets .widget ul li a:hover, 
	.footer-widgets a:hover, 
	.footer-copyright .content .socials a:hover, 
	.clerina-taxs-list ul li a.selected, 
	.comment-respond .logged-in-as a:hover, 
	.entry-footer .tags-links a:hover, 
	.entry-footer .cl-single-post-socials-share ul li a:hover, 
	.clerina-quote span, 
	.single-portfolio .post-navigation .nav-project a, 
	.single-portfolio .post-navigation a:hover, 
	.single-portfolio .post-navigation a:hover .lnr, 
	.single-portfolio .cl-socials ul li:hover i, 
	.clerina-service .content-item .entry-read-more a, 
	.single-service .banner-style-2 a:hover, 
	.cl-coming-soon .flip-clock-wrapper .flip-wrapper .inn, 
	.woocommerce .widget .price_slider_amount .button:hover, 
	.woocommerce .widget_recently_viewed_products .product_list_widget li .woocommerce-Price-amount, 
	.single-product .site .site-content .entry-summary .price, 
	.single-product .site .site-content .woocommerce-tabs .wc-tabs .active a, 
	.single-product .woocommerce-Reviews .average-rating .average-value, 
	.cart .woocommerce .cart-collaterals .coupon .coupon .button:hover, 
	.cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .cart-subtotal td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .order-total td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .cart-subtotal td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .order-total td, 
	.woocommerce-cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .cart-subtotal td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .order-total td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .cart-subtotal td, .cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .order-total td, 
	.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .woocommerce-checkout-payment .place-order .woocommerce-terms-and-conditions-wrapper .woocommerce-terms-and-conditions-checkbox-text a,
	.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .table-wrapter table .cart-subtotal td, .woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .table-wrapter table .order-total td, 
	.woocommerce .woocommerce-message a,.woocommerce .woocommerce-error a,.woocommerce .woocommerce-info a, 
	.woocommerce ul.products li.product .box-content .price, 
	.woocommerce .quantity .increase:hover,.woocommerce .quantity .decrease:hover, 
	.woocommerce-MyAccount-navigation ul li:hover a, .woocommerce-MyAccount-navigation ul li.is-active a, 
	.single-product .site .site-content .entry-summary .cart .group_table mark, 
	.single-product .site .site-content .entry-summary .cart .group_table ins,
	.footer-navigation .content a:hover, 
	.footer-navigation .content a:hover .extra-icon,
	.sticky .entry-title:hover:before
	{color: $colors;}

	.woocommerce table.shop_table a.remove:hover
	{color: $colors !important;}

	/* Border */
	.hover-1:hover, 
	.hover-1:hover, 
	.backtotop:hover,
	.cl-contact-form-7.style-2 div.wpcf7 input[type=submit]:hover, 
	.cl-price-table.style-2:hover, 
	.slick-dots li.slick-active button, 
	.page-template-template-coming-soon-page .form-comming input[type=submit]:hover, 
	.pag-2 .pagination .next, 
	.pag-2 .pagination .next:hover, 
	.single-service .banner-style-2 a, 
	.cl-socials ul li:hover i, 
	.cl-testimonial-carousel .testimonial-list.light-style .slick-active button:before, 
	.woocommerce .widget .price_slider_amount .button, 
	.single-product .site .site-content .product .woocommerce-product-gallery .flex-control-thumbs li .flex-active, 
	.single-product .site .site-content .product .woocommerce-product-gallery .flex-control-thumbs li:hover img, 
	.single-product .site .site-content .entry-summary .cart .button, 
	.single-product .woocommerce-Reviews .comment-form .form-submit .submit, 
	.cart .woocommerce .cart-collaterals .wc-proceed-to-checkout .button, 
	.woocommerce-cart .woocommerce .cart-collaterals .wc-proceed-to-checkout .button
	{border-color: $colors;}
	
	/* Border Left Color */
	.single-service .clerina-quote
	{border-left-color: $colors;}

	/* Background */
	.clerina_portfolio-carousel .project-wrapper .content .inner .more .read-more, 
	.clerina_portfolio-carousel .portfolio-list .slick-prev:hover, 
	.clerina_portfolio-carousel .portfolio-list .slick-next:hover, 
	.clerina_portfolio-carousel .project-wrapper .content .inner .more .read-more, 
	.backtotop, 
	.page-template-template-coming-soon-page .form-comming input[type=submit], 
	.portfolio-grid .portfolio-wrapper .content-item .entry-header .hover .plus-symbol, 
	.portfolio-layout-full-width .portfolio-wrapper .content-item .entry-header .hover .inner .view-more .plus-symbol, 
	.pagination .page-numbers:hover, 
	.pagination .current, 
	.single-portfolio .cl-image-carousel .slick-arrow, 
	.single-service .banner-style-1, 
	.cl-socials ul li:hover i, 
	.cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .continue, 
	.cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .actions .update_cart:hover, .cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .actions .clear-cart:hover, 
	.woocommerce-cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .continue, 
	.woocommerce-cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .actions .update_cart:hover, .cart .woocommerce .woocommerce-cart-form .shop_table tbody tr .actions .clear-cart:hover, 
	.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .woocommerce-checkout-payment .place-order .button, 
	.woocommerce .woocommerce-pagination .page-numbers li .page-numbers:hover, 
	.woocommerce .woocommerce-pagination .page-numbers li .current, 
	.woocommerce ul.products li.product .box-images .button,.woocommerce ul.products li.product .box-images .added_to_cart
	{background: $colors;}
	
	/* Other */
	.vc_tta-style-classic .vc_tta-panel.vc_active .vc_tta-panel-heading, 
	.vc_tta-style-classic .vc_tta-panel.vc_active .vc_tta-panel-body,
	.wpb-js-composer .vc_tta.vc_tta-o-no-fill .vc_tta-panels .vc_tta-panel-body
	{background-color: $colors !important;}
	
	/* Booking browser */
	.clerina-booking-form .cpb-range-field input[type=range]::-webkit-slider-thumb
	{background: $colors; box-shadow: 0 0 0 1px $colors;}
	.clerina-booking-form .cpb-range-field input[type=range]::-moz-range-thumb
	{background: $colors; box-shadow: 0 0 0 1px $colors;}
	.clerina-booking-form .cpb-range-field input[type=range]::-ms-thumb
	{background: $colors; box-shadow: 0 0 0 1px $colors;}
	.woocommerce .widget .price_slider_wrapper .ui-slider-handle
	{box-shadow: 0 0 0 1px $colors;}

CSS;
}

/**
 * Get typography CSS base on settings
 *
 * @since 1.1.6
 */
if ( ! function_exists( 'clerina_typography_css' ) ) :
	function clerina_typography_css() {
		$css        = '';
		$properties = array(
			'font-family'    => 'font-family',
			'font-size'      => 'font-size',
			'variant'        => 'font-weight',
			'line-height'    => 'line-height',
			'letter-spacing' => 'letter-spacing',
			'color'          => 'color',
			'text-transform' => 'text-transform',
			'text-align'     => 'text-align',
		);

		$settings = array(
			'body_typo'        => 'body,
										.clerina_portfolio-carousel .project-wrapper .content .inner .title,
										.clerina-booking-form .cpb-field h5',
			'heading1_typo'    => 'h1',
			'heading2_typo'    => 'h2',
			'heading3_typo'    => 'h3,
										.clerina-service .clerina-appointment .appointment .title,
										.woocommerce .widget_recently_viewed_products .product_list_widget .product-title,
										.single-product .site .site-content .entry-summary .product_title,
										.single-product .woocommerce-Reviews .comment-respond .comment-reply-title,
										.cart .woocommerce .woocommerce-cart-form .shop_table thead tr th,
										.cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot tr th, 
										.cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody tr th,
										.cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .shipping .title, 
										.cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .shipping .title,
										.woocommerce-cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot tr th, 
										.woocommerce-cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody tr th,
										.woocommerce-cart .woocommerce .cart-collaterals .cart_totals .bg-table table tfoot .shipping .title, 
										.woocommerce-cart .woocommerce .cart-collaterals .cart_totals .bg-table table tbody .shipping .title,
										.woocommerce-checkout .clerina-billing .woocommerce-billing-fields__field-wrapper .form-row label, 
										.woocommerce-checkout .clerina-billing .shipping_address .form-row label,
										.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .table-wrapter table tr th,
										.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .table-wrapter table .shipping .title,
										.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .woocommerce-checkout-payment .wc_payment_methods li label,
										.woocommerce-checkout .clerina-form-oder .woocommerce-checkout-review-order .woocommerce-checkout-payment .place-order .woocommerce-terms-and-conditions-wrapper,
										.woocommerce ul.products li.product .box-content .woocommerce-loop-product__title,
										.clerina-icon-box-2-row .main-icon .iconbox-title .title,
										.clerina-booking-form .cpb-input-label',
			'heading4_typo'    => 'h4',
			'heading5_typo'    => 'h5',
			'heading6_typo'    => 'h6',
			'menu_typo'        => '.primary-nav, .primary-nav > ul > li > a, .primary-mobile-nav a',
			'sub_menu_typo'    => '.primary-nav li li a',
			'button_typo'      => 'button, .clerina-button-group .clerina-button,
										.clerina-service-carousel .clerina-service-grid-wrapter .hover .entry-readmore .read-more,
										.clerina_portfolio-carousel .project-wrapper .content .inner .more .read-more,
										.clerina-image-box-carousel .clerina-image-box-item .image-box-carousel-wrapper .image-box-readmore .read-more,
										.clerina_portfolio-carousel .cl-portfolio-header .nav-filter li a,
										.cl-price-table a,
										.cl-cta a,
										button,
										input[type=button],
										input[type=reset],
										input[type=submit],
										.btn-primary,
										.site-header .menu-extras li .mini-item-counter,
										.clerina-taxs-list ul li a,
										.post-author .author-name,
										.error-404 .page-content a,
										.categories-filter .button-group .button,
										.portfolio-layout-full-width .portfolio-wrapper .content-item .entry-header .hover .inner .view-more .plus-symbol,
										.single-portfolio .post-navigation a,
										.clerina-service .content-item .entry-read-more a,
										.clerina-service .clerina-appointment .appointment .wpcf7-form p .wpcf7-submit,
										.single-service .banner-style-1 a,
										.single-service .banner-style-2 a,
										.cl-coming-soon .flip-clock-wrapper .flip-wrapper .inn,
										.single-product .site .site-content .woocommerce-tabs .wc-tabs li a,
										.cart .woocommerce .cart-collaterals .button,
										.woocommerce-cart .woocommerce .cart-collaterals .button,
										.woocommerce ul.products li.product .box-images .onsale,
										.clerina-modal input[type=submit]',
			'footer_text_typo' => '.footer-widgets, .footer-copyright',
		);

		foreach ( $settings as $setting => $selector ) {
			$typography = clerina_get_option( $setting );
			$default    = (array) clerina_get_option_default( $setting );
			$style      = '';

			foreach ( $properties as $key => $property ) {
				if ( isset( $typography[ $key ] ) && ! empty( $typography[ $key ] ) ) {
					if ( isset( $default[ $key ] ) && strtoupper( $default[ $key ] ) == strtoupper( $typography[ $key ] ) ) {
						continue;
					}

					$value = 'font-family' == $key ? '"' . rtrim( trim( $typography[ $key ] ), ',' ) . '"' : $typography[ $key ];
					$value = 'variant' == $key ? str_replace( 'regular', '400', $value ) : $value;

					if ( $value ) {
						$style .= $property . ': ' . $value . ';';
					}
				}
			}

			if ( ! empty( $style ) ) {
				$css .= $selector . '{' . $style . '}';
			}
		}

		return $css;
	}
endif;
/**
 * Returns CSS for the typography.
 *
 * @return string typography CSS.
 */
function clerina_get_heading_typography_css() {

	$headings   = array(
		'h1' => 'heading1_typo',
		'h2' => 'heading2_typo',
		'h3' => 'heading3_typo',
		'h4' => 'heading4_typo',
		'h5' => 'heading5_typo',
		'h6' => 'heading6_typo'
	);
	$inline_css = '';
	foreach ( $headings as $heading ) {
		$keys = array_keys( $headings, $heading );
		if ( $keys ) {
			$inline_css .= clerina_get_heading_font( $keys[0], $heading );
		}
	}

	return $inline_css;

}

/**
 * Returns CSS for the typography.
 *
 *
 * @param array $body_typo Color scheme body typography.
 *
 * @return string typography CSS.
 */
function clerina_get_heading_font( $key, $heading ) {

	$inline_css   = '';
	$heading_typo = clerina_get_option( $heading );

	if ( $heading_typo ) {
		if ( isset( $heading_typo['font-family'] ) && strtolower( $heading_typo['font-family'] ) !== 'Lato' ) {
			$inline_css .= $key . '{font-family:' . rtrim( trim( $heading_typo['font-family'] ), ',' ) . ', Arial, sans-serif}';
		}
	}

	if ( empty( $inline_css ) ) {
		return;
	}

	return <<<CSS
	{$inline_css}
CSS;
}