<?php
/**
 * @package Clerina
 */
$page_header = clerina_get_page_header_image();


if ( ! $page_header ) {
	return;
}

$css_item    = '';
$breadcrumbs = false;

if ( isset( $page_header['elements'] ) ) {
	$els = $page_header['elements'];

	if ( ! in_array( 'title', $els ) ) {
		$css_item .= ' hide-title';
	}

	if ( in_array( 'breadcrumb', $els ) ) {
		$breadcrumbs = true;
	}
}

if ( isset( $page_header['text_color'] ) ) {
	$css_item .= ' text-' . $page_header['text_color'];
}

if ( isset( $page_header['parallax'] ) && $page_header['parallax']) {
	$css_item .= ' parallax';
} else {
	$css_item .= ' no-parallax';
}

if ( isset( $page_header['bg_image'] ) && $page_header['bg_image'] ) {
	$css_item .= ' has-image';
} else {
	$css_item .= ' no-image';
}

?>
<div class="page-header <?php echo esc_attr( $css_item ); ?>">
	<div class="page-header-content">
		<?php if ( isset( $page_header['bg_image'] ) && $page_header['bg_image'] ) {
			echo '<div class="featured-image" style="background-image: url('.esc_url( $page_header['bg_image']).')"></div>';
		}?>
		<div class="container">
			<div class="page-header-area">
				<?php
				the_archive_title( '<h1 class="page-title">', '</h1>' );
				?>
				<div class="page-header-breadcrumbs">
					<?php
					if ( $breadcrumbs ) {
						clerina_get_breadcrumbs();
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>