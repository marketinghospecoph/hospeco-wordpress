<?php
/**
 * Custom functions that act on header templates
 *
 * @package 'Clerina'
 */

/**
 * Register fonts
 *
 * @since  1.0.0
 *
 * @return string
 */

if ( ! function_exists( 'clerina_fonts_url' ) ):
	function clerina_fonts_url() {
		$fonts_url = '';

		/* Translators: If there are characters in your language that are not
		* supported by Montserrat, translate this to 'off'. Do not translate
		* into your own language.
		*/

		if ( 'off' !== _x( 'on', 'Lato: on or off', 'clerina' ) ) {
			$font_families[] = 'Lato:300,400,500,600,700';
		}

		if ( 'off' !== _x( 'on', 'Poppins font: on or off', 'clerina' ) ) {
			$font_families[] = 'Poppins:300,400,500,600,700';
		}
		if ( 'off' !== _x( 'on', 'Playball font: on or off', 'clerina' ) ) {
			$font_families[] = 'Playball:300,400,500,600,700';
		}


		if ( ! empty( $font_families ) ) {
			$query_args = array(
				'family' => urlencode( implode( '|', $font_families ) ),
				'subset' => urlencode( 'latin,latin-ext' ),
			);

			$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
		}

		return esc_url_raw( $fonts_url );
	}
endif;

/**
 * Get header menu
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
if ( ! function_exists( 'clerina_header_menu' ) ) :
	function clerina_header_menu() {

		?>
        <div class="primary-nav nav">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
					) );
			}
			?>
        </div>
		<?php
	}
endif;

/**
 * Get header sidebar
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
if ( ! function_exists( 'clerina_header_sidebar' ) ) :
	function clerina_header_sidebar() {
		if ( ! is_active_sidebar( 'header-sidebar' ) ) {
			return;
		}
		?>
        <div class="header-sidebar">
			<?php
			ob_start();
			dynamic_sidebar( 'header-sidebar' );
			$output = ob_get_clean();

			echo apply_filters( 'clerina_header_bar', $output );
			?>
        </div>
		<?php
	}
endif;

/**
 * Get header menu
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
if ( ! function_exists( 'clerina_extra_menu' ) ) :
	function clerina_extra_menu() {
		$menu_extras = clerina_get_option( 'menu_extras' );

		if ( empty( $menu_extras ) ) {
			return;
		}

		$class = '';
		if ( clerina_get_option( 'header_layout' ) == '2' && ! intval( clerina_get_option( 'menu_booking' ) ) ) {
			$class = ' full-width';
		}

		echo '<ul class="menu-extras' . esc_attr( $class ) . '">';

		foreach ( $menu_extras as $item ) {
			get_template_part( 'template-parts/headers/' . $item );
		}

		echo '</ul>';

	}
endif;

/**
 * Get header booking
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
if ( ! function_exists( 'clerina_header_booking' ) ) :
	function clerina_header_booking( $hidden = true ) {
		if ( ! intval( clerina_get_option( 'menu_booking' ) ) ) {
			return;
		}

		$booking_text = clerina_get_option( 'menu_booking_text' );
		$booking_link = clerina_get_option( 'menu_booking_link' );

		if ( empty( $booking_text ) ) {
			return;
		}

		$class = 'hidden-xs hidden-sm hidden-md';
		if ( ! $hidden ) {
			$class = 'hidden-lg';
		}
		echo sprintf( '<div class="%s col-header-btn col-lg-3"><a class="btn-primary btn-booking" href="%s">%s</a></div>', $class, esc_url( $booking_link ), esc_html( $booking_text ) );
	}
endif;

/**
 * Get header container
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
if ( ! function_exists( 'clerina_header_container' ) ) :
	function clerina_header_container() {
		if ( clerina_get_option( 'header_layout' ) == '2' ) {
			return 'clerina-container';
		}

		return 'container';

	}
endif;