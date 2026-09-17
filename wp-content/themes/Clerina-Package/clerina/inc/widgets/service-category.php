<?php

class Clerina_Service_Category_Widget extends WP_Widget {
	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor
	 *
	 * @return Clerina_Service_Category_Widget
	 */
	function __construct() {
		$this->defaults = array(
			'title'     => '',
			'limit'     => 6,
			'show_cat'  => 1,
			'cat_title' => '',
		);

		parent::__construct(
			'services-category-widget',
			esc_html__( 'Clerina - Services Category', 'clerina' ),
			array(
				'classname'   => 'services-category-widget widget_categories',
				'description' => esc_html__( 'Advanced services category widget.', 'clerina' )
			)
		);
	}

	/**
	 * Display widget
	 *
	 * @param array $args Sidebar configuration
	 * @param array $instance Widget settings
	 *
	 * @return void
	 */
	function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		extract( $args );

		$category = get_the_terms( get_the_ID(), 'service_category' );
		$slug     = '';

		if ( ! empty( $category ) && $instance['show_cat'] == 1 ) {
			$slug      = $category[0]->slug;
			$term_link = get_term_link( $slug, 'service_category' );
		}

		echo wp_kses_post( $before_widget );

		if ( $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) ) {
			echo wp_kses_post( $before_title ) . $title . wp_kses_post( $after_title );
		}


		global $wp_query;
		$terms       = get_terms( 'service_category' );
		$curent_term = wp_get_post_terms( get_the_ID(), 'service_category' );

		$count = count( $terms );
		if ( $count > 0 ) {
			echo '<ul class="service-category">';
			foreach ( $terms as $term ) {
				$class = '';
				if ( ! is_wp_error( $curent_term ) && ! empty( $curent_term ) ) {
					if ( $curent_term[0]->term_id == $term->term_id ) {
						$class = 'current-menu-item';
					}
				}
				echo '<li class="menu-item ' . $class . '"><a href="' . esc_url( get_term_link( $term ) ) . '">' . $term->name . '</a></li>';
			}
			echo '</ul>';
		}

		echo wp_kses_post( $after_widget );

	}

	/**
	 * Update widget
	 *
	 * @param array $new_instance New widget settings
	 * @param array $old_instance Old widget settings
	 *
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$new_instance['title']     = strip_tags( $new_instance['title'] );
		$new_instance['cat_title'] = strip_tags( $new_instance['cat_title'] );
		$new_instance['limit']     = intval( $new_instance['limit'] );
		$new_instance['show_cat']  = ! empty( $new_instance['show_cat'] );

		return $new_instance;
	}

	/**
	 * Display widget settings
	 *
	 * @param array $instance Widget settings
	 *
	 * @return void
	 */
	function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		?>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'clerina' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <p>
            <input id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="text" size="2"
                   value="<?php echo intval( $instance['limit'] ); ?>">
            <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number Of Posts', 'clerina' ); ?></label>
        </p>

        <p>
            <input id="<?php echo esc_attr( $this->get_field_id( 'show_cat' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_cat' ) ); ?>" type="checkbox"
                   value="1" <?php checked( $instance['show_cat'] ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_cat' ) ); ?>"><?php esc_html_e( 'Show Services are the same Categories', 'clerina' ); ?></label>
        </p>

		<?php
	}
}