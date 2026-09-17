( function() {
	/* globals frmDom, wp, jQuery, Dropzone, frmStylerFunctions, frmProStyleSettingsSVGs, flatpickr */
	'use strict';

	if ( 'object' !== typeof window.frmStylerFunctions ) {
		return;
	}

	const { getCardByStyleId, getStyleInputNameModalContent, trackUnsavedChange, stylerModal } = window.frmStylerFunctions;
	const isListPage = document.getElementsByClassName( 'frm-style-card' ).length > 0;

	const elements = {
		templateCssTag: false // Keep track of this so we can remove the previous tag when adding a new one.
	};
	let abortController;

	// How much room an inline range needs before it is worth showing both of its
	// months. Below this the two share the width a real form gives one, and the day
	// cells end up too small to tell a style anything.
	const MIN_WIDTH_FOR_TWO_MONTHS = 380;

	initPreview();

	if ( isListPage ) {
		initListPage();
	} else {
		initEditPage();
	}

	/**
	 * @returns {void}
	 */
	function initListPage() {
		const newStyleTrigger           = document.getElementById( 'frm_new_style_trigger' );
		const { div, a, span, success } = frmDom;
		const { footerButton }          = frmDom.modal;
		const { onClickPreventDefault } = frmDom.util;
		const { doJsonPost }            = frmDom.ajax;
		const { __ }                    = wp.i18n;
		const newStyleUrl               = newStyleTrigger.dataset.newStyleUrl;

		onClickPreventDefault( newStyleTrigger, handleNewStyleTriggerClick );
		wp.hooks.addFilter( 'frm_style_card_dropdown_options', 'formidable', addDropdownOptionsToStyleCard );
		wp.hooks.addAction( 'frm_style_card_click', 'formidable', handleCardClick );
		addApplyButtonEventListener();
		initDatepickerSample();

		/**
		 * @returns {void}
		 */
		function handleNewStyleTriggerClick() {
			if ( 'undefined' !== typeof frmExpiredVars ) {
				wp.hooks.doAction( 'frm_show_expired_modal' );
				return;
			}

			stylerModal(
				'frm_new_style_modal',
				{
					title: __( 'Create new style', 'formidable-pro' ),
					content: getStyleInputNameModalContent( 'new' ),
					footer: getNewStyleModalFooter()
				}
			);
		}

		/**
		 * Add Pro style options to the style card dropdown menu.
		 *
		 * @param {Array} options
		 * @param {Object} args {
		 *     @type {Object} data {
		 *         @type {String} styleId
		 *         @type {String} duplicateUrl
		 *         @type {boolean} isTemplate
		 *     }
		 *     @type {Function} addIconToOption
		 * }
		 * @returns {Array}
		 */
		function addDropdownOptionsToStyleCard( options, args ) {
			const { data, addIconToOption, isTemplate } = args;

			if ( isTemplate ) {
				return options;
			}

			// Swap the order a bit. Remove the reset option at the bottom of the list (defined in lite).
			// Then add it back after adding Set as Default and Duplicate.
			const resetOption = options.pop();
			const renameOption = options.pop();

			options.push(
				{ anchor: getSetAsDefaultOption( data.styleId, addIconToOption ), type: 'set-as-default' },
				renameOption,
				{ anchor: getDuplicateOption( data.duplicateUrl, data.styleId, addIconToOption ), type: 'duplicate' },
				resetOption,
				{ anchor: getDeleteOption( data.styleId, addIconToOption ), type: 'delete' }
			);

			return options;
		}

		/**
		 * @param {String} styleId
		 * @param {Function} addIconToOption
		 * @returns {HTMLElement}
		 */
		function getDeleteOption( styleId, addIconToOption ) {
			const deleteOption = a( __( 'Delete', 'formidable-pro' ) );
			addIconToOption( deleteOption, 'frm_delete_icon' );

			onClickPreventDefault(
				deleteOption,
				() => stylerModal(
					'frm_delete_style_modal',
					{
						title: __( 'Delete Style', 'formidable-pro' ),
						content: getDeleteStyleModalContent(),
						footer: getDeleteStyleModalFooter( styleId )
					}
				)
			);

			return deleteOption;
		}

		function getDeleteStyleModalContent() {
			const content = div({
				text: __( 'Permanently delete this style?', 'formidable-pro' ),
				className: 'inside'
			});
			return content;
		}

		/**
		 * @param {String} styleId
		 * @returns {HTMLElement}
		 */
		function getDeleteStyleModalFooter( styleId ) {
			const cancelButton = footerButton({ text: __( 'Cancel', 'formidable-pro' ), buttonType: 'cancel' });
			cancelButton.classList.add( 'dismiss' );

			const deleteButton = footerButton({ text: __( 'Delete Style', 'formidable-pro' ), buttonType: 'red' });
			onClickPreventDefault( deleteButton, () => deleteStyle( styleId ) );

			return div({
				children: [ cancelButton, deleteButton ]
			});
		}

		/**
		 * @param {String} styleId
		 * @returns {void}
		 */
		function deleteStyle( styleId ) {
			const formData = new FormData();
			formData.append( 'id', styleId );
			doJsonPost( 'delete_style', formData ).then(
				() => {
					fadeAndRemoveStyleCard( styleId );
					success( span( __( 'Successfully deleted style', 'formidable-pro' ) ) );
				}
			);
		}

		/**
		 * Styles are deleted with a fetch call so after the card is deleted we also need to hide the deleted style's card.
		 *
		 * @param {String} styleId
		 * @returns {void}
		 */
		function fadeAndRemoveStyleCard( styleId ) {
			const card = getCardByStyleId( styleId );
			if ( ! card ) {
				return;
			}

			if ( card.classList.contains( 'frm-active-style-card' ) ) {
				selectDefaultStyle();
			}

			jQuery( card ).fadeOut(() => {
				card.remove();
				syncCustomPagination();
				maybeDeleteCustomCardWrapper();
			});
		}

		/**
		 * Trigger a click on the default style.
		 * This is required when deleting the selected style. Otherwise nothing would be selected.
		 *
		 * @returns {void}
		 */
		function selectDefaultStyle() {
			const defaultCard = document.getElementById( 'frm_default_style_cards_wrapper' ).querySelector( '.frm-style-card' );
			if ( defaultCard ) {
				defaultCard.click();
			}
		}

		/**
		 * Adjust the pages after a card is deleted.
		 *
		 * @returns {void}
		 */
		function syncCustomPagination() {
			const cardWrapper = document.getElementById( 'frm_custom_style_cards_wrapper' );
			const pagination  = cardWrapper.querySelector( '.frm-style-card-pagination' );
			if ( ! pagination ) {
				return;
			}

			const firstHiddenCard = cardWrapper.querySelector( '.frm-style-card.frm_hidden' );
			if ( ! firstHiddenCard ) {
				return;
			}

			firstHiddenCard.classList.remove( 'frm_hidden' );

			const numberOfHiddenCards = cardWrapper.querySelectorAll( '.frm-style-card.frm_hidden' ).length;
			if ( 0 === numberOfHiddenCards ) {
				pagination.remove();
				return;
			}

			const anchor = pagination.querySelector( '.frm-show-all-styles' );
			anchor.textContent = __( 'Show all (%d)', 'formidable' ).replace( '%d', numberOfHiddenCards );
		}

		/**
		 * After the last card is deleted, we don't want to show an empty custom card wrapper so remove it.
		 *
		 * @returns {void}
		 */
		function maybeDeleteCustomCardWrapper() {
			const cardWrapper = document.getElementById( 'frm_custom_style_cards_wrapper' );
			if ( ! cardWrapper || cardWrapper.querySelector( '.frm-style-card' ) ) {
				// Either the wrapper was already removed, or there are still more cards.
				return;
			}

			if ( cardWrapper.previousElementSibling.classList.contains( 'frm_form_settings' ) ) {
				// Remove the section title before the wrapper as well.
				cardWrapper.previousElementSibling.remove();
			}

			cardWrapper.remove();
		}

		/**
		 * @param {String} duplicateUrl
		 * @param {String} styleId
		 * @param {Function} addIconToOption
		 * @returns
		 */
		function getDuplicateOption( duplicateUrl, styleId, addIconToOption ) {
			const duplicateOption = a( __( 'Duplicate', 'formidable-pro' ) );
			addIconToOption( duplicateOption, 'frm_clone_icon' );

			onClickPreventDefault(
				duplicateOption,
				() => {
					const card         = getCardByStyleId( styleId );
					const titleElement = card.querySelector( '.frm-style-card-title' );
					stylerModal(
						'frm_duplicate_style_modal',
						{
							title: __( 'Duplicate style', 'formidable-pro' ),
							content: getStyleInputNameModalContent( 'duplicate', titleElement.textContent ),
							footer: getDuplicateStyleModalFooter( duplicateUrl )
						}
					);
				}
			);

			return duplicateOption;
		}

		/**
		 * @param {String} duplicateUrl
		 * @returns {HTMLElement}
		 */
		function getDuplicateStyleModalFooter( duplicateUrl ) {
			const cancelButton = footerButton({ text: __( 'Cancel', 'formidable-pro' ), buttonType: 'cancel' });
			cancelButton.classList.add( 'dismiss' );

			const duplicateButton = footerButton({ text: __( 'Duplicate style', 'formidable-pro' ), buttonType: 'primary' });
			onClickPreventDefault(
				duplicateButton,
				() => maybeRedirectToStylerEdit( duplicateUrl, document.getElementById( 'frm_duplicate_style_name_input' ).value )
			);

			return div({
				children: [ cancelButton, duplicateButton ]
			});
		}

		/**
		 * @param {String} styleId
		 * @param {Function} addIconToOption
		 * @returns {HTMLElement}
		 */
		function getSetAsDefaultOption( styleId, addIconToOption ) {
			const setAsDefaultOption = a( __( 'Set as Default', 'formidable-pro' ) );
			addIconToOption( setAsDefaultOption, 'frm_check1_icon' );

			onClickPreventDefault(
				setAsDefaultOption,
				() => {
					const formData = new FormData();
					formData.append( 'style_id', styleId );
					doJsonPost( 'set_style_as_default', formData ).then(
						() => {
							moveDefaultStyle( styleId );
							success( span( __( 'Successfully set style as default', 'formidable-pro' ) ) );
						}
					);
				}
			);

			return setAsDefaultOption;
		}

		/**
		 * Move the default style into the default category. The old default goes into custom styles (in its old place).
		 *
		 * @param {String} styleId
		 * @returns {void}
		 */
		function moveDefaultStyle( styleId ) {
			const defaultWrapper = document.getElementById( 'frm_default_style_cards_wrapper' );
			const customWrapper  = document.getElementById( 'frm_custom_style_cards_wrapper' );
			const currentDefault = defaultWrapper.querySelector( '.frm-style-card' );
			const newDefaultCard = getCardByStyleId( styleId );

			customWrapper.insertBefore( currentDefault, newDefaultCard );
			defaultWrapper.append( newDefaultCard );
		}

		/**
		 * @returns {HTMLElement}
		 */
		function getNewStyleModalFooter() {
			const createStyleButton = footerButton({
				text: __( 'Create new style', 'formidable-pro' ),
				buttonType: 'primary'
			});
			createStyleButton.setAttribute( 'disabled', 'disabled' );
			createStyleButton.classList.remove( 'dismiss' );
			onClickPreventDefault( createStyleButton, handleCreateStyleButtonClick );
			const cancelButton = footerButton({
				text: __( 'Cancel', 'formidable-pro' ),
				buttonType: 'cancel'
			});
			cancelButton.classList.add( 'dismiss' );
			return div({ children: [ cancelButton, createStyleButton ] });
		}

		/**
		 * @returns {void}
		 */
		function handleCreateStyleButtonClick() {
			maybeRedirectToStylerEdit( newStyleUrl, document.getElementById( 'frm_new_style_name_input' ).value );
		}

		/**
		 * Redirect to edit a new style.
		 *
		 * @param {String} url The url we're redirecting to. It is either a path to the new style action, or the duplicate action.
		 * @param {String} styleName The name of the new style.
		 * @returns {void}
		 */
		function maybeRedirectToStylerEdit( url, styleName ) {
			if ( '' === styleName ) {
				// Avoid redirecting with an empty name.
				// The button gets disabled on an input event when the name is empty.
				return;
			}

			window.location.href = addFormIdToRedirectUrl( addStyleNameToRedirectUrl( url, styleName ) );
		}

		/**
		 * @param {String} url
		 * @param {String} styleName
		 * @returns {String}
		 */
		function addStyleNameToRedirectUrl( url, styleName ) {
			return url + '&style_name=' + encodeURIComponent( styleName );
		}

		/**
		 * @param {String} url
		 * @returns {String}
		 */
		function addFormIdToRedirectUrl( url ) {
			const params = new URLSearchParams( document.location.search );
			const formId = params.get( 'form' );

			if ( ! formId || isNaN( formId ) ) {
				return url;
			}

			return url + '&form=' + parseInt( formId );
		}

		/**
		 * @param {Object} args
		 * @returns {void}
		 */
		function handleCardClick( args ) {
			const { card, styleIdInput }   = args;
			const isTemplate = 'undefined' !== typeof card.dataset.templateKey;

			if ( ! isTemplate ) {
				syncApplyButtonText( __( 'Apply style', 'formidable-pro' ) );
				removeFormidablesStylesFromDatepickerDiv();
				// This uses a hook that handles custom cards as well, so exit early if the card is not a template.
				return;
			}

			syncApplyButtonText( __( 'Install and apply style', 'formidable-pro' ) );

			styleIdInput.value = card.dataset.templateKey;
			showTemplateInPreview( card.dataset.templateKey );

			removeFormidablesStylesFromDatepickerDiv();
		}

		function removeFormidablesStylesFromDatepickerDiv() {
			const datepickerDiv = document.getElementById( 'ui-datepicker-div' );
			if ( ! datepickerDiv ) {
				return;
			}

			Array.from( document.getElementsByClassName( 'frm-style-card' ) ).forEach(
				card => datepickerDiv.classList.remove( card.dataset.classname )
			);
		}

		/**
		 * @returns {void}
		 */
		function syncApplyButtonText( text ) {
			const applyButton = document.getElementById( 'frm_apply_style' );
			if ( applyButton ) {
				applyButton.querySelector( '.frm-apply-button-text' ).textContent = text;
			}
		}

		/**
		 * @param {String} key
		 * @returns {void}
		 */
		function showTemplateInPreview( key ) {
			const formData = new FormData();
			formData.append( 'template_key', key );

			const preview = document.getElementById( 'frm_style_preview' );
			preview.classList.add( 'frm-loading-style-template' );

			if ( 'undefined' !== typeof abortController && 'function' === typeof abortController.abort && ! abortController.signal.aborted ) {
				// Abort the previous fetch request if we click to preview a template and the previous request has not finished.
				abortController.abort();
			}

			abortController = new AbortController(); // Create a new abort controller because the old one has been aborted.

			const args = { signal: abortController.signal };
			doJsonPost( 'preview_style_template', formData, args )
				.then( handleTemplatePreviewData )
				.catch( () => {} ); // .catch is triggered when aborted. We don't need to handle this as it is aborted intentionally.
		}

		/**
		 * Use the style settings from the template XML and pass them to the frm_change_styling action.
		 * This will return the CSS tag that matches those settings, which we add to the document head.
		 *
		 * @param {Object} response
		 * @returns {void}
		 */
		async function handleTemplatePreviewData( response ) {
			const formData = new FormData();
			formData.append( 'action', 'frm_change_styling' );
			formData.append( 'style_name', 'frm_style_frm_style_template' ); // All templates use the frm_style_template post_name.
			formData.append( 'nonce', frmGlobal.nonce );

			const keys = Object.keys( response.settings );
			keys.forEach( key => formData.append( 'frm_style_setting[post_content][' + key + ']', response.settings[ key ] ) );

			const init = {
				method: 'POST',
				body: formData
			};
			const cssResponse = await fetch( ajaxurl, init );
			const newCssTag   = await cssResponse.text();

			const preview = document.getElementById( 'frm_style_preview' );
			preview.classList.remove( 'frm-loading-style-template' );

			const domParser = new DOMParser();
			const doc       = domParser.parseFromString( newCssTag, 'text/html' );
			const styleTag  = doc.querySelector( 'style' );

			if ( ! styleTag ) {
				// The response is invalid to don't change the HTML.
				return;
			}

			if ( false !== elements.templateCssTag && 'function' === typeof elements.templateCssTag.remove ) {
				elements.templateCssTag.remove();
			}

			elements.templateCssTag = styleTag;
			document.head.append( styleTag );
			toggleSampleFormClass( 'frm_style_frm_style_template' );
		}

		/**
		 * @returns {void}
		 */
		function addApplyButtonEventListener() {
			const applyButton = document.getElementById( 'frm_apply_style' );
			if ( ! applyButton ) {
				return;
			}

			applyButton.addEventListener(
				'click',
				() => document.getElementById( 'frm-publishing' ).querySelector( 'button' ).click()
			);
		}
	}

	/**
	 * Set up the logic required only for the edit view.
	 * This is specific to the inputs in the sidebar including background images and datepicker themes.
	 *
	 * @returns {void}
	 */
	function initEditPage() {
		initDatepickerSample();

		function setupEventListeners() {
			jQuery( document ).on( 'change', 'input.frm_image_id[name="frm_style_setting[post_content][bg_image_id]"]', onBgImageUpload );

			const frmFieldset = document.getElementById( 'frm_fieldset' );
			if ( frmFieldset ) {
				jQuery( frmFieldset ).on( 'change', handleReset );
			}

			jQuery( 'select[name$="[theme_selector]"]' ).on( 'change', handleThemeChange ).trigger( 'change' );

			const styleIsNew = '' === document.getElementById( 'frm_styling_form' ).querySelector( 'input[name="ID"]' ).value;
			if ( styleIsNew ) {
				// Show the unsaved changes pop up on load when creating a new style and when duplicating as a style isn't created right away.
				trackUnsavedChange();
			}
		}

		function maybeAddWithBgImageClass() {
			if ( backgroundImageIsSet() ) {
				toggleSampleFormClass( 'frm_with_bg_image', true );
			}
		}

		/**
		 * @returns {boolean}
		 */
		function backgroundImageIsSet() {
			const bgImageInput = document.querySelector( 'input[name="frm_style_setting[post_content][bg_image_id]"]' );
			return bgImageInput && '' !== bgImageInput.value;
		}

		/**
		 * Update the form preview after a background image is uploaded.
		 *
		 * @returns {void}
		 */
		function onBgImageUpload() {
			trackUnsavedChange();
			wp.hooks.doAction( 'frm_pro_on_bg_image_upload', this );
			const fileId = parseInt( this.value );
			const show   = 0 !== fileId;
			toggleSampleFormClass( 'frm_with_bg_image', show );
			toggleAdditionalBgImageSettings( show );
		}

		/**
		 * Show the Image Opacity option when a background image is set.
		 *
		 * @param {boolean} show
		 * @returns {void}
		 */
		function toggleAdditionalBgImageSettings( show ) {
			document.querySelectorAll( '.frm_bg_image_additional_settings' ).forEach(
				setting => setting.classList.toggle( 'frm_hidden', ! show )
			);
		}

		/**
		 * Handle a reset event (called in the edit view only).
		 * Reset the background image upload input when the style is reset.
		 *
		 * @returns {void}
		 */
		function handleReset() {
			const bgImageIdField = document.querySelector( 'input.frm_image_id[name="frm_style_setting[post_content][bg_image_id]"]' );
			if ( bgImageIdField && '' !== bgImageIdField.value ) {
				resetBackgroundImage( bgImageIdField );
			}
		}

		function resetBackgroundImage( bgImageIdField ) {
			bgImageIdField.nextElementSibling.querySelector( '.frm_remove_image_option' ).click();
			toggleAdditionalBgImageSettings( false );
			toggleSampleFormClass( 'frm_with_bg_image', false );
		}

		/**
		 * @returns {false}
		 */
		function handleThemeChange() {
			const themeVal = jQuery( this ).val();
			let css        = themeVal;

			if ( themeVal !== -1 ) {
				if ( themeVal === 'ui-lightness' && frm_admin_js.pro_url !== '' ) {
					css = frm_admin_js.pro_url + '/css/ui-lightness/jquery-ui.css';
					jQuery( '.frm_date_color' ).show();
				} else {
					css = frm_admin_js.jquery_ui_url + '/themes/' + themeVal + '/jquery-ui.css';
					jQuery( '.frm_date_color' ).hide();
				}
			}

			updateUICSS( css );
			document.getElementById( 'frm_theme_css' ).value = themeVal;
			return false;
		}

		/**
		 * Function to append a new theme stylesheet with the new style changes.
		 */
		function updateUICSS( locStr ) {
			if ( locStr == -1 ) {
				jQuery( 'link.ui-theme' ).remove();
				return false;
			}

			const $cssLink = jQuery( '<link href="' + locStr + '" type="text/css" rel="Stylesheet" class="ui-theme" />' );
			jQuery( 'head' ).append( $cssLink );

			const $link = jQuery( 'link.ui-theme' );
			if ( $link.length > 1 ) {
				$link.first().remove();
			}
		}

		maybeAddWithBgImageClass();

		if ( 'function' === typeof wp.domReady ) {
			wp.domReady( setupEventListeners );
			return;
		}
	}

	/**
	 * Initialize common functions required for the preview in both the edit and list views.
	 *
	 * @returns {void}
	 */
	function initPreview() {
		initializeDatepickerFieldsOnFocus();
		initializeInlineDatepickers();
		initializeDropzoneFields();
		initializeStarRatingFields();
		initializeSVGIcons();

		/**
		 * Initialize datepickers as formidablepro.js does not get loaded in the visual styler.
		 *
		 * @returns {void}
		 */
		function initializeDatepickerFieldsOnFocus() {
			document.querySelectorAll( '.frm_date' ).forEach(
				/**
				 * @param {HTMLElement} dateField
				 * @returns {void}
				 */
				dateField => {
					if ( dateField.classList.contains( 'frm_date_inline' ) ) {
						// Inline datepickers get initialized on load, not on focus.
						return;
					}

					dateField.addEventListener(
						'focusin',
						() => {
							if ( ! dateField._flatpickr ) {
								initializeDatepicker( dateField );
							}

							// flatpickr binds its own focus handler when it is built, so the
							// focus that just built it never reaches that handler and the
							// calendar has to be opened here instead.
							if ( dateField._flatpickr ) {
								dateField._flatpickr.open();
								return;
							}

							jQuery( dateField ).datepicker( 'show' );
						}
					);
				}
			);
		}

		/**
		 * @returns {void}
		 */
		function initializeInlineDatepickers() {
			document.querySelectorAll( '.frm_date_inline' ).forEach(
				inlineDatepicker => {
					inlineDatepicker.classList.add( 'frm-datepicker' ); // Give the inline datepicker Formidable styling.
					initializeDatepicker( inlineDatepicker );
				}
			);
		}

		/**
		 * @param {HTMLElement|String} target
		 * @returns {void}
		 */
		function initializeDatepicker( target ) {
			if ( 'undefined' !== typeof flatpickr ) {
				initializeFlatpickr( target );
				return;
			}

			jQuery( target ).datepicker({
				changeMonth: true,
				changeYear: true,
				beforeShow( _, options ) {
					if ( options.dpDiv ) {
						options.dpDiv.addClass( 'frm-datepicker' );
						getAllFormClasses( options.input.get( 0 ) ).forEach(
							className => options.dpDiv.get( 0 ).classList.add( className )
						);
					}
					return options;
				}
			});
		}

		/**
		 * Build a flatpickr calendar that looks like the one the front end renders.
		 *
		 * frmDatepickerPro.getDefaultConfigs cannot be reused here because
		 * formidablepro.js does not get loaded in the styler, so the options that
		 * decide how the calendar is laid out are mirrored from it instead. Leaving
		 * them out gave the preview a calendar the real form never shows: an inline
		 * field rendered nothing at all, and a date range field opened a single
		 * month picker.
		 *
		 * @since 6.35
		 *
		 * @param {HTMLElement|string} target The date input, the div an inline calendar renders into, or a selector for either.
		 * @return {void}
		 */
		function initializeFlatpickr( target ) {
			const element  = 'string' === typeof target ? document.querySelector( target ) : target;
			const isInline = element.classList.contains( 'frm_date_inline' );
			const mode     = isRangeField( element ) ? 'range' : 'single';

			if ( isInline && 'range' === mode ) {
				// Widen the field before the month count is measured off it.
				claimHiddenHalfWidth( element );
			}

			flatpickr( element, {
				dateFormat: 'Y-m-d',
				inline: isInline,
				mode,
				showMonths: getMonthCount( element, isInline, mode ),
				monthSelectorType: 'dropdown',
				// Every front end picker passes this too. Without it the month dropdown
				// lists full month names, which do not fit the width the header gives
				// it, so the preview showed a clipped month the real form never shows.
				shorthandCurrentMonth: true,
				onReady( selectedDates, dateStr, instance ) {
					addStyleClassesToCalendar( instance, isInline );

					if ( 'range' === mode ) {
						restoreStoredRange( instance );
					}
				},
				onChange( selectedDates, dateStr, instance ) {
					if ( 'range' === mode ) {
						splitRangeAcrossPair( instance, selectedDates, dateStr );
					}
				}
			});
		}

		/**
		 * Give each half of a range pair its own date rather than the range string.
		 *
		 * flatpickr writes the whole range into whichever input it is attached to, so
		 * picking a range leaves "start to end" sitting in the field. A real form
		 * never shows that: frmDatepickerPro.updateRangeFieldsOnChange splits the
		 * range, gives each half its own date, and puts both calendars on the same
		 * range. The styler has no field settings to work from, so it does the same
		 * off the pairing in the DOM.
		 *
		 * @since 6.35
		 *
		 * @param {Object} instance      The flatpickr instance that changed.
		 * @param {Array}  selectedDates The dates flatpickr currently has selected.
		 * @param {string} dateStr       Both dates as flatpickr formats a range.
		 * @return {void}
		 */
		function splitRangeAcrossPair( instance, selectedDates, dateStr ) {
			const [ start, end ] = selectedDates;

			// A half picked range has nothing to split yet.
			if ( ! start || ! end ) {
				return;
			}

			const { input } = instance;
			if ( ! input || 'INPUT' !== input.nodeName ) {
				return;
			}

			const format    = ( date ) => flatpickr.formatDate( date, instance.config.dateFormat );
			const startId   = input.dataset.rangeStartFieldId;
			const isEndHalf = Boolean( startId );

			input.value            = format( isEndHalf ? end : start );
			input.dataset.rangeValue = dateStr;

			const partner = isEndHalf
				? document.querySelector( `input[data-field-id="${ startId }"]` )
				: document.querySelector( `input[data-range-start-field-id="${ input.dataset.fieldId }"]` );

			if ( ! partner || partner === input ) {
				return;
			}

			// A popup half is not built until it is focused, so the range is stashed
			// for whenever that happens. restoreStoredRange reads it back.
			partner.dataset.rangeValue = dateStr;

			// setDate does not fire onChange, so this cannot come back around.
			if ( partner._flatpickr ) {
				partner._flatpickr.setDate( dateStr );
			}

			partner.value = format( isEndHalf ? start : end );
		}

		/**
		 * Let an inline range calendar use the room its hidden end half leaves behind.
		 *
		 * A range whose start renders inline has its end half hidden by the form, so
		 * the column beside the calendar is empty. A real form is wide enough that
		 * half of a row still gives the calendar its full size, but the styler's
		 * preview column is a fraction of that width, and half of it comes out at
		 * roughly half the size the front end renders. Taking the empty column back
		 * returns the calendar to about the width a real form gives it.
		 *
		 * @since 6.35
		 *
		 * @param {HTMLElement} element The div an inline calendar renders into.
		 * @return {void}
		 */
		function claimHiddenHalfWidth( element ) {
			const container    = element.closest( '.frm_form_field' );
			const endHalf      = document.querySelector( `[data-range-start-field-id="${ element.dataset.fieldId }"]` );
			const endContainer = endHalf && endHalf.closest( '.frm_form_field' );

			if ( ! container || ! endContainer || ! endContainer.classList.contains( 'frm_hidden' ) ) {
				return;
			}

			container.classList.add( 'frm-styler-inline-range-field' );
		}

		/**
		 * Work out how many months an inline range calendar has room for.
		 *
		 * The front end always gives an inline range both of its months, and so does
		 * the styler when the preview is wide enough for them. Formidable sizes an
		 * inline month to whatever container it is in, so on a narrow preview two of
		 * them split the width one would normally get and the day cells shrink to
		 * around half the size a real form renders. One month at close to full size
		 * represents the style better than two at half of it.
		 *
		 * @since 6.35
		 *
		 * @param {HTMLElement} element  The div an inline calendar renders into.
		 * @param {boolean}     isInline Whether this calendar renders inline in the form.
		 * @param {string}      mode     Either 'range' or 'single'.
		 * @return {number} How many months the calendar should render.
		 */
		function getMonthCount( element, isInline, mode ) {
			if ( ! isInline || 'range' !== mode ) {
				return 1;
			}

			const container = element.closest( '.frm_form_field' );

			return container && container.clientWidth >= MIN_WIDTH_FOR_TWO_MONTHS ? 2 : 1;
		}

		/**
		 * Work out whether a date field is one half of a date range.
		 *
		 * The class alone is not enough. When the range's start renders inline, the
		 * form hides the end half, and only that hidden end half carries
		 * frm_date_range, so an inline range start looks like a plain date field.
		 * frmDatepickerPro.getDefaultConfigs does not have this problem because it
		 * also reads isRangeEnabled out of the field's own settings, which the styler
		 * never loads. The pairing is recovered instead from the end half, which
		 * points back at the field that starts the range.
		 *
		 * @since 6.35
		 *
		 * @param {HTMLElement} element The date input, or the div an inline calendar renders into.
		 * @return {boolean} True when the field is the start or the end of a range.
		 */
		function isRangeField( element ) {
			if ( element.classList.contains( 'frm_date_range' ) ) {
				return true;
			}

			const { fieldId } = element.dataset;

			return Boolean( fieldId ) && null !== document.querySelector( `[data-range-start-field-id="${ fieldId }"]` );
		}

		/**
		 * Put the range its partner picked back on screen in a range calendar.
		 *
		 * A popup half is not built until it is focused, so a range picked on the
		 * other half has nowhere to go until this one exists. splitRangeAcrossPair
		 * stashes it on the input for that, and this reads it back.
		 *
		 * @since 6.35
		 *
		 * @param {Object} instance The flatpickr instance.
		 * @return {void}
		 */
		function restoreStoredRange( instance ) {
			const { input } = instance;

			if ( ! input || 'INPUT' !== input.nodeName || ! input.dataset.rangeValue ) {
				return;
			}

			// The input only carries its own single date, so the range comes back off
			// data-range-value, which is where the front end keeps it too.
			const ownDate = input.value;
			instance.setDate( input.dataset.rangeValue, false );
			input.value = ownDate;
		}

		/**
		 * Copy the previewed form's style classes onto the calendar.
		 *
		 * A popup calendar is appended to the body, so it sits outside the previewed
		 * form and cannot inherit the CSS variables scoped to it. The styler writes
		 * those variables to a `.frm_style_<key>.with_frm_style` rule, so the
		 * calendar needs both classes to follow the sidebar as it is edited. This is
		 * what frmDatepickerPro.getStyleClasses does on the front end.
		 *
		 * @since 6.35
		 *
		 * @param {Object}  instance The flatpickr instance.
		 * @param {boolean} isInline Whether this calendar renders inline in the form.
		 * @return {void}
		 */
		function addStyleClassesToCalendar( instance, isInline ) {
			const { classList } = instance.calendarContainer;

			classList.add( 'frm-datepicker' );

			if ( isInline ) {
				classList.add( 'frm_date_inline' );
			}

			getAllFormClasses( instance.element ).forEach(
				className => classList.add( className )
			);
		}

		function getAllFormClasses( input ) {
			var formContainer, formClasses;
	
			formContainer = input.closest( '.with_frm_style' );
			if ( ! formContainer ) {
				return [];
			}
	
			formClasses = [];
			Array.prototype.forEach.call(
				formContainer.className.split( ' ' ),
				( className ) => {
					var trimmedClassName = className.trim();
					if ( '' !== trimmedClassName && 'frm_forms' !== trimmedClassName ) {
						formClasses.push( trimmedClassName );
					}
				}
			);
	
			return formClasses;
		}

		/**
		 * Check preview for dropzoen fields and initialize them so they aren't just type="file" input fields.
		 *
		 * @returns {void}
		 */
		function initializeDropzoneFields() {
			if ( 'function' !== typeof wp.domReady ) {
				return;
			}

			wp.domReady(
				() => {
					document.getElementById( 'frm_style_preview' ).querySelectorAll( '.frm_dropzone' ).forEach(
						/**
						 * Initialize dropzone for a file field.
						 * Then immediately remove its event listeners as it's for display only.
						 *
						 * @param {HTMLElement} dropzoneField
						 * @returns {void}
						 */
						dropzoneField => {
							const url          = '/file/post'; // The url is required but it does not matter as we are disabling Dropzone.
							const dropzoneArgs = { url };
							const dropzone     = new Dropzone( 'div#' + dropzoneField.id, dropzoneArgs );

							// Calling removeEventListeners disables upload (both from click and drag).
							dropzone.removeEventListeners();
						}
					);
				}
			);
		}

		/**
		 * Make star rating fields interactive. As formidablepro.js is not loaded in the visual styler, we need to fill in that functionality for the preview.
		 *
		 * @returns {void}
		 */
		function initializeStarRatingFields() {
			const starGroups = document.querySelectorAll( '.frm-star-group' );
			if ( ! starGroups.length ) {
				return;
			}

			starGroups.forEach( initializeStarGroup );

			/**
			 * Event event listeners for a star rating field.
			 *
			 * @param {HTMLElement} starGroup
			 * @returns {void}
			 */
			function initializeStarGroup( starGroup ) {
				starGroup.querySelectorAll( 'input' ).forEach(
					input => {
						input.addEventListener( 'click', () => updateStars( input ) );
						input.addEventListener( 'mouseenter', () => updateStars( input ) );
					}
				);

				starGroup.querySelectorAll( '.star-rating' ).forEach(
					star => {
						if ( star.classList.contains( 'star-rating-readonly' ) ) {
							return;
						}

						star.addEventListener( 'mouseenter', () => updateStars( star.previousSibling ) );
						star.addEventListener( 'mouseleave', unhoverStars.bind( star ) );
					}
				);
			}

			/**
			 * @param {HTMLElement} hovered
			 * @returns {void}
			 */
			function updateStars( hovered ) {
				const starGroup = hovered.parentElement;
				const current   = parseInt( hovered.value );
				let selectLabel = false;

				starGroup.classList.add( 'frm-star-hovered' );
				Array.from( starGroup.children ).forEach(
					star => {
						if ( star.classList.contains( 'star-rating' ) ) {
							if ( selectLabel ) {
								star.classList.add( 'star-rating-hover' );
							} else {
								star.classList.remove( 'star-rating-hover', 'star-rating-on' );
							}
							return;
						}

						selectLabel = parseInt( star.value ) <= current;
					}
				);
			}

			/**
			 * @returns {void}
			 */
			function unhoverStars() {
				/*jshint validthis:true */
				const input         = this.previousSibling;
				const starGroup     = input.parentElement;
				const stars         = starGroup.children;
				const selectedInput = starGroup.querySelector( 'input:checked' );
				const selected      = selectedInput ? selectedInput.getAttribute( 'id' ) : false;
				let isSelected      = '';

				starGroup.classList.remove( 'frm-star-hovered' );

				for ( let i = stars.length - 1; i > 0; i-- ) {
					const star = stars[ i ];
					if ( ! star.classList.contains( 'star-rating' ) ) {
						continue;
					}

					star.classList.remove( 'star-rating-hover' );

					if ( isSelected === '' && star.getAttribute( 'for' ) === selected ) {
						isSelected = 'star-rating-on';
					}

					if ( isSelected !== '' ) {
						star.classList.add( isSelected );
					}
				}
			}
		} // End initializeStarRatingFields.

		function initializeSVGIcons() {
			// Collapse icon position.
			frmDom.util.documentOn( 'change', '#frm_collapse_pos', ( event ) => {
				const wrapperEls = document.querySelectorAll( '.frm_section_heading .frm_trigger' );
				if ( ! wrapperEls ) {
					return;
				}

				const position = event.target.value;

				wrapperEls.forEach( wrapperEl => {
					const svgs = wrapperEl.querySelectorAll( '.frmsvg' );
					svgs.forEach( svg => {
						if ( 'before' === position ) {
							wrapperEl.prepend( ' ' );
							wrapperEl.prepend( svg );
						} else {
							wrapperEl.append( ' ' );
							wrapperEl.append( svg );
						}
					});
				});
			});

			/**
			 * Live update Repeater and Section icons.
			 */
			function changeRepeaterAndCollapseSVGIcon() {
				if ( 'object' !== typeof frmProStyleSettingsSVGs ) {
					return;
				}

				const svgIcons = frmProStyleSettingsSVGs;

				/**
				 * Changes the SVG icon when changing icon setting from Styles page.
				 *
				 * @param {String} inputSelector  CSS selector of the setting input.
				 * @param {String} svgTagSelector CSS selector of the <svg> tag of the SVG icon.
				 * @param {String} iconNameFormat Icon name format, contains `{key}`, which is replaced by the setting value.
				 */
				function changeSVGIcon( inputSelector, svgTagSelector, iconNameFormat ) {
					frmDom.util.documentOn( 'change', inputSelector, ( event ) => {
						if ( ! event.target.checked ) {
							return;
						}

						const svgs = document.querySelectorAll( svgTagSelector );
						if ( ! svgs ) {
							return;
						}

						const iconKey= event.target.value && '0' !== event.target.value ? event.target.value : '';
						const newIconKey = iconNameFormat.replace( '{key}', iconKey );

						if ( ! svgIcons[ newIconKey ] ) {
							return;
						}

						svgs.forEach( svg => {
							const newSvg = createElementFromString( svgIcons[ newIconKey ] );
							newSvg.setAttribute( 'width', '1em' );
							newSvg.setAttribute( 'height', '1em' );
							svg.replaceWith( newSvg );
						});
					});
				}

				const createElementFromString = ( str ) => {
					const placeholder = document.createElement( 'div' );
					placeholder.innerHTML = str;
					return placeholder.firstElementChild;
				};

				// Add row icon.
				changeSVGIcon(
					'input[name="frm_style_setting[post_content][repeat_icon]"]',
					'.frm_repeat_buttons .frm_add_form_row svg',
					'frm_plus{key}_icon'
				);

				// Remove row icon.
				changeSVGIcon(
					'input[name="frm_style_setting[post_content][repeat_icon]"]',
					'.frm_repeat_buttons .frm_remove_form_row svg',
					'frm_minus{key}_icon'
				);

				// Collapse icon.
				changeSVGIcon(
					'input[name="frm_style_setting[post_content][collapse_icon]"]',
					'.frm_section_heading .frm_trigger svg',
					'frm_arrowdown{key}_icon'
				);

				function syncTriggerIconsWithSetting() {
					const checkedRadio = document.querySelector( 'input[name="frm_style_setting[post_content][repeat_icon]"]:checked' );
					if ( ! checkedRadio ) {
						return;
					}
					const event = new Event( 'change', {
						bubbles: true,
						cancelable: true
					});

					checkedRadio.dispatchEvent( event );
				}

				syncTriggerIconsWithSetting();
			}
			changeRepeaterAndCollapseSVGIcon();
		}
	}

	/**
	 * @param {String} className
	 * @param {boolean} toggleOn
	 * @returns {void}
	 */
	function toggleSampleFormClass( className, toggleOn ) {
		getSampleForms().forEach( ( formContainer ) =>
			formContainer.querySelector( '.frm-show-form' )?.classList.toggle( className, toggleOn )
		);
	}

	/**
	 * @returns {Array<HTMLElement>}
	 */
	function getSampleForms() {
		return document.getElementById( 'frm_style_preview' ).querySelectorAll( '.frm_forms.with_frm_style' );
	}

	function initDatepickerSample() {
		if ( 'undefined' !== typeof flatpickr ) {
			flatpickr( '#datepicker_sample', {
				dateFormat: 'Y-m-d',
				changeMonth: true,
				changeYear: true,
				inline: true,
				// Every front end picker passes this, in frmDatepickerPro.getDefaultConfigs. Without
				// it the month dropdown lists full month names, which do not fit the width the
				// header gives it, so the sample showed a clipped month the real form never shows.
				shorthandCurrentMonth: true,
				onReady( selectedDates, dateStr, instance ) {
					instance.calendarContainer.classList.add( 'frm-datepicker', 'frm-datepicker-inline-small' );
				}
			});
			return;
		}

		const $sample = jQuery( '#datepicker_sample' );
		if ( $sample.length && 'function' === typeof $sample.datepicker ) {
			$sample.datepicker({ changeMonth: true, changeYear: true });
		}
	}
}() );
