<?php
// don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Better Chat Support Pro Agent Message.
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 */

if ($bubble_title || $bubble_subtitle) : ?>
    <div class="mSupport-multi__popup--header">
        <?php if ($bubble_title) : ?>
            <div class="mSupport_title"><?php echo esc_html($bubble_title); ?></div>
        <?php endif; ?>
        <?php if ($bubble_subtitle) : ?>
            <div class="mSupport_subtitle"><?php echo esc_html($bubble_subtitle); ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>