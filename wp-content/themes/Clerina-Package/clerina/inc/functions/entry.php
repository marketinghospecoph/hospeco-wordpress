<?php
/**
 * Custom functions for post
 *
 * @package 'Clerina'
 */

if ( ! function_exists( 'clerina_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function clerina_posted_on() {
		if ( ! clerina_get_option( 'post_entry_meta_posted_on' ) ) {
			return;
		}

		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() ),
			esc_attr( get_the_modified_date( DATE_W3C ) ),
			esc_html( get_the_modified_date() )
		);

		$posted_on = sprintf(
		/* translators: %s: post date. */
			esc_html_x( '%s', 'post date', 'clerina' ),
			'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
		);

		echo '<span class="posted-on"><span class="label-date">Date: </span>' . $posted_on . '</span>'; // WPCS: XSS OK.

	}
endif;

/**
 * Prints HTML with meta information for the author des short.
 *
 * @since 1.0.0
 */
function clerina_entry_author() {
?>
<div class="entry-author clearfix">
    <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) ?>" class="author-avatar">
        <?php echo get_avatar( get_the_author_meta( 'ID' ), 50 ); ?>
    </a>

    <h3 class="author-name">
        <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) ?>">
            <span><?php esc_html_e( 'By', 'clerina' ); ?> </span> <?php the_author_meta( 'display_name' ); ?>
        </a>
    </h3>
</div>
<?php
}

/**
 * Prints HTML with meta information for the social share
 *
 * @since 1.0.0
 */
function clerina_entry_header() {
	if ( ! intval( clerina_get_option( 'show_post_social_share' ) ) ) {
		return;
	}

	echo '<div class="entry-link">';

	if ( intval( clerina_get_option( 'show_post_social_share' ) ) ) {
		echo '<div class="cl-single-post-socials-share">';
		if ( function_exists( 'clerina_addons_share_link_socials' ) ) {
			echo '<h4>' . esc_html__( 'Share', 'clerina' ) . ':</h4>';
			echo clerina_addons_share_link_socials( get_the_title(), get_the_permalink(), get_the_post_thumbnail() );
		}
		echo '</div>';
	};

	echo '</div>';
}

/**
 * Get or display limited words from given string.
 * Strips all tags and shortcodes from string.
 *
 * @since 1.0.0
 *
 * @param integer $num_words The maximum number of words
 * @param string  $more      More link.
 * @param bool    $echo      Echo or return output
 *
 * @return string|void Limited content.
 */
function clerina_content_limit( $content, $num_words, $more = "&hellip;") {

	// Strip tags and shortcodes so the content truncation count is done correctly
	$content = strip_tags( strip_shortcodes( $content ), apply_filters( 'clerina_content_limit_allowed_tags', '<script>,<style>' ) );

	// Remove inline styles / scripts
	$content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );


	// Truncate $content to $max_char
	$content = wp_trim_words( $content, $num_words );

	if ( $more ) {
		return sprintf(
			'<p>%s <a href="%s" class="more-link" title="%s">%s</a></p>',
			$content,
			get_permalink(),
			sprintf( esc_html__( 'Continue reading &quot;%s&quot;', 'clerina' ), the_title_attribute( 'echo=0' ) ),
			esc_html( $more )
		);
	} else {
		return sprintf( '<p>%s</p>', $content );
	}



}

if ( ! function_exists( 'clerina_posted_by' ) ) :
	/**
	 * Prints HTML with meta information for the current author.
	 */
	function clerina_posted_by() {
		if ( ! clerina_get_option( 'post_entry_meta_posted_by' ) ) {
			return;
		}

		$byline = sprintf(
		/* translators: %s: post author. */
			esc_html_x( 'Author: %s', 'post author', 'clerina' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
		);

		echo '<span class="byline"> ' . $byline . '</span>'; // WPCS: XSS OK.

	}
endif;

if ( ! function_exists( 'clerina_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function clerina_entry_footer() {
		clerina_entry_tags();
		clerina_entry_header();

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo '<span class="comments-link">';
			comments_popup_link(
				sprintf(
					wp_kses(
					/* translators: %s: post title */
						__( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'clerina' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				)
			);
			echo '</span>';
		}
	}
endif;

if ( ! function_exists( 'clerina_entry_tags' ) ) :
	/**
	 * Prints HTML with meta information tags.
	 */
	function clerina_entry_tags() {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'clerina' ) );
			if ( $tags_list ) {
				/* translators: 1: list of tags. */
				printf( '<span class="tags-links"><span class="icon_tags_alt"></span> %1$s</span>', $tags_list ); // WPCS: XSS OK.
			}
		}
	}
