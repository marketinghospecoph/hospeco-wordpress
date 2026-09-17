<?php

/**
 * REST API controller for plugin settings (Free version).
 *
 * Mirrors the Pro SettingsController for shared functionality but omits all
 * Pro-only endpoints (license activation/deactivation). License management is
 * a Pro-only feature.
 *
 * @package better-chat-support
 */

namespace ThemeAtelier\BetterChatSupport\Admin;

if (!defined('ABSPATH')) {
    die;
}

class SettingsController
{
    private $allowed_options = ['mcs-opt', 'mcs_settings'];

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        $ns = 'better-chat-support/v1';

        // Specific routes must be registered before the generic (?P<option>) route
        register_rest_route($ns, '/settings/timezones', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_timezones'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($ns, '/settings/wp-pages', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_wp_pages'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($ns, '/settings/wp-content', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_wp_content'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Help page: server-rendered "Recommended" plugin cards (WordPress.org
        // install/activate states + nonces). The React Help page injects this HTML
        // so the WP plugin-install integration stays pixel-identical and functional.
        register_rest_route($ns, '/help/recommended-plugins', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_recommended_plugins'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Help page: AJAX install/activate for the Recommended plugins so the
        // React cards update in place (no full-page reload).
        // Help page: genuine 5-star reviews scraped from the plugin's WordPress.org
        // support forum (cached for 12h). Powers the redesigned Help "Reviews"
        // showcase so only real, verified 5-star reviews are displayed.
        register_rest_route($ns, '/help/reviews', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_reviews'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($ns, '/help/install-plugin', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'install_plugin'],
            'permission_callback' => [$this, 'check_install_permission'],
            'args'                => [
                'slug' => ['required' => true, 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);

        register_rest_route($ns, '/help/activate-plugin', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'activate_plugin'],
            'permission_callback' => [$this, 'check_activate_permission'],
            'args'                => [
                'plugin' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        // Onboarding newsletter opt-in: forwards the email to the FluentCRM
        // contact webhook server-side (avoids browser CORS to the remote site).
        register_rest_route($ns, '/onboarding/subscribe', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'subscribe_newsletter'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => [
                'email' => ['required' => true, 'sanitize_callback' => 'sanitize_email'],
            ],
        ]);

        // Generic GET/POST for option keys (mcs-opt, mcs_settings)
        register_rest_route($ns, '/settings/(?P<option>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => [
                    'option' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'save_settings'],
                'permission_callback' => [$this, 'check_permission'],
                'args'                => [
                    'option' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ],
        ]);
    }

    public function check_permission(): bool
    {
        return current_user_can('manage_options');
    }

    public function check_install_permission(): bool
    {
        return current_user_can('install_plugins');
    }

    public function check_activate_permission(): bool
    {
        return current_user_can('activate_plugins');
    }

    /**
     * Forward an onboarding email opt-in to the FluentCRM contact webhook.
     *
     * Runs server-side via wp_remote_post so the browser never calls the remote
     * site directly (no CORS), and the contact is added to the configured
     * FluentCRM list. The webhook URL is filterable so it can be overridden
     * without touching code.
     */
    public function subscribe_newsletter(\WP_REST_Request $request)
    {
        $email = sanitize_email($request->get_param('email'));
        if (empty($email) || ! is_email($email)) {
            return new \WP_REST_Response(['success' => false, 'message' => __('Please enter a valid email.', 'better-chat-support')], 400);
        }

        $webhook = apply_filters(
            'better_chat_support_fluentcrm_webhook',
            'https://themeatelier.net/?fluentcrm=1&route=contact&hash=0ab4e42c-0edc-4457-91d0-20b95261adb1'
        );

        $user       = wp_get_current_user();
        $first_name = $user ? ($user->first_name ?: $user->display_name) : '';
        $last_name  = $user ? $user->last_name : '';
        $full_name  = trim($first_name . ' ' . $last_name);

        // Map every webhook field the site can supply automatically. FluentCRM
        // ignores any key it doesn't recognise, so sending the full set is safe.
        $payload = [
            // Contact Fields
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'full_name'  => $full_name,
            'timezone'   => wp_timezone_string(),
            // Custom Fields (site environment)
            'website'    => home_url(),
            'status'     => 'subscribed', // user accepted terms + clicked opt-in
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'language'   => get_locale(),
            'theme'      => wp_get_theme()->get('Name'),
            'created_at' => current_time('Y-m-d'), // FluentCRM date field expects Y-m-d
            'source'     => 'Better Chat Support Onboarding',
        ];

        $response = wp_remote_post($webhook, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'    => wp_json_encode(apply_filters('better_chat_support_fluentcrm_payload', $payload, $email)),
        ]);

        if (is_wp_error($response)) {
            return new \WP_REST_Response(['success' => false, 'message' => $response->get_error_message()], 502);
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $ok   = $code >= 200 && $code < 300;

        return new \WP_REST_Response(
            ['success' => $ok, 'code' => $code],
            $ok ? 200 : 502
        );
    }

    /** Map of plugin slug → main plugin file (for install/active detection). */
    private $plugin_files = [
        'domain-for-sale'        => 'domain-for-sale.php',
        'ask-faq'                => 'ask-faq.php',
        'attentive-security'     => 'attentive-security.php',
        'better-chat-support'    => 'messenger-chat-support.php',
        'bizreview'              => 'bizreview.php',
        'booklet-booking-system' => 'booklet.php',
        'skype-chat'             => 'skype-chat.php',
        'chat-help'              => 'chat-whatsapp.php',
        'chat-telegram'          => 'telegram-chat.php',
        'chat-viber'             => 'chat-viber-lite.php',
        'click-to-dial'          => 'click-to-dial.php',
        'click-to-mail'          => 'click-to-mail.php',
        'darkify'                => 'darkify.php',
        'eventful'               => 'eventful.php',
        'eventful-for-elementor' => 'eventful-for-elementor.php',
        'postify'                => 'postify.php',
        'idonate'                => 'idonate.php',
        'greet-bubble'           => 'greet-bubble.php',
    ];

    /** Slugs to exclude from the recommended list. */
    private $exclude_plugins = [
        'bizreview',
        'chat-viber',
        'chat-telegram',
        'click-to-dial',
        'chat-skype',
        'click-to-mail',
        'ask-faq',
        'attentive-security',
        'booklet-booking-system',
        'postify',
        'better-chat-support',
        'idonate',
    ];

    /**
     * Return the ThemeAtelier "Recommended" plugins as structured JSON for the
     * React Help page (install/active states + nonced action URLs). Data from the
     * WordPress.org API is cached for 24h — but only successful, non-empty
     * results are cached so a transient network failure never poisons the cache
     * (which previously locked the "Couldn't load plugins" message in for 24h).
     */
    public function get_recommended_plugins()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins_arr = get_transient('better_chat_support_recommended_plugins');

        if (!is_array($plugins_arr) || empty($plugins_arr)) {
            $plugins_arr = $this->fetch_themeatelier_plugins();

            // Cache only a successful, non-empty result. If the fetch failed we
            // leave the transient unset so the next page load retries instead of
            // serving a cached empty list for the full 24h window.
            if (!empty($plugins_arr)) {
                set_transient('better_chat_support_recommended_plugins', $plugins_arr, 24 * HOUR_IN_SECONDS);
            }
        }

        if (empty($plugins_arr)) {
            // 200 (not an error status) so the React page can render its own
            // fallback UI; `error` lets it distinguish "fetch failed" from an
            // intentionally empty list.
            return rest_ensure_response(['plugins' => [], 'error' => 'fetch_failed']);
        }

        // Sort by popularity (active installs desc).
        array_multisort(array_column($plugins_arr, 'active_installs'), SORT_DESC, $plugins_arr);

        $out = [];
        foreach ($plugins_arr as $plugin) {
            $slug        = $plugin['slug'];
            $plugin_file = $this->plugin_files[$slug] ?? ($slug . '.php');
            $basename    = $slug . '/' . $plugin_file;

            $image_type = 'png';
            if ('darkify' === $slug) {
                $image_type = 'gif?rev=3301202';
            }

            $installed = file_exists(WP_PLUGIN_DIR . '/' . $basename);
            $active    = $installed && is_plugin_active($basename);

            $out[] = [
                'slug'           => $slug,
                'name'           => $plugin['name'],
                'icon'           => 'https://ps.w.org/' . $slug . '/assets/icon-256x256.' . $image_type,
                'description'    => isset($plugin['short_description']) ? $plugin['short_description'] : '',
                'version'        => isset($plugin['version']) ? $plugin['version'] : '',
                'activeInstalls' => isset($plugin['active_installs']) ? number_format_i18n($plugin['active_installs']) . '+' : '',
                'lastUpdated'    => isset($plugin['last_updated']) ? human_time_diff($plugin['last_updated']) . ' ' . __('ago', 'better-chat-support') : '',
                'rating'         => isset($plugin['rating']) ? (float) $plugin['rating'] : 0,        // 0–100
                'numRatings'     => isset($plugin['num_ratings']) ? (int) $plugin['num_ratings'] : 0,
                'wporgUrl'       => 'https://wordpress.org/plugins/' . $slug . '/',
                'installed'      => $installed,
                'active'         => $active,
                'plugin'         => $basename, // plugin file (folder/file.php) for the activate endpoint
            ];
        }

        return rest_ensure_response(['plugins' => $out]);
    }

    /**
     * Fetch the ThemeAtelier plugins from WordPress.org.
     *
     * Uses the canonical `plugins_api()` helper first (handles HTTPS, retries and
     * response normalisation), and falls back to the legacy serialized endpoint
     * if that is unavailable or fails. Returns a normalised array of plugin data,
     * or an empty array on failure (so the caller can avoid caching it).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetch_themeatelier_plugins(): array
    {
        $args = (object) [
            'author'   => 'themeatelier',
            'per_page' => 120,
            'page'     => 1,
            'fields'   => [
                'slug', 'name', 'version', 'active_installs',
                'last_updated', 'rating', 'num_ratings', 'short_description', 'author',
            ],
        ];

        // Preferred: the canonical WordPress.org plugins API helper.
        if (!function_exists('plugins_api')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        $raw    = [];
        $result = plugins_api('query_plugins', $args);
        if (!is_wp_error($result) && !empty($result->plugins)) {
            $raw = $result->plugins;
        } else {
            // Fallback: legacy serialized endpoint (try HTTPS first, then HTTP).
            $raw = $this->fetch_themeatelier_plugins_legacy($args);
        }

        $plugins_arr = [];
        foreach ($raw as $pl) {
            // plugins_api() may return plugin entries as objects or arrays
            // depending on the WordPress version — normalise to an array.
            $pl = (array) $pl;
            if (empty($pl['slug']) || in_array($pl['slug'], $this->exclude_plugins, true)) {
                continue;
            }
            $plugins_arr[] = [
                'slug'              => $pl['slug'],
                'name'              => isset($pl['name']) ? $pl['name'] : $pl['slug'],
                'version'           => isset($pl['version']) ? $pl['version'] : '',
                'active_installs'   => isset($pl['active_installs']) ? (int) $pl['active_installs'] : 0,
                'last_updated'      => isset($pl['last_updated']) ? strtotime($pl['last_updated']) : 0,
                'rating'            => isset($pl['rating']) ? $pl['rating'] : 0,
                'num_ratings'       => isset($pl['num_ratings']) ? $pl['num_ratings'] : 0,
                'short_description' => isset($pl['short_description']) ? $pl['short_description'] : '',
            ];
        }

        return $plugins_arr;
    }

    /**
     * Legacy fallback: query the serialized WordPress.org plugins endpoint
     * directly. Tries HTTPS first, then HTTP for hosts that block one or the
     * other. Returns the raw `->plugins` array, or an empty array on failure.
     *
     * @param object $args query_plugins request arguments.
     * @return array
     */
    private function fetch_themeatelier_plugins_legacy($args): array
    {
        $request = ['action' => 'query_plugins', 'request' => serialize($args)];

        foreach (['https', 'http'] as $scheme) {
            $response = wp_remote_post(
                $scheme . '://api.wordpress.org/plugins/info/1.0/',
                ['body' => $request, 'timeout' => 30]
            );

            if (is_wp_error($response)) {
                continue;
            }

            $plugins = @unserialize(wp_remote_retrieve_body($response));
            if (isset($plugins->plugins) && count($plugins->plugins) > 0) {
                return $plugins->plugins;
            }
        }

        return [];
    }

    /* ─────────────────────────────────────────────────────────────────────
     * Reviews — genuine 5-star reviews from the WordPress.org support forum.
     * ───────────────────────────────────────────────────────────────────── */

    /** Plugin slug whose reviews are showcased on the Help page. */
    private $reviews_slug = 'better-chat-support';

    /**
     * Return genuine 5-star reviews for the Help page. Reviews are scraped from
     * the plugin's WordPress.org "reviews" support forum (filtered to 5 stars),
     * with each review's body fetched from its topic page. The full payload —
     * overall rating, review count and the parsed reviews — is cached for 12h so
     * the page stays fast and WordPress.org is only hit on a cache refresh.
     *
     * Returns 200 with `error: 'fetch_failed'` (never an HTTP error) so the React
     * page can fall back to its own UI without breaking the layout.
     */
    public function get_reviews()
    {
        $cached = get_transient('better_chat_support_reviews');
        if (is_array($cached) && !empty($cached['reviews'])) {
            return rest_ensure_response($cached);
        }

        $reviews = $this->fetch_wporg_reviews();

        if (empty($reviews)) {
            return rest_ensure_response(['reviews' => [], 'rating' => 0, 'numRatings' => 0, 'error' => 'fetch_failed']);
        }

        // Overall rating / count straight from the WordPress.org plugins API.
        $rating     = 5.0;
        $numRatings = count($reviews);
        if (!function_exists('plugins_api')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        $info = plugins_api('plugin_information', (object) [
            'slug'   => $this->reviews_slug,
            'fields' => ['rating' => true, 'num_ratings' => true, 'short_description' => false],
        ]);
        if (!is_wp_error($info)) {
            if (isset($info->rating)) {
                $rating = round(((float) $info->rating) / 20, 1); // 0–100 → 0–5
            }
            if (isset($info->num_ratings)) {
                $numRatings = (int) $info->num_ratings;
            }
        }

        $payload = [
            'reviews'    => $reviews,
            'rating'     => $rating,
            'numRatings' => $numRatings,
            'reviewsUrl' => 'https://wordpress.org/support/plugin/' . $this->reviews_slug . '/reviews/?filter=5',
        ];

        set_transient('better_chat_support_reviews', $payload, 12 * HOUR_IN_SECONDS);

        return rest_ensure_response($payload);
    }

    /**
     * Scrape genuine 5-star reviews from the plugin's WordPress.org reviews forum.
     *
     * @return array<int, array<string, mixed>> Normalised reviews (empty on failure).
     */
    private function fetch_wporg_reviews(): array
    {
        $list_url = 'https://wordpress.org/support/plugin/' . $this->reviews_slug . '/reviews/?filter=5';
        $body     = $this->remote_get_body($list_url);
        if ('' === $body) {
            return [];
        }

        // Each review is a bbPress topic <ul id="bbp-topic-…">…</ul>. Uses a `~`
        // delimiter because the pattern itself contains a literal `#`
        // (`<!-- #bbp-topic-`), which would otherwise close a `#` delimiter and
        // trigger an "Unknown modifier" warning.
        if (!preg_match_all('~<ul id="bbp-topic-\d+".*?</ul><!-- #bbp-topic-~s', $body, $topics)) {
            return [];
        }

        $reviews = [];
        foreach ($topics[0] as $topic) {
            // Only genuine 5-star reviews (defensive — the URL already filters).
            $stars = substr_count($topic, 'dashicons-star-filled');
            if ($stars < 5) {
                continue;
            }

            if (!preg_match('#<a class="bbp-topic-permalink" href="([^"]+)">(.*?)<div class=\'wporg-ratings#s', $topic, $m)) {
                continue;
            }
            $url   = html_entity_decode(trim($m[1]), ENT_QUOTES);
            $title = trim(wp_strip_all_tags($m[2]));

            $author = '';
            if (preg_match('#bbp-topic-started-by.*?bbp-author-name">(.*?)</span>#s', $topic, $am)) {
                $author = trim(wp_strip_all_tags($am[1]));
            }

            $detail = $this->fetch_wporg_review_detail($url);

            $reviews[] = [
                'title'   => $title,
                'author'  => $author !== '' ? $author : __('WordPress User', 'better-chat-support'),
                'avatar'  => $detail['avatar'],
                'text'    => $detail['text'] !== '' ? $detail['text'] : $title,
                'rating'  => 5,
                'url'     => $url,
            ];

            // Showcase at most 8 reviews — plenty for the slider, keeps it light.
            if (count($reviews) >= 8) {
                break;
            }
        }

        return $reviews;
    }

    /**
     * Fetch a single review topic page and extract its body text + author avatar.
     *
     * @return array{text:string, avatar:string}
     */
    private function fetch_wporg_review_detail(string $url): array
    {
        $out  = ['text' => '', 'avatar' => ''];
        $body = $this->remote_get_body($url);
        if ('' === $body) {
            return $out;
        }

        if (preg_match('#<div class="bbp-topic-content">(.*?)</div><!-- .bbp-topic-content -->#s', $body, $m)) {
            $text = wp_strip_all_tags($m[1]);
            $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES)));
            // Keep cards tidy — trim very long bodies on a word boundary.
            if (mb_strlen($text) > 320) {
                $text = rtrim(mb_substr($text, 0, 320)) . '…';
            }
            $out['text'] = $text;
        }

