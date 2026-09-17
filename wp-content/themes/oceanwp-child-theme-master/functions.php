<?php
/**
 * OceanWP Child Theme Functions
 *
 * When running a child theme (see http://codex.wordpress.org/Theme_Development
 * and http://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions will be used.
 *
 * Text Domain: oceanwp
 * @link http://codex.wordpress.org/Plugin_API
 *
 */

/**
 * Load the parent style.css file
 *
 * @link http://codex.wordpress.org/Child_Themes
 */
function oceanwp_child_enqueue_parent_style() {

	// Dynamically get version number of the parent stylesheet (lets browsers re-cache your stylesheet when you update the theme).
	$theme   = wp_get_theme( 'OceanWP' );
	$version = $theme->get( 'Version' );

	// Load the stylesheet.
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'oceanwp-style' ), $version );
	
}

add_action( 'wp_enqueue_scripts', 'oceanwp_child_enqueue_parent_style' );

/**
 * Change number of related products output
 */ 
function woo_related_products_limit() {
  global $product;
	
	$args['posts_per_page'] = 6;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'jk_related_products_args', 20 );
  function jk_related_products_args( $args ) {
	$args['posts_per_page'] = 5; // 4 related products
	$args['columns'] = 5; // arranged in 2 columns
	return $args;
}

function show_loggedin_function( $atts ) {

	global $current_user, $user_login;
      	get_currentuserinfo();
	add_filter('widget_text', 'do_shortcode');
	if ($user_login) 
		return '<a class="login-link" style="color: #FFFFFF !important; font-weight: 600;" href="https://hospeco.ph/my-account/">Hi, ' . $current_user->display_name . '!</a>';
	else
		return '<a class="login-link" style="color: #FFFFFF !important; font-weight: 600;" href="https://hospeco.ph/my-account/">Sign up | Login</a>';
	
}
add_shortcode( 'show_loggedin_as', 'show_loggedin_function' );

function wc_remove_image_effect_support() {

    remove_theme_support( 'wc-product-gallery-zoom' );
    remove_theme_support( 'wc-product-gallery-lightbox' );
    remove_theme_support( 'wc-product-gallery-slider' );

}
add_action( 'after_setup_theme', 'wc_remove_image_effect_support', 100 );

function disable_shipping_calc_on_cart( $show_shipping ) {
    if( is_cart() ) {
        return false;
    }
    return $show_shipping;
}
add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'disable_shipping_calc_on_cart', 99 );

add_action( 'woocommerce_register_form', 'my_custom_user_registration_field' );
function my_custom_user_registration_field() {
    ?>
    <p class="form-row form-row-wide">
        <label for="my_custom_field"><?php _e( 'HVC Account Number', 'woocommerce' ); ?></label>
        <input type="text" class="input-text" name="my_custom_field" id="my_custom_field" value="<?php if ( ! empty( $_POST['my_custom_field'] ) ) esc_attr_e( $_POST['my_custom_field'] ); ?>"/>
		<span style="font-size: 12px;">Please enter the account number in the input field if you have Higieneco Value Card.</span>
    </p>
    <?php
}

add_action( 'woocommerce_created_customer', 'my_custom_save_registration_field' );
function my_custom_save_registration_field( $customer_id ) {
    if ( isset( $_POST['my_custom_field'] ) ) {
        update_user_meta( $customer_id, 'my_custom_field', sanitize_text_field( $_POST['my_custom_field'] ) );
    }
}


