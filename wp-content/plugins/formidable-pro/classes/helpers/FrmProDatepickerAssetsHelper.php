<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProDatepickerAssetsHelper {

	/**
	 * Initialize datepicker on the style settings page.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	public static function init_admin_js_and_css() {
		if ( FrmProAppHelper::use_jquery_datepicker() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			return;
		}
		self::enqueue_flatpickr_assets();
	}

	/**
	 * Load the theme assets for the datepicker.
	 *
	 * @since 6.24
	 *
	 * @param string $theme_css The theme CSS.
	 *
	 * @return void
	 */
	public static function load_theme_assets( $theme_css ) {
		if ( ! FrmProAppHelper::use_jquery_datepicker() ) {
			return;
		}

		wp_enqueue_style( 'jquery-theme', FrmProStylesController::jquery_css_url( $theme_css ), self::jquery_theme_dependencies( $theme_css ), FrmProDb::$plug_version );
	}

	/**
	 * Loads the structural jQuery UI stylesheet underneath a theme that comes from somewhere else.
	 *
	 * Every named theme apart from the default is fetched from a CDN, so it can be missing, blocked
	 * or unreachable. When that happens the datepicker gets no jQuery UI CSS at all and renders with
	 * literal Prev and Next text over stacked controls rather than arrows. Registering a bundled
	 * sheet as a dependency guarantees it loads first, so a failed theme degrades to a usable
	 * calendar, and a theme that does load supplies the appearance.
	 *
	 * The sheet used here holds layout only. ui-lightness/jquery-ui.css is a complete theme plus a
	 * block of Formidable's own color rules, and those are written as .ui-datepicker .ui-widget-header
	 * rather than the bare .ui-widget-header a stock theme uses, so putting it underneath a theme
	 * out-specified the theme instead of deferring to it. A site that picked a named theme saw
	 * Formidable's colors with only the theme's icon sprite left showing through.
	 *
	 * @since 6.35
	 *
	 * @param string $theme_css The selected theme.
	 *
	 * @return array<string> Style handles the theme stylesheet must load after.
	 */
	private static function jquery_theme_dependencies( $theme_css ) {
		if ( FrmProStylesController::use_default_style( $theme_css ) ) {
			return array();
		}

		wp_enqueue_style( 'frm-jquery-ui-base', FrmProAppHelper::plugin_url() . '/css/jquery-ui-structure.css', array(), FrmProDb::$plug_version );

		return array( 'frm-jquery-ui-base' );
	}

	/**
	 * Enqueue the datepicker assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	public static function enqueue_datepicker_assets() {
		if ( FrmProAppHelper::use_jquery_datepicker() ) {
			self::enqueue_jquery_assets();
			return;
		}

		self::enqueue_flatpickr_assets();
	}

	/**
	 * Enqueue the jQuery assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	private static function enqueue_jquery_assets() {
		FrmProStylesController::enqueue_jquery_css();
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	/**
	 * Enqueues the Flatpickr localization file for a date field's locale.
	 *
	 * This lives here because the l10n files ship inside this plugin, so this is the only place
	 * that can know which locales Flatpickr actually covers and what each file calls itself. The
	 * Dates add-on enqueues the same files for its own screens and calls this rather than keeping
	 * a second copy of the mapping.
	 *
	 * @since 6.35
	 *
	 * @param string $locale       The locale stored on the date field, in jQuery UI's codes.
	 * @param array  $dependencies Script handles this file must load after.
	 * @param string $version      Version string for the enqueued handle.
	 *
	 * @return void
	 */
	public static function enqueue_flatpickr_locale( $locale, $dependencies = array(), $version = '' ) {
		$script_path = self::get_flatpickr_locale_path( $locale );

		if ( ! $script_path ) {
			return;
		}

		wp_enqueue_script(
			'flatpickr-locale-' . basename( $script_path, '.js' ),
			FrmProAppHelper::plugin_url() . $script_path,
			$dependencies,
			$version ? $version : FrmProDb::$plug_version
		);
	}

	/**
	 * Gets the path to the Flatpickr localization file that covers a locale.
	 *
	 * @since 6.35
	 *
	 * @param string $locale The locale stored on the date field, in jQuery UI's codes.
	 *
	 * @return string The plugin relative path, or an empty string when Flatpickr has no file for it.
	 */
	public static function get_flatpickr_locale_path( $locale ) {
		if ( ! $locale ) {
			return '';
		}

		// Flatpickr is English out of the box and ships no en* localization file, so en-GB and
		// friends would only ever request a file that does not exist.
		if ( str_starts_with( $locale, 'en' ) ) {
			return '';
		}

		$locale_files = self::get_flatpickr_locale_files();
		$safe_locale  = preg_replace( '/[^A-Za-z0-9_-]/', '', $locale_files[ $locale ] ?? $locale );
		$script_path  = '/js/utils/flatpickr/l10n/' . $safe_locale . '.js';

		// Flatpickr covers fewer locales than jQuery UI did. Without this the browser requests a
		// missing file and logs a 404 plus a MIME type error on every page holding a date field.
		if ( ! file_exists( FrmProAppHelper::plugin_path() . $script_path ) ) {
			return '';
		}

		return $script_path;
	}

	/**
	 * Maps a date field's locale code onto the Flatpickr localization file that covers it.
	 *
	 * The field stores jQuery UI's locale codes and Flatpickr names a number of the same languages
	 * differently, so without this every one of these silently falls back to English. Codes that
	 * Flatpickr has no file for at all are deliberately absent, since English is all there is for
	 * them. Note that jQuery UI's sr is Cyrillic and its sr-SR is Latin, which is the reverse of
	 * how Flatpickr names the two.
	 *
	 * @since 6.35
	 *
	 * @return array<string,string>
	 */
	private static function get_flatpickr_locale_files() {
		return array(
			'ar-DZ' => 'ar-dz',
			'ca'    => 'cat',
			'cy-GB' => 'cy',
			'de-AT' => 'at',
			'el'    => 'gr',
			'fr-CA' => 'fr',
			'fr-CH' => 'fr',
			'kk'    => 'kz',
			'nb'    => 'no',
			'pt-BR' => 'pt',
			'sr'    => 'sr-cyr',
			'sr-SR' => 'sr',
			'vi'    => 'vn',
			'zh-CN' => 'zh',
			'zh-HK' => 'zh-tw',
			'zh-TW' => 'zh-tw',
		);
	}

	/**
	 * Enqueues the flatpickr assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	private static function enqueue_flatpickr_assets() {
		wp_enqueue_script(
			'flatpickr',
			FrmProAppHelper::plugin_url() . '/js/utils/flatpickr/flatpickr.min.js',
			array(),
			FrmProDb::$plug_version,
			true
		);

		wp_enqueue_style( 'flatpickr', FrmProAppHelper::plugin_url() . '/css/flatpickr.css', array(), FrmProDb::$plug_version );

		// Keep the style settings in their own stylesheet so flatpickr.css stays replaceable.
		// The dependency is what guarantees these rules load after the ones they override.
		wp_enqueue_style( 'frm-datepicker', FrmProAppHelper::plugin_url() . '/css/frm-datepicker.css', array( 'flatpickr' ), FrmProDb::$plug_version );
	}
}
