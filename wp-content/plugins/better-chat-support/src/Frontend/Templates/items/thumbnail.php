<?php
// don't call the file directly.
if (! defined('ABSPATH')) {
	exit;
}

use ThemeAtelier\BetterChatSupport\Includes\Helpers;

/**
 * Better Chat Support Agent Thumbnail.
 *
 * "Default" no longer means the bundled user.webp placeholder: it renders the
 * agent's initials in a coloured circle, matching Chat Help. A custom upload
 * still wins, and "None" renders nothing.
 *
 * @package    better-chat-support
 * @subpackage better-chat-support/src/Frontend
 */

// Multi-agent lists loop over agents and only set $agent_photo_url, so infer
// the type from whether a photo was supplied. Kept in its own variable because
// this template is included once per agent and must not leak a type between
// loop iterations.
$mSupport_photo_type = isset($agent_photo_type) ? $agent_photo_type : (!empty($agent_photo_url) ? 'custom' : 'default');

if ($mSupport_photo_type === 'none') {
	return;
}

if ($mSupport_photo_type === 'default' || empty($agent_photo_url)) {
	$agent_initials = Helpers::agent_initials(isset($agent_name) ? $agent_name : '');
?>
	<div class="image">
		<div class="mSupport_avatar_initials"><?php echo esc_html($agent_initials); ?></div>
	</div>
<?php
} else {
?>
	<div class="image">
		<img src="<?php echo esc_attr($agent_photo_url); ?>" />
	</div>
<?php
}
