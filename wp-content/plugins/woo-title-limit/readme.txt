=== Title Limit for WooCommerce ===
Contributors: DimaW
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X6JSPSAFCXJBW
Tags: woocommerce, product title, title, length, limit, shop
Requires at least: 3.0.1
Tested up to: 6.8.3
Stable tag: 2.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Set a limit for WooCommerce product titles at the frontend of your shop.

== Description ==
Simply set the maximum length of product titles for WooCommerce in the shop, category, tag, product view and on the homepage.
No broken templates due to too long product titles.
Useful for automatically added affiliate products.

= Features: =
* Set max. title length for the shop view
* Set max. title length for the product category view
* Set max. title length for the product tag view
* Set max. title length for the product view
* Set max. title length for the home page
* Optional: limit title length at the end of the current word instead of breaking the title
* Automatically limit product titles in Woocommerce widgets (optional)
* Add "..." if product titles are longer then the limit

== Installation ==

1. Upload the plugin directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Title Limit for WooCommerce screen to configure the plugin and set up your title lengths.

== Frequently Asked Questions ==

Please use the Support Forum of the WordPress Plugin Directory.

== Screenshots ==
1. WooCommerce frontend without a limit for product titles.
2. WooCommerce frontend with a limit for product titles using Woo Title Limit.
3. Woo Title Limit easy to use settings page.
4. Woo Title Limit easy to use settings page.
5. Woo Title Limit easy to use settings page.

== Changelog ==

= 2.0.6 =

* Fixed version

= 2.0.5 =

* Plugin name changed to comply with WordPress guidelines

= 2.0.4 =

* tested up to WordPress 6.8.3
* tested up to WooCommerce 10.2.2

= 2.0.3 =

* fix version

= 2.0.2 =

* tested up to WordPress 5.8.3
* tested up to WooCommerce 6.0.0

= 2.0.1 =

* tested up to version WordPress 5.5.1
* tested up to WooCommerce 4.6.1

= 2.0.0 =
* The whole plugin was refactored. The entire structure is now easy to maintain. In this way, new functions can be integrated more easily and errors corrected more quickly.
* The settings page was revised and divided into tabs. This makes it easier to integrate new settings and improves the overview.
* Die Einstellungen werden in der Datenbank jetzt in mehreren Optionen gespeichert. Version 2.0.0 ist dabei kompatibel zu früheren Versionen, da bei einem Update die alten Einstellungen migriert werden. Du kannst jederzeit auf eine ältere Version wechseln, solltest du Fehler feststellen.
* update: The texts have been revised and now better describe the respective option.
* add: additional translations
* add: options for product tag sites
* add: readme.md file
* fix: warnings and notices fixed, removed unnecessary code
* tested with: wordpress 5.3.2 and WooCommerce 3.9.1

= 1.4.4 =
* fix: undefined notice

= 1.4.3 =
* fix: version

= 1.4.2 =
* fix: undefined notices
* tested for wordpress 5.1

= 1.4.1 =
* fix: not working on home page
* tested for wordpress 4.9.4

= 1.4 =
* added: product title settings for home page
* tested: woocommerce 3.0.9

= 1.3 =
* tested: wordpress 4.8
* seperate css to files
* bugfixes

= 1.2.2 =
* tested: wordpress 4.7.1

= 1.2.1 =
* fixed: no title output for Woocommerce shortcodes

= 1.2 =
* added: input fields now required
* added: option to limit title by end of the upcoming word instead of breaking the word at limit
* added: option for automatically limit product titles in Woocommerce widgets (beta)
* added: uninstall routine - after deleting the plugin at backend, plugin options in database deleted too
* updated: translation and languages directory
* fixed: error selecting right post type
* fixed: "..." only added if the title contains more characters then the character limit

= 1.1.1 =
* fixed: error in v1.1

= 1.1 =
* added: new option to add "..." at the end of a shortened title
* added: new tags
* added: better description of the plugin
* added: new screenshot showing the new option
* fixed: translation/spelling errors in description and option window


= 1.0.3 =
* fixed: a php error message at frontend if Woocommerce isnt activated or installed
* added: warning message at Woo Title Limit options page if Woo Title Limit is activated but WooComerce still not installed or activated
* added: text-domain and domain-path to plugin header for localization support
* added: some comments to plugin code for better unterstanding
* added: screenshots, logo (icon) and banner to wordpress.org plugins page

= 1.0.2 =
* fixed: (again) readme and description errors

= 1.0.1 =
* fixed: readme and description errors

= 1.0 =
* Plugin release
