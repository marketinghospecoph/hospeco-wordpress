<?php

namespace DgoraWcas\Integrations\Plugins\Elementor\PostsWidgetsSkins;

use ElementorPro\Modules\Posts\Skins\Skin_Classic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SkinClassic extends Skin_Classic {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _register_controls_actions() {
		add_action(
			'elementor/element/fibosearch-posts/classic_section_design_layout/after_section_end',
			[
				$this,
				'register_additional_design_controls',
			]
		);
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
