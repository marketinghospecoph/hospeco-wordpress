<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://themeatelier.net
 * @since      1.0.0
 *
 * @package better-chat-support
 * @subpackage better-chat-support
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

/**
 * Delete plugin data function.
 *
 * @return void
 */
function better_chat_support_delete_plugin_data()
{
	global $wpdb;
	// Delete plugin option settings.
	$options = [
		'mcs-opt',
		'mcs_settings',
		'better_chat_support_version',
		'better_chat_support_db_version',
		'better_chat_support_first_version',
		'better_chat_support_activation_date',
		'themeatelier_offer_banner_dismissed_new_year_2026',
	];

	foreach ($options as $option_name) {
		delete_option($option_name);       // Delete regular option.
		delete_site_option($option_name); // Delete multisite option.
	}

	// Delete plugin cached data (transients).
	$transients = [
		'better_chat_support_recommended_plugins',
		'themeatelier_plugins',
	];
	foreach ($transients as $transient) {
		delete_transient($transient);
		delete_site_transient($transient);
	}

	// Delete the analytics table.
	$table_name = $wpdb->prefix . 'mcs_analytics';
	$wpdb->query("DROP TABLE IF EXISTS `$table_name`");
}

// The "Clean-up Data on Deletion" toggle is saved in the Advanced settings
// option (mcs_settings) — read it from there. Only run cleanup when enabled.
$mcs_settings = get_option('mcs_settings');
// Default OFF: data is only deleted when the toggle is EXPLICITLY enabled.
// A missing key, or any falsy/"false"/"0" value, must never trigger cleanup.
$cleanup_raw                     = is_array($mcs_settings) ? ($mcs_settings['cleanup_data_deletion'] ?? false) : false;
$better_chat_support_data_delete = (true === $cleanup_raw || 1 === $cleanup_raw || '1' === $cleanup_raw || 'true' === $cleanup_raw);

if ($better_chat_support_data_delete) {
	better_chat_support_delete_plugin_data();
}
