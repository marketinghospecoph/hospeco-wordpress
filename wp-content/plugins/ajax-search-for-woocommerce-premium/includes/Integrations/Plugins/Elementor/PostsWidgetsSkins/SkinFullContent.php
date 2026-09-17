<?php

namespace DgoraWcas\Integrations\Plugins\Elementor\PostsWidgetsSkins;

use ElementorPro\Modules\Posts\Skins\Skin_Full_Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SkinFullContent extends Skin_Full_Content {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _register_controls_actions() {
		$widget_name = $this->parent->get_name();
		add_action(
			'elementor/element/' . $widget_name . '/section_layout/before_section_end',
			[
				$this,
				'register_skin_controls',
			]
		);
		add_action(
			'elementor/element/fibosearch-posts/section_query/after_section_end',
			[
				$this,
				'register_style_sections',
			]
		);
	}

	public function render() {
		parent::render();

		if ( ! $this->parent->get_query()->have_posts() ) {
			$message = $this->parent->get_settings_for_display( 'nothing_found_message' );
			if ( ! empty( $message ) ) {
				$this->render_loop_header();
				?>
				<div class="elementor-posts-nothing-found">
					<?php echo esc_html( $message ); ?>
				</div>
				<?php
				$this->render_loop_footer();
			}
		}
	}
}