endif;

if ( ! function_exists( 'clerina_entry_categories' ) ) :
	/**
	 * Prints HTML with meta information categories.
	 */
	function clerina_entry_categories() {
		if ( 'post' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$categories_list = get_the_category_list( esc_html__( ', ', 'clerina' ) );
			if ( $categories_list ) {
				/* translators: 1: list of categories. */
				printf( '<span class="cat-links">' . esc_html__( 'Posted in %1$s', 'clerina' ) . '</span>', $categories_list ); // WPCS: XSS OK.
			}
		}
	}
endif;

if ( ! function_exists( 'clerina_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 */
	function clerina_post_thumbnail($size = 'thumbnail') {
		if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
			return;
		}

		if ( is_singular() ) :
			?>

			<div class="post-thumbnail">
                <a href="<?php the_permalink(); ?>">
				    <?php the_post_thumbnail($size); ?>
                </a>
			</div><!-- .post-thumbnail -->

		<?php else : ?>

			<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php

				the_post_thumbnail( $size, array(
					'alt' => the_title_attribute( array(
						'echo' => false,
					) ),
				) );
				?>
			</a>

			<?php
		endif; // End is_singular().
	}
endif;

/**
 * Get author meta
 *
 * @since  1.0
 *
 */
function clerina_author_box() {

	if ( ! intval( clerina_get_option( 'show_author_box' ) ) ) {
		return;
	}

	if ( ! get_the_author_meta( 'description' ) ) {
		return;
	}

	?>
	<div class="post-author">
		<div class="post-author-box clearfix">
			<div class="post-author-avatar">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 95 ); ?>
			</div>
			<div class="post-author-info">
				<h3 class="author-name"><?php the_author_meta( 'display_name' ); ?>
					<span><?php echo esc_html__( 'Author', 'clerina' ) ?> </span></h3>
				<div class="author-desc"><?php the_author_meta( 'description' ); ?></div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Check is portfolio
 *
 * @since  1.0
 */

if ( ! function_exists( 'clerina_is_portfolio' ) ) :
	function clerina_is_portfolio() {
		if ( is_post_type_archive( 'portfolio' ) || is_tax( 'portfolio_category' ) ) {
			return true;
		}

		return false;
	}
endif;

/**
 * Check is services
 *
 * @since  1.0
 */

if ( ! function_exists( 'clerina_is_service' ) ) :
	function clerina_is_service() {
		if ( is_post_type_archive( 'service' ) || is_tax( 'service_category' ) ) {
			return true;
		}

		return false;
	}
endif;

/**
 * Check is portfolio
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_category' ) ) {
	function clerina_portfolio_category() {
		$terms = get_the_terms( get_the_ID(), 'portfolio_category' );
		if ( $terms && ! is_wp_error( $terms ) ) :
			$count = 0;
			$len   = count( $terms );
			foreach ( $terms as $item ) {
				$outer = ', ';
				if ( $count == ( $len - 1 ) ) {
					$outer = '';
				}
				$id   = esc_url( get_term_link( $item->term_id, 'portfolio_category' ) );
				$name = $item->name;
				printf( '<a class="category" href="%s">%s</a>%s', $id, $name, $outer );
				$count ++;
			}

		endif;
	}
}

/**
 * Check is portfolio
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_slug' ) ) {
	function clerina_portfolio_slug() {
		$terms = get_the_terms( get_the_ID(), 'portfolio_category' );
		if ( $terms && ! is_wp_error( $terms ) ) :
			printf( '%s', $terms[0]->slug );
		endif;
	}
}

/*
 * Project categories filter
 */
if ( ! function_exists( 'clerina_portfolio_isotope' ) ) {
	function clerina_portfolio_isotope() {
		if ( ! intval( clerina_get_option( 'portfolio_cats_filters' ) ) ) {
			return;
		}
		$cats = array();
		global $wp_query;
		$cats_number = clerina_get_option( 'portfolio_cat_number' );
		$count       = 0;
		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$post_categories = wp_get_post_terms( get_the_ID(), 'portfolio_category' );

			foreach ( $post_categories as $cat ) {
				if ( empty( $cats[ $cat->term_id ] ) ) {
					$cats[ $cat->term_id ] = array( 'name' => $cat->name, 'slug' => $cat->slug, );
				}
			}
		}
		$list_category = array();
		foreach ( $cats as $category ) {
			if ( $count == $cats_number ):break; endif;
			$slug            = esc_attr( $category['slug'] );
			$name            = esc_html( $category['name']);
			$list_category[] = "<span class='button' data-filter='.$slug'>$name</span>";
			$count ++;
		}
		$list_category = implode( ' ', $list_category );
		$viewall       = esc_html__( 'All Projects', 'clerina' );

		printf( "<div class='text-center categories-filter portfolio-cats-filter' id='portfolio-cats-filters'>
						<div id='filters' class='button-group'><span class='button active' data-filter='*'>%s</span>%s</div></div>",
			$viewall, $list_category );
	}
}
add_action( 'clerina_portfolio_before_content', 'clerina_portfolio_isotope' );

