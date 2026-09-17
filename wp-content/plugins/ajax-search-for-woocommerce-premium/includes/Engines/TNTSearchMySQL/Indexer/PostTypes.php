<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support post types in indexer
 */
class PostTypes {
	private $postTypes = null;

	public function init() {
		add_filter( 'dgwt/wcas/labels', [ $this, 'setPostTypeLabels' ], 5 );
		add_filter( 'dgwt/wcas/labels', [ $this, 'fixPostTypeLabels' ], PHP_INT_MAX - 5 );

		add_filter( 'dgwt/wcas/allowed_post_types', [ $this, 'addAllowedPostTypes' ], 10, 2 );

		add_filter( 'dgwt/wcas/settings/section=autocomplete', [ $this, 'addPostTypesToAutocompleteSettings' ] );

		add_filter( 'dgwt/wcas/indexer/readable/post_types_with_images', [ $this, 'postTypesWithImages' ], 5 );
	}

	/**
	 * Get available post types with its details
	 *
	 * @return array
	 */
	public function getPostTypes() {
		if ( is_array( $this->postTypes ) ) {
			return $this->postTypes;
		}

		$this->registerAllPostTypes();

		return $this->postTypes;
	}

	/**
	 * Get a list of registered post types slugs
	 *
	 * @return array
	 */
	public function getPostTypesSlugs() {
		$postTypes = $this->getPostTypes();

		if ( empty( $postTypes ) ) {
			return [];
		}

		return wp_list_pluck( $postTypes, 'post_type' );
	}

	/**
	 * Get details of selected post type
	 *
	 * @param string $postType Post type slug
	 */
	public function getPostTypeDetails( $postType ) {
		$postTypes = $this->getPostTypes();

		if ( empty( $postType ) ) {
			return [];
		}

		$index = array_search( $postType, array_column( $postTypes, 'post_type' ) );

		return $index === false ? [] : $postTypes[ $index ];
	}

	/**
	 * Get active post types for selected context
	 *
	 * @param string|array $contexts Search context. Accepts 'search_direct', 'image_support' and 'show_images' (or mix of them).
	 */
	public function getActivePostTypes( $contexts ) {
		if ( is_string( $contexts ) ) {
			$contexts = [ $contexts ];
		}

		$result = [];

		foreach ( $contexts as $context ) {
			$postTypes = array_filter(
				$this->getPostTypes(),
				function ( $postType ) use ( $context ) {
					return isset( $postType[ $context ] ) && $postType[ $context ];
				}
			);

			$result = array_merge( $result, wp_list_pluck( $postTypes, 'post_type' ) );
		}

		return array_unique( $result );
	}

	/**
	 * Add post types labels
	 *
	 * @param array $labels Labels used at frontend
	 *
	 * @return array
	 */
	public function setPostTypeLabels( $labels ) {
		$postTypes = $this->getPostTypes();

		if ( empty( $postTypes ) ) {
			return $labels;
		}

		foreach ( $postTypes as $postType ) {
			if ( isset( $postType['labels']['name'] ) ) {
				$labels[ 'post_type_' . $postType['post_type'] . '_plu' ] = $postType['labels']['name'];
			}
			if ( isset( $postType['labels']['singular_name'] ) ) {
				$labels[ 'post_type_' . $postType['post_type'] ] = $postType['labels']['singular_name'];
			}
		}

		return $labels;
	}

	/**
	 * Backward compatibility for labels
	 *
	 * @param array $labels Labels used at frontend
	 *
	 * @return array
	 */
	public function fixPostTypeLabels( $labels ) {
		// Post. Old: 'post', 'post_plu'.
		if ( isset( $labels['post'] ) ) {
			$labels['post_type_post'] = $labels['post'];
			unset( $labels['post'] );
		}
		if ( isset( $labels['post_plu'] ) ) {
			$labels['post_type_post_plu'] = $labels['post_plu'];
			unset( $labels['post_plu'] );
		}

		// Product tag. Old: 'page', 'page_plu'.
		if ( isset( $labels['page'] ) ) {
			$labels['post_type_page'] = $labels['page'];
			unset( $labels['page'] );
		}
		if ( isset( $labels['page_plu'] ) ) {
			$labels['post_type_page_plu'] = $labels['page_plu'];
			unset( $labels['page_plu'] );
		}

		return $labels;
	}


	/**
	 * Populate list of post types that has image support
	 *
	 * @param array $postTypes
	 *
	 * @return array
	 */
	public function postTypesWithImages( $postTypes ) {
		return array_merge( $postTypes, $this->getActivePostTypes( 'image_support' ) );
	}

	public function addAllowedPostTypes( $postTypes, $filter ) {
		if ( empty( $filter ) || $filter === 'no-products' ) {
			$postTypes = array_merge( $postTypes, $this->getActivePostTypes( 'search_direct' ) );
		}

		return $postTypes;
	}