        if (preg_match('#bbp-topic-author.*?<img[^>]*\ssrc=([\'"])(.*?)\1#s', $body, $am)) {
            $out['avatar'] = html_entity_decode(trim($am[2]), ENT_QUOTES);
        }

        return $out;
    }

    /** Thin wrapper around wp_remote_get that returns the body string (or ''). */
    private function remote_get_body(string $url): string
    {
        $response = wp_remote_get($url, [
            'timeout'    => 15,
            'user-agent' => 'BetterChatSupport/' . BETTER_CHAT_SUPPORT_VERSION . '; ' . home_url('/'),
        ]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return '';
        }
        return (string) wp_remote_retrieve_body($response);
    }

    /**
     * Load the WordPress admin includes required for programmatic plugin
     * installation/activation (only when an action actually runs).
     */
    private function load_plugin_admin_includes(): void
    {
        if (!function_exists('plugins_api')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!class_exists('WP_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        if (!class_exists('WP_Ajax_Upgrader_Skin')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
        }
        // file.php / misc.php provide WP_Filesystem + request_filesystem_credentials.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
    }

    /**
     * Resolve the canonical plugin basename (folder/file.php) for a slug. Prefers
     * the known mapping, then any already-installed file matching the slug folder.
     */
    private function resolve_plugin_basename(string $slug): string
    {
        $basename = $slug . '/' . ($this->plugin_files[$slug] ?? ($slug . '.php'));
        if (file_exists(WP_PLUGIN_DIR . '/' . $basename)) {
            return $basename;
        }

        // Fall back to scanning the slug's folder for the main plugin file.
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (array_keys(get_plugins()) as $file) {
            if (strpos($file, $slug . '/') === 0) {
                return $file;
            }
        }

        return $basename;
    }

    /**
     * AJAX install a WordPress.org plugin by slug. Returns the updated
     * installed/active state so the React card can refresh without a reload.
     */
    public function install_plugin(\WP_REST_Request $request)
    {
        $slug = sanitize_key((string) $request->get_param('slug'));
        if (empty($slug)) {
            return new \WP_Error('missing_slug', __('No plugin specified.', 'better-chat-support'), ['status' => 400]);
        }

        $this->load_plugin_admin_includes();

        $basename = $this->resolve_plugin_basename($slug);

        // Only download if it isn't already on disk.
        if (!file_exists(WP_PLUGIN_DIR . '/' . $basename)) {
            $api = plugins_api('plugin_information', (object) [
                'slug'   => $slug,
                'fields' => ['sections' => false],
            ]);
            if (is_wp_error($api) || empty($api->download_link)) {
                return new \WP_Error(
                    'install_failed',
                    __('Could not retrieve plugin information from WordPress.org.', 'better-chat-support'),
                    ['status' => 502]
                );
            }

            $skin     = new \WP_Ajax_Upgrader_Skin();
            $upgrader = new \Plugin_Upgrader($skin);
            $result   = $upgrader->install($api->download_link);

            if (is_wp_error($result)) {
                return new \WP_Error('install_failed', $result->get_error_message(), ['status' => 500]);
            }
            if (is_wp_error($skin->result)) {
                return new \WP_Error('install_failed', $skin->result->get_error_message(), ['status' => 500]);
            }
            if (!$result) {
                $errors  = $skin->get_errors();
                $message = (is_wp_error($errors) && $errors->has_errors())
                    ? $errors->get_error_message()
                    : __('Plugin installation failed. Please try again.', 'better-chat-support');
                return new \WP_Error('install_failed', $message, ['status' => 500]);
            }

            // The upgrader knows the actual installed file (folder may differ).
            $installed_file = $upgrader->plugin_info();
            if ($installed_file) {
                $basename = $installed_file;
            } else {
                $basename = $this->resolve_plugin_basename($slug);
            }
        }

        return rest_ensure_response([
            'success'   => true,
            'plugin'    => $basename,
            'installed' => file_exists(WP_PLUGIN_DIR . '/' . $basename),
            'active'    => is_plugin_active($basename),
        ]);
    }

    /**
     * AJAX activate an installed plugin by its basename (folder/file.php).
     */
    public function activate_plugin(\WP_REST_Request $request)
    {
        $plugin = trim((string) $request->get_param('plugin'), '/');
        // Expect a "folder/file.php" basename — reject anything else (no traversal).
        if (!preg_match('#^[A-Za-z0-9._-]+/[A-Za-z0-9._-]+\.php$#', $plugin)) {
            return new \WP_Error('invalid_plugin', __('Invalid plugin specified.', 'better-chat-support'), ['status' => 400]);
        }

        $this->load_plugin_admin_includes();

        if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            return new \WP_Error('not_installed', __('Plugin is not installed.', 'better-chat-support'), ['status' => 404]);
        }

        if (is_plugin_active($plugin)) {
            return rest_ensure_response(['success' => true, 'plugin' => $plugin, 'installed' => true, 'active' => true]);
        }

        // Buffer and discard any output the plugin's activation hooks may emit
        // (notices, deprecations, echoed HTML). Otherwise it leaks into the body
        // ahead of our JSON, breaking the response so a successful activation is
        // reported to the UI as a failure.
        ob_start();
        $result = activate_plugin($plugin);
        ob_end_clean();

        // The actual active state is the source of truth: some plugins return a
        // WP_Error or emit output yet still activate correctly. Only report a
        // failure when the plugin genuinely did not become active.
        if (is_plugin_active($plugin)) {
            return rest_ensure_response([
                'success'   => true,
                'plugin'    => $plugin,
                'installed' => true,
                'active'    => true,
            ]);
        }

        $message = is_wp_error($result)
            ? $result->get_error_message()
            : __('Plugin activation failed. Please try again.', 'better-chat-support');
        return new \WP_Error('activate_failed', $message, ['status' => 500]);
    }

    public function get_settings(\WP_REST_Request $request)
    {
        $option = $request->get_param('option');
        if (!in_array($option, $this->allowed_options, true)) {
            return new \WP_Error('invalid_option', 'Invalid option key.', ['status' => 400]);
        }

        $data = get_option($option, []);
        if (!is_array($data)) {
            $data = [];
        }

        if ($option === 'mcs-opt' && !array_key_exists('opt-chat-agents', $data)) {
            $data['opt-chat-agents'] = self::default_agents();
        }

        return rest_ensure_response($data);
    }

    public static function default_agents(): array
    {
        $base_url = BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/images/';
        return [
            [
                'agent-name'         => 'Sarah C. Patrick',
                'agent-designation'  => __('Technical support', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'user.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
            [
                'agent-name'         => 'Patricia J. Hunt',
                'agent-designation'  => __('Marketing support', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'agent1.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
            [
                'agent-name'         => 'Frederic M. Tune',
                'agent-designation'  => __('Sales support', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'agent2.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
            [
                'agent-name'         => 'Douglas A. Smith',
                'agent-designation'  => __('Product manager', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'agent3.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
            [
                'agent-name'         => 'Douglas A. Smith',
                'agent-designation'  => __('Support Manager', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'agent4.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
            [
                'agent-name'         => 'Garland D. Homer',
                'agent-designation'  => __('Technical support', 'better-chat-support'),
                'agent-fbid'         => 'ThemeAtelier',
                'agent-timezone'     => '',
                'agent-photo'        => ['url' => $base_url . 'agent1.webp'],
                'agent-online-text'  => __('I am online', 'better-chat-support'),
                'agent-offline-text' => __('I am offline', 'better-chat-support'),
                'opt-availablity'    => [],
            ],
        ];
    }

    public function save_settings(\WP_REST_Request $request)
    {
        $option = $request->get_param('option');
        if (!in_array($option, $this->allowed_options, true)) {
            return new \WP_Error('invalid_option', 'Invalid option key.', ['status' => 400]);
        }
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return new \WP_Error('invalid_data', 'Request body must be a JSON object.', ['status' => 400]);
        }
        $sanitized = self::deep_sanitize($body);
        update_option($option, $sanitized);
        return rest_ensure_response(['success' => true, 'message' => 'Settings saved successfully.']);
    }

    /**
     * Recursively sanitize a settings payload. `public static` (not just
     * `private`) so `Admin\Rest\PreviewRest` can reuse the exact same
     * sanitization rules for unsaved preview values instead of duplicating them.
     */
    public static function deep_sanitize($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[sanitize_text_field((string) $key)] = self::deep_sanitize($value);
            }
            return $result;
        }
        if (is_string($data)) {
            // Allow HTML for fields like wp_editor / custom CSS/JS (kses_post is sufficient)
            return wp_kses_post($data);
        }
        // Booleans and numbers pass through as-is
        return $data;
    }

    public function get_timezones(): \WP_REST_Response
    {
        $result = [];

        foreach (\DateTimeZone::listIdentifiers() as $id) {
            $code = '';
            try {
                $location = (new \DateTimeZone($id))->getLocation();
                if (is_array($location) && isset($location['country_code'])) {
                    $raw = $location['country_code'];
                    if (is_string($raw) && preg_match('/^[A-Z]{2}$/', $raw)) {
                        $code = $raw;
                    }
                }
            } catch (\Throwable $e) {
                // Some identifiers have no location data
            }

            $result[] = ['id' => $id, 'code' => $code];
        }

        return rest_ensure_response($result);
    }

    public function get_wp_pages(): \WP_REST_Response
    {
        $pages = get_posts([
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        return rest_ensure_response(array_map(static fn($p) => [
            'id'    => $p->ID,
            'title' => get_the_title($p),
        ], $pages));
    }

    public function get_wp_content(): \WP_REST_Response
    {
        // Pages
        $pages_raw = get_posts(['post_type' => 'page', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
        $pages = array_map(static fn($p) => ['id' => $p->ID, 'title' => get_the_title($p)], $pages_raw);

        // Posts
        $posts_raw = get_posts(['post_type' => 'post', 'posts_per_page' => 500, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
        $posts = array_map(static fn($p) => ['id' => $p->ID, 'title' => get_the_title($p)], $posts_raw);

        // Post categories
        $cats_raw = get_terms(['taxonomy' => 'category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $categories = !is_wp_error($cats_raw) ? array_map(static fn($t) => ['id' => $t->term_id, 'name' => $t->name], $cats_raw) : [];

        // Post tags
        $tags_raw = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $tags = !is_wp_error($tags_raw) ? array_map(static fn($t) => ['id' => $t->term_id, 'name' => $t->name], $tags_raw) : [];

        // WooCommerce products (only if WC active)
        $products = [];
        if (post_type_exists('product')) {
            $products_raw = get_posts(['post_type' => 'product', 'posts_per_page' => 500, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
            $products = array_map(static fn($p) => ['id' => $p->ID, 'title' => get_the_title($p)], $products_raw);
        }

        // WooCommerce product categories
        $product_categories = [];
        if (taxonomy_exists('product_cat')) {
            $pc_raw = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
            $product_categories = !is_wp_error($pc_raw) ? array_map(static fn($t) => ['id' => $t->term_id, 'name' => $t->name], $pc_raw) : [];
        }

        // WooCommerce product tags
        $product_tags = [];
        if (taxonomy_exists('product_tag')) {
            $pt_raw = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
            $product_tags = !is_wp_error($pt_raw) ? array_map(static fn($t) => ['id' => $t->term_id, 'name' => $t->name], $pt_raw) : [];
        }

        return rest_ensure_response(compact('pages', 'posts', 'categories', 'tags', 'products', 'product_categories', 'product_tags'));
    }
}
