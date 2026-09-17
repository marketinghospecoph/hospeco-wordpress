<?php
// don't call the file directly.

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Better Chat Support Pro Agent Message.
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 */


echo '<div class="mSupport__popup__content">';
if ($agent_message) : ?>
    <div class="sms">
        <div class="sms__text">
            <div class="agent-message">
                <?php echo wp_kses_post($agent_message); ?>
            </div>
            <?php
            if ($show_current_time) {
                echo '<div class="current-time"></div>';
            }
            ?>
        </div>
    </div>
<?php endif;
if ($before_chat_icon === 'no_icon') {
    $open_icon = '<i class="icofont-facebook-messenger"></i>';
} else {
    if (!empty($before_chat_icon)) {
        $open_icon = '<i class="' . esc_attr($before_chat_icon) . '"></i>';
    } else {
        $open_icon = '<i class="icofont-facebook-messenger"></i>';
    }
}

$url = 'https://www.m.me/' . $facebook_id;
$open_in_new_tab = isset($ch_settings['open_in_new_tab']) ? $ch_settings['open_in_new_tab'] : '';
$open_in_new_tab = $open_in_new_tab ? '_blank' : '_self';

?>
    <div
        class="mSupport__send-message better_chat_support_link"
        target="_blank"
        type="submit"
        style="--mSupport-color-primary: <?php echo esc_attr($background) ?>;--mSupport-color-secondary:<?php echo esc_attr($hover_background) ?>;--text-color: <?php echo esc_attr($color) ?>;--text-hover-color: <?php echo esc_attr($hover_color) ?>">
        <?php

        echo wp_kses_post($open_icon);
        echo esc_html($chat_button_text);
        echo '<a href="' . esc_attr($url) . '" target="' . esc_attr($open_in_new_tab) . '"></a>';
        ?>
    </div>

</div>