	/**
	 * Add autocomplete options for post types
	 *
	 * @param array $settingsScope
	 *
	 * @return array
	 */
	public function addPostTypesToAutocompleteSettings( $settingsScope ) {
		$basePosition = 1900;

		$skippedPostTypes = [ 'post', 'page' ];

		foreach ( $this->getPostTypes() as $postType ) {
			if ( in_array( $postType['post_type'], $skippedPostTypes ) ) {
				continue;
			}

			$settingsScope[ $basePosition ] = [
				'name'       => 'show_post_type_' . $postType['post_type'],
				'label'      => sprintf( __( 'Show %s', 'ajax-search-for-woocommerce' ), mb_strtolower( $postType['labels']['name'] ) ),
				'class'      => 'js-dgwt-wcas-adv-settings dgwt-wcas-premium-only' . ( $postType['image_support'] ? ' js-dgwt-wcas-options-toggle-sibling' : '' ),
				'type'       => 'checkbox',
				'default'    => 'off',
				'input_data' => 'data-option-trigger="show_matching_post_type" data-post-type="' . $postType['post_type'] . '"',
			];

			$basePosition += 2;

			if ( $postType['image_support'] ) {
				$settingsScope[ $basePosition ] = [
					'name'       => 'show_post_type_' . $postType['post_type'] . '_images',
					'label'      => __( 'show images', 'ajax-search-for-woocommerce' ),
					'class'      => 'js-dgwt-wcas-adv-settings dgwt-wcas-premium-only',
					'type'       => 'checkbox',
					'default'    => 'off',
					'desc'       => __( 'show images', 'ajax-search-for-woocommerce' ),
					'move_dest'  => 'show_post_type_' . $postType['post_type'],
					'input_data' => 'data-option-trigger="show_post_type_images" data-post-type="' . $postType['post_type'] . '"',
				];

				$basePosition += 2;
			}
		}

		return $settingsScope;
	}

	/**
	 * Register all post types
	 *
	 * @return void
	 */
	private function registerAllPostTypes() {
		$this->postTypes = [];

		$this->registerPostType(
			[
				'post_type' => 'post',
				'labels'    => [
					'name'          => __( 'Posts' ),
					'singular_name' => __( 'Post' ),
				],
			]
		);

		$this->registerPostType(
			[
				'post_type' => 'page',
				'labels'    => [
					'name'          => __( 'Pages' ),
					'singular_name' => __( 'Page' ),
				],
			]
		);

		$postTypes = apply_filters( 'dgwt/wcas/indexer/post_types', [] );

		if ( is_array( $postTypes ) && ! empty( $postTypes ) ) {
			foreach ( $postTypes as $postType ) {
				$this->registerPostType( $postType );
			}
		}
	}

	/**
	 * Register post type
	 *
	 * @param string|array $postType
	 */
	private function registerPostType( $postType ) {
		// Prepare default data if post type is passed just as string.
		if ( is_string( $postType ) && post_type_exists( $postType ) ) {
			$postTypeObj = get_post_type_object( $postType );
			$postType    = [
				'post_type'     => $postType,
				'labels'        => [
					'name'          => $postTypeObj->labels->name,
					'singular_name' => $postTypeObj->labels->singular_name,
				],
				'image_support' => false,
			];
		}

		if ( ! is_array( $postType ) ) {
			return;
		}

		$postTypeData = [
			'post_type'     => '',
			'labels'        => [
				'name'          => '',
				'singular_name' => '',
			],
			'image_support' => false,
			'search_direct' => false,
			'show_images'   => false,
		];

		$postType = apply_filters( 'dgwt/wcas/indexer/post_types/register_post_type', $postType );

		// Post type.
		if ( empty( $postType['post_type'] ) || ! post_type_exists( $postType['post_type'] ) ) {
			return;
		}
		$postTypeData['post_type'] = $postType['post_type'];

		// Name.
		if ( isset( $postType['labels']['name'] ) && is_string( $postType['labels']['name'] ) ) {
			$postTypeData['labels']['name'] = $postType['labels']['name'];
		} else {
			$postTypeObj                    = get_post_type_object( $postTypeData['post_type'] );
			$postTypeData['labels']['name'] = $postTypeObj->labels->name;
		}

		// Singular name.
		if ( isset( $postType['labels']['singular_name'] ) && is_string( $postType['labels']['singular_name'] ) ) {
			$postTypeData['labels']['singular_name'] = $postType['labels']['singular_name'];
		} else {
			$postTypeObj                             = get_post_type_object( $postTypeData['post_type'] );
			$postTypeData['labels']['singular_name'] = $postTypeObj->labels->singular_name;
		}

		// Image support
		$postTypeData['image_support'] = post_type_supports( $postTypeData['post_type'], 'thumbnail' );

		if ( in_array( $postType['post_type'], [ 'post', 'page' ] ) ) {
			$postTypeData['search_direct'] = DGWT_WCAS()->settings->getOption( 'show_matching_' . $postType['post_type'] . 's' ) === 'on';
		} else {
			$postTypeData['search_direct'] = DGWT_WCAS()->settings->getOption( 'show_post_type_' . $postType['post_type'] ) === 'on';
		}
		$postTypeData['show_images'] = DGWT_WCAS()->settings->getOption( 'show_post_type_' . $postType['post_type'] . '_images' ) === 'on';

		// Ensure we have proper container for post types.
		if ( $this->postTypes === null ) {
			$this->postTypes = [];
		}

		// Prevent to register same post type twice.
		foreach ( $this->postTypes as $registeredPostType ) {
			if ( $registeredPostType['post_type'] === $postTypeData['post_type'] ) {
				return;
			}
		}

		$this->postTypes[] = $postTypeData;
	}
}