/**
 * Portfolio class
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_class' ) ) {
	function clerina_portfolio_class() {
		$class = 'col-xs-6 col-sm-4 element-item portfolio-wrapper portfolio-item';
		if ( clerina_get_option( 'portfolio_layout' ) == 'full-width' ) {
			$class = 'col-xs-6 col-sm-4 col-md-3 element-item portfolio-wrapper portfolio-item';
		}
		echo esc_attr($class);
	}
}
/**
 * Portfolio class
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_thumbnail' ) ) {
	function clerina_portfolio_thumbnail() {

		if ( clerina_get_option( 'portfolio_layout' ) == 'full-width' ) {
			the_post_thumbnail( 'clerina-portfolio-full-width' );
		} else {
			the_post_thumbnail( 'clerina-portfolio-grid' );
		}

	}
}
/**
 * Portfolio class
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_meta' ) ) {
	function clerina_portfolio_meta() {

		if ( clerina_get_option( 'portfolio_layout' ) == 'grid' ) {
			?>
			<div class="entry-meta">
				<?php clerina_portfolio_category(); ?>
			</div>
			<?php
		}
	}
}
/**
 * Portfolio class
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_portfolio_hover' ) ) {
	function clerina_portfolio_hover() {

		if ( clerina_get_option( 'portfolio_layout' ) == 'grid' ) {
			?>
			<a href="<?php the_permalink(); ?>" class="hover">
				<span class="svg-icon plus-symbol">&#x4c;</span>
			</a>
			<?php
		} else {
			?>
			<div class="hover">
				<div class="inner">
					<a href="<?php the_permalink(); ?>" class="entry-title"><h3 class="title"><?php the_title(); ?></h3>
					</a>
					<a href="<?php the_permalink(); ?>" class="view-more"><span class="plus-symbol"><?php echo esc_html__( 'View Project', 'clerina' ); ?></span></a>
				</div>
			</div>
			<?php
		}
	}
}
/**
 * Check is portfolio
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_pagination' ) ) {
	function clerina_pagination() {
		$post_type = '';
		if(clerina_is_service()){
			$post_type = 'service';
		}
		if(clerina_is_portfolio()){
			$post_type = 'portfolio';
		}
		if(clerina_is_testimonial()){
			$post_type = 'testimonial';
		}
		$nav_class = 'pag-1';
        if ( clerina_get_option( $post_type.'_pagination_style' ) == '2' ) {
            $show_more = esc_html__( 'Load more', 'clerina' );
            $next           = "<span>$show_more</span>";
            $prev           = "";
	        $nav_class = 'pag-2';
        } else {
            $next           = "<span class='fa fa-angle-right'></span>";
            $prev           = "<span class='fa fa-angle-left'></span>";
        }

        echo sprintf('<div class="col-xs-12 numeric-navigation text-center %s">', esc_attr( $nav_class ));
        echo get_the_posts_pagination( array(
            'prev_text' => $prev,
            'next_text' => $next,
        ) );
		echo '</div>';
	}
}
/**
 * Portfolio Featured Image
 *
 * @since  1.0
 */
if ( ! function_exists( 'portfolio_single_title' ) ) {
	function portfolio_single_title() {

		if ( is_singular( 'portfolio' ) ) {
			return;
		}

		if ( get_post_meta( get_the_ID(), 'hide_title', true ) == '0' ):
			echo '<div class="col-xs-12 content-title">' . the_title( '<h3 class="entry-title">', '</h3>' ) . '</div>';
		endif;
	}
}

/**
 * Display navigation to next/previous post when applicable.
 *
 * @since 1.0
 * @return void
 */
