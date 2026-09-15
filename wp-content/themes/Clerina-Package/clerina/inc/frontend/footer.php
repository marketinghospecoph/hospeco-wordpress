<?php
/**
 * Hooks for template footer
 *
 * @package 'Clerina'
 */


/**
 * Show footer
 */

if ( ! function_exists( 'clerina_show_footer' ) ) :
	function clerina_show_footer() {
		get_template_part( 'template-parts/footers/layout' );
	}

endif;

add_action( 'clerina_footer', 'clerina_show_footer', 10, 1 );

/**
 * Show footer widgets
 */

if ( ! function_exists( 'clerina_footer_widgets' ) ) :
	function clerina_footer_widgets() {
		if ( ! intval( clerina_get_option( 'footer_widget' ) ) ) {
			return;
		}

		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}

		if ( is_404() ) {
			return;
		}

		if ( is_active_sidebar( 'footer-1' ) == false &&
		     is_active_sidebar( 'footer-2' ) == false &&
		     is_active_sidebar( 'footer-3' ) == false &&
		     is_active_sidebar( 'footer-4' ) == false ) {
			return;
		}

		$footer_layout = clerina_get_option( 'footer_layout' );
		$class_row     = 'row';
		if ( $footer_layout == 'full-width' ) {
			$class_row = 'footer-flex';
		}
		?>

        <div id="footer-widgets" class="footer-widgets widgets-area">
            <div class="<?php echo clerina_footer_container_class(); ?>">
                <div class="<?php echo esc_attr( $class_row ); ?>">
					<?php
					$columns = max( 1, absint( clerina_get_option( 'footer_widget_columns' ) ) );

					$col_class = 'col-xs-12 col-md-6 col-lg-' . floor( 12 / $columns );
					if ( $footer_layout == 'full-width' ) {
						$col_class = '';
					}
					for ( $i = 1; $i <= $columns; $i ++ ) :
						?>
                        <div class="footer-sidebar footer-<?php echo esc_attr( $i ) ?> <?php echo esc_attr( $col_class ) ?>">
							<?php
							ob_start();
							dynamic_sidebar( "footer-$i" );
							$output = ob_get_clean();

							echo apply_filters( "clerina_footer_bar_$i", $output );
							?>
                        </div>
					<?php endfor; ?>
                </div>
            </div>
        </div>
		<?php
	}

endif;

add_action( 'clerina_footer', 'clerina_footer_widgets', 10, 1 );

/**
 * Show footer copyright
 */

if ( ! function_exists( 'clerina_copyright' ) ) :
	function clerina_copyright() {
		$copyright        = clerina_get_option( 'footer_copyright' );
		$copyright_image  = clerina_get_option( 'footer_copyright_image' );
		$copyright_social = clerina_get_option( 'footer_copyright_social' );
		if ( empty( $copyright ) &&
		     empty( $copyright_image ) &&
		     empty( $copyright_social ) ) {
			return;
		}

		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}

		if ( is_404() ) {
			return;
		}
		?>

        <div id="footer-copyright" class="footer-copyright">
            <div class="<?php echo clerina_footer_container_class(); ?>">
                <div class="content">
					<?php
					clerina_copyright_image();
					clerina_copyright_text();
					clerina_copyright_social();
					?>
                </div>
            </div>
        </div>

		<?php
	}

endif;

add_action( 'clerina_footer', 'clerina_copyright', 10, 1 );

/**
 * Copyright Images
 */

function clerina_copyright_image() {
	$images = clerina_get_option( 'footer_copyright_image' );
	if ( empty( $images ) ) {
		return;
	}
	echo '<div class="images"><img alt="' . esc_attr__( 'Copyright Images', 'clerina' ) . '" src="' . esc_url( $images ) . '"/></div>';
}

/**
 * Copyright Text
 */

function clerina_copyright_text() {
	$text = clerina_get_option( 'footer_copyright' );
	if ( empty( $text ) ) {
		return;
	}

	echo '<div class="text">' . wp_kses( $text, wp_kses_allowed_html( 'post' ) ) . '</div>';
}

/**
 * Copyright Social
 */

