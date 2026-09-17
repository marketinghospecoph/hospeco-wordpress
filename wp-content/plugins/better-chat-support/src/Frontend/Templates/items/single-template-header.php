<?php
// don't call the file directly.

use ThemeAtelier\BetterChatSupport\Includes\Helpers;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Better Chat Support Pro Agent Message.
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 */

$header_close_icon = isset($options['header_close_icon']) ? $options['header_close_icon'] : 'yes';
?>
<div class="mSupport__popup--header 
    <?php echo $header_content_position === 'center' ? 'header-center' : ''; ?>">
    <?php if ($header_close_icon !== 'no') : ?>
        <span class="mSupport-popup-close" role="button" tabindex="0" aria-label="<?php echo esc_attr__('Close', 'better-chat-support'); ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </span>
    <?php endif; ?>
    <?php include Helpers::better_chat_support_locate_template('items/thumbnail.php'); ?>
    <div class="info">
        <?php if ($agent_name) : ?>
            <div class="info__name"><?php echo esc_html($agent_name); ?></div>
        <?php endif;
        if ($agent_subtitle || $offline_agent_subtitle) : ?>
            <div class="info__title" data-online="<?php echo esc_attr($agent_subtitle); ?>"
                data-offline="<?php echo esc_attr($offline_agent_subtitle); ?>"><?php echo esc_html($agent_subtitle); ?></div>
        <?php endif; ?>
    </div>
</div>