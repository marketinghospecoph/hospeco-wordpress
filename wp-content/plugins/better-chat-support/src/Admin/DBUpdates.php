<?php

/**
 * The admin-facing functionality of the plugin.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package better-chat-support
 * @subpackage better-chat-support/Admin
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

namespace ThemeAtelier\BetterChatSupport\Admin;

/**
 * The admin class
 */
class DBUpdates
{
    /**
     * DB updates that need to be run
     *
     * @var array
     */
    private static $updates = array(
        '1.2.21' => 'updates/update-1.2.21.php',
        '1.3.0' => 'updates/update-1.3.0.php',
        '2.0.0' => 'updates/update-2.0.0.php',
    );
    
    /**
     * The class constructor.
     *
     */
    function __construct()
    {
        add_action('plugins_loaded', array($this, 'perform_updates'));
    }

    /**
     * Check if an update is needed.
     *
     * @return bool
     */
    public function is_needs_update() {
        $installed_version = get_option('better_chat_support_version');
        $first_version     = get_option('better_chat_support_first_version');
        $activation_date   = get_option('better_chat_support_activation_date');

        if (false === $installed_version) {
            update_option('better_chat_support_version', BETTER_CHAT_SUPPORT_VERSION);
            update_option('better_chat_support_db_version', BETTER_CHAT_SUPPORT_VERSION);
        }
        if (false === $first_version) {
            update_option('better_chat_support_first_version', BETTER_CHAT_SUPPORT_VERSION);
        }
        if (false === $activation_date) {
            update_option('better_chat_support_activation_date', current_time('timestamp'));
        }

        if (version_compare($installed_version, BETTER_CHAT_SUPPORT_VERSION, '<')) {
            return true;
        }
        return false;
    }

    /**
     * Perform all updates.
     *
     */
    public function perform_updates() {
        if (!$this->is_needs_update()) {
            return;
        }

        $installed_version = get_option('better_chat_support_version');

        foreach (self::$updates as $version => $path) {
            if (version_compare($installed_version, $version, '<')) {
                include $path;
                update_option('better_chat_support_version', $version);
            }
        }

        update_option('better_chat_support_version', BETTER_CHAT_SUPPORT_VERSION);
    }
}