function clerina_copyright_social() {

	$footer_socials = clerina_get_option( 'footer_copyright_social' );

	if ( empty( $footer_socials ) ) {
		return;
	}

	?>
    <div class="socials footer-social">
		<?php

		if ( $footer_socials ) {
			$socials = (array) clerina_get_socials();

			foreach ( $footer_socials as $social ) {
				foreach ( $socials as $name => $label ) {
					$link_url = isset( $social['link_url'] ) ? $social['link_url'] : '';

					if ( preg_match( '/' . $name . '/', $link_url ) ) {

						if ( $name == 'google' ) {
							$name = 'google-plus';
						}

						printf( '<a href="%s" target="_blank"><i class="fa fa-%s"></i></a>', esc_url( $link_url ), esc_attr( $name ) );
						break;
					}
				}
			}
		}
		?>
    </div>
	<?php
}

/**
 * Show footer navigation
 */

if ( ! function_exists( 'clerina_footer_navigation' ) ) :
	function clerina_footer_navigation() {
		if ( is_page_template( 'template-coming-soon-page.php' ) ) {
			return;
		}
		if ( ! intval( clerina_get_option( 'mobile_nav' ) ) ) {
			return;
		}

		$options = clerina_get_option( 'mobile_option' );
		?>

        <div id="footer-navigation" class="footer-navigation">
            <div class="container">
                <div class="content item-<?php echo esc_attr( count( $options ) ); ?>">
					<?php foreach ( $options as $option ) :
						switch ( $option ) {
							case 'home':
								echo sprintf( '
                                            <div class="">
                                                <a href="%s">
                                                    <i class="icon-home extra-icon"></i>
                                                    <b>%s</b>
                                                </a>
                                            </div>',
									esc_url( home_url() ),
									esc_html__( 'Home', 'clerina' )
								);
								break;
							case 'booking':
								echo sprintf( '
                                            <div class="">
                                                <a href="%s">
                                                    <i class="icon-calendar-full extra-icon"></i>
                                                    <b>%s</b>
                                                </a>
                                            </div>',
									esc_url( clerina_get_option( 'mobile_booking' ) ),
									esc_html__( 'Booking', 'clerina' )
								);
								break;
							case 'cart':
								echo '<ul class="">';
								get_template_part( 'template-parts/headers/cart' );
								echo '</ul>';
								break;
						}
					endforeach; ?>
                </div>
            </div>
        </div>

		<?php
	}

endif;

add_action( 'clerina_footer', 'clerina_footer_navigation', 10, 1 );

/**
 * Adds photoSwipe dialog element
 */
function clerina_gallery_images_lightbox() {

	if ( ! is_singular() ) {
		return;
	}
	?>
    <div id="pswp" class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

        <div class="pswp__bg"></div>

        <div class="pswp__scroll-wrap">

            <div class="pswp__container">
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
            </div>

            <div class="pswp__ui pswp__ui--hidden">

                <div class="pswp__top-bar">


                    <div class="pswp__counter"></div>

                    <button class="pswp__button pswp__button--close"
                            title="<?php esc_attr_e( 'Close (Esc)', 'clerina' ) ?>"></button>

                    <button class="pswp__button pswp__button--share"
                            title="<?php esc_attr_e( 'Share', 'clerina' ) ?>"></button>

                    <button class="pswp__button pswp__button--fs"
                            title="<?php esc_attr_e( 'Toggle fullscreen', 'clerina' ) ?>"></button>

                    <button class="pswp__button pswp__button--zoom"
                            title="<?php esc_attr_e( 'Zoom in/out', 'clerina' ) ?>"></button>

                    <div class="pswp__preloader">
                        <div class="pswp__preloader__icn">
                            <div class="pswp__preloader__cut">
                                <div class="pswp__preloader__donut"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                    <div class="pswp__share-tooltip"></div>
                </div>

                <button class="pswp__button pswp__button--arrow--left"
                        title="<?php esc_attr_e( 'Previous (arrow left)', 'clerina' ) ?>">
                </button>

                <button class="pswp__button pswp__button--arrow--right"
                        title="<?php esc_attr_e( 'Next (arrow right)', 'clerina' ) ?>">
                </button>

                <div class="pswp__caption">
                    <div class="pswp__caption__center"></div>
                </div>

            </div>

        </div>

    </div>
	<?php
}

