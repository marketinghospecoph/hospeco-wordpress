<?php

/**
 * Handles the Better Chat Support functionality.
 *
 * @package    Better Chat Support
 * @version    1.0
 * @author     ThemeAtelier
 * @website    https://themeatelier.net/
 */

namespace ThemeAtelier\BetterChatSupport\Frontend\Shortcode;

use ThemeAtelier\BetterChatSupport\Frontend\CustomButtonsTemplates;

/**
 * Class CustomShortcode
 *
 * This class handles the custom shortcode functionality for WhatsApp chat buttons.
 *
 * @since 1.0.0
 */
class CustomShortcode
{


	/**
	 * Handles the custom buttons shortcode rendering.
	 *
	 * This function is responsible for rendering custom WhatsApp buttons via shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return string The HTML output for the custom WhatsApp buttons.
	 */
	public function mcs_custom_buttons_shortcode($atts)
	{
		// Function implementation goes here.
		$atts = shortcode_atts(
			array(
				'style'       => '1',
				'photo'       		=> BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/images/user.webp',
				'background'       	=> '#0084ff',
				'hover_background'     => '#0066ff',
				'text_color'       		=> '#ffffff',
				'hover_text_color'     => '#ffffff',
				'icon_color'     		=> '#0084ff',
				'hover_icon_color'     => '#0066ff',
				'icon_background'     => '#ffffff',
				'hover_icon_background'     => '#ffffff',
				'border'	=> '0',
				'border_style'		=> 'solid',
				'border_color'		=> '#0084ff',
				'border_hover_color'	=> '#0066ff',
				'icon_border'		=> '0',
				'icon_border_style'	=> 'solid',
				'icon_border_color'	=> '#0084ff',
				'icon_border_hover_color'	=> '#0066ff',
				'padding'     => '5px 15px 5px 6px',
				'top_label'   => esc_html__('John / Technical support', 'chat-help'),
				'main_label'  => esc_html__('How can I help you?', 'chat-help'),
				'online'      => esc_html__('I am online', 'chat-help'),
				'offline'     => esc_html__('I am offline', 'chat-help'),
				'fbid' 		  => 'themeAtelier',
				'visibility'  => 'everywhere',
				'sizes'       => '1',
				'icon_bg' 	  => 'yes',
				'icon' 		  => 'yes',
				'border_radius'     => '50px',
				'icon_border_radius'     => '50px',
				'timezone'    => '',
				'sunday'      => esc_html__('00:00-23:59', 'chat-help'),
				'monday'      => esc_html__('00:00-23:59', 'chat-help'),
				'tuesday'     => esc_html__('00:00-23:59', 'chat-help'),
				'wednesday'   => esc_html__('00:00-23:59', 'chat-help'),
				'thursday'    => esc_html__('00:00-23:59', 'chat-help'),
				'friday'      => esc_html__('00:00-23:59', 'chat-help'),
				'saturday'    => esc_html__('00:00-23:59', 'chat-help'),
			),
			$atts
		);

		ob_start();
		$button_obj = new CustomButtonsTemplates($atts);

		if (! empty($atts['style'])) {
			// Style Switch
			switch ($atts['style']) {
				case '1':
					$button_obj->mcs_button_s1();
					break;
				case '2':
					$button_obj->mcs_button_s2();
					break;
				default:
					$button_obj->mcs_button_s1();
					break;
			}
		}

		return ob_get_clean();
	}
}
