<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}
?>
<div class="dgwt-wcas-posts-results-grid dgwt-wcas-posts-results-grid--<?php echo sanitize_key( $args['post_type'] ); ?>">
	<?php
	foreach ( $args['results']['results'] as $result ) {
		echo '<div class="dgwt-wcas-posts-results-grid-item">';
		echo '<a href="' . esc_url( $result['url'] ) . '" >';
		if ( ! empty( $args['show_image'] ) && ! empty( $result['image'] ) ) {
			echo '<div class="dgwt-wcas-posts-results-image">';
			echo '<img src="' . esc_url( $result['image'] ) . '" alt="' . esc_attr( $result['name'] ) . '" />';
			echo '</div>';
		}
		echo '<span class="dgwt-wcas-posts-results-label">' . esc_html( $result['name'] ) . '</span>';
		echo '</a>';
		echo '</div>';
	}
	?>
</div>
