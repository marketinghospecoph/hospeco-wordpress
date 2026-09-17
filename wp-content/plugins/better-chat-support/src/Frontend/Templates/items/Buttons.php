<?php

/**
 * Multi Template Class
 *
 * This class handles the multi template functionality for Better Chat Support.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

namespace ThemeAtelier\BetterChatSupport\Frontend\Templates\items;

use ThemeAtelier\BetterChatSupport\Includes\Helpers;

// don't call the file directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Buttons
 *
 * Handles the rendering of multiple templates in the plugin.
 *
 * @since 1.0.0
 */
class Buttons
{
    public static function buttons($options, $ch_settings)
    {
        // Default Options
        $chat_type = $options['chat_layout'] ?? 'agent';
        $open_in_new_tab = isset($ch_settings['open_in_new_tab']) ? $ch_settings['open_in_new_tab'] : '';
        $button_size = isset($options['button_size']) ? $options['button_size'] : '1';
        $floating_button_style = $options['opt-button-style'] ?? '1';
        $circle_button_icon = $options['circle-button-icon'] ?? 'icofont-facebook-messenger';
        $circle_button_close = $options['circle-button-close'] ?? 'icofont-close';

        // Determine the open icon

        if (!empty($circle_button_icon)) {
            $open_icon = '<i class="' . esc_attr($circle_button_icon) . '"></i>';
        } else {
            $open_icon = '<i class="icofont-facebook-messenger"></i>';
        }

        // Determine the close icon
        if (!empty($circle_button_close)) {
            $close_icon = '<i class="' . esc_attr($circle_button_close) . '"></i>';
        } else {
            $close_icon = '<i class="icofont-close"></i>';
        }

        $circle_button_icon_1 = $options['circle-button-icon-1'] ?? 'icofont-facebook-messenger';
        $circle_button_icon_native = $options['circle-button-icon-native'] ?? 'icofont-facebook-messenger';
        $circle_button_icon_custom = $options['circle-button-icon-custom']['url'] ?? 'icofont-facebook-messenger';

        if ($circle_button_icon_1 == 'native') {
            $circle_button_icon_1 = $circle_button_icon_native;
        }

        $circle_button_close_1 = $options['circle-button-close-1'] ?? 'icofont-close';
        $circle_button_close_native = $options['circle-button-close-native'] ?? 'icofont-facebook-messenger';
        $circle_button_close_custom = $options['circle-button-close-custom']['url'] ?? 'icofont-facebook-messenger';

        if ($circle_button_close_1 == 'native') {
            $circle_button_close_1 = $circle_button_close_native;
        }

        $tooltip_enabled = $options['bubble_button_tooltip'] ?? 'on_hover';
        $tooltip_text = $options['bubble_button_tooltip_text'] ?? 'Need Help? Chat with us';
        $circle_animation = !empty($options['circle-animation']) ? $options['circle-animation'] : '1';
        /* React admin default for bubble-text is "How may I help you?" */
        $button_label = !empty($options['bubble-text']) ? $options['bubble-text'] : 'How may I help you?';
        $select_timezone = $options['select-timezone'] ?? '';
        $opt_availablity = $options['opt-availablity'] ?? '';
        $tooltip_class = '';
        if ($tooltip_enabled == 'on_hover') {
            $tooltip_class = 'hover_tooltip';
        }
        $color_settings = !empty($options['color_settings']) ? $options['color_settings'] : array();
        $color_primary = !empty($color_settings['primary']) ? $color_settings['primary'] : '#0084ff';
        $color_secondary = !empty($color_settings['secondary']) ? $color_settings['secondary'] : '#0066ff';
        $button_bg = !empty($options['bubble_button_bg']) ? $options['bubble_button_bg'] : array();
        $bg_color = !empty($button_bg['normal_color']) ? $button_bg['normal_color'] : $color_primary;
        $bg_hover_color = !empty($button_bg['hover_color']) ? $button_bg['hover_color'] : $color_secondary;

        $bubble_button_text = !empty($options['bubble_button_text']) ? $options['bubble_button_text'] : array();
        $text_color = !empty($bubble_button_text['normal_color']) ? $bubble_button_text['normal_color'] : '#ffffff';
        $text_hover_color = !empty($bubble_button_text['hover_color']) ? $bubble_button_text['hover_color'] : '#ffffff';

        $bubble_icon_bg_color = !empty($options['bubble_icon_bg_color']) ? $options['bubble_icon_bg_color'] : array();
        $normal_bg_color = !empty($bubble_icon_bg_color['normal_color']) ? $bubble_icon_bg_color['normal_color'] : '#ffffff';
        $hover_bg_color = !empty($bubble_icon_bg_color['hover_color']) ? $bubble_icon_bg_color['hover_color'] : '#ffffff';
        /* React admin default for bubble_icon_bg is true (ON) — match it here */
        $icon_bg = !isset($options['bubble_icon_bg']) || !empty($options['bubble_icon_bg']) ? 'icon_bg' : '';
        $bubble_icon_color = !empty($options['bubble_icon_color']) ? $options['bubble_icon_color'] : array();

        if ($floating_button_style == '2' && $icon_bg === 'icon_bg') {
            $normal_icon_color = !empty($bubble_icon_color['normal_color']) ? $bubble_icon_color['normal_color'] : $bg_color;
            $hover_icon_color = !empty($bubble_icon_color['hover_color']) ? $bubble_icon_color['hover_color'] : $bg_hover_color;
        } else {
            $normal_icon_color = !empty($bubble_icon_color['normal_color']) ? $bubble_icon_color['normal_color'] : '#ffffff';
            $hover_icon_color = !empty($bubble_icon_color['hover_color']) ? $bubble_icon_color['hover_color'] : '#ffffff';
        }

        $button_text = isset($options['bubble-text']) ? $options['bubble-text'] : 'How may I help you?';
        $chat_button_top_label = isset($options['chat_button_top_label']) ? $options['chat_button_top_label'] : 'Support Team';
        $chat_button_text = isset($options['chat_button_text']) ? $options['chat_button_text'] : 'Start a conversation';

        $agent_avatar = isset($options['agent_avatar']) ? $options['agent_avatar'] : '';
        $agent_avatar_type = isset($options['agent_avatar_type']) ? $options['agent_avatar_type'] : 'default';
        $agent_avatar_url = isset($agent_avatar['url']) ? $agent_avatar['url'] : '';
        $chat_button_image = isset($options['chat_button_image']) ? $options['chat_button_image'] : '';
        $chat_button_image_type = isset($options['chat_button_image_type']) ? $options['chat_button_image_type'] : 'default';
        $chat_button_image_url = isset($chat_button_image['url']) ? $chat_button_image['url'] : '';
        $button_top_label = isset($options['button_top_label']) ? $options['button_top_label'] : 'John Doe / Technical support';
        $online_text = isset($options['online_text']) ? $options['online_text'] : 'I Am Online';
        $offline_text = isset($options['offline_text']) ? $options['offline_text'] : 'I Am Offline';

        $bubble_button_border = isset($options['bubble_button_border']) ? $options['bubble_button_border'] : array();
        $border_all = isset($bubble_button_border['all']) ? $bubble_button_border['all'] . 'px' : '0px';
        $border_style = isset($bubble_button_border['style']) ? $bubble_button_border['style'] : 'solid';
        $border_radius = isset($bubble_button_border['border_radius']) ? $bubble_button_border['border_radius'] . 'px' : '50px';
        $border_color = isset($bubble_button_border['color']) ? $bubble_button_border['color'] : '';
        $border_color = !empty($border_color) ? $border_color : $color_primary;
        $hover_border_color = isset($bubble_button_border['hover_color']) ? $bubble_button_border['hover_color'] : '';
        $hover_border_color = !empty($hover_border_color) ? $hover_border_color : $color_secondary;

        $bubble_icon_border = isset($options['bubble_icon_border']) ? $options['bubble_icon_border'] : array();
        $icon_border_all = isset($bubble_icon_border['all']) ? $bubble_icon_border['all'] . 'px' : '0px';
        $icon_border_style = isset($bubble_icon_border['style']) ? $bubble_icon_border['style'] : 'solid';
        $icon_border_color = isset($bubble_icon_border['color']) ? $bubble_icon_border['color'] : '';
        $icon_border_color = !empty($icon_border_color) ? $icon_border_color : $color_primary;
        $hover_icon_border_color = isset($bubble_icon_border['hover_color']) ? $bubble_icon_border['hover_color'] : '';
        $hover_icon_border_color = !empty($hover_icon_border_color) ? $hover_icon_border_color : $color_secondary;
        $icon_border_radius = isset($bubble_icon_border['border_radius']) ? $bubble_icon_border['border_radius'] . 'px' : '50px';

        // Bubble button paddings
        $bubble_button_padding = isset($options['bubble-button-padding']) ? $options['bubble-button-padding'] : array();
        $bubble_button_padding_top =  isset($bubble_button_padding['top']) ? $bubble_button_padding['top'] : '5';
        $bubble_button_padding_right =  isset($bubble_button_padding['right']) ? $bubble_button_padding['right'] : '15';
        $bubble_button_padding_bottom =  isset($bubble_button_padding['bottom']) ? $bubble_button_padding['bottom'] : '5';
        $bubble_button_padding_left =  isset($bubble_button_padding['left']) ? $bubble_button_padding['left'] : '6';
        $bubble_button_padding_unit = isset($bubble_button_padding['unit']) ? $bubble_button_padding['unit'] : 'px';

        $padding = $bubble_button_padding_top . $bubble_button_padding_unit . ' ' . $bubble_button_padding_right . $bubble_button_padding_unit . ' ' . $bubble_button_padding_bottom . $bubble_button_padding_unit . ' ' . $bubble_button_padding_left . $bubble_button_padding_unit;
        $notification_icon = isset($options['notification_icon']) ? $options['notification_icon'] : '';

        if ($agent_avatar_type === 'default') {
            $agent_avatar_url = BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/images/user.webp';
        } elseif ($agent_avatar_type === 'custom' && $agent_avatar_url) {
            $agent_avatar_url;
        } elseif ($agent_avatar_type === 'none') {
            $agent_avatar_url = '';
        }
        if ($chat_button_image_type === 'default') {
            $chat_button_image_url = BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/images/messenger.svg';
        } elseif ($chat_button_image_type === 'custom' && $chat_button_image_url) {
            $chat_button_image_url;
        } elseif ($chat_button_image_type === 'none') {
            $chat_button_image_url = '';
        }

        $user_availability = Helpers::user_availability($opt_availablity);

        // Keep Button Style 1 as Is
        if ($floating_button_style === '1') {
            $bubble_type = '<div style="--mSupport-border: ' . esc_attr($border_all . ' ' . $border_style) . '; --mSupport-btn-scale: ' . esc_attr($button_size) . '; --mSupport-border-radius: ' . esc_attr($border_radius) . '; --mSupport-background: ' . esc_attr($bg_color) . '; --mSupport-hover-background: ' . esc_attr($bg_hover_color) . '; --mSupport-icon-normal-color: ' . esc_attr($normal_icon_color) . '; --mSupport-icon-hover-color: ' . esc_attr($hover_icon_color) . '; --mSupport-border-color: ' . esc_attr($border_color) . '; --mSupport-border-hover-color: ' . esc_attr($hover_border_color) . ';" class="mSupport_button mSupport-bubble circle-bubble circle-animation-' . esc_attr($circle_animation . ' mSupport_' . $chat_type . ' layout_' . $chat_type . ' ' . $tooltip_class) . '">';
            if ($notification_icon) {
                $bubble_type .= '<span class="notification_icon"></span>';
            }
            $bubble_type .= '<span class="open-icon">';
            if ($circle_button_icon_1 == 'custom') {
                if (!empty($circle_button_icon_custom)) {
                    $bubble_type .= '<img src="' . esc_url($circle_button_icon_custom) . '" alt="" />';
                } else {
                    $bubble_type .= '<i class="icofont-facebook-messenger"></i>';
                }
            } else {
                $bubble_type .= '<i class="' . esc_attr($circle_button_icon_1) . '"></i>';
            }
            $bubble_type .= '</span>';
            $bubble_type .= '<span class="close-icon">';
            if ($circle_button_close_1 == 'custom') {
                if (!empty($circle_button_close_custom)) {
                    $bubble_type .= '<img src="' . esc_url($circle_button_close_custom) . '" alt="" />';
                } else {
                    $bubble_type .= '<i class="icofont-facebook-messenger"></i>';
                }
            } else {
                $bubble_type .= '<i class="' . esc_attr($circle_button_close_1) . '"></i>';
            }
            $bubble_type .= '</span>';
            if ($chat_type == 'button') {
                $facebook_id = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';
                $url = 'https://www.m.me/' . $facebook_id;

                $open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';
                $bubble_type .= '<a href="' . esc_attr($url) . '" target="' . esc_attr($open_in_new_tab) . '" class="chat-link better_chat_support_link"></a>';
            }
            if ($tooltip_enabled != 'hide' && !empty($tooltip_text)) {
                $bubble_type .= '<span class="tooltip_text">' . wp_kses_post($tooltip_text) . '</span>';
            }
            $bubble_type .= '</div>';
            return $bubble_type;
        } else if ($floating_button_style === '2') {
            // Optimize for All Other Button Styles
            $icons = '';
            if ($circle_button_icon !== 'no_icon') {
                // Generate the HTML
                $icons = '
                <div class="bubble__icon bubble-animation' . esc_attr($circle_animation . ' ' . $icon_bg) . '">
                    <span class="bubble__icon--open">' . $open_icon . '</span>
                    <span class="bubble__icon--close">' . $close_icon . '</span>
                </div>';
            }

            $bubble_type = '<div style="--mSupport-padding: ' . esc_attr($padding) . '; --mSupport-btn-scale: ' . esc_attr($button_size) . '; --mSupport-border: ' . esc_attr($border_all . ' ' . $border_style) . '; --mSupport-border-radius: ' . esc_attr($border_radius) . '; --mSupport-background: ' . esc_attr($bg_color) . '; --mSupport-hover-background: ' . esc_attr($bg_hover_color) . '; --mSupport-icon-normal-color: ' . esc_attr($normal_icon_color) . '; --mSupport-icon-hover-color: ' . esc_attr($hover_icon_color) . '; --mSupport-icon-normal-bg-color: ' . esc_attr($normal_bg_color) . '; --mSupport-icon-hover-bg-color: ' . esc_attr($hover_bg_color) . '; --mSupport-border-color: ' . esc_attr($border_color) . '; --mSupport-border-hover-color: ' . esc_attr($hover_border_color) . '; --mSupport-text-color: ' . esc_attr($text_color) . '; --mSupport-text-hover-color: ' . esc_attr($text_hover_color) . '; --mSupport-icon-border: ' . esc_attr($icon_border_all . ' ' . $icon_border_style) . '; --mSupport-icon-border-color: ' . esc_attr($icon_border_color) . '; --mSupport-hover-icon-border-color: ' . esc_attr($hover_icon_border_color) . '; --mSupport-icon-border-radius: ' . esc_attr($icon_border_radius) . ';" class="mSupport_button mSupport-bubble bubble ' . esc_attr($button_size . ' mSupport_' . $chat_type . ' layout_' . $chat_type . ' ' . $tooltip_class) . '">';
            $bubble_type .= ($notification_icon ? '<span class="notification_icon"></span>' : '') . $icons . esc_attr($button_label);

            // Add Tooltip
            if ($tooltip_enabled != 'hide' && $tooltip_text) {
                $bubble_type .= '<span class="tooltip_text">' . wp_kses_post($tooltip_text) . '</span>';
            }
            if ($chat_type === 'button') {
                $facebook_id = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';
                $url = 'https://www.m.me/' . $facebook_id;
                $open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';
                $bubble_type .= '<a href="' . esc_attr($url) . '" target="' . esc_attr($open_in_new_tab) . '" class="chat-link better_chat_support_link"></a>';
            }
            $bubble_type .= '</div>';
            return $bubble_type;
        } else {
            $bubble_type = '<div style="--mSupport-padding: ' . esc_attr($padding) . '; --mSupport-btn-scale: ' . esc_attr($button_size) . '; --mSupport-border: ' . esc_attr($border_all . ' ' . $border_style) . '; --mSupport-border-radius: ' . esc_attr($border_radius) . '; --mSupport-background: ' . esc_attr($bg_color) . '; --mSupport-hover-background: ' . esc_attr($bg_hover_color) . '; --mSupport-border-color: ' . esc_attr($border_color) . '; --mSupport-border-hover-color: ' . esc_attr($hover_border_color) . '; --mSupport-text-color: ' . esc_attr($text_color) . '; --mSupport-text-hover-color: ' . esc_attr($text_hover_color) . '; --mSupport-icon-border: ' . esc_attr($icon_border_all . ' ' . $icon_border_style) . '; --mSupport-icon-border-color: ' . esc_attr($icon_border_color) . '; --mSupport-hover-icon-border-color: ' . esc_attr($hover_icon_border_color) . '; --mSupport-icon-border-radius: ' . esc_attr($icon_border_radius) . ';" class="mSupport_button mSupport-bubble mSupport_button_advance ' . esc_attr($button_size . ' mSupport_' . $chat_type . ' layout_' . $chat_type . ' ' . $tooltip_class) . '">';
            if (in_array($chat_type, ['multi', 'multi_agent_form'])) {
                if ($chat_button_image_url) {
                    $bubble_type .= '<img decoding="async" src="' . esc_url($chat_button_image_url) . '">';
                }
            } else {
                if ($agent_avatar_url) {
                    $bubble_type .= '<img decoding="async" src="' . esc_url($agent_avatar_url) . '">';
                }
            }
            $bubble_type .= '<div class="info-wrapper">
				<div class="info-wrapper">';
            if (in_array($chat_type, ['multi', 'multi_agent_form'])) {
                $bubble_type .= '<div class="info">' . esc_html($chat_button_top_label) . '</div>
                    <div class="mSupport_title">' . esc_html($chat_button_text) . '</div>';
            } else {
                $bubble_type .= '<div class="info">' . esc_html($button_top_label) . '</div>
                    <div class="mSupport_title">' . esc_html($button_text) . '</div>
                    <div class="online">' . esc_html($online_text) . '</div>
                    <div class="offline">' . esc_html($offline_text) . '</div>';
            }
            $bubble_type .= '</div>';
            // Add Tooltip
            if ($tooltip_enabled != 'hide' && $tooltip_text) {
                $bubble_type .= '<span class="tooltip_text">' . wp_kses_post($tooltip_text) . '</span>';
            }
            $bubble_type .= '</div>';
            if ($chat_type === 'button') {
                $facebook_id = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';
                $url = 'https://www.m.me/' . $facebook_id;
                $open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';
                $bubble_type .= '<a href="' . esc_attr($url) . '" target="' . esc_attr($open_in_new_tab) . '" class="chat-link better_chat_support_link"></a>';
            }
            $bubble_type .= '</div>';

            return $bubble_type;
        }
    }
}
