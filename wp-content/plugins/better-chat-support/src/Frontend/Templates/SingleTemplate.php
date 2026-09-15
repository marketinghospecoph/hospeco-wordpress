<?php

/**
 * Single Template Class
 *
 * This class handles the single template functionality for Better Chat Support.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

namespace ThemeAtelier\BetterChatSupport\Frontend\Templates;

use ThemeAtelier\BetterChatSupport\Includes\Helpers;

// don't call the file directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Class SingleTemplate
 *
 * Handles the rendering of single templates in the plugin.
 *
 * @since 1.0.0
 */
class SingleTemplate
{
	/**
	 * Handles single template logic.
	 *
	 * This method contains the logic to display or render single templates.
	 *
	 * @since 1.0.0
	 */
	public static function singleTemplate($options, $ch_settings, $bubble_type, $random, $unique_id, $chat_type)
	{
		$optAvailablity = isset($options['opt-availablity']) ? $options['opt-availablity'] : '';
		$user_availability = Helpers::user_availability($optAvailablity);
		$agent_message = isset($options['agent-message']) ? $options['agent-message'] : 'Hello, Welcome to the site. Please click below button for chatting me through messenger.';
		$show_current_time = isset($options['show_current_time']) ? $options['show_current_time'] : true;
		$bubble_position = isset($options['bubble-position']) ? $options['bubble-position'] : 'bottom_right';
		$enable_positioning_tablet = isset($options['enable-positioning-tablet']) ? $options['enable-positioning-tablet'] : '';
		$bubble_position_tablet = isset($options['bubble-position-tablet']) ? $options['bubble-position-tablet'] : 'bottom_right';
		$enable_positioning_mobile = isset($options['enable-positioning-mobile']) ? $options['enable-positioning-mobile'] : '';
		$bubble_position_mobile = isset($options['bubble-position-mobile']) ? $options['bubble-position-mobile'] : 'bottom_right';
		$select_animation = isset($options['select-animation']) ? $options['select-animation'] : 'random';
		$theme_style = isset($options['theme_style']) ? $options['theme_style'] : 'flat_theme';
		$theme_background_image = isset($options['theme_background_image']) ? $options['theme_background_image'] : '';
        $theme_background_image_url = !empty($theme_background_image['url']) ? $theme_background_image['url'] : BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/images/messenger_theme_background.jpg';
		$bubble_style = isset($options['bubble-style']) ? $options['bubble-style'] : 'default';
		$select_timezone = isset($options['select-timezone']) ? $options['select-timezone'] : '';
		$header_content_position = isset($options['header-content-position']) ? $options['header-content-position'] : 'center';
		$before_chat_icon = isset($options['before-chat-icon']) ? $options['before-chat-icon'] : 'icofont-facebook-messenger';
		$chat_button_text = isset($options['chat-button-text']) ? $options['chat-button-text'] : 'Send Message';
		$agent_photo_type = isset($options['agent_photo_type']) ? $options['agent_photo_type'] : 'default';
		$agent_photo = isset($options['agent-photo']) ? $options['agent-photo'] : '';
		$agent_photo_url = isset($agent_photo['url']) ? $agent_photo['url'] : '';

		// "Default" renders the agent's initials (see items/thumbnail.php), so it
		// deliberately leaves $agent_photo_url empty instead of pointing at the
		// bundled user.webp placeholder.
		if ($agent_photo_type === 'default' || $agent_photo_type === 'none') {
			$agent_photo_url = '';
		}
		$agent_name = isset($options['agent-name']) ? $options['agent-name'] : 'John Doe';
		$agent_subtitle = isset($options['agent-subtitle']) ? $options['agent-subtitle'] : 'Typically replies within a day';
		$offline_agent_subtitle = !empty($options['offline_agent_subtitle']) ? $options['offline_agent_subtitle'] : $agent_subtitle;
		$color_settings = isset($options['color_settings']) ? $options['color_settings'] : '';
		$primary = !empty($color_settings['primary']) ? $color_settings['primary'] : '#0084ff';
		$secondary = !empty($color_settings['secondary']) ? $color_settings['secondary'] : '#0066ff';
		$send_button_color = isset($options['send_button_color']) ? $options['send_button_color'] : '';
		$color = '#fff';
		$hover_color = '#fff';
		$background = $primary;
		$hover_background = $secondary;
		$floating_button_style = isset($options['opt-button-style']) ? $options['opt-button-style'] : '1';
		$facebook_id = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';

		$bubble_visibility = isset($options['bubble-visibility']) ? $options['bubble-visibility'] : 'everywhere';
		// Method implementation goes here.
		if ('random' === $select_animation) :
			$animation = $random;
		else :
			$animation = !empty($select_animation) ? $select_animation : '14';
		endif;

		if ($enable_positioning_tablet) {
			$bubble_position_tablet = 'tablet_' . $bubble_position_tablet;
		} else {
			$bubble_position_tablet = '';
		}
		if ($enable_positioning_mobile) {
			$bubble_position_mobile = 'mobile_' . $bubble_position_mobile;
		} else {
			$bubble_position_mobile = '';
		}
		$button_style = '';

		$auto_show_popup   = !empty($options['autoshow-popup']);
		$auto_open_timeout = isset($options['auto_open_popup_timeout']) ? (int) $options['auto_open_popup_timeout'] : 0;

		echo '<div style="--custom_theme_bg: url('. esc_url($theme_background_image_url) .')" id="' . esc_attr($unique_id) . '" class="mSupport_bubble mSupport ' . esc_attr($bubble_position . ' ' . $bubble_position_tablet . ' ' . $bubble_position_mobile . ' ' . $button_style . ' ' . $theme_style) . ' mSupport-' . esc_attr($bubble_visibility) . '-only ';

		// Add position-specific class if position is 'left'.
		if ('left' === $bubble_position) {
			echo 'mSupport-left ';
		}

		// Auto-open: add mSupport-show immediately when timeout is 0.
		if ($auto_show_popup && $auto_open_timeout === 0) {
			echo 'mSupport-show ';
		}

		$timeout_attr = ($auto_show_popup && $auto_open_timeout > 0)
			? ' data-auto-open-timeout="' . esc_attr($auto_open_timeout) . '"'
			: '';

		echo 'chat-availability" data-timezone="' . esc_attr($select_timezone) . '" data-availability="' . esc_attr($user_availability) . '"' . $timeout_attr . '>';
		echo wp_kses_post($bubble_type); ?>
		<div class="mSupport__popup animation<?php echo esc_attr($animation) ?> ">
			<?php
			include Helpers::better_chat_support_locate_template('items/single-template-header.php');
			include Helpers::better_chat_support_locate_template('items/agent-message.php');
			?>
		</div>
		</div>
<?php
	}
}