function clerina_portfolio_nav() {
	if ( clerina_get_option( 'portfolio_single_nav' ) == true ) {
		// Don't print empty markup if there's nowhere to navigate.
		$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
		$next     = get_adjacent_post( false, '', false );

		if ( ! $next && ! $previous ) {
			return;
		}
		?>
		<nav class="navigation post-navigation portfolio-navigation" role="navigation">
			<div class="nav-links">
				<?php
				previous_post_link( '<div class="nav-previous">%link</div>', _x( '<span class="lnr icon-arrow-left"></span><span class="meta-nav">' . esc_html__( "PREV", 'clerina' ) . '</span>', 'Previous post link', 'clerina' ) );
				if ( clerina_get_option( 'portfolio_single_project' ) ) {
					$link = clerina_get_option( 'portfolio_single_project' );
					$text = esc_html__( 'All Project', 'clerina' );
					echo sprintf( "<div class='nav-project'><a href='%s'>%s</a></div>", esc_url($link), $text );
				}
				next_post_link( '<div class="nav-next">%link</div>', _x( '<span class="meta-nav">' . esc_html__( "NEXT", 'clerina' ) . '</span><span class="lnr icon-arrow-right"></span>', 'Next post link', 'clerina' ) );
				?>
			</div><!-- .nav-links -->
		</nav><!-- .navigation -->
		<?php
	}
}

/**
 * Service section title
 *
 * @since  1.0
 */
if ( ! function_exists( 'service_section_title' ) ) {
	function service_section_title() {
		$title = $descr = '';
		if ( clerina_get_option( 'service_section_title' ) ) {
			$title = clerina_get_option( 'service_section_title' );
		}
		if ( clerina_get_option( 'service_layout_description' ) ) {
			$descr = clerina_get_option( 'service_layout_description' );
		}
		if ( $title != '' || $descr != '' ) {
			?>
			<div class="service-section-title">
				<div class="title">
					<h3><?php echo wp_kses_post($title); ?></h3>
				</div>
				<div class="descr">
					<p><?php echo wp_kses_post($descr); ?></p>
				</div>
			</div>
			<?php
		}
	}
}

add_action( 'clerina_service_before_content', 'service_section_title' );

/**
 * Service description
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_service_descr' ) ) {
	function clerina_service_descr() {
		$desc      = get_the_excerpt();
		$number_ex = 20;
		if ( clerina_get_option( 'service_long_excerpt' ) ) {
			$number_ex = clerina_get_option( 'service_long_excerpt' );
		}
		if ( ! empty( $desc ) ) {
			?>
			<div class="descr text-center">
				<?php
				echo clerina_content_limit( $desc, $number_ex, false );
				?>
			</div>
			<?php
		}
	}
}
/**
 * Service
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_service_bar' ) ) {
	function clerina_service_bar() {
		if(clerina_get_option('service_sidebar') == '1'){
			get_sidebar();
		}
	}
}
/**
 * Service
 *
 * @since  1.0
 */
if ( ! function_exists( 'class_service' ) ) {
	function class_service() {
		if(clerina_get_option('service_sidebar') == '1'){
			echo "col-xs-12 col-sm-9 bar-right";
		}else {
			echo "col-xs-12 col-sm-12";
		}
	}
}
/**
 * Service appointment
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_service_appointment' ) ) {
	function clerina_service_appointment() {
		if ( clerina_get_option( 'service_appointment' ) == '1' ) {
			$background = '';
			$id    = clerina_get_option( 'service_contact_id' );
			$bg    = clerina_get_option( 'service_contact_background' );
			$title = clerina_get_option( 'service_contact_title' );
			if ( $bg ) : $background = "style='background-image:url($bg)'";endif;
			?>

			<div class="clerina-appointment">
				<div class="clerina-fullwidth" <?php echo wp_kses_post($background); ?>>
					<div class="container">
                        <div class="col-xs-12 col-md-6 cl-contact-form-7 appointment">
                            <?php
                            if ( $title ) {
                              echo sprintf('<h3 class="title">%s</h3>', esc_html($title));
                            }

                            if ( $id ) {
	                            echo do_shortcode( sprintf( '[contact-form-7 id="%s"]', $id ) );
                            }
                            ?>
                        </div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action( 'clerina_service_after_content', 'clerina_service_appointment' );

/**
 * Blog description
 *
 * @since  1.0
 */
