<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

?>
<ul class="dgwt-wcas-posts-results-list dgwt-wcas-posts-results-list--<?php echo sanitize_key( $args['post_type'] ); ?>">
	<?php
	foreach ( $args['results']['results'] as $result ) {
		echo '<li class="dgwt-wcas-posts-results-label"><a href="' . esc_url( $result['url'] ) . '">' . esc_html( $result['name'] ) . '</a></li>';
	}
	?>
</ul>
