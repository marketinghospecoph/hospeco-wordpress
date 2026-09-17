<?php

/**
 * Admin live-preview REST controller.
 *
 * Renders the REAL frontend widget — `Frontend::render_widget()`, the same
 * compiled CSS and jQuery script the live site uses — from inside the admin
 * SPA, so editing a Floating Chat setting updates a genuine iframe'd copy of
 * the widget instead of a hand-maintained re-implementation.
 *
 * The posted `values` are sanitized (via `SettingsController::deep_sanitize()`,
 * the same rules `save_settings()` applies) and merged over the real saved
 * `mcs-opt` option — but NEVER persisted. This endpoint only ever reads, it
 * never calls `update_option()`.
 *
 * @package better-chat-support
 * @subpackage better-chat-support/Admin/Rest
 */

namespace ThemeAtelier\BetterChatSupport\Admin\Rest;

use ThemeAtelier\BetterChatSupport\Admin\SettingsController;
use ThemeAtelier\BetterChatSupport\Frontend\Frontend;

if (!defined('ABSPATH')) {
    die;
}

class PreviewRest
{
    /** Fixed instead of `wp_rand(1, 13)` so the popup animation doesn't
     *  visibly change on every unrelated field edit while previewing. */
    const PREVIEW_ANIMATION_SEED = 7;

    /** Fixed id for the preview's `#better_chat_support_button_{id}` root, so
     *  the script running inside the preview iframe always targets a stable
     *  element. */
    const PREVIEW_UNIQUE_ID = 'mcs_admin_preview';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('better-chat-support/v1', '/preview/settings/mcs-opt', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'preview_settings'],
            'permission_callback' => [$this, 'can_manage'],
        ]);
    }

    public function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * POST /preview/settings/mcs-opt — merges the posted (unsaved) values over
     * the real saved `mcs-opt` option and renders the widget for that merged
     * state, without persisting anything.
     */
    public function preview_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $incoming = $request->get_param('values');
        $incoming = is_array($incoming) ? $incoming : [];
        $sanitized = SettingsController::deep_sanitize($incoming);

        $existing = get_option('mcs-opt', []);
        $existing = is_array($existing) ? $existing : [];
        $merged   = array_merge($existing, $sanitized);

        return rest_ensure_response($this->render($merged));
    }

    /**
     * Render the widget for a merged (never persisted) options array: the
     * CSS-variable style block + widget HTML from `Frontend::render_widget()`,
     * plus a `<head>` fragment loading the real frontend CSS/JS handles via
     * `WP_Styles`/`WP_Scripts::do_items()` (resolves each handle's actual
     * registered src/version/deps — nothing here hardcodes a URL).
     *
     * @return array{head:string,body:string,foot:string}
     */
    private function render(array $options): array
    {
        $ch_settings = get_option('mcs_settings');
        $ch_settings = is_array($ch_settings) ? $ch_settings : [];

        ob_start();
        Frontend::render_widget($options, $ch_settings, self::PREVIEW_UNIQUE_ID, self::PREVIEW_ANIMATION_SEED);
        $body = ob_get_clean();

        [$head, $foot] = $this->render_assets($options);

        return ['head' => $head, 'body' => $body, 'foot' => $foot];
    }

    /**
     * Mirrors the enqueue/localize calls in `Frontend::enqueue_scripts()` and
     * `Frontend::better_chat_support_header_script()`, built from the merged
     * preview options, then prints the real registered CSS + JS tags.
     *
     * Styles are safe to return for `<head>`, but the scripts MUST be
     * rendered separately and placed after the widget's body HTML: on the
     * real site `mcs-main` is registered `in_footer = true` and both it and
     * the widget markup hook into `wp_footer` (markup first), so by the time
     * the script runs, the widget it queries for already exists in the DOM.
     * Printing the script in `<head>` instead would run it before the body
     * exists, so no click handler would ever attach.
     *
     * @return array{0:string,1:string} [$headHtml, $footHtml]
     */
    private function render_assets(array $options): array
    {
        wp_enqueue_style('icofont');
        wp_enqueue_style('mcs-main');
        wp_enqueue_script('moment');
        wp_enqueue_script('moment-timezone');
        wp_enqueue_script('mcs-main');

        // Mirrors Frontend::better_chat_support_header_script(), normally
        // printed on `wp_head` — mSupport-main.js references this bare global
        // (unguarded, top-level) at load time.
        $alternative_bubble = $options['alternative_mSupportBubble'] ?? '';
        $inline_vars = '<script type="text/javascript">var alternativeMSupportBubble = '
            . wp_json_encode((string) $alternative_bubble) . ';</script>';

        ob_start();
        wp_styles()->do_items(['icofont', 'mcs-main']);
        $head = ob_get_clean();

        ob_start();
        echo $inline_vars;
        wp_scripts()->do_items(['jquery', 'moment', 'moment-timezone', 'mcs-main']);
        $foot = ob_get_clean();

        return [$head, $foot];
    }
}