if ( ! function_exists( 'clerina_blog_descr' ) ) {
	function clerina_blog_descr() {
		$desc      = get_the_excerpt();
		$number_ex = 20;
		if ( clerina_get_option( 'blog_excerpt_length' ) ) {
			$number_ex = clerina_get_option( 'blog_excerpt_length' );
		}
		if ( ! empty( $desc ) ) {
			?>
            <div class="descr">
				<?php
				echo clerina_content_limit( $desc, $number_ex, false );
				?>
            </div>
			<?php
		}
	}
}

/**
 * show taxonomy filter
 *
 * @return string
 */

if ( ! function_exists( 'clerina_taxs_list' ) ) :
	function clerina_taxs_list( $taxonomy = 'category' ) {
		$term_id   = 0;
		$cats      = $output = '';
		$found     = false;
		$number    = clerina_get_option( 'blog_categories_numbers' );
		$cats_slug = clerina_get_option( 'blog_categories' );
		$id        = 'clerina-taxs-list';

		if ( is_tax( $taxonomy ) || is_category() ) {

			$queried_object = get_queried_object();
			if ( $queried_object ) {
				$term_id = $queried_object->term_id;
			}
		}

		if ( $cats_slug ) {
			$cats_slug = explode( ',', $cats_slug );

			foreach ( $cats_slug as $slug ) {
				$cat = get_term_by( 'slug', $slug, $taxonomy );

				if ( $cat ) {
					$cat_selected = '';
					if ( $cat->term_id == $term_id ) {
						$cat_selected = 'selected';
						$found        = true;
					}

					$cats .= sprintf( '<li><a href="%s" class="%s">%s</a></li>', esc_url( get_term_link( $cat ) ), esc_attr( $cat_selected ), esc_html( $cat->name ) );
				}
			}

		} else {
			$args = array(
				'number'  => $number,
				'orderby' => 'count',
				'order'   => 'DESC',

			);

			$categories = get_terms( $taxonomy, $args );
			if ( ! is_wp_error( $categories ) && $categories ) {
				foreach ( $categories as $cat ) {
					$cat_selected = '';
					if ( $cat->term_id == $term_id ) {
						$cat_selected = 'selected';
						$found        = true;
					}

					$cats .= sprintf( '<li><a href="%s" class="%s">%s</a></li>', esc_url( get_term_link( $cat ) ), esc_attr( $cat_selected ), esc_html( $cat->name ) );
				}
			}
		}

		$cat_selected = $found ? '' : 'selected';

		if ( $cats ) {
			$url = get_page_link( get_option( 'page_for_posts' ) );
			if ( 'posts' == get_option( 'show_on_front' ) ) {
				$url = home_url();
			}

			$output = sprintf(
				'<ul>
					<li><a href="%s" class="%s">%s</a></li>
					 %s
				</ul>',
				esc_url( $url ),
				esc_attr( $cat_selected ),
				esc_html__( 'All', 'clerina' ),
				$cats
			);
		}

		if ( $output ) {
			$output = apply_filters( 'clerina_tax_html', $output );

			printf( '<div id="%s" class="clerina-taxs-list">%s</div>', esc_attr( $id ), $output );
		}
    }
endif;

/**
 * Check is blog
 *
 * @since  1.0
 */

if ( ! function_exists( 'clerina_is_blog' ) ) :
	function clerina_is_blog() {
		if ( ( is_archive() || is_author() || is_category() || is_home() || is_tag() ) && 'post' == get_post_type() ) {
			return true;
		}

		return false;
	}

endif;

/**
 * Check is catalog
 *
 * @return bool
 */
if ( ! function_exists( 'clerina_is_catalog' ) ) :
	function clerina_is_catalog() {

		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return true;
		}

		return false;
	}
endif;

/**
 * Load image
 *
 * @since  1.0
 */
function clerina_load_image( $img_id, $size ) {
	$image_thumb = '';
	if ( function_exists( 'wpb_getImageBySize' ) ) {
		$image       = wpb_getImageBySize(
			array(
				'attach_id'  => $img_id,
				'thumb_size' => $size,
			)
		);
		$image_thumb = $image['thumbnail'];
	} else {
		$image = wp_get_attachment_image_src( $img_id, $size );
		if ( $image ) {
			$img_url     = $image[0];
			$img_size    = $size;
			$image_thumb = "<img alt='$img_size'  src='$img_url'/>";
		}
	}

	return $image_thumb;
}

/**
 * Check is testimonial
 *
 * @since  1.0
 */

if ( ! function_exists( 'clerina_is_testimonial' ) ) :
	function clerina_is_testimonial() {
		if ( is_post_type_archive( 'testimonial' ) || is_tax( 'testimonial_category' ) ) {
			return true;
		}

		return false;
	}
