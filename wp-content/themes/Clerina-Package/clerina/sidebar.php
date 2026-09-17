<?php
/**
 * The sidebar containing the main widget area
 *
 * @link    https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package 'Clerina'
 */

if ( clerina_get_layout() == 'full-content' ) {
	return;
}

$col = 'col-md-4 cl-widget-col-4';

if ( clerina_is_catalog() || (function_exists('is_product') && is_product()) ) {
	$col = 'col-md-3';
}

$sidebar = 'blog-sidebar';

if ( is_page() ) {
	$sidebar = 'page-sidebar';
}
$class_sidebar = '';

if ( is_singular( 'service' ) ) {
	$sidebar       = 'service-sidebar';
	$class_sidebar = 'service-sidebar';
}
if ( clerina_is_catalog() || (function_exists('is_product') && is_product()) ) {
	$sidebar       = 'shop-sidebar';
	$class_sidebar = 'shop-sidebar';
}

if ( ! is_active_sidebar( $sidebar ) ) {
	return;
}

?>

<aside id="primary-sidebar" class="widget-area primary-sidebar <?php echo esc_attr( $col ); ?> col-sm-12 col-xs-12 <?php echo esc_attr($class_sidebar); ?>">
	<?php dynamic_sidebar( $sidebar ); ?>
</aside><!-- #secondary -->
