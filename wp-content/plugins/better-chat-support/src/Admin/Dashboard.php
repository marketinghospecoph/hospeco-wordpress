<?php

namespace ThemeAtelier\BetterChatSupport\Admin;

if (! defined('ABSPATH')) {
    die;
}

class Dashboard
{
    /** WordPress screen IDs where we load the React admin app. */
    private const REACT_SCREENS = [
        'toplevel_page_mcs',
        'messenger_page_mcs-floating',
        'messenger_page_shortcodes',
        'messenger_page_settings',
        'messenger_page_mcs-help',
    ];

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 100);
        add_action('admin_head', [$this, 'suppress_notices_and_fix_layout']);
    }

    public function suppress_notices_and_fix_layout(): void
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, self::REACT_SCREENS, true)) {
            return;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');

        // Resolve the saved theme BEFORE the first paint.
        //
        // ThemeProvider adds `light`/`dark` to <html> from a useEffect, which
        // only runs after React has mounted and painted — so a dark-mode admin
        // rendered one full light frame first and visibly flashed white. This
        // runs synchronously while <head> is parsed, so the very first paint is
        // already in the right theme; the provider's effect then sets the same
        // class and changes nothing.
        //
        // Mirrors ThemeProvider's contract exactly: storage key `mcs-theme`,
        // values light|dark|system, default light, `system` resolved from the OS
        // preference. Wrapped in try/catch because localStorage throws in some
        // privacy modes — the admin must still load if it does.
        echo '<script id="mcs-theme-boot">'
            . '(function(){try{'
            . 'var t=localStorage.getItem("mcs-theme")||"light";'
            . 'if(t==="system"){t=window.matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}'
            . 'document.documentElement.classList.add(t==="dark"?"dark":"light");'
            . '}catch(e){}})();'
            . '</script>';

        // Stabilise the initial paint so the WP admin menu does not flicker/shift
        // when React mounts. Reserving the app height keeps a vertical scrollbar
        // present from the first paint (no width shift), and scrollbar-gutter is a
        // belt-and-braces guard against scrollbar-induced reflow.
        //
        // The app root and the surrounding wp-admin chrome are painted here too,
        // keyed off the class the boot script just set — the bundled stylesheet
        // is what normally colours them, and until it applies the browser would
        // otherwise paint WordPress's light chrome behind a dark app.
        echo '<style id="mcs-admin-stabilize">'
            . 'html{scrollbar-gutter:stable;}'
            . '#mcs_react{background:#f0f0f1;}'
            . 'html.dark #mcs_react,'
            . 'html.dark body.wp-admin,'
            . 'html.dark #wpwrap,'
            . 'html.dark #wpcontent,'
            . 'html.dark #wpbody,'
            . 'html.dark #wpbody-content,'
            . 'html.dark #wpfooter{background-color:#0f1216;}'
            . '</style>';
    }

    public function enqueue_scripts(string $hook): void
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, self::REACT_SCREENS, true)) {
            return;
        }

        wp_dequeue_style('common');
        wp_deregister_style('common-css');

        // Make WP media uploader available on all plugin pages.
        wp_enqueue_media();

        // IcoFont stylesheet so the React icon picker can render icon previews.
        // Registered in Helpers::register_all_scripts() on the wp_loaded hook.
        wp_enqueue_style('icofont');

        // The React Help page reuses the original Help page stylesheet so the
        // static sections (Get Started, Lite vs Pro, Pro Plugins, footer) keep
        // their look. It is enqueued on ALL React screens (not just the Help
        // screen) because the SPA can navigate to Help from any page without a
        // full reload — gating it to the Help screen would leave it unstyled on
        // client-side navigation. All its selectors are help-scoped, so it is
        // inert on other pages.
        // Load the extracted Tailwind/React CSS before the first paint so the
        // browser doesn't need to repaint after the JS bundle injects styles.
        wp_enqueue_style(
            'better-chat-support-admin-dashboard',
            BETTER_CHAT_SUPPORT_DIR_URL . 'src/Admin/assets/css/better-chat-support-admin.css',
            [],
            BETTER_CHAT_SUPPORT_VERSION
        );

        $min = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
        wp_enqueue_style(
            'better-chat-support-help-page',
            BETTER_CHAT_SUPPORT_DIR_URL . 'src/Admin/HelpPage/assets/css/help-page' . $min . '.css',
            [],
            BETTER_CHAT_SUPPORT_VERSION
        );

        $rest_url = esc_url_raw(rest_url('better-chat-support/v1'));
        $nonce    = wp_create_nonce('wp_rest');

        $options         = get_option('mcs-opt', []);
        $has_global_chat = !empty($options['opt-fbid']);

        // Preload the saved chat options inline so the React Floating Chat page can
        // initialise its state synchronously on first paint — no REST round-trip,
        // no spinner, and no Single→Multi layout flicker on reload. This mirrors
        // exactly what GET /settings/mcs-opt returns (including the default-agent
        // seeding for fresh installs) so the async fetch never changes the result.
        $preload_opt = is_array($options) ? $options : [];
        if (!array_key_exists('opt-chat-agents', $preload_opt)) {
            $preload_opt['opt-chat-agents'] = SettingsController::default_agents();
        }

        // Also preload the general settings (mcs_settings) so the Settings page
        // renders instantly on reload without waiting on its own REST fetch.
        $preload_settings = get_option('mcs_settings', []);
        if (!is_array($preload_settings)) {
            $preload_settings = [];
        }

        add_action('admin_print_scripts', function () use ($rest_url, $nonce, $screen, $has_global_chat, $preload_opt, $preload_settings) {
            $data = [
                'restUrl'     => $rest_url,
                'nonce'       => $nonce,
                'version'     => BETTER_CHAT_SUPPORT_VERSION,
                'adminUrl'    => esc_url(admin_url('admin.php')),
                'currentPage' => $screen->id,
                'pluginUrl'   => esc_url(BETTER_CHAT_SUPPORT_DIR_URL),
                'userName'    => wp_get_current_user()->first_name ?: wp_get_current_user()->display_name,
                // Prefill the onboarding newsletter opt-in with the admin's email.
                'adminEmail'  => sanitize_email(wp_get_current_user()->user_email),
                'docsUrl'     => esc_url(defined('BETTER_CHAT_SUPPORT_DOCS_URL') ? BETTER_CHAT_SUPPORT_DOCS_URL : ''),
                'demoUrl'     => esc_url(defined('BETTER_CHAT_SUPPORT_DEMO_URL') ? BETTER_CHAT_SUPPORT_DEMO_URL : ''),
                // Free build: analytics (Traffic Breakdown) is a locked Pro feature.
                'isPro'       => false,
                // First-run setup wizard signal: true until a Facebook ID is saved.
                // The React Floating Chat page shows the animated onboarding overlay
                // when this is true (and the user has not dismissed it locally).
                'onboarding'  => ! $has_global_chat,
                'strings'     => [
                    // LayoutPresetField
                    'Demo'                      => esc_html__('Demo', 'better-chat-support'),
                    // SelectField
                    'Select…'                   => esc_html__('Select…', 'better-chat-support'),
                    'Search…'                   => esc_html__('Search…', 'better-chat-support'),
                    'No results found.'         => esc_html__('No results found.', 'better-chat-support'),
                    'result'                    => esc_html__('result', 'better-chat-support'),
                    'results'                   => esc_html__('results', 'better-chat-support'),
                    // MultiSelectField
                    'Select items…'             => esc_html__('Select items…', 'better-chat-support'),
                    'No items available.'       => esc_html__('No items available.', 'better-chat-support'),
                    // MediaField
                    'WordPress media library is not available.' => esc_html__('WordPress media library is not available.', 'better-chat-support'),
                    'Select Image'              => esc_html__('Select Image', 'better-chat-support'),
                    'Use this image'            => esc_html__('Use this image', 'better-chat-support'),
                    'Change Image'              => esc_html__('Change Image', 'better-chat-support'),
                    'Remove'                    => esc_html__('Remove', 'better-chat-support'),
                    'Upload or select an image' => esc_html__('Upload or select an image', 'better-chat-support'),
                    'Preview'                   => esc_html__('Preview', 'better-chat-support'),
                    // SpacingField
                    'Top'                       => esc_html__('Top', 'better-chat-support'),
                    'Right'                     => esc_html__('Right', 'better-chat-support'),
                    'Bottom'                    => esc_html__('Bottom', 'better-chat-support'),
                    'Left'                      => esc_html__('Left', 'better-chat-support'),
                    'Unit'                      => esc_html__('Unit', 'better-chat-support'),
                    // FormField
                    'Live Demo'                 => esc_html__('Live Demo', 'better-chat-support'),
                    'Open Docs'                 => esc_html__('Open Docs', 'better-chat-support'),
                    // SwitcherField
                    'Enabled'                   => esc_html__('Enabled', 'better-chat-support'),
                    'Disabled'                  => esc_html__('Disabled', 'better-chat-support'),
                    // IconField
                    'Add Icon'                  => esc_html__('Add Icon', 'better-chat-support'),
                    'Remove Icon'               => esc_html__('Remove Icon', 'better-chat-support'),
                    'No icon selected'          => esc_html__('No icon selected', 'better-chat-support'),
                    'Close'                     => esc_html__('Close', 'better-chat-support'),
                    'Search icons…'             => esc_html__('Search icons…', 'better-chat-support'),
                    'No icons match "%s".'      => esc_html__('No icons match "%s".', 'better-chat-support'),
                    'Showing %1$s of %2$s icons' => esc_html__('Showing %1$s of %2$s icons', 'better-chat-support'),
                    // AgentGroupField
                    'I am online'               => esc_html__('I am online', 'better-chat-support'),
                    'I am offline'              => esc_html__('I am offline', 'better-chat-support'),
                    'Drag to reorder'           => esc_html__('Drag to reorder', 'better-chat-support'),
                    'Duplicate agent'           => esc_html__('Duplicate agent', 'better-chat-support'),
                    'Remove agent'              => esc_html__('Remove agent', 'better-chat-support'),
                    'Agent Name'                => esc_html__('Agent Name', 'better-chat-support'),
                    'Designation'               => esc_html__('Designation', 'better-chat-support'),
                    'Facebook ID'               => esc_html__('Facebook ID', 'better-chat-support'),
                    'Timezone'                  => esc_html__('Timezone', 'better-chat-support'),
                    'Online Text'               => esc_html__('Online Text', 'better-chat-support'),
                    'Offline Text'              => esc_html__('Offline Text', 'better-chat-support'),
                    'Agent Photo'               => esc_html__('Agent Photo', 'better-chat-support'),
                    'Availability'              => esc_html__('Availability', 'better-chat-support'),
                    'Select timezone…'          => esc_html__('Select timezone…', 'better-chat-support'),
                    'Add Agent'                 => esc_html__('Add Agent', 'better-chat-support'),
                    'Agent'                     => esc_html__('Agent', 'better-chat-support'),
                    // AvailabilityField
                    'Sunday'                    => esc_html__('Sunday', 'better-chat-support'),
                    'Monday'                    => esc_html__('Monday', 'better-chat-support'),
                    'Tuesday'                   => esc_html__('Tuesday', 'better-chat-support'),
                    'Wednesday'                 => esc_html__('Wednesday', 'better-chat-support'),
                    'Thursday'                  => esc_html__('Thursday', 'better-chat-support'),
                    'Friday'                    => esc_html__('Friday', 'better-chat-support'),
                    'Saturday'                  => esc_html__('Saturday', 'better-chat-support'),
                    'Sun'                       => esc_html__('Sun', 'better-chat-support'),
                    'Mon'                       => esc_html__('Mon', 'better-chat-support'),
                    'Tue'                       => esc_html__('Tue', 'better-chat-support'),
                    'Wed'                       => esc_html__('Wed', 'better-chat-support'),
                    'Thu'                       => esc_html__('Thu', 'better-chat-support'),
                    'Fri'                       => esc_html__('Fri', 'better-chat-support'),
                    'Sat'                       => esc_html__('Sat', 'better-chat-support'),
                    'Cancel'                    => esc_html__('Cancel', 'better-chat-support'),
                    'Ok'                        => esc_html__('Ok', 'better-chat-support'),
                    'Offline'                   => esc_html__('Offline', 'better-chat-support'),
                    'Online'                    => esc_html__('Online', 'better-chat-support'),
                    'Mark offline'              => esc_html__('Mark offline', 'better-chat-support'),
                    'Mark online'               => esc_html__('Mark online', 'better-chat-support'),
                    'Mark this day as offline (unavailable)' => esc_html__('Mark this day as offline (unavailable)', 'better-chat-support'),
                    'Mark this day as online (always available)' => esc_html__('Mark this day as online (always available)', 'better-chat-support'),
                    'From'                      => esc_html__('From', 'better-chat-support'),
                    'To'                        => esc_html__('To', 'better-chat-support'),
                    // ButtonSetField / CheckboxGroupField / LayoutPresetField (Free Pro locks)
                    'Available in Pro'          => esc_html__('Available in Pro', 'better-chat-support'),
                    'PRO'                       => esc_html__('PRO', 'better-chat-support'),
                    // DevicesSection
                    'Devices'                   => esc_html__('Devices', 'better-chat-support'),
                    'Desktop'                   => esc_html__('Desktop', 'better-chat-support'),
                    'Mobile'                    => esc_html__('Mobile', 'better-chat-support'),
                    'Others'                    => esc_html__('Others', 'better-chat-support'),
                    'View'                      => esc_html__('View', 'better-chat-support'),
                    'Conversion'                => esc_html__('Conversion', 'better-chat-support'),
                    'Visitors'                  => esc_html__('Visitors', 'better-chat-support'),
                    // TablesSection
                    'Traffic Breakdown'         => esc_html__('Traffic Breakdown', 'better-chat-support'),
                    'Countries'                 => esc_html__('Countries', 'better-chat-support'),
                    'Pages'                     => esc_html__('Pages', 'better-chat-support'),
                    'Browsers'                  => esc_html__('Browsers', 'better-chat-support'),
                    'Country'                   => esc_html__('Country', 'better-chat-support'),
                    'Page URL'                  => esc_html__('Page URL', 'better-chat-support'),
                    'Browser'                   => esc_html__('Browser', 'better-chat-support'),
                    'Views'                     => esc_html__('Views', 'better-chat-support'),
                    'No data'                   => esc_html__('No data', 'better-chat-support'),
                    'Pro Feature'               => esc_html__('Pro Feature', 'better-chat-support'),
                    'Upgrade to Pro'            => esc_html__('Upgrade to Pro', 'better-chat-support'),
                    // Shared header page titles
                    'Dashboard'                 => esc_html__('Dashboard', 'better-chat-support'),
                    'Floating Chat'             => esc_html__('Floating Chat', 'better-chat-support'),
                    'Shortcodes'                => esc_html__('Shortcodes', 'better-chat-support'),
                    'Settings'                  => esc_html__('Settings', 'better-chat-support'),
                    'Help'                      => esc_html__('Help', 'better-chat-support'),
                    // Dashboard page
                    'Analytics'                 => esc_html__('Analytics', 'better-chat-support'),
                    'This Week'                 => esc_html__('This Week', 'better-chat-support'),
                    'Last Week'                 => esc_html__('Last Week', 'better-chat-support'),
                    'This Month'                => esc_html__('This Month', 'better-chat-support'),
                    'Last Month'                => esc_html__('Last Month', 'better-chat-support'),
                    'This Year'                 => esc_html__('This Year', 'better-chat-support'),
                    'Last Year'                 => esc_html__('Last Year', 'better-chat-support'),
                    'Custom Date'               => esc_html__('Custom Date', 'better-chat-support'),
                    'Support'                   => esc_html__('Support', 'better-chat-support'),
                    'Visitor'                   => esc_html__('Visitor', 'better-chat-support'),
                    'Conversion Rate'           => esc_html__('Conversion Rate', 'better-chat-support'),
                    // Settings page
                    'Advance'                   => esc_html__('Advance', 'better-chat-support'),
                    'Save Settings'             => esc_html__('Save Settings', 'better-chat-support'),
                    'Loading editor…'           => esc_html__('Loading editor…', 'better-chat-support'),
                    'Saving…'                   => esc_html__('Saving…', 'better-chat-support'),
                    'Changes Saved'             => esc_html__('Changes Saved', 'better-chat-support'),
                    'Reset Section'             => esc_html__('Reset Section', 'better-chat-support'),
                    'Failed to load settings.'  => esc_html__('Failed to load settings.', 'better-chat-support'),
                    'Save failed. Please try again.' => esc_html__('Save failed. Please try again.', 'better-chat-support'),
                    'Network error. Please try again.' => esc_html__('Network error. Please try again.', 'better-chat-support'),
                    'Network error.'            => esc_html__('Network error.', 'better-chat-support'),
                    'Reset this section to default values?' => esc_html__('Reset this section to default values?', 'better-chat-support'),
                    'Clean-up Data on Deletion' => esc_html__('Clean-up Data on Deletion', 'better-chat-support'),
                    'Completely remove all plugin data when the plugin is deleted.' => esc_html__('Completely remove all plugin data when the plugin is deleted.', 'better-chat-support'),
                    'Open in New Tab'           => esc_html__('Open in New Tab', 'better-chat-support'),
                    'Open the Messenger chat link in a new browser tab when clicked.' => esc_html__('Open the Messenger chat link in a new browser tab when clicked.', 'better-chat-support'),
                    'Custom CSS'                => esc_html__('Custom CSS', 'better-chat-support'),
                    'Add your own CSS to override or extend the default chat styling.' => esc_html__('Add your own CSS to override or extend the default chat styling.', 'better-chat-support'),
                    '/* Your custom CSS here */' => esc_html__('/* Your custom CSS here */', 'better-chat-support'),
                    'Custom JavaScript'         => esc_html__('Custom JavaScript', 'better-chat-support'),
                    'Add your own JavaScript to extend or customise chat behaviour.' => esc_html__('Add your own JavaScript to extend or customise chat behaviour.', 'better-chat-support'),
                    '// Your custom JavaScript here' => esc_html__('// Your custom JavaScript here', 'better-chat-support'),
                    // FloatingChat page — layout/theme/icon labels
                    'Choose Your Chat Experience' => esc_html__('Choose Your Chat Experience', 'better-chat-support'),
                    'Chat is disabled. You can still use shortcodes and Gutenberg blocks.' => esc_html__('Chat is disabled. You can still use shortcodes and Gutenberg blocks.', 'better-chat-support'),
                    'Disable Chat'              => esc_html__('Disable Chat', 'better-chat-support'),
                    'Single Agent'              => esc_html__('Single Agent', 'better-chat-support'),
                    'Chat Button'               => esc_html__('Chat Button', 'better-chat-support'),
                    'Multi-Agent List'          => esc_html__('Multi-Agent List', 'better-chat-support'),
                    'Multi-Agent Grid'          => esc_html__('Multi-Agent Grid', 'better-chat-support'),
                    'Icon Button'               => esc_html__('Icon Button', 'better-chat-support'),
                    'Simple Button'             => esc_html__('Simple Button', 'better-chat-support'),
                    'Advance Button'            => esc_html__('Advance Button', 'better-chat-support'),
                    'Flat Theme'                => esc_html__('Flat Theme', 'better-chat-support'),
                    'Custom Theme'              => esc_html__('Custom Theme', 'better-chat-support'),
                    'Messenger'                 => esc_html__('Messenger', 'better-chat-support'),
                    'Live Support'              => esc_html__('Live Support', 'better-chat-support'),
                    'Chat'                      => esc_html__('Chat', 'better-chat-support'),
                    'Telegram'                  => esc_html__('Telegram', 'better-chat-support'),
                    'Paper Plane'               => esc_html__('Paper Plane', 'better-chat-support'),
                    'Close Line'                => esc_html__('Close Line', 'better-chat-support'),
                    'Close Circle'              => esc_html__('Close Circle', 'better-chat-support'),
                    'UI Close'                  => esc_html__('UI Close', 'better-chat-support'),
                    'Close Square'              => esc_html__('Close Square', 'better-chat-support'),
                    'Native'                    => esc_html__('Native', 'better-chat-support'),
                    'Custom'                    => esc_html__('Custom', 'better-chat-support'),
                    'No Icon'                   => esc_html__('No Icon', 'better-chat-support'),
                    // FloatingChat — sidebar section labels
                    'General'                   => esc_html__('General', 'better-chat-support'),
                    'Header & Footer'           => esc_html__('Header & Footer', 'better-chat-support'),
                    'Button'                    => esc_html__('Button', 'better-chat-support'),
                    'Style'                     => esc_html__('Style', 'better-chat-support'),
                    'Others'                    => esc_html__('Others', 'better-chat-support'),
                    'Backup'                    => esc_html__('Backup', 'better-chat-support'),
                    // FloatingChat — section headings
                    'Multi-Agent Settings'      => esc_html__('Multi-Agent Settings', 'better-chat-support'),
                    'Box Header'                => esc_html__('Box Header', 'better-chat-support'),
                    'Send Message Button'       => esc_html__('Send Message Button', 'better-chat-support'),
                    'Box Footer'                => esc_html__('Box Footer', 'better-chat-support'),
                    'Button Style'              => esc_html__('Button Style', 'better-chat-support'),
                    'Status Text'               => esc_html__('Status Text', 'better-chat-support'),
                    'Icon Button Icons'         => esc_html__('Icon Button Icons', 'better-chat-support'),
                    'Simple Button Icons'       => esc_html__('Simple Button Icons', 'better-chat-support'),
                    'Button Size'               => esc_html__('Button Size', 'better-chat-support'),
                    'Colors'                    => esc_html__('Colors', 'better-chat-support'),
                    'Border & Padding'          => esc_html__('Border & Padding', 'better-chat-support'),
                    'Notification Badge'        => esc_html__('Notification Badge', 'better-chat-support'),
                    'Button Tooltip'            => esc_html__('Button Tooltip', 'better-chat-support'),
                    'Position'                  => esc_html__('Position', 'better-chat-support'),
                    'Popup Behaviour'           => esc_html__('Popup Behaviour', 'better-chat-support'),
                    'Color Scheme'              => esc_html__('Color Scheme', 'better-chat-support'),
                    'Typography'                => esc_html__('Typography', 'better-chat-support'),
                    'Other'                     => esc_html__('Other', 'better-chat-support'),
                    'Device Visibility'         => esc_html__('Device Visibility', 'better-chat-support'),
                    'Content Visibility'        => esc_html__('Content Visibility', 'better-chat-support'),
                    'Export'                    => esc_html__('Export', 'better-chat-support'),
                    'Import'                    => esc_html__('Import', 'better-chat-support'),
                    // FloatingChat — FormField labels and help text
                    'Add your profile or page ID to receive messages.' => esc_html__('Add your profile or page ID to receive messages.', 'better-chat-support'),
                    'Test Link'              => esc_html__('Test Link', 'better-chat-support'),
                    'Enter a Facebook username to test' => esc_html__('Enter a Facebook username to test', 'better-chat-support'),
                    'Current Time'              => esc_html__('Current Time', 'better-chat-support'),
                    'Enable to display the current time before the agent\'s message.' => esc_html__('Enable to display the current time before the agent\'s message.', 'better-chat-support'),
                    'Message From Agent'        => esc_html__('Message From Agent', 'better-chat-support'),
                    'Add a custom message to display inside the agent\'s message box.' => esc_html__('Add a custom message to display inside the agent\'s message box.', 'better-chat-support'),
                    'Select your local timezone. Availability schedules will be applied based on this timezone.' => esc_html__('Select your local timezone. Availability schedules will be applied based on this timezone.', 'better-chat-support'),
                    'Set your daily availability using 24-hour format (e.g. 09:00 to 18:00). To mark a full day as offline, set both From and To values to 00:00.' => esc_html__('Set your daily availability using 24-hour format (e.g. 09:00 to 18:00). To mark a full day as offline, set both From and To values to 00:00.', 'better-chat-support'),
                    'Show Search Field'         => esc_html__('Show Search Field', 'better-chat-support'),
                    'Show or hide the search box. This helps visitors quickly find an agent when you have many agents.' => esc_html__('Show or hide the search box. This helps visitors quickly find an agent when you have many agents.', 'better-chat-support'),
                    'Chat Agents'               => esc_html__('Chat Agents', 'better-chat-support'),
                    'Add and manage your chat agents. Each agent can have a name, photo, Facebook ID, pre-filled message, timezone, availability, designation, and custom online/offline text.' => esc_html__('Add and manage your chat agents. Each agent can have a name, photo, Facebook ID, pre-filled message, timezone, availability, designation, and custom online/offline text.', 'better-chat-support'),
                    'Agent Photo Type'          => esc_html__('Agent Photo Type', 'better-chat-support'),
                    'Choose how the agent photo is displayed — Default uses the built-in photo, Custom lets you upload your own, None shows no photo.' => esc_html__('Choose how the agent photo is displayed — Default uses the built-in photo, Custom lets you upload your own, None shows no photo.', 'better-chat-support'),
                    'Upload an agent photo to display inside the chat bubble.' => esc_html__('Upload an agent photo to display inside the chat bubble.', 'better-chat-support'),
                    'Enter the agent\'s name to display inside the chat bubble.' => esc_html__('Enter the agent\'s name to display inside the chat bubble.', 'better-chat-support'),
                    'Online Subtitle'           => esc_html__('Online Subtitle', 'better-chat-support'),
                    'Enter a subtitle to display below the agent\'s name in the chat bubble.' => esc_html__('Enter a subtitle to display below the agent\'s name in the chat bubble.', 'better-chat-support'),
                    'Offline Subtitle'          => esc_html__('Offline Subtitle', 'better-chat-support'),
                    'Enter a subtitle to display when the agent is offline in the chat bubble.' => esc_html__('Enter a subtitle to display when the agent is offline in the chat bubble.', 'better-chat-support'),
                    'Header Content Alignment'  => esc_html__('Header Content Alignment', 'better-chat-support'),
                    'Choose the alignment for the header content.' => esc_html__('Choose the alignment for the header content.', 'better-chat-support'),
                    'Icon for Send Message Button' => esc_html__('Icon for Send Message Button', 'better-chat-support'),
                    'Select an icon to display before the send message button text.' => esc_html__('Select an icon to display before the send message button text.', 'better-chat-support'),
                    'Native Icon'               => esc_html__('Native Icon', 'better-chat-support'),
                    'Custom Icon Image'         => esc_html__('Custom Icon Image', 'better-chat-support'),
                    'Send Message Button Label' => esc_html__('Send Message Button Label', 'better-chat-support'),
                    'Enter the text to display on the send message button.' => esc_html__('Enter the text to display on the send message button.', 'better-chat-support'),
                    'Bubble Title'              => esc_html__('Bubble Title', 'better-chat-support'),
                    'Enter the main title text to display at the top of the chat bubble.' => esc_html__('Enter the main title text to display at the top of the chat bubble.', 'better-chat-support'),
                    'Bubble Subtitle'           => esc_html__('Bubble Subtitle', 'better-chat-support'),
                    'Enter a subtitle to display below the main title inside the chat bubble.' => esc_html__('Enter a subtitle to display below the main title inside the chat bubble.', 'better-chat-support'),
                    'Footer Content'            => esc_html__('Footer Content', 'better-chat-support'),
                    'Enable or disable the footer text below the chat box. You can also replace the default text with your own custom message.' => esc_html__('Enable or disable the footer text below the chat box. You can also replace the default text with your own custom message.', 'better-chat-support'),
                    'Custom Footer Text'        => esc_html__('Custom Footer Text', 'better-chat-support'),
                    'Text or HTML shown in the footer when footer content is enabled.' => esc_html__('Text or HTML shown in the footer when footer content is enabled.', 'better-chat-support'),
                    'Floating Button Style'     => esc_html__('Floating Button Style', 'better-chat-support'),
                    'Choose a style for the floating chat button.' => esc_html__('Choose a style for the floating chat button.', 'better-chat-support'),
                    'Agent Avatar Type'         => esc_html__('Agent Avatar Type', 'better-chat-support'),
                    'Agent Avatar Image'        => esc_html__('Agent Avatar Image', 'better-chat-support'),
                    'Button Top Label'          => esc_html__('Button Top Label', 'better-chat-support'),
                    'Small label above the main button text.' => esc_html__('Small label above the main button text.', 'better-chat-support'),
                    'Button Main Label'         => esc_html__('Button Main Label', 'better-chat-support'),
                    'Chat Button Image Type'    => esc_html__('Chat Button Image Type', 'better-chat-support'),
                    'Chat Button Image'         => esc_html__('Chat Button Image', 'better-chat-support'),
                    'Show Notification Icon'    => esc_html__('Show Notification Icon', 'better-chat-support'),
                    'Notification Number'       => esc_html__('Notification Number', 'better-chat-support'),
                    'Number shown on the badge. None = no number.' => esc_html__('Number shown on the badge. None = no number.', 'better-chat-support'),
                    'Tooltip Behaviour'         => esc_html__('Tooltip Behaviour', 'better-chat-support'),
                    'Tooltip Text'              => esc_html__('Tooltip Text', 'better-chat-support'),
                    'Tooltip Background'        => esc_html__('Tooltip Background', 'better-chat-support'),
                    'Tooltip Width'             => esc_html__('Tooltip Width', 'better-chat-support'),
                    'Max width in px.'          => esc_html__('Max width in px.', 'better-chat-support'),
                    'Bubble Position'           => esc_html__('Bubble Position', 'better-chat-support'),
                    'Different Position for Tablet' => esc_html__('Different Position for Tablet', 'better-chat-support'),
                    'Tablet Position'           => esc_html__('Tablet Position', 'better-chat-support'),
                    'Different Position for Mobile' => esc_html__('Different Position for Mobile', 'better-chat-support'),
                    'Mobile Position'           => esc_html__('Mobile Position', 'better-chat-support'),
                    'Auto Open Popup'           => esc_html__('Auto Open Popup', 'better-chat-support'),
                    'Automatically opens the chat popup on page load.' => esc_html__('Automatically opens the chat popup on page load.', 'better-chat-support'),
                    'Auto Open Delay'           => esc_html__('Auto Open Delay', 'better-chat-support'),
                    'Delay in ms before auto-opening (0 = immediate).' => esc_html__('Delay in ms before auto-opening (0 = immediate).', 'better-chat-support'),
                    'Bubble Animation'          => esc_html__('Bubble Animation', 'better-chat-support'),
                    'How the chat bubble appears on screen.' => esc_html__('How the chat bubble appears on screen.', 'better-chat-support'),
                    'Theme Style'               => esc_html__('Theme Style', 'better-chat-support'),
                    'Visual style for the chat popup.' => esc_html__('Visual style for the chat popup.', 'better-chat-support'),
                    'Background Image'          => esc_html__('Background Image', 'better-chat-support'),
                    'Bubble Layout Mode'        => esc_html__('Bubble Layout Mode', 'better-chat-support'),
                    'Light / Dark / Night colour mode.' => esc_html__('Light / Dark / Night colour mode.', 'better-chat-support'),
                    'Primary & Secondary Colors' => esc_html__('Primary & Secondary Colors', 'better-chat-support'),
                    'Brand colours used throughout the chat bubble.' => esc_html__('Brand colours used throughout the chat bubble.', 'better-chat-support'),
                    'Send Button Colors'        => esc_html__('Send Button Colors', 'better-chat-support'),
                    'Google Font Family'        => esc_html__('Google Font Family', 'better-chat-support'),
                    'Leave blank to inherit the site\'s font.' => esc_html__('Leave blank to inherit the site\'s font.', 'better-chat-support'),
                    'Custom Bubble Trigger'     => esc_html__('Custom Bubble Trigger', 'better-chat-support'),
                    'Extra CSS selectors that also open the chat (e.g. .my-btn, #chat-trigger).' => esc_html__('Extra CSS selectors that also open the chat (e.g. .my-btn, #chat-trigger).', 'better-chat-support'),
                    'Show Chat On'              => esc_html__('Show Chat On', 'better-chat-support'),
                    'Control which device types see the chat bubble.' => esc_html__('Control which device types see the chat bubble.', 'better-chat-support'),
                    'Enable Visibility Rules By' => esc_html__('Enable Visibility Rules By', 'better-chat-support'),
                    'Select content types that should use include/exclude rules.' => esc_html__('Select content types that should use include/exclude rules.', 'better-chat-support'),
                    'Export Settings'           => esc_html__('Export Settings', 'better-chat-support'),
                    'Download your current settings as a JSON backup file.' => esc_html__('Download your current settings as a JSON backup file.', 'better-chat-support'),
                    'Download Backup'           => esc_html__('Download Backup', 'better-chat-support'),
                    'Import Settings'           => esc_html__('Import Settings', 'better-chat-support'),
                    'Restore settings from a previously exported JSON file. This overwrites current settings.' => esc_html__('Restore settings from a previously exported JSON file. This overwrites current settings.', 'better-chat-support'),
                    'After importing, click Save Settings to apply.' => esc_html__('After importing, click Save Settings to apply.', 'better-chat-support'),
                    'Reset all settings to defaults?' => esc_html__('Reset all settings to defaults?', 'better-chat-support'),
                    'Imported. Click Save to apply.' => esc_html__('Imported. Click Save to apply.', 'better-chat-support'),
                    'Invalid file.'             => esc_html__('Invalid file.', 'better-chat-support'),
                    'Could not parse file.'     => esc_html__('Could not parse file.', 'better-chat-support'),
                    // FloatingChat — option value strings
                    'Default (inherit)'         => esc_html__('Default (inherit)', 'better-chat-support'),
                    'Default'                   => esc_html__('Default', 'better-chat-support'),
                    'None'                      => esc_html__('None', 'better-chat-support'),
                    'Center'                    => esc_html__('Center', 'better-chat-support'),
                    'Yes'                       => esc_html__('Yes', 'better-chat-support'),
                    'No'                        => esc_html__('No', 'better-chat-support'),
                    'Everywhere'                => esc_html__('Everywhere', 'better-chat-support'),
                    'Desktop Only'              => esc_html__('Desktop Only', 'better-chat-support'),
                    'Tablet Only'               => esc_html__('Tablet Only', 'better-chat-support'),
                    'Mobile Only'               => esc_html__('Mobile Only', 'better-chat-support'),
                    'Include (show on selected)' => esc_html__('Include (show on selected)', 'better-chat-support'),
                    'Exclude (hide on selected)' => esc_html__('Exclude (hide on selected)', 'better-chat-support'),
                    'Theme Pages'               => esc_html__('Theme Pages', 'better-chat-support'),
                    'Posts'                     => esc_html__('Posts', 'better-chat-support'),
                    'Products'                  => esc_html__('Products', 'better-chat-support'),
                    'Post Categories'           => esc_html__('Post Categories', 'better-chat-support'),
                    'Post Tags'                 => esc_html__('Post Tags', 'better-chat-support'),
                    'Product Categories'        => esc_html__('Product Categories', 'better-chat-support'),
                    'Product Tags'              => esc_html__('Product Tags', 'better-chat-support'),
                    'Light Mode'                => esc_html__('Light Mode', 'better-chat-support'),
                    'Dark Mode'                 => esc_html__('Dark Mode', 'better-chat-support'),
                    'Night Mode'                => esc_html__('Night Mode', 'better-chat-support'),
                    'Bottom Right'              => esc_html__('Bottom Right', 'better-chat-support'),
                    'Bottom Left'               => esc_html__('Bottom Left', 'better-chat-support'),
                    'Middle Right'              => esc_html__('Middle Right', 'better-chat-support'),
                    'Middle Left'               => esc_html__('Middle Left', 'better-chat-support'),
                    'On Hover'                  => esc_html__('On Hover', 'better-chat-support'),
                    'Always Show'               => esc_html__('Always Show', 'better-chat-support'),
                    'Hide'                      => esc_html__('Hide', 'better-chat-support'),
                    'XS'                        => esc_html__('XS', 'better-chat-support'),
                    'S'                         => esc_html__('S', 'better-chat-support'),
                    'M'                         => esc_html__('M', 'better-chat-support'),
                    'L'                         => esc_html__('L', 'better-chat-support'),
                    'XL'                        => esc_html__('XL', 'better-chat-support'),
                    'XXL'                       => esc_html__('XXL', 'better-chat-support'),
                    'Normal'                    => esc_html__('Normal', 'better-chat-support'),
                    'Hover'                     => esc_html__('Hover', 'better-chat-support'),
                    'Primary'                   => esc_html__('Primary', 'better-chat-support'),
                    'Secondary'                 => esc_html__('Secondary', 'better-chat-support'),
                    'Color'                     => esc_html__('Color', 'better-chat-support'),
                    'Hover Color'               => esc_html__('Hover Color', 'better-chat-support'),
                    'Background'                => esc_html__('Background', 'better-chat-support'),
                    'Hover Background'          => esc_html__('Hover Background', 'better-chat-support'),
                    'Fade Right'                => esc_html__('Fade Right', 'better-chat-support'),
                    'Fade Down'                 => esc_html__('Fade Down', 'better-chat-support'),
                    'Ease Down'                 => esc_html__('Ease Down', 'better-chat-support'),
                    'Fade In Scale'             => esc_html__('Fade In Scale', 'better-chat-support'),
                    'Rotation'                  => esc_html__('Rotation', 'better-chat-support'),
                    'Slide Fall'                => esc_html__('Slide Fall', 'better-chat-support'),
                    'Slide Down'                => esc_html__('Slide Down', 'better-chat-support'),
                    'Rotate Left'               => esc_html__('Rotate Left', 'better-chat-support'),
                    'Flip Horizontal'           => esc_html__('Flip Horizontal', 'better-chat-support'),
                    'Flip Vertical'             => esc_html__('Flip Vertical', 'better-chat-support'),
                    'Flip Up'                   => esc_html__('Flip Up', 'better-chat-support'),
                    'Super Scaled'              => esc_html__('Super Scaled', 'better-chat-support'),
                    'Slide Up'                  => esc_html__('Slide Up', 'better-chat-support'),
                    'Random'                    => esc_html__('Random', 'better-chat-support'),
                    'Fade'                      => esc_html__('Fade', 'better-chat-support'),
                    'Rotate'                    => esc_html__('Rotate', 'better-chat-support'),
                    // FloatingChat — margin/position labels
                    'Margin — Bottom Right'     => esc_html__('Margin — Bottom Right', 'better-chat-support'),
                    'Margin — Bottom Left'      => esc_html__('Margin — Bottom Left', 'better-chat-support'),
                    'Margin — Middle Right'     => esc_html__('Margin — Middle Right', 'better-chat-support'),
                    'Margin — Middle Left'      => esc_html__('Margin — Middle Left', 'better-chat-support'),
                    'Tablet — Bottom Right'     => esc_html__('Tablet — Bottom Right', 'better-chat-support'),
                    'Tablet — Bottom Left'      => esc_html__('Tablet — Bottom Left', 'better-chat-support'),
                    'Tablet — Middle Right'     => esc_html__('Tablet — Middle Right', 'better-chat-support'),
                    'Tablet — Middle Left'      => esc_html__('Tablet — Middle Left', 'better-chat-support'),
                    'Mobile — Bottom Right'     => esc_html__('Mobile — Bottom Right', 'better-chat-support'),
                    'Mobile — Bottom Left'      => esc_html__('Mobile — Bottom Left', 'better-chat-support'),
                    'Mobile — Middle Right'     => esc_html__('Mobile — Middle Right', 'better-chat-support'),
                    'Mobile — Middle Left'      => esc_html__('Mobile — Middle Left', 'better-chat-support'),
                    // FloatingChat — icon-related labels
                    'Open Icon'                 => esc_html__('Open Icon', 'better-chat-support'),
                    'Open — Native Icon'        => esc_html__('Open — Native Icon', 'better-chat-support'),
                    'Open — Custom Icon'        => esc_html__('Open — Custom Icon', 'better-chat-support'),
                    'Close Icon'                => esc_html__('Close Icon', 'better-chat-support'),
                    'Close — Native Icon'       => esc_html__('Close — Native Icon', 'better-chat-support'),
                    'Close — Custom Icon'       => esc_html__('Close — Custom Icon', 'better-chat-support'),
                    'Open/Close Transition Effect' => esc_html__('Open/Close Transition Effect', 'better-chat-support'),
                    'Button Icon'               => esc_html__('Button Icon', 'better-chat-support'),
                    'Button — Native Icon'      => esc_html__('Button — Native Icon', 'better-chat-support'),
                    'Button — Custom Icon'      => esc_html__('Button — Custom Icon', 'better-chat-support'),
                    'Custom Size'               => esc_html__('Custom Size', 'better-chat-support'),
                    'Scale % relative to normal size (100%).' => esc_html__('Scale % relative to normal size (100%).', 'better-chat-support'),
                    'Icon Color'                => esc_html__('Icon Color', 'better-chat-support'),
                    'Icon Background'           => esc_html__('Icon Background', 'better-chat-support'),
                    'Icon Background Color'     => esc_html__('Icon Background Color', 'better-chat-support'),
                    'Button Background'         => esc_html__('Button Background', 'better-chat-support'),
                    'Button Label Color'        => esc_html__('Button Label Color', 'better-chat-support'),
                    'Button Border'             => esc_html__('Button Border', 'better-chat-support'),
                    'Icon Border'               => esc_html__('Icon Border', 'better-chat-support'),
                    'Button Padding'            => esc_html__('Button Padding', 'better-chat-support'),
                    'Select timezone'           => esc_html__('Select timezone', 'better-chat-support'),
                    'Search fonts…'             => esc_html__('Search fonts…', 'better-chat-support'),
                    // Shortcodes page
                    'Select Button Style'       => esc_html__('Select Button Style', 'better-chat-support'),
                    'Shortcode'                 => esc_html__('Shortcode', 'better-chat-support'),
                    'Copy Shortcode'            => esc_html__('Copy Shortcode', 'better-chat-support'),
                    'Copied!'                   => esc_html__('Copied!', 'better-chat-support'),
                    'Copy Failed — Select & Ctrl+C' => esc_html__('Copy Failed — Select & Ctrl+C', 'better-chat-support'),
                    'Available Attributes ='    => esc_html__('Available Attributes =', 'better-chat-support'),
                    // Help page
                    'Get Started'               => esc_html__('Get Started', 'better-chat-support'),
                    'Recommended'               => esc_html__('Recommended', 'better-chat-support'),
                    'Lite Vs Pro'               => esc_html__('Lite Vs Pro', 'better-chat-support'),
                    'Pro Plugins'               => esc_html__('Pro Plugins', 'better-chat-support'),
                    'Active'                    => esc_html__('Active', 'better-chat-support'),
                    'Activating…'               => esc_html__('Activating…', 'better-chat-support'),
                    'Install Now'               => esc_html__('Install Now', 'better-chat-support'),
                    'Installing…'               => esc_html__('Installing…', 'better-chat-support'),
                    'More Details'              => esc_html__('More Details', 'better-chat-support'),
                    'Couldn\'t load plugins right now. Please try again later.' => esc_html__('Couldn\'t load plugins right now. Please try again later.', 'better-chat-support'),
                    'Hot'                       => esc_html__('Hot', 'better-chat-support'),
                    'active'                    => esc_html__('active', 'better-chat-support'),
                    'Upgrading To Pro!'         => esc_html__('Upgrading To Pro!', 'better-chat-support'),
                    'Welcome to Better Chat Support for Messenger!' => esc_html__('Welcome to Better Chat Support for Messenger!', 'better-chat-support'),
                    'Thank you for installing Better Chat Support for Messenger! This playlist will help you get started with the plugin. Enjoy!' => esc_html__('Thank you for installing Better Chat Support for Messenger! This playlist will help you get started with the plugin. Enjoy!', 'better-chat-support'),
                    'Overview'                  => esc_html__('Overview', 'better-chat-support'),
                    'Better Chat Support Plugin - Overview' => esc_html__('Better Chat Support Plugin - Overview', 'better-chat-support'),
                    'Plugin Settings'           => esc_html__('Plugin Settings', 'better-chat-support'),
                    'Upgrade To Pro'            => esc_html__('Upgrade To Pro', 'better-chat-support'),
                    'Documentation'             => esc_html__('Documentation', 'better-chat-support'),
                    'Explore Messenger Chat Support plugin capabilities in our enriched documentation.' => esc_html__('Explore Messenger Chat Support plugin capabilities in our enriched documentation.', 'better-chat-support'),
                    'Technical Support'         => esc_html__('Technical Support', 'better-chat-support'),
                    'For personalized assistance, reach out to our skilled support team for prompt help.' => esc_html__('For personalized assistance, reach out to our skilled support team for prompt help.', 'better-chat-support'),
                    'Hire Us!'                  => esc_html__('Hire Us!', 'better-chat-support'),
                    'We are available for freelance work for any WordPress, React, NextJS projects. Click to contact us.' => esc_html__('We are available for freelance work for any WordPress, React, NextJS projects. Click to contact us.', 'better-chat-support'),
                    'Enhance your Website with our Free Robust Plugins' => esc_html__('Enhance your Website with our Free Robust Plugins', 'better-chat-support'),
                    'Free, high-quality plugins by ThemeAtelier — install in one click.' => esc_html__('Free, high-quality plugins by ThemeAtelier — install in one click.', 'better-chat-support'),
                    'Lite Vs Pro Comparison'    => esc_html__('Lite Vs Pro Comparison', 'better-chat-support'),
                    'FEATURES'                  => esc_html__('FEATURES', 'better-chat-support'),
                    'Lite'                      => esc_html__('Lite', 'better-chat-support'),
                    'All Free Version Features' => esc_html__('All Free Version Features', 'better-chat-support'),
                    'Single Agent & Chat Button Layouts' => esc_html__('Single Agent & Chat Button Layouts', 'better-chat-support'),
                    'Icon & Simple Button Styles' => esc_html__('Icon & Simple Button Styles', 'better-chat-support'),
                    'Time based availability for agents' => esc_html__('Time based availability for agents', 'better-chat-support'),
                    'Bubble animation'          => esc_html__('Bubble animation', 'better-chat-support'),
                    'Open/Close Transition Effects' => esc_html__('Open/Close Transition Effects', 'better-chat-support'),
                    'Typography & Google Fonts' => esc_html__('Typography & Google Fonts', 'better-chat-support'),
                    'Responsive Positioning (Desktop/Tablet/Mobile)' => esc_html__('Responsive Positioning (Desktop/Tablet/Mobile)', 'better-chat-support'),
                    'Button Tooltip & Notification Badge' => esc_html__('Button Tooltip & Notification Badge', 'better-chat-support'),
                    'Custom CSS & JavaScript'   => esc_html__('Custom CSS & JavaScript', 'better-chat-support'),
                    'Shortcode Buttons'         => esc_html__('Shortcode Buttons', 'better-chat-support'),
                    'WooCommerce Buttons'       => esc_html__('WooCommerce Buttons', 'better-chat-support'),
                    'Gutenberg Button Blocks'   => esc_html__('Gutenberg Button Blocks', 'better-chat-support'),
                    'Export/Import Option'      => esc_html__('Export/Import Option', 'better-chat-support'),
                    'Multi-Agent Layouts (List & Grid)' => esc_html__('Multi-Agent Layouts (List & Grid)', 'better-chat-support'),
                    'Advance Chat Button Style' => esc_html__('Advance Chat Button Style', 'better-chat-support'),
                    'Dark & Night Layout Mode'  => esc_html__('Dark & Night Layout Mode', 'better-chat-support'),
                    'Custom Button Icons & Images' => esc_html__('Custom Button Icons & Images', 'better-chat-support'),
                    'Advanced Visibility (Posts, Products, Categories, Tags)' => esc_html__('Advanced Visibility (Posts, Products, Categories, Tags)', 'better-chat-support'),
                    'Automatic Updates & Priority Support' => esc_html__('Automatic Updates & Priority Support', 'better-chat-support'),
                    'Upgrade To PRO & Enjoy Advanced Features!' => esc_html__('Upgrade To PRO & Enjoy Advanced Features!', 'better-chat-support'),
                    'Already,'                  => esc_html__('Already,', 'better-chat-support'),
                    'people are using Better Chat Support on their websites to turn visitors into customers with powerful video greetings — why won\'t you?' => esc_html__('people are using Better Chat Support on their websites to turn visitors into customers with powerful video greetings — why won\'t you?', 'better-chat-support'),
                    'Upgrade to Pro Now!'       => esc_html__('Upgrade to Pro Now!', 'better-chat-support'),
                    '14-Day No-Questions-Asked' => esc_html__('14-Day No-Questions-Asked', 'better-chat-support'),
                    'Refund Policy'             => esc_html__('Refund Policy', 'better-chat-support'),
                    'See All Features'          => esc_html__('See All Features', 'better-chat-support'),
                    'Pro Live Demo'             => esc_html__('Pro Live Demo', 'better-chat-support'),
                    'NO NEED TO TAKE OUR WORD FOR IT' => esc_html__('NO NEED TO TAKE OUR WORD FOR IT', 'better-chat-support'),
                    'Our Users Love Better Chat Support!' => esc_html__('Our Users Love Better Chat Support!', 'better-chat-support'),
                    'Upgrade your Website with our High-quality Plugins!' => esc_html__('Upgrade your Website with our High-quality Plugins!', 'better-chat-support'),
                    'View Details'              => esc_html__('View Details', 'better-chat-support'),
                    'Made With'                 => esc_html__('Made With', 'better-chat-support'),
                    'By the Team'               => esc_html__('By the Team', 'better-chat-support'),
                    'Get connected with'        => esc_html__('Get connected with', 'better-chat-support'),
                ],
            ];

            // Keep the legacy mcsDashboard alias so the existing Dashboard.jsx still works.
            $legacy = array_merge($data, [
                'widgets'       => [],
                'hasGlobalChat' => $has_global_chat,
            ]);

            // Font data is injected on every React screen because the SPA can
            // navigate to the Floating Chat (typography) page from any entry point
            // without a full page reload.
            $typography = self::get_typography_data();

            echo wp_print_inline_script_tag(
                'window.mcsAdmin = ' . wp_json_encode($data) . ';' .
                'window.mcsDashboard = ' . wp_json_encode($legacy) . ';' .
                'window.mcsChat = { restUrl: ' . wp_json_encode($rest_url) . ', nonce: ' . wp_json_encode($nonce) . ' };' .
                'window.mcsPreloaded = { opt: ' . wp_json_encode($preload_opt) . ', settings: ' . wp_json_encode($preload_settings) . ' };' .
                'window.BetterChatSupport_typography_json = ' . wp_json_encode($typography) . ';'
            );
        });

        wp_enqueue_script(
            'better-chat-support-admin-dashboard',
            BETTER_CHAT_SUPPORT_DIR_URL . 'src/Admin/assets/js/better-chat-support-admin.js',
            [],
            BETTER_CHAT_SUPPORT_VERSION,
            true
        );
    }

    /**
     * Build the typography font data for the React admin.
     *
     * Shares the same data source as the Pro version so the React Google Font
     * Family control works identically. Exposed as
     * window.BetterChatSupport_typography_json.
     */
    private static function get_typography_data(): array
    {
        $google_fonts = [];
        $google_fonts_file = BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Admin/google-fonts.php';

        if (file_exists($google_fonts_file)) {
            require_once $google_fonts_file;
            if (function_exists('BetterChatSupport_get_google_fonts')) {
                $google_fonts = BetterChatSupport_get_google_fonts();
            }
        }

        $webfonts = [
            'safe' => [
                'label' => esc_html__('Safe Web Fonts', 'better-chat-support'),
                'fonts' => apply_filters('BetterChatSupport_field_typography_safewebfonts', [
                    'Arial',
                    'Arial Black',
                    'Helvetica',
                    'Times New Roman',
                    'Courier New',
                    'Tahoma',
                    'Verdana',
                    'Impact',
                    'Trebuchet MS',
                    'Comic Sans MS',
                    'Lucida Console',
                    'Lucida Sans Unicode',
                    'Georgia, serif',
                    'Palatino Linotype',
                ]),
            ],
            'google' => [
                'label' => esc_html__('Google Web Fonts', 'better-chat-support'),
                'fonts' => apply_filters('BetterChatSupport_field_typography_googlewebfonts', $google_fonts),
            ],
        ];

        $defaultstyles = apply_filters('BetterChatSupport_field_typography_defaultstyles', ['normal', 'italic', '700', '700italic']);

        $googlestyles = apply_filters('BetterChatSupport_field_typography_googlestyles', [
            '100'       => 'Thin 100',
            '100italic' => 'Thin 100 Italic',
            '200'       => 'Extra-Light 200',
            '200italic' => 'Extra-Light 200 Italic',
            '300'       => 'Light 300',
            '300italic' => 'Light 300 Italic',
            'normal'    => 'Normal 400',
            'italic'    => 'Normal 400 Italic',
            '500'       => 'Medium 500',
            '500italic' => 'Medium 500 Italic',
            '600'       => 'Semi-Bold 600',
            '600italic' => 'Semi-Bold 600 Italic',
            '700'       => 'Bold 700',
            '700italic' => 'Bold 700 Italic',
            '800'       => 'Extra-Bold 800',
            '800italic' => 'Extra-Bold 800 Italic',
            '900'       => 'Black 900',
            '900italic' => 'Black 900 Italic',
        ]);

        return [
            'webfonts'      => apply_filters('BetterChatSupport_field_typography_webfonts', $webfonts),
            'defaultstyles' => $defaultstyles,
            'googlestyles'  => $googlestyles,
        ];
    }
}