endif;

/**
 * Display entry job testimonial
 *
 * @since  1.0
 */

if ( ! function_exists( 'clerina_entry_rating' ) ) :
	function clerina_entry_rating( $rating = '' ) {
		$rating = intval( $rating );

		$score     = min( 10, abs( $rating ) );
		$full_star = $score / 2;
		$half_star = $score % 2;
		$stars     = array();

		for ( $i = 1; $i <= 5; $i ++ ) {
			if ( $i <= $full_star ) {
				$stars[] = '<i class="fa fa-star"></i>';
			} elseif ( $half_star ) {
				$stars[]   = '<i class="fa fa-star-half-o"></i>';
				$half_star = false;
			} else {
				$stars[] = '<i class="fa fa-star-o"></i>';
			}
		}

		return sprintf('<div class="list-star">%s</div>', join( "\n", $stars ));
	}
endif;

/**
 * Display numeric pagination
 *
 * @since 1.0
 * @return void
 */
function clerina_numeric_pagination() {
	global $wp_query;

	if( $wp_query->max_num_pages < 2 ) {
		return;
	}

	$portfolio_nav = clerina_get_option( 'portfolio_pagination_style' );
	$type_nav  = clerina_get_option( 'blog_nav_type' );
	$view_more = apply_filters( 'clerina_portfolio_view_more_text', esc_html__( 'LOAD MORE', 'clerina' ) );
	$css = '';

	$next_html = sprintf(
		'<span id="clerina-portfolio-previous-ajax" class="nav-previous-ajax">
			<span class="nav-text">%s</span>
			<span class="loading-icon">
				<span class="bubble">
					<span class="dot"></span>
				</span>
				<span class="bubble">
					<span class="dot"></span>
				</span>
				<span class="bubble">
					<span class="dot"></span>
				</span>
			</span>
		</span>',
		$view_more
	);

	if ( clerina_is_blog() && $type_nav == 'ajax' ) {
		$css .= ' blog-nav-ajax';
	} elseif ( clerina_is_portfolio() && $portfolio_nav == 'ajax' ) {
		$css .= ' portfolio-nav-ajax';
	} elseif ( clerina_is_testimonial() ) {
		$css .= ' testimonial-nav-ajax';
	}

	?>
    <div class="col-xs-12 pag-2 numeric-navigation text-center">
        <nav class="navigation paging-navigation <?php echo esc_attr( $css ); ?>">
            <?php
            $big = 999999999;
            $args = array(
                'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'total'     => $wp_query->max_num_pages,
                'current'   => max( 1, get_query_var( 'paged' ) ),
                'prev_text' => '<i class="fa fa-angle-left" aria-hidden="true"></i>',
                'next_text' => '<i class="fa fa-angle-right" aria-hidden="true"></i>',
                'type'      => 'plain',
            );

            if (
                ( clerina_is_blog() && $type_nav == 'ajax' ) ||
                ( clerina_is_portfolio() && $portfolio_nav == 'ajax' ) ||
                ( clerina_is_testimonial() )
            ) {
                $args['prev_text'] = '';
                $args['next_text'] = $next_html;
            }

            echo paginate_links( $args );
            ?>
        </nav>
    </div>
	<?php
}

