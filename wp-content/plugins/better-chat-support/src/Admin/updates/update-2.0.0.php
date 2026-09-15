<?php

/**
 * Update version.
 */
update_option('better_chat_support_version', BETTER_CHAT_SUPPORT_VERSION);
update_option('better_chat_support_db_version', BETTER_CHAT_SUPPORT_VERSION);

/**
 * Convert old data keys to new ones.
 */
function better_chat_support_convert_old_to_new_data_2_0_0($options)
{
    /*
     * Only convert genuinely legacy (Codestar-era) data. These keys exist ONLY
     * in the old schema and are removed once converted, so their absence means
     * the data is already on the new React schema. Re-running the conversion on
     * new-schema data would wrongly reset "Choose Your Chat Experience"
     * (chat_layout) and re-apply button-style defaults. Bail out when no legacy
     * markers are present.
     */
    $is_legacy = isset($options['opt-chat-type'])
        || isset($options['enable_floating_chat'])
        || isset($options['agent-listGrid']);
    if (!$is_legacy) {
        return;
    }

    $opt_chat_type = isset($options['opt-chat-type']) ? $options['opt-chat-type'] : 'single';
    $enable_floating_chat = isset($options['enable_floating_chat']) ? $options['enable_floating_chat'] : '';
    $agent_istGrid = isset($options['agent-listGrid']) ? $options['agent-listGrid'] : '';

    if ($opt_chat_type == 'single') {
        $options['chat_layout'] = 'agent';
    } else if ($opt_chat_type == 'multi') {
        if ($agent_istGrid == 'list') {
            $options['chat_layout'] = 'multi';
        } else {
            $options['chat_layout'] = 'multi_grid';
        }
    }
    if (!$enable_floating_chat) {
        $options['chat_layout'] = 'off';
    }

    $button_style = isset($options['opt-button-style']) ? $options['opt-button-style'] : '';
    $color_settings = isset($options['color_settings']) ? $options['color_settings'] : '';
    $primary = isset($color_settings['primary']) ? $color_settings['primary'] : '#0084ff';
    $secondary = isset($color_settings['secondary']) ? $color_settings['secondary'] : '#0066ff';

    if ($button_style == '1') {
        $options['bubble_button_border']['border_radius'] = '50';
    } else if ($button_style == '2') {
        $options['bubble_icon_bg'] = true;
        $options['bubble_button_border']['border_radius'] = '5';
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = true;
    } else if ($button_style == '3') {
        $options['bubble_icon_bg'] = true;
        $options['bubble_button_border']['border_radius'] = '5';
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = true;
        $options['bubble_button_border']['all'] = '1';
        $options['bubble_button_border']['color'] = $primary;
        $options['bubble_button_border']['hover_color'] = $secondary;
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = $primary;
        $options['bubble_button_text']['hover_color'] = $secondary;
        $options['bubble_icon_bg_color']['normal_color'] = $primary;
        $options['bubble_icon_bg_color']['hover_color'] = $secondary;
        $options['bubble-button-padding']['top'] = '5';
        $options['bubble-button-padding']['right'] = '15';
        $options['bubble-button-padding']['bottom'] = '5';
        $options['bubble-button-padding']['left'] = '6';
    } else if ($button_style == '4') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = true;
        $options['bubble_button_border']['border_radius'] = '50';
    } else if ($button_style == '5') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = true;
        $options['bubble_button_border']['border_radius'] = '50';
        $options['bubble_button_border']['all'] = '1';
        $options['bubble_button_border']['color'] = $primary;
        $options['bubble_button_border']['hover_color'] = $secondary;
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = $primary;
        $options['bubble_button_text']['hover_color'] = $secondary;
        $options['bubble_icon_bg_color']['normal_color'] = $primary;
        $options['bubble_icon_bg_color']['hover_color'] = $secondary;
    } else if ($button_style == '6') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = false;
        $options['bubble_button_border']['border_radius'] = '5';
        $options['bubble-button-padding']['top'] = '5';
        $options['bubble-button-padding']['right'] = '15';
        $options['bubble-button-padding']['bottom'] = '5';
        $options['bubble-button-padding']['left'] = '6';
        $options['bubble-button-padding']['unit'] = 'px';
        $options['bubble_button_border']['all'] = '1';
        $options['bubble_button_border']['color'] = $primary;
        $options['bubble_button_border']['hover_color'] = $secondary;
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = $primary;
        $options['bubble_button_text']['hover_color'] = $secondary;
    } else if ($button_style == '7') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = false;
        $options['bubble_button_border']['border_radius'] = '5';
        $options['bubble-button-padding']['top'] = '5';
        $options['bubble-button-padding']['right'] = '15';
        $options['bubble-button-padding']['bottom'] = '5';
        $options['bubble-button-padding']['left'] = '6';
        $options['bubble-button-padding']['unit'] = 'px';
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = '#ffffff';
        $options['bubble_button_text']['hover_color'] = '#ffffff';
    } else if ($button_style == '8') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = false;
        $options['bubble_button_border']['border_radius'] = '50';
        $options['bubble-button-padding']['top'] = '5';
        $options['bubble-button-padding']['right'] = '15';
        $options['bubble-button-padding']['bottom'] = '5';
        $options['bubble-button-padding']['left'] = '6';
        $options['bubble-button-padding']['unit'] = 'px';
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = '#ffffff';
        $options['bubble_button_text']['hover_color'] = '#ffffff';
    } else if ($button_style == '9') {
        $options['opt-button-style'] = '2';
        $options['bubble_icon_bg'] = false;
        $options['bubble_button_border']['border_radius'] = '50';
        $options['bubble-button-padding']['top'] = '5';
        $options['bubble-button-padding']['right'] = '15';
        $options['bubble-button-padding']['bottom'] = '5';
        $options['bubble-button-padding']['left'] = '6';
        $options['bubble-button-padding']['unit'] = 'px';
        $options['bubble_button_border']['all'] = '1';
        $options['bubble_button_border']['color'] = $primary;
        $options['bubble_button_border']['hover_color'] = $secondary;
        $options['bubble_button_bg']['normal_color'] = '#ffffff';
        $options['bubble_button_bg']['hover_color'] = '#ffffff';
        $options['bubble_button_text']['normal_color'] = $primary;
        $options['bubble_button_text']['hover_color'] = $secondary;
    }

    $agent_photo     = isset($options['agent-photo']) ? $options['agent-photo'] : '';
    $agent_photo_url = (isset($agent_photo['url']) && !empty($agent_photo['url'])) ? $agent_photo['url'] : '';

    if ($agent_photo_url) {
        $options['agent_photo_type'] = 'custom';
    }

    unset($options['opt-chat-type']);
    unset($options['enable_floating_chat']);
    unset($options['agent-listGrid']);
    update_option('mcs-opt', $options);
}

/**
 * Update old to new data.
 */
$options = get_option('mcs-opt');
if ($options) {
    better_chat_support_convert_old_to_new_data_2_0_0($options);
}
