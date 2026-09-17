<?php

/**
 * Update version.
 */
update_option('better_chat_support_version', BETTER_CHAT_SUPPORT_VERSION);
update_option('better_chat_support_db_version', BETTER_CHAT_SUPPORT_VERSION);

/**
 * Convert old data keys to new ones.
 */
function better_chat_support_convert_old_to_new_data_1_3_0($options)
{
    $license_key = isset($options['license_key']) ? $options['license_key'] : '';
    $cleanup_data_deletion = isset($options['cleanup_data_deletion']) ? $options['cleanup_data_deletion'] : '';
    $bubble_position = isset($options['bubble-position']) ? $options['bubble-position'] : '';

    if ($bubble_position === 'right') {
        $options['bubble-position'] = 'bottom_right';
    } elseif ($bubble_position === 'left') {
        $options['bubble-position'] = 'bottom_left';
    }

    $mcs_settings = get_option('mcs_settings');
    $mcs_settings['license_key'] = $license_key;
    $mcs_settings['cleanup_data_deletion'] = $cleanup_data_deletion;

    update_option('mcs_settings', $mcs_settings);

    unset($options['license_key']);
    unset($options['cleanup_data_deletion']);
    update_option('mcs-opt', $options);

    return $options;
}

/**
 * Update old to new data.
 */
$options = get_option('mcs-opt');
if ($options) {
    better_chat_support_convert_old_to_new_data_1_3_0($options);
}
