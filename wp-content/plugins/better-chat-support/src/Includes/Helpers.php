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
 * @subpackage better-chat-support/Helpers
 * @author     ThemeAtelier<themeatelierbd@gmail.com>
 */

namespace ThemeAtelier\BetterChatSupport\Includes;

/**
 * The Helpers class to manage all public facing stuffs.
 *
 * @since 1.0.0
 */
class Helpers
{
	/**
	 * The min of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $min   The slug of this plugin.
	 */
	private $min;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct()
	{
		$this->min   = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
	}

	/**
	 * Register the All scripts for the public-facing side of the site.
	 *
	 * @since    2.0
	 */
	public function register_all_scripts()
	{
		wp_register_style('icofont', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/css/icofont' . $this->min . '.css', array(), '1.0', false);
		wp_register_style('mcs-main', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/css/mSupport-main' . $this->min . '.css', array(), '1.0', false);
		/********************
				Js Enqueue
		 ********************/
		wp_register_script('moment', array('jquery'), '1.0', true);
		wp_register_script('moment-timezone', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/js/moment-timezone-with-data' . $this->min . '.js', array('jquery'), '1.0', true);
		wp_register_script('mcs-main', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/js/mSupport-main' . $this->min . '.js', array('jquery'), '1.0', true);
		wp_register_script('tma-bubble-stack', BETTER_CHAT_SUPPORT_DIR_URL . 'src/Frontend/assets/js/bubble-stack.js', array(), BETTER_CHAT_SUPPORT_VERSION, true);
	}

	public static function user_availability($optAvailablity)
	{
		$days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

		$availability = [];
		foreach ($days as $day) {
			$from = !empty($optAvailablity["availablity-$day"]['from']) ? $optAvailablity["availablity-$day"]['from'] : '00:00';
			$to = !empty($optAvailablity["availablity-$day"]['to']) ? $optAvailablity["availablity-$day"]['to'] : '23:59';

			$availability[$day] = "$from-$to";
		}

		return json_encode($availability);
	}

	/**
	 * Build the initials shown by the built-in ("Default") agent avatar.
	 *
	 * Takes the first letter of the first and last word, so "John Doe" becomes
	 * "JD" and a single-word name becomes one letter. Returns an empty string
	 * when no name is set, which renders an empty coloured circle.
	 *
	 * @param  string $name Agent name.
	 * @return string
	 */
	public static function agent_initials($name)
	{
		$name = trim(wp_strip_all_tags((string) $name));
		if ($name === '') {
			return '';
		}

		$words = preg_split('/\s+/', $name);
		$initials = mb_substr($words[0], 0, 1);
		if (count($words) > 1) {
			$initials .= mb_substr($words[count($words) - 1], 0, 1);
		}

		return mb_strtoupper($initials);
	}



	/**
	 * Custom Template locator .
	 *
	 * @param  mixed $template_name template name .
	 * @param  mixed $template_path template path .
	 * @param  mixed $default_path default path .
	 * @return string
	 */
	public static function better_chat_support_locate_template($template_name, $template_path = '', $default_path = '')
	{
		if (! $template_path) {
			$template_path = 'better-chat-support/templates';
		}
		if (! $default_path) {
			$default_path = BETTER_CHAT_SUPPORT_DIR_PATH . 'src/Frontend/Templates/';
		}
		$template = locate_template(trailingslashit($template_path) . $template_name);
		// Get default template.
		if (! $template) {
			$template = $default_path . $template_name;
		}
		// Return what we found.
		return $template;
	}

	public static function should_display_element($options)
	{
		$visibilities = !empty($options['visibility']) ? $options['visibility'] : [];

		/*
		 * Resolve each rule's sub-fields from either the new React flat format
		 * (keys stored directly on $options) or the legacy Codestar nested format
		 * (keys stored inside a group array, e.g. $options['visibility_by_page']).
		 * New flat keys take precedence so a re-save in the new admin wins.
		 */
		$g = function (string $key, $group_key, $field_key, $default = '') use ($options) {
			if (isset($options[$key])) {
				return $options[$key]; // flat (new React admin)
			}
			$group = !empty($options[$group_key]) && is_array($options[$group_key]) ? $options[$group_key] : [];
			return isset($group[$field_key]) ? $group[$field_key] : $default; // nested (legacy Codestar)
		};

		// Theme Pages
		$theme_page_target = $g('theme_page_target', 'visibility_by_theme_page', 'theme_page_target', '');
		$theme_page_all    = $g('theme_page_all',    'visibility_by_theme_page', 'theme_page_all',    '');
		$theme_page_raw    = $g('theme_page',        'visibility_by_theme_page', 'theme_page',        []);
		$theme_page        = is_array($theme_page_raw) && !empty($theme_page_raw)
			? array_combine(array_values($theme_page_raw), array_values($theme_page_raw))
			: [];

		// Pages
		$page_target = $g('page_target', 'visibility_by_page', 'page_target', '');
		$page_all    = $g('page_all',    'visibility_by_page', 'page_all',    '');
		$page        = is_array($g('page', 'visibility_by_page', 'page', [])) ? $g('page', 'visibility_by_page', 'page', []) : [];

		// Posts
		$posts_target = $g('posts_target', 'visibility_by_posts', 'posts_target', '');
		$posts_all    = $g('posts_all',    'visibility_by_posts', 'posts_all',    '');
		$posts        = is_array($g('posts', 'visibility_by_posts', 'posts', [])) ? $g('posts', 'visibility_by_posts', 'posts', []) : [];

		// Products
		$product_target = $g('product_target', 'visibility_by_product', 'product_target', '');
		$product_all    = $g('product_all',    'visibility_by_product', 'product_all',    '');
		$product        = is_array($g('product', 'visibility_by_product', 'product', [])) ? $g('product', 'visibility_by_product', 'product', []) : [];

		// Post Categories
		$category_target = $g('category_target', 'visibility_by_category', 'category_target', '');
		$category_all    = $g('category_all',    'visibility_by_category', 'category_all',    '');
		$category        = is_array($g('category', 'visibility_by_category', 'category', [])) ? $g('category', 'visibility_by_category', 'category', []) : [];

		// Post Tags
		$tags_target = $g('tags_target', 'visibility_by_tags', 'tags_target', '');
		$tags_all    = $g('tags_all',    'visibility_by_tags', 'tags_all',    '');
		$tags        = is_array($g('tags', 'visibility_by_tags', 'tags', [])) ? $g('tags', 'visibility_by_tags', 'tags', []) : [];

		// Product Categories
		$product_category_target = $g('product_category_target', 'visibility_by_product_category', 'product_category_target', '');
		$product_category_all    = $g('product_category_all',    'visibility_by_product_category', 'product_category_all',    '');
		$product_category        = is_array($g('product_category', 'visibility_by_product_category', 'product_category', [])) ? $g('product_category', 'visibility_by_product_category', 'product_category', []) : [];

		// Product Tags
		$product_tags_target = $g('product_tags_target', 'visibility_by_product_tags', 'product_tags_target', '');
		$product_tags_all    = $g('product_tags_all',    'visibility_by_product_tags', 'product_tags_all',    '');
		$product_tags        = is_array($g('product_tags', 'visibility_by_product_tags', 'product_tags', [])) ? $g('product_tags', 'visibility_by_product_tags', 'product_tags', []) : [];
		$current_page_id = get_queried_object_id();

		if (empty($visibilities)) {
			return true; // no rules configured → always show
		}

		/*
		 * Helper: evaluate one rule on the current page.
		 * Returns true  → show the widget on this page.
		 * Returns false → hide the widget on this page.
		 * Returns null  → this rule does not apply to the current page type;
		 *                 the caller should continue checking other rules.
		 */
		$check_rule = function (string $target, bool $all, array $list, bool $is_current_type) use ($current_page_id) {
			if (!$is_current_type) {
				return null; // rule type doesn't cover the current page — skip
			}
			if ($target === 'include') {
				if ($all || empty($list)) return true;
				return in_array($current_page_id, $list);
			} else { // exclude
				if ($all || empty($list)) return false;
				return !in_array($current_page_id, $list);
			}
		};

		/*
		 * For theme_page rules we need to match against named page conditions rather
		 * than a queried-object ID, so handle it separately.
		 */
		$check_theme_rule = function (string $target, bool $all, array $list) {
			$conditions = [
				'post_page'   => is_home(),
				'search_page' => is_search(),
				'404_page'    => is_404(),
			];
			$matched_key = null;
			foreach ($conditions as $key => $matches) {
				if ($matches) { $matched_key = $key; break; }
			}
			if ($matched_key === null) return null; // not on a theme page
			if ($target === 'include') {
				if ($all || empty($list)) return true;
				return in_array($matched_key, $list);
			} else {
				if ($all || empty($list)) return false;
				return !in_array($matched_key, $list);
			}
		};

		/*
		 * Identify which visibility slot the current page falls into.
		 * The $visibilities array is a WHITELIST: the widget is only shown on
		 * the content types listed there. If the current page type is not in
		 * the list → hide. Once the type is matched → apply the include/exclude
		 * sub-rule to decide the final show/hide.
		 *
		 * Pages not covered by any of the known type detectors (e.g. custom
		 * post type archives, the static front page, etc.) are treated as
		 * "unknown" and always shown so the widget is never accidentally hidden
		 * on pages the rules weren't designed to control.
		 */

		// Determine current page type
		$current_type = null;
		if (is_home() || is_search() || is_404()) {
			$current_type = 'theme_page';
		} elseif (is_page()) {
			$current_type = 'page';
		} elseif (is_single() && 'post' === get_post_type()) {
			$current_type = 'posts';
		} elseif (is_single() && 'product' === get_post_type()) {
			$current_type = 'product';
		} elseif (is_category()) {
			$current_type = 'category';
		} elseif (is_tag()) {
			$current_type = 'tags';
		} elseif (function_exists('is_product_category') && is_product_category()) {
			$current_type = 'product_category';
		} elseif (function_exists('is_product_tag') && is_product_tag()) {
			$current_type = 'product_tags';
		}

		// Unknown page type (front page, custom post types, etc.) → always show
		if ($current_type === null) {
			return true;
		}

		// Current page type is not whitelisted → hide
		if (!in_array($current_type, $visibilities)) {
			return false;
		}

		// Current page type is whitelisted → apply the include/exclude sub-rule
		if ($current_type === 'theme_page') {
			$result = $check_theme_rule(
				$theme_page_target ?: 'include',
				(bool) $theme_page_all,
				$theme_page
			);
			return $result !== null ? $result : true;
		}

		$sub_rules = [
			'page'             => [$page_target,             (bool) $page_all,             $page,             is_page()],
			'posts'            => [$posts_target,            (bool) $posts_all,            $posts,            is_single() && 'post' === get_post_type()],
			'product'          => [$product_target,          (bool) $product_all,          $product,          is_single() && 'product' === get_post_type()],
			'category'         => [$category_target,         (bool) $category_all,         $category,         is_category()],
			'tags'             => [$tags_target,             (bool) $tags_all,             $tags,             is_tag()],
			'product_category' => [$product_category_target, (bool) $product_category_all, $product_category, function_exists('is_product_category') && is_product_category()],
			'product_tags'     => [$product_tags_target,    (bool) $product_tags_all,     $product_tags,     function_exists('is_product_tag') && is_product_tag()],
		];

		if (isset($sub_rules[$current_type])) {
			[$target, $all, $list, $is_type] = $sub_rules[$current_type];
			$result = $check_rule($target ?: 'include', $all, $list, (bool) $is_type);
			return $result !== null ? $result : true;
		}

		return true;
	}
}
