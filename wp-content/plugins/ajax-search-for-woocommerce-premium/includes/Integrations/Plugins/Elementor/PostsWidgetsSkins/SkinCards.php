<?php

namespace DgoraWcas\Integrations\Plugins\Elementor\PostsWidgetsSkins;

use ElementorPro\Modules\Posts\Skins\Skin_Cards;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SkinCards extends Skin_Cards {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _register_controls_actions() {
		add_action(
			'elementor/element/fibosearch-posts/section_layout/before_section_end',
			[
				$this,
				'register_controls',
			]
		);
		add_action(
			'elementor/element/fibosearch-posts/section_query/after_section_end',
			[
				$this,
				'register_style_sections',
			]
		);
		add_action(
			'elementor/element/fibosearch-posts/cards_section_design_image/before_section_end',
			[
				$this,
				'register_additional_design_image_controls',
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
