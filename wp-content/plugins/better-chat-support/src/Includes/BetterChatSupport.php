<?php

/**
 * The file of the BetterChatSupport class.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package BetterChatSupport
 */

namespace ThemeAtelier\BetterChatSupport\Includes;

use ThemeAtelier\BetterChatSupport\Includes\Loader;
use ThemeAtelier\BetterChatSupport\Includes\Helpers;
use ThemeAtelier\BetterChatSupport\Admin\Admin;
use ThemeAtelier\BetterChatSupport\Frontend\Frontend;
use ThemeAtelier\BetterChatSupport\Frontend\Shortcode\CustomShortcode;

// don't call the file directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The main class of the plugin.
 *
 * Handle all the class and methods of the plugin.
 *
 * @author     ThemeAtelier <themeatelierbd@gmail.com>
 */
class BetterChatSupport
{
    /**
     * Plugin version
     *
     * @since    1.0.0
     * @access   protected
     * @var string
     */
    protected $version;

    /**
     * Plugin slug
     *
     * @since    1.0.0
     * @access   protected
     * @var string
     */
    protected $plugin_slug;

    /**
     * The min of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $min   The slug of this plugin.
     */
    private $min;

    /**
     * Main Loader.
     *
     * The loader that's responsible for maintaining and registering all hooks that empowers
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var object
     */
    protected $loader;
    /**
     * Constructor for the BetterChatSupport class.
     *
     * Sets up all the appropriate hooks and actions within the plugin.
     */
    public function __construct()
    {
        $this->version     = BETTER_CHAT_SUPPORT_VERSION;
        $this->plugin_slug = 'better-chat-support';
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_actions();
        $this->min = (apply_filters('better_chat_support_dev_mode', false) || WP_DEBUG) ? '' : '.min';
        add_action('plugins_loaded', array($this, 'better_chat_support_load_textdomain'));
        add_action('plugin_loaded', array($this, 'init_plugin'));
        add_action('admin_enqueue_scripts', array($this, 'backend_enqueue_scripts'));

        add_action('elementor/widgets/register', [$this, 'register_better_chat_support_widget']);
    }

    public function better_chat_support_redirect_to($plugin)
    {
        // deactivate_plugins('better_chat_support/better_chat_support.php');

        if (BETTER_CHAT_SUPPORT_BASENAME === $plugin) {
            $redirect_url = esc_url(admin_url('admin.php?page=mcs-floating#/floating'));
            exit(wp_kses_post(wp_safe_redirect($redirect_url)));
        }
    }


    public function init_plugin()
    {
        do_action('better_chat_support_loaded');
        load_plugin_textdomain('better-chat-support', false, BETTER_CHAT_SUPPORT_DIRNAME . "/languages");
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The slug of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    // load text domain from plugin folder
    public function better_chat_support_load_textdomain()
    {
        load_plugin_textdomain('better-chat-support', false,  BETTER_CHAT_SUPPORT_DIRNAME . '/languages');
    }

    public function register_better_chat_support_widget($widgets_manager)
    {
        require_once BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Frontend/elementor-widgets/elementor-widget.php';
        $widgets_manager->register(new \Elementor_MCS_Buttons());
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Loader. Orchestrates the hooks of the plugin.
     * - Teamproi18n. Defines internationalization functionality.
     * - Admin. Defines all hooks for the admin area.
     * - Frontend. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        $this->loader = new Loader();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new Frontend($this->get_plugin_slug(), $this->get_version());
        $plugin_helpers = new Helpers();
        $button_shortcode = new CustomShortcode();

        $this->loader->add_action('wp_loaded', $plugin_helpers, 'register_all_scripts');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('mcs', $button_shortcode, 'mcs_custom_buttons_shortcode');
        $this->loader->add_shortcode('better_chat_support', $button_shortcode, 'mcs_custom_buttons_shortcode');
    }

    // Backend enqueue scripts
    public function backend_enqueue_scripts($hook)
    {
        wp_enqueue_style('better-chat-support-review-notice', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Admin/Helpers/assets/css/review-notice.css', array(), BETTER_CHAT_SUPPORT_VERSION, false);
    }

    /**
     * Register all of the hooks related to the admin dashboard functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin   = new Admin($this->get_plugin_slug(), $this->get_version());
        $plugin_helpers = new Helpers();
        $this->loader->add_action('wp_loaded', $plugin_helpers, 'register_all_scripts');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Initialize WordPress action hooks
     *
     * @return void
     */
    private function init_actions()
    {
        add_action('activated_plugin', array($this, 'better_chat_support_redirect_to'));
    }
}
