<?php

/**
 * Update version.
 */
update_option('better_chat_support_version', BETTER_CHAT_SUPPORT_VERSION);
update_option('better_chat_support_db_version', BETTER_CHAT_SUPPORT_VERSION);

/**
 * Convert old data keys to new ones.
 */
function better_chat_support_convert_old_to_new_data_1_2_21($options)
{
    $options['before-chat-icon'] = 'icofont-facebook-messenger';
    $options['circle-button-icon'] = 'icofont-facebook-messenger';
    $options['circle-button-close'] = 'icofont-close';
    $options['select-animation'] = '1';
    $options['circle-animation'] = '1';

    return $options;
}

/**
 * Update old to new data.
 */
$options = get_option('mcs-opt');
if ($options) {
    $updated_options = better_chat_support_convert_old_to_new_data_1_2_21($options);
    update_option('mcs-opt', $updated_options);
}
