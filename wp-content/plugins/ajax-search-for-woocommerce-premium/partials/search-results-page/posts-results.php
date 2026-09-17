<?php

// Exit if accessed directly
use DgoraWcas\Helpers;
use DgoraWcas\Shortcode;

if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

if ( empty( $args['post_type'] ) ) {
	return;
}
?>
	<div class="dgwt-wcas-posts-results">

		<?php
		if ( empty( $args['results']['results'] ) ) {
			echo apply_filters( 'dgwt/wcas/shortcode/fibosearch_posts_results/no_results/', $args['no_results'], $args['post_type'], $args );
		} else {
			echo '<h3 class="dgwt-wcas-posts-results-headline">' . esc_html( $args['headline'] ) . '</h3>';
		}
		$filename = apply_filters( 'dgwt/wcas/shortcode/fibosearch_posts_results/partial_path/' . $args['layout'], DGWT_WCAS_DIR . 'partials/search-results-page/' . $args['layout'] . '.php' );
		echo Shortcode::getTemplatePart( $filename, $args );
		?>
	</div>
<?php
