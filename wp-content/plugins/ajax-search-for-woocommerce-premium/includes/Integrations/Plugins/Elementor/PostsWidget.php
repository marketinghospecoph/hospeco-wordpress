<?php

namespace DgoraWcas\Integrations\Plugins\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ElementorPro\Modules\Posts\Widgets\Posts;
use ElementorPro\Modules\Posts\Widgets\Posts_Base;
use ElementorPro\Modules\QueryControl\Module as Module_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FiboSearchWidget Class
 */
class PostsWidget extends Posts {
	public function get_name(): string {
		return 'fibosearch-posts';
	}

	public function get_title(): string {
		return esc_html__( 'FiboSearch Posts Search Results', 'ajax-search-for-woocommerce' );
	}

	public function get_categories(): array {
		return [ 'woocommerce-elements-archive' ];
	}

	public function get_keywords(): array {
		return [ 'fibo', 'search', 'fibosearch', 'post', 'page' ];
	}

	protected function get_html_wrapper_class() {
		/**
		 * Normally the CSS class is "elementor-widget-WIDGET_NAME", but for some reason image
		 * scaling doesn't work (with JS; as for the Posts widget) and a suffix has been added.
		 */
		return 'elementor-widget-' . $this->get_name() . '-results elementor-widget-posts';
	}

	public function get_custom_help_url(): string {
		return 'https://fibosearch.com/documentation/features/displaying-more-than-just-products-on-the-woocommerce-search-results-page/';
	}

	protected function register_controls() {
		parent::register_controls();

		$this->remove_control( 'section_pagination' );
		$this->remove_control( 'section_pagination_style' );

		$this->remove_control( 'fibosearch-posts_query_args' );
		$this->remove_control( 'fibosearch-posts_query_include' );
		$this->remove_control( 'fibosearch-posts_posts_ids' );
		$this->remove_control( 'fibosearch-posts_include_term_ids' );
		$this->remove_control( 'fibosearch-posts_related_taxonomies' );
		$this->remove_control( 'fibosearch-posts_include_authors' );
		$this->remove_control( 'fibosearch-posts_query_exclude' );
		$this->remove_control( 'fibosearch-posts_exclude_ids' );
		$this->remove_control( 'fibosearch-posts_offset' );
		$this->remove_control( 'fibosearch-posts_exclude_term_ids' );
		$this->remove_control( 'fibosearch-posts_exclude_authors' );
		$this->remove_control( 'fibosearch-posts_avoid_duplicates' );
		$this->remove_control( 'fibosearch-posts_include' );
		$this->remove_control( 'fibosearch-posts_exclude' );
		$this->remove_control( 'fibosearch-posts_select_date' );
		$this->remove_control( 'fibosearch-posts_select_date' );
		$this->remove_control( 'fibosearch-posts_date_before' );
		$this->remove_control( 'fibosearch-posts_date_afte' );
		$this->remove_control( 'fibosearch-posts_orderby' );
		$this->remove_control( 'fibosearch-posts_order' );
		$this->remove_control( 'fibosearch-posts_ignore_sticky_posts' );

		// Limit "Post type" options.
		$postType          = $this->get_controls( 'fibosearch-posts_post_type' );
		$searchInPostTypes = DGWT_WCAS()->tntsearchMySql->postTypes->getActivePostTypes( 'search_direct' );
		if ( isset( $postType['options'] ) ) {
			$postType['options'] = array_filter(
				$postType['options'],
				function ( $type ) use ( $searchInPostTypes ) {
					return in_array( $type, $searchInPostTypes, true );
				},
				ARRAY_FILTER_USE_KEY
			);
			$this->update_control( 'fibosearch-posts_post_type', $postType );
		}

		$this->start_injection(
			[
				'at' => 'before',
				'of' => '_skin',
			]
		);
		$this->add_control(
			'wc_notice_use_customizer',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => sprintf( __( 'This widget displays relevant posts or pages. Learn more in our <a target="_blank" href="%s">documentation</a>.', 'ajax-search-for-woocommerce' ), esc_url( $this->get_custom_help_url() ) ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'nothing_found_message',
			[
				'label'   => esc_html_x( 'Nothing Found Message', 'elementor-widget', 'ajax-search-for-woocommerce' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html_x( 'No search results for this post type.', 'elementor-widget', 'ajax-search-for-woocommerce' ),
			]
		);

		$this->end_injection();
	}

	/**
	 * @return void
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function render() {
		parent::render();
	}

	protected function register_skins() {
		$this->add_skin( new PostsWidgetsSkins\SkinClassic( $this ) );
		$this->add_skin( new PostsWidgetsSkins\SkinCards( $this ) );
		$this->add_skin( new PostsWidgetsSkins\SkinFullContent( $this ) );
	}

	public function query_posts() {
		$perPage  = $this->get_settings( $this->get_current_skin_id() . '_posts_per_page' );
		$postType = $this->get_settings( $this->get_name() . '_post_type' );
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$phrase  = isset( $_GET['s'] ) ? $_GET['s'] : ''; // FiboSearch takes care of the security of the phrase on its side.
		$results = DGWT_WCAS()->searchPosts(
			$phrase,
			[
				'post_type' => $postType,
				'per_page'  => $perPage,
				'fields'    => 'ids',
			]
		);

		$ids = empty( $results['results'] ) ? [ 0 ] : $results['results'];

		$query_args = [
			'posts_per_page' => $perPage,
			'paged'          => 1,
			'post__in'       => $ids,
			'orderby'        => 'post__in',
			'post_type'      => $postType,
		];

		/** @var Module_Query $elementor_query */
		$elementor_query = Module_Query::instance();
		$this->query     = $elementor_query->get_query( $this, $this->get_name(), $query_args, [] );
	}

	/**
	 * Override the type in widget wrapper so that the JS scripts work as for the Posts widget
	 *
	 * @return void
	 */
	protected function add_render_attributes() {
		parent::add_render_attributes();

		$settings = $this->get_settings();
		//phpcs:ignore  Generic.Strings.UnnecessaryStringConcat.Found
		$this->add_render_attribute( '_wrapper', 'data-widget_type', 'posts' . '.' . ( ! empty( $settings['_skin'] ) ? $settings['_skin'] : 'default' ), true );
	}

	/**
	 * Override the CSS configuration so that the file from the Posts widget is loaded
	 *
	 * @return array
	 */
	public function get_widget_css_config( $widget_name ) {
		return parent::get_widget_css_config( 'posts' );
	}
}
