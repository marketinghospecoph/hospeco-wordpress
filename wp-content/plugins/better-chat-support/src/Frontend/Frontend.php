<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package better-chat-support
 * @subpackage better-chat-support/Frontend
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

namespace ThemeAtelier\BetterChatSupport\Frontend;

use ThemeAtelier\BetterChatSupport\Frontend\Templates\ButtonTemplate;
use ThemeAtelier\BetterChatSupport\Frontend\Templates\items\Buttons;
use ThemeAtelier\BetterChatSupport\Frontend\Templates\SingleTemplate;
use ThemeAtelier\BetterChatSupport\Includes\Helpers;

/**
 * The Frontend class to manage all public facing stuffs.
 *
 * @since 1.0.0
 */
class Frontend
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

    public $unique_id;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name       The name of the plugin.
     * @param      string $version    The version of this plugin.
     */
    public function __construct()
    {
        $this->unique_id = uniqid();
        $this->min   = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
        add_action('wp_footer', [$this, 'better_chat_support_chat_popup']);

        add_action('wp_head', array($this, 'better_chat_support_header_script'), 1);
        add_action('login_head', array($this, 'better_chat_support_header_script'), 1);
        add_action('register_head', array($this, 'better_chat_support_header_script'), 1);
    }

    function better_chat_support_header_script()
    {
        $options = get_option('mcs-opt');
        $alternative_mSupportBubble = isset($options['alternative_mSupportBubble']) ? $options['alternative_mSupportBubble'] : "";
?>
        <script type="text/javascript" class="better_chat_support_inline_js">
            var alternativeMSupportBubble = "<?php echo esc_attr($alternative_mSupportBubble); ?>";
        </script>
    <?php
    }

    public function enqueue_scripts()
    {
        $mcs_options = get_option('mcs-opt', []);
        $mcs_settings = get_option('mcs_settings');
        $mcs_custom_css = isset($mcs_settings['mcs_custom_css']) ? $mcs_settings['mcs_custom_css'] : '';
        $mcs_custom_js = isset($mcs_settings['mcs_custom_js']) ? $mcs_settings['mcs_custom_js'] : '';
        wp_enqueue_style('icofont');
        wp_enqueue_style('mcs-main');

        /* Resolve the selected font family (legacy Codestar array or new flat key). */
        $typography = $this->get_typography_settings($mcs_options);

        /* Enqueue the Google Font BEFORE building inline CSS so the face is
           available when the font-family rule applies. */
        if (!empty($typography['family']) && $typography['is_google']) {
            $this->enqueue_google_font($typography['family']);
        }

        $custom_css = '';
        if ($mcs_custom_css) {
            $custom_css .= $mcs_custom_css;
        }

        /* Add typography CSS that depends on the font being loaded */
        if (!empty($typography['family'])) {
            $custom_css .= $this->generate_font_css($typography);
        }

        wp_add_inline_style('mcs-main', $custom_css);
        /********************
				Js Enqueue
         ********************/
        wp_enqueue_script('moment');
        wp_enqueue_script('moment-timezone');
        wp_enqueue_script('mcs-main');
        wp_enqueue_script('tma-bubble-stack');

        if (!empty($mcs_custom_js)) {
            wp_add_inline_script('mcs-main', $mcs_custom_js);
        }
    }

    /**
     * Resolve the selected font family from saved options.
     *
     * Supports both storage formats for backward compatibility:
     *   1. Legacy Codestar array — mcs-opt['better_chat_support_typography']['font-family']
     *   2. New React flat key   — mcs-opt['better_chat_support_typography_family']
     * The flat key takes precedence when set.
     *
     * @return array{family:string,backup:string,is_google:bool}
     */
    private function get_typography_settings(array $mcs_options): array
    {
        $family    = '';
        $backup    = '';
        $is_google = true;

        /* Legacy Codestar array format (existing users) */
        $legacy = isset($mcs_options['better_chat_support_typography']) && is_array($mcs_options['better_chat_support_typography'])
            ? $mcs_options['better_chat_support_typography']
            : [];

        if (!empty($legacy['font-family'])) {
            $family    = $legacy['font-family'];
            $backup    = isset($legacy['backup-font-family']) ? $legacy['backup-font-family'] : '';
            $is_google = isset($legacy['type']) ? ($legacy['type'] === 'google') : true;
        }

        /* New React flat key overrides when set */
        if (!empty($mcs_options['better_chat_support_typography_family'])) {
            $family    = $mcs_options['better_chat_support_typography_family'];
            $backup    = '';
            $is_google = isset($this->get_google_font_list()[$family]);
        }

        return compact('family', 'backup', 'is_google');
    }

    /**
     * Load the Google Fonts lookup list (family => [variants, subsets]).
     * Used only to decide whether a selected family needs the Google Fonts API.
     */
    private function get_google_font_list(): array
    {
        static $fonts = null;
        if ($fonts !== null) {
            return $fonts;
        }

        $fonts = [];
        $file  = BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Admin/google-fonts.php';
        if (file_exists($file)) {
            require_once $file;
            if (function_exists('BetterChatSupport_get_google_fonts')) {
                $fonts = BetterChatSupport_get_google_fonts();
            }
        }
        return $fonts;
    }

    /**
     * Enqueue a Google Font family at its default weight (no weight param).
     */
    private function enqueue_google_font(string $font_family): void
    {
        $font_url_family = str_replace(' ', '+', sanitize_text_field($font_family));
        $font_url = "https://fonts.googleapis.com/css2?family={$font_url_family}&display=swap";
        wp_enqueue_style('google-font-' . sanitize_key($font_family), $font_url, [], null);
    }

    /**
     * Generate font-family CSS for the chat elements.
     * Only emits font-family; weight/style are left to the chat's own stylesheet.
     *
     * @param array{family:string,backup:string} $typography
     */
    private function generate_font_css(array $typography): string
    {
        $selector = '.mSupport,.mSupport-multi,.mSupport-multi input, .advance_button, .mSupport__popup__content input, .mSupport__popup__content textarea';
        $family   = sanitize_text_field($typography['family']);
        $backup   = !empty($typography['backup']) ? ', ' . sanitize_text_field($typography['backup']) : '';

        return $selector . '{font-family:"' . $family . '"' . $backup . ';}';
    }

    public function better_chat_support_chat_popup()
    {
        $options = get_option('mcs-opt');
        $ch_settings = get_option('mcs_settings');
        self::render_widget($options, $ch_settings, $this->unique_id);
    }

    /**
     * Renders the widget's CSS-variable style block + markup for an arbitrary
     * options array. Shared by the real `wp_footer` hook above (real saved
     * options, random popup-animation seed) and the admin live-preview REST
     * endpoint (unsaved/merged options, fixed seed) — see
     * `Admin\Rest\PreviewRest`, which reuses this instead of hand-maintaining
     * a separate re-implementation of the widget markup.
     *
     * @param string   $raw_unique_id Unprefixed id; the widget root becomes
     *                                `#better_chat_support_button_{$raw_unique_id}`.
     * @param int|null $random        Popup-animation seed (1-13). Pass a fixed
     *                                value for a stable preview; null uses a
     *                                real random seed (the live-site behavior).
     */
    public static function render_widget($options, $ch_settings, string $raw_unique_id, ?int $random = null): void
    {
        $unique_id = "better_chat_support_button_$raw_unique_id";
        $enable_floating_chat = isset($options['enable_floating_chat']) ? $options['enable_floating_chat'] : '1';
        $fbId = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';
        $optAvailability = isset($options['opt-availablity']) ? $options['opt-availablity'] : '';
        $user_availability = Helpers::user_availability($optAvailability);

        $chat_type = isset($options['chat_layout']) ? $options['chat_layout'] : 'agent';
        $random = $random ?? wp_rand(1, 13);
        $select_animation = isset($options['select-animation']) ? $options['select-animation'] : 'random';
        if ('random' === $select_animation) {
            $select_animation = $random;
        }
        $bubble_visibility = isset($options['bubble-visibility']) ? $options['bubble-visibility'] : 'everywhere';
        $auto_show_popup = isset($options['autoshow-popup']) ? $options['autoshow-popup'] : '';
        if ($auto_show_popup) {
            $auto_show_popup = 'mSupport-show';
        }
        $bubble_style = isset($options['bubble-style']) ? $options['bubble-style'] : '';
        $bubble_position = isset($options['bubble-position']) ? $options['bubble-position'] : 'bottom_right';
        $select_timezone = isset($options['select-timezone']) ? $options['select-timezone'] : '';
        $header_content_position = isset($options['header-content-position']) ? $options['header-content-position'] : 'left';
        $agent_photo = isset($options['agent-photo']) ? $options['agent-photo'] : '';
        $agent_photo_url = isset($agent_photo['url']) ? $agent_photo['url'] : '';
        $agent_name = isset($options['agent-name']) ? $options['agent-name'] : '';
        $agent_subtitle = isset($options['agent-subtitle']) ? $options['agent-subtitle'] : '';
        $agent_message = isset($options['agent-message']) ? $options['agent-message'] : '';
        $before_chat_icon = isset($options['before-chat-icon']) ? $options['before-chat-icon'] : 'icofont-facebook-messenger';
        $chat_button_text = isset($options['chat-button-text']) ? $options['chat-button-text'] : 'Send a message';
        $facebook_id = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';
        $chat_agents = isset($options['opt-chat-agents']) ? $options['opt-chat-agents'] : '';

        $bubble_type = Buttons::buttons($options, $ch_settings);

        $should_display_element = Helpers::should_display_element($options);
        if ($should_display_element) {
            self::render_chat_template($chat_type, $options, $ch_settings, $bubble_type, $random, $unique_id);
        }

        $bubble_button_tooltip_background = !empty($options['bubble_button_tooltip_background']) ? $options['bubble_button_tooltip_background'] : '#f5f7f9';
        $bubble_button_tooltip_width = isset($options['bubble_button_tooltip_width']) ? $options['bubble_button_tooltip_width'] : 190;
        // Right
        $right_bottom              = isset($options['right_bottom']) ? $options['right_bottom'] : array();
        $right_bottom_value_bottom = isset($right_bottom['bottom']) ? $right_bottom['bottom'] : '25';
        $right_bottom_value_right  = isset($right_bottom['right']) ? $right_bottom['right'] : '30';
        $right_bottom_unit         = isset($right_bottom['unit']) ? $right_bottom['unit'] : 'px';

        // Left
        $left_bottom              = isset($options['left_bottom']) ? $options['left_bottom'] : array();
        $left_bottom_value_bottom = isset($left_bottom['bottom']) ? $left_bottom['bottom'] : '25';
        $left_bottom_value_left   = isset($left_bottom['left']) ? $left_bottom['left'] : '30';
        $left_bottom_unit         = isset($left_bottom['unit']) ? $left_bottom['unit'] : 'px';

        // Right Middle
        $right_middle             = isset($options['right_middle']) ? $options['right_middle'] : array();
        $right_middle_value_right = isset($right_middle['right']) ? $right_middle['right'] : '0';
        $right_middle_unit        = isset($right_middle['unit']) ? $right_middle['unit'] : 'px';

        // Left Middle
        $left_middle            = isset($options['left_middle']) ? $options['left_middle'] : array();
        $left_middle_value_left = isset($left_middle['left']) ? $left_middle['left'] : '0';
        $left_middle_unit       = isset($left_middle['unit']) ? $left_middle['unit'] : 'px';

        // Tablet positioning
        $enable_tablet_positioning = isset($options['enable-positioning-tablet']) ? $options['enable-positioning-tablet'] : '';
        $bubble_position_tablet    = isset($options['bubble-position-tablet']) ? $options['bubble-position-tablet'] : 'right_bottom';

        // Right for Tablet
        $right_bottom_tablet              = isset($options['right_bottom_tablet']) ? $options['right_bottom_tablet'] : array();
        $right_bottom_value_bottom_tablet = isset($right_bottom_tablet['bottom']) ? $right_bottom_tablet['bottom'] : '25';
        $right_bottom_value_right_tablet  = isset($right_bottom_tablet['right']) ? $right_bottom_tablet['right'] : '30';
        $right_bottom_unit_tablet         = isset($right_bottom_tablet['unit']) ? $right_bottom_tablet['unit'] : 'px';

        // Left for Tablet
        $left_bottom_tablet              = isset($options['left_bottom_tablet']) ? $options['left_bottom_tablet'] : array();
        $left_bottom_value_bottom_tablet = isset($left_bottom_tablet['bottom']) ? $left_bottom_tablet['bottom'] : '25';
        $left_bottom_value_left_tablet   = isset($left_bottom_tablet['left']) ? $left_bottom_tablet['left'] : '30';
        $left_bottom_unit_tablet         = isset($left_bottom_tablet['unit']) ? $left_bottom_tablet['unit'] : 'px';

        // Right Middle for Tablet
        $right_middle_tablet             = isset($options['right_middle_tablet']) ? $options['right_middle_tablet'] : array();
        $right_middle_value_right_tablet = isset($right_middle_tablet['right']) ? $right_middle_tablet['right'] : '0';
        $right_middle_unit_tablet        = isset($right_middle_tablet['unit']) ? $right_middle_tablet['unit'] : 'px';

        // Left Middle for Tablet
        $left_middle_tablet            = isset($options['left_middle_tablet']) ? $options['left_middle_tablet'] : array();
        $left_middle_value_left_tablet = isset($left_middle_tablet['left']) ? $left_middle_tablet['left'] : '0';
        $left_middle_unit_tablet       = isset($left_middle_tablet['unit']) ? $left_middle_tablet['unit'] : 'px';

        // Right for Mobile
        $right_bottom_mobile              = isset($options['right_bottom_mobile']) ? $options['right_bottom_mobile'] : array();
        $right_bottom_value_bottom_mobile = isset($right_bottom_mobile['bottom']) ? $right_bottom_mobile['bottom'] : '25';
        $right_bottom_value_right_mobile  = isset($right_bottom_mobile['right']) ? $right_bottom_mobile['right'] : '30';
        $right_bottom_unit_mobile         = isset($right_bottom_mobile['unit']) ? $right_bottom_mobile['unit'] : 'px';

        // Left for Mobile
        $left_bottom_mobile              = isset($options['left_bottom_mobile']) ? $options['left_bottom_mobile'] : array();
        $left_bottom_value_bottom_mobile = isset($left_bottom_mobile['bottom']) ? $left_bottom_mobile['bottom'] : '25';
        $left_bottom_value_left_mobile   = isset($left_bottom_mobile['left']) ? $left_bottom_mobile['left'] : '30';
        $left_bottom_unit_mobile         = isset($left_bottom_mobile['unit']) ? $left_bottom_mobile['unit'] : 'px';

        // Right Middle for Mobile
        $right_middle_mobile             = isset($options['right_middle_mobile']) ? $options['right_middle_mobile'] : array();
        $right_middle_value_right_mobile = isset($right_middle_mobile['right']) ? $right_middle_mobile['right'] : '0';
        $right_middle_unit_mobile        = isset($right_middle_mobile['unit']) ? $right_middle_mobile['unit'] : 'px';

        // Left Middle for Mobile
        $left_middle_mobile            = isset($options['left_middle_mobile']) ? $options['left_middle_mobile'] : array();
        $left_middle_value_left_mobile = isset($left_middle_mobile['left']) ? $left_middle_mobile['left'] : '0';
        $left_middle_unit_mobile       = isset($left_middle_mobile['unit']) ? $left_middle_mobile['unit'] : 'px';

        $color_settings = isset($options['color_settings']) ? $options['color_settings'] : '';
        $primary = !empty($color_settings['primary']) ? $color_settings['primary'] : '#0084ff';
        $secondary = !empty($color_settings['secondary']) ? $color_settings['secondary'] : '#0066ff';
    ?>
        <style type="text/css" class="better_chat_support_inline_css">
            #better_chat_support_button_<?php echo esc_attr($raw_unique_id); ?> {
                --right_bottom_value_bottom: <?php echo esc_attr($right_bottom_value_bottom . $right_bottom_unit) ?>;
                --right_bottom_value_right: <?php echo esc_attr($right_bottom_value_right . $right_bottom_unit) ?>;
                --left_bottom_value_bottom: <?php echo esc_attr($left_bottom_value_bottom . $left_bottom_unit) ?>;
                --left_bottom_value_left: <?php echo esc_attr($left_bottom_value_left . $left_bottom_unit) ?>;
                --right_middle_value_right: <?php echo esc_attr($right_middle_value_right . $right_middle_unit) ?>;
                --left_middle_value_left: <?php echo esc_attr($left_middle_value_left . $left_middle_unit) ?>;
                --enable_tablet_positioning: <?php echo esc_attr($enable_tablet_positioning . $bubble_position_tablet) ?>;
                --right_bottom_value_bottom_tablet: <?php echo esc_attr($right_bottom_value_bottom_tablet . $right_bottom_unit_tablet) ?>;
                --right_bottom_value_right_tablet: <?php echo esc_attr($right_bottom_value_right_tablet . $right_bottom_unit_tablet) ?>;
                --left_bottom_value_left_tablet: <?php echo esc_attr($left_bottom_value_left_tablet . $left_bottom_unit_tablet) ?>;
                --left_bottom_value_bottom_tablet: <?php echo esc_attr($left_bottom_value_bottom_tablet . $left_bottom_unit_tablet) ?>;
                --right_middle_value_right_tablet: <?php echo esc_attr($right_middle_value_right_tablet . $right_middle_unit_tablet) ?>;
                --left_middle_value_left_tablet: <?php echo esc_attr($left_middle_value_left_tablet . $left_middle_unit_tablet) ?>;
                --right_bottom_value_bottom_mobile: <?php echo esc_attr($right_bottom_value_bottom_mobile . $right_bottom_unit_mobile) ?>;
                --right_bottom_value_right_mobile: <?php echo esc_attr($right_bottom_value_right_mobile . $right_bottom_unit_mobile) ?>;
                --left_bottom_value_bottom_mobile: <?php echo esc_attr($left_bottom_value_bottom_mobile . $left_bottom_unit_mobile) ?>;
                --left_bottom_value_left_mobile: <?php echo esc_attr($left_bottom_value_left_mobile . $left_bottom_unit_mobile) ?>;
                --right_middle_value_right_mobile: <?php echo esc_attr($right_middle_value_right_mobile . $right_middle_unit_mobile) ?>;
                --left_middle_value_left_mobile: <?php echo esc_attr($left_middle_value_left_mobile . $left_middle_unit_mobile) ?>;
                --bubble_button_tooltip_background: <?php echo esc_attr($bubble_button_tooltip_background) ?>;
                --bubble_button_tooltip_width: <?php echo esc_attr($bubble_button_tooltip_width) ?>px;

                --mSupport-color-primary: <?php echo esc_attr($primary); ?>;
                --mSupport-color-secondary: <?php echo esc_attr($secondary); ?>;
            }
        </style>
<?php
    }

    public static function render_chat_template($chat_type, $options, $ch_settings, $bubble_type, $random, $unique_id)
    {
        $facebook_ID = isset($options['opt-fbid']) ? $options['opt-fbid'] : '';

        switch ($chat_type) {
            case 'off':
                break;
            case 'button':
                if (!empty($facebook_ID)) {
                    ButtonTemplate::buttonTemplate($options, $ch_settings, $bubble_type, $unique_id);
                }
                break;
            case 'agent':
                if (!empty($facebook_ID)) {
                    SingleTemplate::singleTemplate($options, $ch_settings, $bubble_type, $random, $unique_id, $chat_type);
                }
                break;
            default:
        }
    }
}
