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

use ThemeAtelier\BetterChatSupport\Admin\Analytics;
use ThemeAtelier\BetterChatSupport\Admin\Dashboard;
use ThemeAtelier\BetterChatSupport\Admin\SettingsController;
use ThemeAtelier\BetterChatSupport\Admin\Rest\PreviewRest;
use ThemeAtelier\BetterChatSupport\Admin\DBUpdates;
use ThemeAtelier\BetterChatSupport\Admin\Helpers\ReviewNotice;
use ThemeAtelier\BetterChatSupport\Admin\Helpers\ThemeAtelier_Offer_Banner;

/**
 * The admin class
 */
class Admin
{
    /**
     * The slug of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_slug   The slug of this plugin.
     */
    private $plugin_slug;

    /**
     * The min of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $min   The slug of this plugin.
     */
    private $min;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The class constructor.
     *
     * @param string $plugin_slug The slug of the plugin.
     * @param string $version Current version of the plugin.
     */
    function __construct($plugin_slug, $version)
    {
        $this->plugin_slug = $plugin_slug;
        $this->version     = $version;
        $this->min         = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
        // Database Updater.
        new DBUpdates();
        new ReviewNotice();
        new Analytics();
        new Dashboard();
        new SettingsController();
        new PreviewRest();
        // Admin Menu
        add_action('admin_menu', array($this, 'add_plugin_page'));

        // Process Help page plugin activate/deactivate links (React Help page).
        add_filter('admin_footer_text', array($this, 'admin_footer'), 1, 2);
        $active_plugins = get_option('active_plugins');
        foreach ($active_plugins as $active_plugin) {
            $_temp = strpos($active_plugin, 'messenger-chat-support.php');
            if (false != $_temp) {
                add_filter('plugin_action_links_' . $active_plugin, array($this, 'better_chat_support_plugin_action_links'));
                add_filter('plugin_row_meta', array($this, 'after_better_chat_support_row_meta'), 10, 4);
            }
        }

        if (! defined('THEMEATELIER_OFFER_BANNER_LOADED')) {
            define('THEMEATELIER_OFFER_BANNER_LOADED', true);
            ThemeAtelier_Offer_Banner::instance();
        }
    }

    public function add_plugin_page()
    {
        add_menu_page(
            esc_html__('Messenger', 'better-chat-support'),
            esc_html__('Messenger', 'better-chat-support'),
            'manage_options',
            'mcs',
            [$this, 'render_react_page'],
            'dashicons-format-status',
            65
        );
        add_submenu_page(
            'mcs',
            esc_html__('Dashboard', 'better-chat-support'),
            esc_html__('Dashboard', 'better-chat-support'),
            'manage_options',
            'mcs',
            [$this, 'render_react_page']
        );
        add_submenu_page(
            'mcs',
            esc_html__('Floating Chat', 'better-chat-support'),
            esc_html__('Floating Chat', 'better-chat-support'),
            'manage_options',
            'mcs-floating',
            [$this, 'render_react_page']
        );
        do_action('better_chat_support_before_upgrade_pro_menu');

        add_submenu_page(
            'mcs',
            esc_html__('Shortcodes', 'better-chat-support'),
            __('Shortcodes', 'better-chat-support'),
            'manage_options',
            'shortcodes',
            [$this, 'render_react_page']
        );
        add_submenu_page(
            'mcs',
            esc_html__('Settings', 'better-chat-support'),
            __('Settings', 'better-chat-support'),
            'manage_options',
            'settings',
            [$this, 'render_react_page']
        );
        add_submenu_page(
            'mcs',
            esc_html__('Help', 'better-chat-support'),
            __('Help', 'better-chat-support'),
            'manage_options',
            'mcs-help',
            [$this, 'render_react_page']
        );

        add_submenu_page(
            'mcs',
            esc_html__('Upgrade To Premium', 'better-chat-support'),
            sprintf(
                '<span style="color: #35b747;font-weight:bold;" class="better-chat-support-get-pro-text">%s</span>',
                __('Upgrade To Pro! 👑', 'better-chat-support')
            ),
            'manage_options',
            BETTER_CHAT_SUPPORT_DEMO_URL . '?utm_source=better_chat_support_plugin&utm_medium=submenu_page&utm_campaign=new_year_2026'
        );
        do_action('better_chat_support_after_upgrade_pro_menu');
    }

    public static function render_react_page(): void
    {
        echo '<div id="mcs_react"></div>';
    }

    public function better_chat_support_plugin_action_links($links)
    {
        $new_links = array(
            sprintf('<a href="' . esc_url(admin_url('admin.php?page=mcs')) . '">' . esc_html__('Settings', 'better-chat-support') . '</a>'),
            sprintf('<a target="_blank" href="https://themeatelier.net/create-support-ticket/">' . esc_html__('Support', 'better-chat-support') . '</a>'),
            // sprintf('<a style="font-weight: bold;color:#171717" target="_blank" href="'. BETTER_CHAT_SUPPORT_DEMO_URL .'?ref=1">' . esc_html__('Go Pro', 'better-chat-support') . '</a>'),
        );

        $links[] = sprintf('<a style="font-weight: bold;color:#35b747" target="_blank" href="' . BETTER_CHAT_SUPPORT_DEMO_URL . '?utm_source=better_chat_support_plugin&utm_medium=action_link&utm_campaign=regular">%s</a>', esc_html__('Go Pro', 'better-chat-support'));

        return array_merge($new_links, $links);
    }

    /**
     * Add plugin row meta link.
     *
     * @since 2.0
     *
     * @param array  $plugin_meta .
     * @param string $file .
     *
     * @return array
     */
    public function after_better_chat_support_row_meta($plugin_meta, $file)
    {

        if (BETTER_CHAT_SUPPORT_BASENAME === $file) {
            $plugin_meta[] = '<a href="' . BETTER_CHAT_SUPPORT_DEMO_URL . '" target="_blank">' . __('Live Demo', 'better-chat-support') . '</a>';
        }

        return $plugin_meta;
    }

    /**
     * Review Text.
     *
     * @param string $text text.
     *
     * @return string
     */
    public function admin_footer($text)
    {
        $screen = get_current_screen();
        if (
            'toplevel_page_mcs' === $screen->id
            || 'messenger_page_mcs-floating' === $screen->id
            || 'messenger_page_shortcodes' === $screen->id
            || 'messenger_page_settings' === $screen->id
            || 'messenger_page_mcs-help' === $screen->id
        ) {
            return sprintf(
                /* translators: 1: start strong tag, 2: close strong tag. 3: start link 4: close link */
                __('<i>Enjoying %1$sBetter Chat Support for Messenger?%2$s Please rate us %3$sWordPress.org%4$s. Your positive feedback will help us grow more. Thank you! 😊</i>', 'better-chat-support'),
                '<strong>',
                '</strong>',
                '<span class="better-chat-support-footer-text-star">★★★★★</span> <a href="https://wordpress.org/support/plugin/better-chat-support/reviews/#new-post" target="_blank">',
                '</a>'
            );
        }

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public static function enqueue_scripts($hook) {}
}
