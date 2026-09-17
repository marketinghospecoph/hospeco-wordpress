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
 * Class ButtonTemplate
 *
 * Handles the rendering of single templates in the plugin.
 *
 * @since 1.0.0
 */
class ButtonTemplate
{
	/**
	 * Handles single template logic.
	 *
	 * This method contains the logic to display or render single templates.
	 *
	 * @since 1.0.0
	 */
	public static function buttonTemplate($options, $ch_settings, $bubble_type, $unique_id)
	{
		$optAvailablity = isset($options['opt-availablity']) ? $options['opt-availablity'] : '';
		$user_availability = Helpers::user_availability($optAvailablity);
		$bubble_position = isset($options['bubble-position']) ? $options['bubble-position'] : 'bottom_right';
		$enable_positioning_tablet = isset($options['enable-positioning-tablet']) ? $options['enable-positioning-tablet'] : '';
		$bubble_position_tablet = isset($options['bubble-position-tablet']) ? $options['bubble-position-tablet'] : 'bottom_right';
		$enable_positioning_mobile = isset($options['enable-positioning-mobile']) ? $options['enable-positioning-mobile'] : '';
		$bubble_position_mobile = isset($options['bubble-position-mobile']) ? $options['bubble-position-mobile'] : 'bottom_right';
		$bubble_style = isset($options['bubble-style']) ? $options['bubble-style'] : 'default';
		$select_timezone = isset($options['select-timezone']) ? $options['select-timezone'] : '';

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

		$bubble_visibility = isset($options['bubble-visibility']) ? $options['bubble-visibility'] : 'everywhere';
		echo '<div id="' . esc_attr($unique_id) . '" class="mSupport_bubble no_bubble mSupport ' . esc_attr($bubble_position) . ' ' . esc_attr($bubble_position_tablet) . ' ' . esc_attr($bubble_position_mobile) . ' mSupport-' . esc_attr($bubble_visibility) . '-only ';

		// Add position-specific class if position is 'left'.
		if ('left' === $bubble_position) {
			echo 'mSupport-left';
		}

		echo 'chat-availability" data-timezone="' . esc_attr($select_timezone) . '" data-availability="' . esc_attr($user_availability) . '">';
		echo wp_kses_post($bubble_type); ?>
		</div>
<?php
	}
}