add_action( 'clerina_after_footer', 'clerina_gallery_images_lightbox' );

/**
 * Add newsletter popup on the footer
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'clerina_newsletter_popup' ) ) :
	function clerina_newsletter_popup() {
		if ( ! intval( clerina_get_option( 'newsletter_popup' ) ) ) {
			return;
		}

		if ( ! intval( clerina_get_option( 'newsletter_home_popup' ) ) ) {
			if ( is_page_template( 'template-homepage.php' ) || is_front_page() ) {
				return;
			}
		}

		$cl_newletter = '';
		if ( isset( $_COOKIE['cl_newletter'] ) ) {
			$cl_newletter = $_COOKIE['cl_newletter'];
		}

		if ( ! empty( $cl_newletter ) ) {
			return;
		}

		$output = array();

		if ( $desc = clerina_get_option( 'newsletter_content' ) ) {
			$output[] = sprintf( '<div class="n-desc">%s</div>', wp_kses( $desc, wp_kses_allowed_html( 'post' ) ) );
		}

		if ( $form = clerina_get_option( 'newsletter_form' ) ) {
			$output[] = sprintf( '<div class="n-form">%s</div>', do_shortcode( wp_kses( $form, wp_kses_allowed_html( 'post' ) ) ) );
		}

		$output[] = sprintf( '<a href="#" class="n-close">%s</a>', esc_html__( 'Don\'t show this popup again', 'clerina' ) );

		?>
        <div id="cl-newsletter-popup" class="clerina-modal cl-newsletter-popup" tabindex="-1" aria-hidden="true">
            <div class="cl-modal-overlay"></div>
            <div class="modal-content">
                <a href="#" class="close-modal">
                    <i class="icon-cross"></i>
                </a>
                <div class="newletter-content">
					<?php $image = clerina_get_option( 'newsletter_bg_image' );
					if ( $image ) {
						echo sprintf( '<div class="n-image" style="background-image:url(%s)"></div>', esc_url( $image ) );
					} ?>
                    <div class="nl-inner">
						<?php echo implode( '', $output ) ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
		<?php
	}
endif;

add_action( 'wp_footer', 'clerina_newsletter_popup' );

/**
 * Display back to top
 *
 * @since 1.0.0
 */
function clerina_back_to_top() {
	if ( ! intval( clerina_get_option( 'back_to_top' ) ) ) {
		return;
	}
	?>
    <a id="scroll-top" class="backtotop" href="#page-top">
        <i class="fa fa-angle-up"></i>
    </a>
	<?php
}

add_action( 'wp_footer', 'clerina_back_to_top' );

/**
 * Add off mobile menu to footer
 *
 * @since 1.0.0
 */
function clerina_off_canvas_mobile_menu() {
	?>
    <div class="primary-mobile-nav" id="primary-mobile-nav" role="navigation">
        <div class="mobile-nav-content">
            <div class="close-btn">
                <a href="#" class="close-canvas-mobile-panel"></a>
            </div>
            <div class="box-nav">
				<?php
				clerina_get_search_form();

				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
				) );
				?>
            </div>
        </div>
    </div>
	<?php
}

add_action( 'wp_footer', 'clerina_off_canvas_mobile_menu' );

/**
 * Display a layer to close canvas panel everywhere inside page
 *
 * @since 1.0.0
 */

if ( ! function_exists( 'clerina_site_canvas_layer' ) ) :
	function clerina_site_canvas_layer() {
		?>
        <div id="off-canvas-layer" class="clerina-off-canvas-layer"></div>
		<?php
	}

endif;

add_action( 'wp_footer', 'clerina_site_canvas_layer' );

/**
 * Get search form on Mobile
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'clerina_get_search_form' ) ) :
	function clerina_get_search_form() {
		?>
        <div class="search-form">
            <form class="header-search-content" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <div class="search-wrapper">
                    <input type="text" name="s" class="search-field" autocomplete="off"
                           placeholder="<?php esc_attr_e( 'Search', 'clerina' ); ?>">
                    <input type="submit" class="search-submit"/>
                </div>
            </form>
        </div>
		<?php
	}
endif;