if ( ! function_exists( 'clerina_get_page_header_image' ) ) :

	/**
	 * Get page header image URL
	 *
	 * @return string
	 */
	function clerina_get_page_header_image() {
		if ( clerina_get_post_meta( 'hide_page_header' ) ) {
			return false;
		}

		if ( is_404() || is_page_template( 'template-coming-soon-page.php' ) ) {
			return false;
		}

		if ( is_page_template( array( 'template-homepage.php' ) ) ) {
			return false;
		}
		$elements = array( 'title', 'breadcrumb' );

		$page_header = array(
			'bg_image'   => '',
			'text_color' => 'dark',
			'parallax'   => false,
			'elements'   => $elements,
		);


		if ( clerina_is_portfolio() ) {
			if ( ! intval( clerina_get_option( 'portfolio_page_header' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'portfolio_page_header_els' );
			$page_header['bg_image']   = clerina_get_option( 'portfolio_page_header_bg' );
			$page_header['text_color'] = clerina_get_option( 'portfolio_page_header_text_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'portfolio_page_header_parallax' ) );

		} elseif ( clerina_is_service() ) {
			if ( ! intval( clerina_get_option( 'service_page_header' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'service_page_header_els' );
			$page_header['bg_image']   = clerina_get_option( 'service_page_header_bg' );
			$page_header['text_color'] = clerina_get_option( 'service_page_header_text_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'service_page_header_parallax' ) );


		} elseif ( clerina_is_testimonial() ) {
			if ( ! intval( clerina_get_option( 'testimonial_page_header' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'testimonial_page_header_els' );
			$page_header['bg_image']   = clerina_get_option( 'testimonial_page_header_bg' );
			$page_header['text_color'] = clerina_get_option( 'testimonial_page_header_text_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'testimonial_page_header_parallax' ) );


		} elseif ( clerina_is_catalog() ) {
			if ( ! intval( clerina_get_option( 'page_header_shop' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'page_header_shop_els' );
			$page_header['bg_image']   = clerina_get_option( 'page_header_shop_bg' );
			$page_header['text_color'] = clerina_get_option( 'page_header_shop_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'page_header_shop_parallax' ) );

		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			if ( ! intval( clerina_get_option( 'single_product_page_header' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'single_product_page_header_els' );
			$page_header['bg_image']   = clerina_get_option( 'single_product_page_header_bg' );
			$page_header['text_color'] = clerina_get_option( 'single_product_page_header_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'service_page_header_parallax' ) );

		} elseif ( is_singular( 'post' )
		           || is_singular( 'portfolio' )
		           || is_singular( 'service' )
		           || is_singular( 'testimonial' )
		           || is_page()
		           || is_page_template( 'template-fullwidth.php' )
		) {
			if ( is_singular( 'post' ) ) {
				if ( ! intval( clerina_get_option( 'single_page_header' ) ) ) {
					return false;
				}

				$elements   = clerina_get_option( 'single_page_header_els' );
				$bg_image   = clerina_get_option( 'single_page_header_bg' );
				$text_color = clerina_get_option( 'single_page_header_text_color' );
				$parallax   = intval( clerina_get_option( 'single_page_header_parallax' ) );

			} elseif ( is_singular( 'portfolio' ) ) {
				if ( ! intval( clerina_get_option( 'single_portfolio_page_header' ) ) ) {
					return false;
				}

				$elements   = clerina_get_option( 'single_portfolio_page_header_els' );
				$bg_image   = clerina_get_option( 'single_portfolio_page_header_bg' );
				$text_color = clerina_get_option( 'single_portfolio_page_header_text_color' );
				$parallax   = intval( clerina_get_option( 'single_portfolio_page_header_parallax' ) );


			} elseif ( is_singular( 'service' ) ) {
				if ( ! intval( clerina_get_option( 'single_service_page_header' ) ) ) {
					return false;
				}

				$elements   = clerina_get_option( 'single_service_page_header_els' );
				$bg_image   = clerina_get_option( 'single_service_page_header_bg' );
				$parallax   = intval( clerina_get_option( 'single_service_page_header_parallax' ) );
				$text_color = clerina_get_option( 'single_service_page_header_text_color' );

			} elseif ( is_singular( 'testimonial' ) ) {
				if ( ! intval( clerina_get_option( 'single_testimonial_page_header' ) ) ) {
					return false;
				}

				$elements   = clerina_get_option( 'single_testimonial_page_header_els' );
				$bg_image   = clerina_get_option( 'single_testimonial_page_header_bg' );
				$parallax   = intval( clerina_get_option( 'single_testimonial_page_header_parallax' ) );
				$text_color = clerina_get_option( 'single_testimonial_page_header_text_color' );

			} elseif ( is_page() || is_page_template( 'template-fullwidth.php' ) ) {
				if ( ! intval( clerina_get_option( 'page_page_header' ) ) ) {
					return false;
				}

				$elements   = clerina_get_option( 'page_page_header_els' );
				$bg_image   = clerina_get_option( 'page_page_header_bg' );
				$text_color = clerina_get_option( 'page_page_header_text_color' );
				$parallax   = intval( clerina_get_option( 'page_page_header_parallax' ) );
			}

			$page_header['bg_image']   = $bg_image;
			$page_header['text_color'] = $text_color;
			$page_header['parallax']   = $parallax;

		} else {
			if ( ! intval( clerina_get_option( 'blog_page_header' ) ) ) {
				return false;
			}

			$elements                  = clerina_get_option( 'blog_page_header_els' );
			$page_header['bg_image']   = clerina_get_option( 'blog_page_header_bg' );
			$page_header['text_color'] = clerina_get_option( 'blog_page_header_text_color' );
			$page_header['parallax']   = intval( clerina_get_option( 'blog_page_header_parallax' ) );
		}

		if ( clerina_get_post_meta( 'hide_title' ) ) {
			$key = array_search( 'title', $elements );
			if ( $key !== false ) {
				unset( $elements[$key] );
			}
		}

		if ( clerina_get_post_meta( 'hide_breadcrumb' ) ) {
			$key = array_search( 'breadcrumb', $elements );
			if ( $key !== false ) {
				unset( $elements[$key] );
			}
		}

		if ( clerina_get_post_meta( 'hide_parallax' ) ) {
			unset( $page_header['parallax'] );
		}

		if ( clerina_get_post_meta( 'page_bg' ) ) {
			$meta_page_bg            = clerina_get_post_meta( 'page_bg' );
			$page_header['bg_image'] = wp_get_attachment_url( $meta_page_bg );
		}

		if ( clerina_get_post_meta( 'text_color' ) ) {
			$text_color                = clerina_get_post_meta( 'text_color' );
			$page_header['text_color'] = $text_color;
		}

		$page_header['elements'] = $elements;

		return $page_header;
	}
endif;

/**
 * Get breadcrumbs
 *
 * @since  1.0.0
 *
 * @return string
 */

if ( ! function_exists( 'clerina_get_breadcrumbs' ) ) :
	function clerina_get_breadcrumbs() {

		ob_start();
		?>
        <nav class="breadcrumbs">
			<?php
			clerina_breadcrumbs(
				array(
					'before'   => '',
					'taxonomy' => function_exists( 'is_woocommerce' ) && is_woocommerce() ? 'product_cat' : 'category',
				)
			);
			?>
        </nav>
		<?php
		echo ob_get_clean();
	}

endif;

/**
 * Get blog meta
 *
 * @since  1.0
 *
 * @return string
 */
if ( ! function_exists( 'clerina_get_post_meta' ) ) :
	function  clerina_get_post_meta( $meta ) {

		$post_id = get_the_ID();
		if ( is_home() && ! is_front_page() ) {
			$post_id = get_queried_object_id();
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$post_id = intval( get_option( 'woocommerce_shop_page_id' ) );
		}

		return get_post_meta( $post_id, $meta, true );

	}
endif;

/**
 * Get socials
 *
 * @since  1.0.0
 *
 *
 * @return string
 */
function clerina_get_socials() {
	$socials = array(
		'facebook'   => esc_html__( 'Facebook', 'clerina' ),
		'twitter'    => esc_html__( 'Twitter', 'clerina' ),
		'google'     => esc_html__( 'Google', 'clerina' ),
		'tumblr'     => esc_html__( 'Tumblr', 'clerina' ),
		'flickr'     => esc_html__( 'Flickr', 'clerina' ),
		'vimeo'      => esc_html__( 'Vimeo', 'clerina' ),
		'youtube'    => esc_html__( 'Youtube', 'clerina' ),
		'linkedin'   => esc_html__( 'LinkedIn', 'clerina' ),
		'pinterest'  => esc_html__( 'Pinterest', 'clerina' ),
		'dribbble'   => esc_html__( 'Dribbble', 'clerina' ),
		'spotify'    => esc_html__( 'Spotify', 'clerina' ),
		'instagram'  => esc_html__( 'Instagram', 'clerina' ),
		'tumbleupon' => esc_html__( 'Tumbleupon', 'clerina' ),
		'wordpress'  => esc_html__( 'WordPress', 'clerina' ),
		'rss'        => esc_html__( 'Rss', 'clerina' ),
		'deviantart' => esc_html__( 'Deviantart', 'clerina' ),
		'share'      => esc_html__( 'Share', 'clerina' ),
		'skype'      => esc_html__( 'Skype', 'clerina' ),
		'behance'    => esc_html__( 'Behance', 'clerina' ),
		'apple'      => esc_html__( 'Apple', 'clerina' ),
		'yelp'       => esc_html__( 'Yelp', 'clerina' ),
	);

	return apply_filters( 'clerina_header_socials', $socials );
}

if ( ! function_exists( 'clerina_menu_icon' ) ) :
	/**
	 * Get menu icon
	 */
	function clerina_menu_icon() {
		printf(
			'<span id="clerina-navbar-toggle" class="navbar-icon">
					<i class="icon-menu"></i>
				</span>'
		);
	}
endif;