// Add custom field to user profile
add_action( 'show_user_profile', 'my_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'my_custom_user_profile_fields' );
function my_custom_user_profile_fields( $user ) {
?>
    <h3><?php _e('Account Number', 'my_domain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="my_custom_field"><?php _e('HVC Account Number', 'my_domain'); ?></label></th>
            <td>
                <input type="text" name="my_custom_field" id="my_custom_field" value="<?php echo esc_attr( get_the_author_meta( 'my_custom_field', $user->ID ) ); ?>" class="regular-text" /><br />
                <span class="description"><?php _e('Please enter your HVC account number.', 'my_domain'); ?></span>
            </td>
        </tr>
    </table>
<?php
}

// Save custom field data
add_action( 'personal_options_update', 'save_my_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_my_custom_user_profile_fields' );
function save_my_custom_user_profile_fields( $user_id ) {
    if ( current_user_can( 'edit_user', $user_id ) ) {
        update_user_meta( $user_id, 'my_custom_field', sanitize_text_field( $_POST['my_custom_field'] ) );
    }
}

function remove_gravity_forms_submit_button($button, $form){
    return '';
}
add_filter('gform_submit_button', 'remove_gravity_forms_submit_button', 10, 2);

add_filter( 'manage_edit-wc_points_rewards_columns', 'add_account_number_column' );
function add_account_number_column( $columns ) {
    $columns['account_number'] = __( 'Account Number', 'woocommerce-points-and-rewards' );
    return $columns;
}

add_action( 'manage_wc_points_rewards_posts_custom_column' , 'add_account_number_data', 10, 2 );
function add_account_number_data( $column, $post_id ) {
    if ( 'account_number' === $column ) {
        $user_id = get_post_meta( $post_id, '_customer_user', true );
        $account_number = get_user_meta( $user_id, 'my_custom_field', true );
        echo esc_html( $account_number );
    }
}

add_filter( 'elementor/image_size/get_attachment_image_html', function( $html, $settings ) {
    // Check if a custom flag is set in the widget settings
    if ( ! empty( $settings['css_classes'] ) && str_contains( $settings['css_classes'], 'lcp-image' ) ) {
        $html = str_replace( '<img ', '<img fetchpriority="high" ', $html );
    }
    return $html;
}, 10, 2 );

/*
add_action('wp_head', function() {
    if (is_front_page()) {
        echo '<link rel="preload" as="image" fetchpriority="high" href="https://hospeco.ph/wp-content/uploads/2026/04/Month-of-April_Website-Banner.jpg">';
    }
}, 1);*/

/*
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size){
    if ( $attachment->ID === 43238 ) {
        $attr['fetchpriority'] = 'high';
    }
    return $attr;
}, 10, 3);*/

/*
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size){
    // Only apply to the 'full' or specific size images if needed
    $attr['fetchpriority'] = 'high';
    return $attr;
}, 10, 3);*/

add_action( 'elementor/query/exclude_no_price', function( $query ) {
    $meta_query = $query->get( 'meta_query' ) ?: [];

    $meta_query[] = [
        'key'     => '_price',
        'value'   => '',
        'compare' => '!=',
    ];

    $query->set( 'meta_query', $meta_query );
});

remove_action('wp_dashboard_setup', 'ocean_add_dashboard_widget');

add_action('woocommerce_review_order_before_payment', 'hospeco_other_stores_buttons');

function hospeco_other_stores_buttons() {
    echo '<div style="background: #f9f9f9; padding: 20px; margin-bottom: 30px; border-left: 4px solid #0052CC; border-radius: 4px;">';
    echo '<p style="margin: 0 0 15px 0; font-weight: bold; font-size: 16px;">💡 For customers outside Metro Manila, you can check out through our official accounts:</p>';
    echo '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
    echo '<a href="https://shopee.ph/hospecophilippines?smtt=0.0.9" target="_blank" style="display: inline-block; padding: 10px 20px; background: #ee4d2d; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Shopee</a>';
    echo '<a href="https://www.lazada.com.ph/shop/hospeco-/" target="_blank" style="display: inline-block; padding: 10px 20px; background: #7cb342; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">Lazada</a>';
    echo '<a href="https://www.tiktok.com/@hospecophilippines?_r=1&_t=ZS-97J2oaA0heO" target="_blank" style="display: inline-block; padding: 10px 20px; background: #000; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">TikTok Shop</a>';
    echo '</div>';
    echo '</div>';
}