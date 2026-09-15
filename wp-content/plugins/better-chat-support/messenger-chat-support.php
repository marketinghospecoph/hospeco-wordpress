<?php
/*
 *  Plugin Name:    Better Chat Support For Messenger
 *  Plugin URI:     https://themeatelier.net/downloads/better-chat-support-for-messenger/
 *  Description:    Can easily create Bubble & buttons for messenger chat in any WordPress site. Gutenberg, Elementor and shortcodes supported. 
 *  Author:         ThemeAtelier
 *  Author URI:     http://themeatelier.net/
 *  Requirements:   PHP 5.2.4 or above, WordPress 3.3 or above.
 *  Version:        2.4.0
 * Text Domain:     better-chat-support
 * Domain Path:     /languages
 */

// Block Direct access

use ThemeAtelier\BetterChatSupport\Includes\BetterChatSupport;

if (!defined('ABSPATH')) {
    die;
}
require_once __DIR__ . '/vendor/autoload.php';

if (!defined('BETTER_CHAT_SUPPORT_VERSION')) {
    define('BETTER_CHAT_SUPPORT_VERSION', '2.4.0');
}
if (!defined('BETTER_CHAT_SUPPORT_DIRNAME')) {
    define('BETTER_CHAT_SUPPORT_DIRNAME', dirname(__FILE__));
}
if (!defined('BETTER_CHAT_SUPPORT_DIR_PATH')) {
    define('BETTER_CHAT_SUPPORT_DIR_PATH', plugin_dir_path(__FILE__));
}
if (!defined('BETTER_CHAT_SUPPORT_BASENAME')) {
    define('BETTER_CHAT_SUPPORT_BASENAME', plugin_basename(__FILE__));
}
if (!defined('BETTER_CHAT_SUPPORT_DIR_URL')) {
    define('BETTER_CHAT_SUPPORT_DIR_URL', plugin_dir_url(__FILE__));
}

if (!defined('BETTER_CHAT_SUPPORT_DEMO_URL')) {
    define('BETTER_CHAT_SUPPORT_DEMO_URL', 'https://themeatelier.net/downloads/better-chat-support-for-messenger/');
}
if (!defined('BETTER_CHAT_SUPPORT_DOCS_URL')) {
    define('BETTER_CHAT_SUPPORT_DOCS_URL', 'https://docs.themeatelier.net/docs/better-chat-support-pro/');
}

function better_chat_support()
{
    // Launch the plugin.
    $better_chat_support = new BetterChatSupport();
    $better_chat_support->run();
}

/**
 * Pro version check.
 *
 * @return boolean
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';
if (! (is_plugin_active('better-chat-support-pro/better-chat-support-pro.php') || is_plugin_active_for_network('better-chat-support-pro/better-chat-support-pro.php'))) {
    better_chat_support();
    // Register block
    function create_block_better_chat_support_init()
    {
        register_block_type_from_metadata(BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Frontend/blocks/');
    }
    add_action('init', 'create_block_better_chat_support_init');

    // Register block category
    function better_chat_support_plugin_block_categories($categories)
    {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'better-chat-support-block',
                    'title' => __('Better Chat Support block', 'better-chat-support'),
                ],
            ]
        );
    }
    add_action('block_categories_all', 'better_chat_support_plugin_block_categories', 10, 2);
}


/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function better_chat_support_appsero_init()
{

    if (!class_exists('MessengerChatSupportAppSero\Insights')) {
        require_once BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Admin/appsero/Client.php';
    }
    $client = new MessengerChatSupportAppSero\Client('1becaa75-d9c0-4be4-bdba-311b0a0c5550', 'Messenger chat support', __FILE__);
    // Active insights
    $client->insights()->init();
}

better_chat_support_appsero_init();
