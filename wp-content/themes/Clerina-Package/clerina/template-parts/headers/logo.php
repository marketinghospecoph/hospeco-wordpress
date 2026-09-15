<?php
/**
 * Hooks for template logo
 *
 * @package clerina
 */

$logo = clerina_get_option( 'logo' );

if ( ! $logo ) {
	$logo = get_template_directory_uri() . '/images/svg/home1_logo_top.svg';
}

?>
<a href="<?php echo esc_url( home_url() ) ?>" class="header-logo">
    <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
         class="logo logo-dark">
</a>
<?php

printf(
	'<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>',
	is_home() || is_front_page() ? 'h1' : 'p',
	esc_url( home_url( '' ) ),
	get_bloginfo( 'name' )
);
?>
<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
