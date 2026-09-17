jQuery(document).ready(function ($) {
	"use strict";

	// Show/hide settings for post format when choose post format
	var $format = $('#post-formats-select').find('input.post-format'),
		$formatBox = $('#post-format-settings');

	$format.on('change', function () {
		var type = $format.filter(':checked').val();

		$formatBox.hide();
		if ($formatBox.find('.rwmb-field').hasClass(type)) {
			$formatBox.show();
		}

		$formatBox.find('.rwmb-field').slideUp();
		$formatBox.find('.' + type).slideDown();
	});
	$format.filter(':checked').trigger('change');

	// Show/hide settings for custom layout settings
	$('#custom_layout').on('change', function () {
		if ($(this).is(':checked')) {
			$('.rwmb-field.custom-layout').slideDown();
		}
		else {
			$('.rwmb-field.custom-layout').slideUp();
		}
	}).trigger('change');

	$('#post-style-settings #post_style').on('change', function () {
		if ($(this).val() == '2') {
			$('#post-style-settings').find('.show-post-header-2').slideDown();
		}
		else {
			$('#post-style-settings').find('.show-post-header-2').slideUp();
		}

	}).trigger('change');

	$('#product-360-view').find('.rwmb-image-item').css({
		width: '50px'
	});

	// Show/hide settings for template settings
	$('#page_template').on('change', function () {

		if ($(this).val() == 'template-homepage.php' ||
            $(this).val() == 'template-fullwidth.php' ||
			$(this).val() == 'template-coming-soon-page.php' ) {
			$('#page-header-settings').hide();
		} else {
			$('#page-header-settings').show();
		}

	}).trigger('change');
});
