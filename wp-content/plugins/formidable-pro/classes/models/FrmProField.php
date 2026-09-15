<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProField {

	/**
	 * @param array $field_data
	 *
	 * @return array
	 */
	public static function create( $field_data ) {
		if ( $field_data['field_options']['label'] !== 'none' ) {
			$field_data['field_options']['label'] = '';
		}

		self::switch_in_section_field_option( $field_data );

		switch ( $field_data['type'] ) {
			case 'select':
				$width                               = FrmStylesController::get_style_val( 'auto_width', $field_data['form_id'] );
				$field_data['field_options']['size'] = $width;
				break;
			case 'divider':
				if ( ! empty( $field_data['field_options']['repeat'] ) ) {
					// Create the repeatable form.
					$field_data['field_options']['form_select'] = self::create_repeat_form(
						0,
						array(
							'parent_form_id' => $field_data['form_id'],
							'field_name'     => $field_data['name'],
						)
					);
				}
				break;
			case 'file':
				$field_data['field_options']['restrict'] = 1;

				if ( ! $field_data['field_options']['ftypes'] ) {
					$field_data['field_options']['ftypes'] = array(
						'jpg|jpeg|jpe' => 'image/jpeg',
						'png'          => 'image/png',
						'gif'          => 'image/gif',
					);
				}
				break;
		}

		return $field_data;
	}

	/**
	 * Change the default in_section value to the ID of the section where a new field was dragged and dropped
	 *
	 * @since 2.0.24
	 *
	 * @param array $field_data
	 *
	 * @return void
	 */
	private static function switch_in_section_field_option( &$field_data ) {
		if ( in_array( $field_data['type'], array( 'divider', 'end_divider', 'form' ), true ) ) {
			return;
		}

		if ( self::maybe_use_repeater_form_id( $field_data ) ) {
			// Do not override the in_section value if it is already set
			// This way it can be passed in field_options when the field is created.
			return;
		}

		$ajax_action = FrmAppHelper::get_post_param( 'action', '', 'sanitize_title' );

		if ( 'frm_insert_field' !== $ajax_action ) {
			return;
		}

		$section_id                                = FrmAppHelper::get_post_param( 'section_id', 0, 'absint' );
		$field_data['field_options']['in_section'] = $section_id;
	}

	/**
	 * When insertFormField is called, it always passes the parent form ID.
	 * So map it to the repeater's form ID when applicable.
	 *
	 * @since 6.24
	 *
	 * @param array $field_data
	 *
	 * @return bool True if the section logic has already been handled. When this is true, we exit switch_in_section_field_option early.
	 */
	private static function maybe_use_repeater_form_id( &$field_data ) {
		if ( empty( $field_data['field_options']['in_section'] ) ) {
			return false;
		}

		$section_field = FrmField::getOne( $field_data['field_options']['in_section'] );

		if ( $section_field && ! empty( $section_field->field_options['repeat'] ) ) {
			$field_data['form_id'] = $section_field->field_options['form_select'];
		}

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function skip_update_field_setting( $settings ) {
		unset( $settings['post_field'], $settings['custom_field'] );
		unset( $settings['taxonomy'], $settings['exclude_cat'] );
		return $settings;
	}

	/**
	 * @param array    $field_options
	 * @param stdClass $field
	 * @param array    $values
	 *
	 * @return array
	 */
	public static function update( $field_options, $field, $values ) {
		foreach ( $field_options['hide_field'] as $i => $f ) {
			if ( ! $f ) {
				unset( $field_options['hide_field'][ $i ], $field_options['hide_field_cond'][ $i ] );

				if ( isset( $field_options['hide_opt'] ) && is_array( $field_options['hide_opt'] ) ) {
					unset( $field_options['hide_opt'][ $i ] );
				}
			}
			unset( $i, $f );
		}

		if ( $field->type === 'hidden' && ! empty( $field_options['required'] ) ) {
			$field_options['required'] = false;
		} elseif ( $field->type === 'file' ) {
			self::format_mime_types( $field_options, $field->id );
		}

		$field_options['custom_currency'] = 0; // This setting no longer exists.

		if ( isset( $field_options['custom_decimals'] ) ) {
			$field_options['custom_decimals'] = absint( $field_options['custom_decimals'] );
		}

		// Ensure proper handling when the format dropdown is "None".
		if ( isset( $field_options['format'] ) && '' === $field_options['format'] && isset( $field_options['calc_dec'] ) && is_numeric( $field_options['calc_dec'] ) ) {
			$field_options['calc_dec'] = '';
		}

		$field_options = self::sanitize_custom_thousand_separator( $field_options );
		$field_options = self::update_show_slider_range_value( $field->type, $field_options );

		return self::reset_conditional_logic_settings( $field->id, $field_options );
	}

	/**
	 * @since 6.20
	 *
	 * @param string $field_type
	 * @param array  $field_options
	 *
	 * @return array
	 */
	private static function update_show_slider_range_value( $field_type, $field_options ) {
		if ( 'range' !== $field_type ) {
			return $field_options;
		}
		$field_options['show_slider_range'] = absint( $field_options['show_slider_range'] );
		return $field_options;
	}

	/**
	 * Return true if the conditional logic settings should be reset.
	 *
	 * @since 6.32
	 *
	 * @param array $field_options
	 *
	 * @return bool
	 */
	private static function should_reset_conditional_logic_settings( $field_options ) {
		if ( empty( $field_options['hide_field'] ) ) {
			return true;
		}

		return isset( $field_options['enable_conditional_logic'] ) && '0' === $field_options['enable_conditional_logic'];
	}

	/**
	 * If the conditional logic is disabled, reset its settings.
	 *
	 * @since 6.24
	 *
	 * @param int   $field_id The field ID.
	 * @param array $field_options The field options.
	 *
	 * @return array
	 */
	private static function reset_conditional_logic_settings( $field_id, $field_options ) {
		if ( ! self::should_reset_conditional_logic_settings( $field_options ) ) {
			return $field_options;
		}

		$defaults = array(
			'enable_conditional_logic' => '0',
			'show_hide'                => 'show',
			'any_all'                  => 'any',
			'hide_field'               => array(),
			'hide_field_cond'          => array( '==' ),
			'hide_opt'                 => array(),
		);

		foreach ( $defaults as $key => $value ) {
			$field_options[ $key ] = $value;
			// Update POST data to reset the conditional logic settings after the Form Builder is updated and the page reloads.
			$_POST['field_options'][ $key . '_' . $field_id ] = $value;
		}

		return $field_options;
	}

	/**
	 * @param array      $options
	 * @param int|string $field_id
	 *
	 * @return void
	 */
	private static function format_mime_types( &$options, $field_id ) {
		$file_options = $options['ftypes'] ?? array();

		if ( ! $file_options ) {
			return;
		}

		$mime_array = array();

		foreach ( $file_options as $file_option ) {
			$values                   = explode( '|||', $file_option );
			$mime_array[ $values[0] ] = $values[1];
		}

		$options['ftypes']                               = $mime_array;
		$_POST['field_options'][ 'ftypes_' . $field_id ] = $mime_array;
	}

	/**
	 * Sanitize the custom thousand separator as sanitizing has been disabled for this option.
	 * This is a special edge case because we do not want to trim the thousand separator.
	 *
	 * @since 5.5.6
	 *
	 * @param array $field_options
	 *
	 * @return array
	 */
	private static function sanitize_custom_thousand_separator( $field_options ) {
		if ( ! empty( $field_options['custom_thousand_separator'] ) ) {
			$field_options['custom_thousand_separator'] = strip_tags( $field_options['custom_thousand_separator'] );
		}
		return $field_options;
	}

	/**
	 * The field settings that store the id of another field, and the shape each one is stored in.
	 *
	 * A setting listed here is switched to the new field id whenever a field is duplicated or
	 * imported, including when the field it points at is created afterwards. Settings that store a
	 * field id inside shortcode text, such as calc and default_value, do not belong here. Those are
	 * already handled for any order by FrmForm::switch_field_ids_in_fields().
	 *
	 * Shapes:
	 *   int   - a single field id.
	 *   int[] - an array of field ids. Keys are preserved, so paired arrays stay aligned.
	 *   csv   - a comma separated string of field ids.
	 *
	 * @since 6.35
	 *
	 * @param string $field_type Field type the settings are read for. Some settings only hold a
	 *                           field id for certain field types.
	 *
	 * @return array<string,string> Setting name => value shape.
	 */
	public static function field_id_settings( $field_type = '' ) {
		$settings = array(
			'hide_field'        => 'int[]',
			'watch_lookup'      => 'int[]',
			'product_field'     => 'int[]',
			'exclude_fields'    => 'csv',
			'get_values_field'  => 'int',
			'linked_date_field' => 'int',
			'in_section'        => 'int',
		);

		if ( 'data' === $field_type ) {
			// form_select holds a field id on a Dynamic field. On an embedded form and on a
			// repeating section it holds a form id, which
			// FrmProXMLHelper::switch_form_ids_after_import() switches instead.
			$settings['form_select'] = 'int';
		}

		/**
		 * Filter the field settings that store the id of another field.
		 *
		 * Add-ons use this to have their own settings switched to the new field ids on import and
		 * on form duplication, including when the field they point at is created afterwards.
		 *
		 * @since 6.35
		 *
		 * @param array<string,string> $settings   Setting name => value shape, one of int, int[] or csv.
		 * @param string               $field_type Field type the settings are read for.
		 */
		return apply_filters( 'frm_field_id_settings', $settings, $field_type );
	}

	/**
	 * Switch the field ids stored in a single setting value to their new ids.
	 *
	 * An id with no entry in the map is left alone rather than dropped, so paired arrays such as
	 * hide_field and hide_field_cond keep the same keys.
	 *
	 * @since 6.35
	 *
	 * @param array|int|string $value   Original setting value.
	 * @param string           $shape   Value shape, one of int, int[] or csv.
	 * @param array            $id_map  Map of original field ids to their new ids.
	 * @param bool             $changed Set to true when at least one id was switched.
	 *
	 * @return array|int|string The switched value.
	 */
	public static function switch_ids_in_setting_value( $value, $shape, $id_map, &$changed ) {
		if ( 'csv' === $shape ) {
			$old_ids = self::split_csv_ids( $value );
			$new_ids = array();

			foreach ( $old_ids as $old_id ) {
				$new_ids[] = self::switch_single_field_id( $old_id, $id_map, $changed );
			}

			return implode( ',', $new_ids );
		}

		if ( 'int[]' === $shape ) {
			$new_ids = array();

			foreach ( (array) $value as $key => $old_id ) {
				$new_ids[ $key ] = self::switch_single_field_id( $old_id, $id_map, $changed );
			}

			return $new_ids;
		}

		return self::switch_single_field_id( $value, $id_map, $changed );
	}

	/**
	 * Split a csv setting value into the field ids it holds.
	 *
	 * @since 6.35
	 *
	 * @param array|int|string $value A comma separated list of field ids.
	 *
	 * @return array<string>
	 */
	private static function split_csv_ids( $value ) {
		$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
		return array_filter( array_map( 'trim', array_map( 'strval', $ids ) ), 'strlen' );
	}

	/**
	 * @since 6.35
	 *
	 * @param int|string $old_id  Original field id.
	 * @param array      $id_map  Map of original field ids to their new ids.
	 * @param bool       $changed Set to true when the id was switched.
	 *
	 * @return int|string The new field id, or the original when it is not in the map.
	 */
	private static function switch_single_field_id( $old_id, $id_map, &$changed ) {
		if ( empty( $id_map[ $old_id ] ) ) {
			return $old_id;
		}

		$changed = true;
		$new_id  = $id_map[ $old_id ];

		// Keep the original value type so the serialized field options keep their shape.
		return is_string( $old_id ) ? (string) $new_id : $new_id;
	}

	/**
	 * @param array $values
	 * @param array $atts {
	 *
	 *     @type bool $after True on the second run.
	 * }
	 *
	 * @return array
	 */
	public static function duplicate( $values, $atts = array() ) {
		global $frm_duplicate_ids;

		$is_second_run = $atts['after'] ?? false;

		if ( ! $frm_duplicate_ids || empty( $values['field_options'] ) ) {
			if ( ! $is_second_run ) {
				self::mark_field_key_as_unprocessed( $values['field_key'] );
			}

			return $values;
		}

		// Switch out fields from calculation or default values
		$switch_string = array( 'default_value', 'calc' );

		foreach ( $switch_string as $opt ) {
			if ( empty( $values['field_options'][ $opt ] ) && empty( $values[ $opt ] ) ) {
				continue;
			}

			$this_val = $values[ $opt ] ?? $values['field_options'][ $opt ];

			if ( is_array( $this_val ) ) {
				continue;
			}

			$ids = FrmProFieldsHelper::filter_keys_for_regex( $this_val, array_keys( $frm_duplicate_ids ) );

			if ( ! $ids ) {
				continue;
			}

			$ids = implode( '|', $ids );

			preg_match_all( '/\[(' . $ids . ')\]/s', $this_val, $matches, PREG_PATTERN_ORDER );
			unset( $ids );

			if ( ! isset( $matches[1] ) ) {
				unset( $matches );
				continue;
			}

			foreach ( $matches[1] as $val ) {
				if ( $is_second_run && in_array( $val, $frm_duplicate_ids ) ) {
					// The field id may have already been replaced.
					continue;
				}

				$this_val = str_replace( '[' . $val . ']', '[' . $frm_duplicate_ids[ $val ] . ']', $this_val );

				if ( isset( $values[ $opt ] ) ) {
					$values[ $opt ] = $this_val;
				} else {
					$values['field_options'][ $opt ] = $this_val;
				}
				unset( $val );
			}

			unset( $this_val, $matches );
		}

		self::unserialize_conditional_logic_options( $values );
		self::switch_ids_in_field_id_settings( $frm_duplicate_ids, $values, $is_second_run );

		return $values;
	}

	/**
	 * Conditional logic is stored across three arrays that share their keys, and any of them may
	 * still be serialized. Unserialize them together so they stay aligned.
	 *
	 * @since 6.35
	 *
	 * @param array $values Field values, passed by reference.
	 *
	 * @return void
	 */
	private static function unserialize_conditional_logic_options( &$values ) {
		if ( empty( $values['field_options']['hide_field'] ) ) {
			return;
		}

		foreach ( array( 'hide_field_cond', 'hide_opt', 'hide_field' ) as $logic ) {
			if ( isset( $values['field_options'][ $logic ] ) ) {
				FrmAppHelper::unserialize_or_decode( $values['field_options'][ $logic ] );
			} else {
				$values['field_options'][ $logic ] = array();
			}
		}
	}

	/**
	 * Switch every setting that stores another field's id to the new field ids.
	 *
	 * A setting that points at a field created later cannot be switched yet, so the field key is
	 * marked for the second pass in FrmProDuplicateFieldsHelper::maybe_fix_field_ids_after_duplicate().
	 *
	 * @since 6.35
	 *
	 * @param array $frm_duplicate_ids Map of original field ids to their new ids.
	 * @param array $values            Field values, passed by reference.
	 * @param bool  $is_second_run     True on the second pass.
	 *
	 * @return void
	 */
	private static function switch_ids_in_field_id_settings( $frm_duplicate_ids, &$values, $is_second_run ) {
		if ( ! isset( $values['field_options']['in_section'] ) ) {
			// Every field tracks the section it sits in, even when it sits in none.
			$values['field_options']['in_section'] = 0;
		}

		if ( $is_second_run ) {
			$frm_duplicate_ids = self::drop_already_switched_ids( $frm_duplicate_ids );
		}

		$field_type        = $values['type'] ?? '';
		$has_unresolved_id = false;

		foreach ( self::field_id_settings( $field_type ) as $setting => $shape ) {
			if ( empty( $values['field_options'][ $setting ] ) ) {
				continue;
			}

			$old_value = $values['field_options'][ $setting ];
			$changed   = false;

			$values['field_options'][ $setting ] = self::switch_ids_in_setting_value( $old_value, $shape, $frm_duplicate_ids, $changed );

			if ( self::setting_has_unresolved_id( $old_value, $shape, $frm_duplicate_ids ) ) {
				$has_unresolved_id = true;
			}
		}

		if ( $has_unresolved_id && ! $is_second_run ) {
			self::mark_field_key_as_unprocessed( $values['field_key'] );
		}
	}

	/**
	 * Check whether a setting still points at a field that has not been created yet.
	 *
	 * @since 6.35
	 *
	 * @param array|int|string $value             Original setting value.
	 * @param string           $shape             Value shape, one of int, int[] or csv.
	 * @param array            $frm_duplicate_ids Map of original field ids to their new ids.
	 *
	 * @return bool
	 */
	private static function setting_has_unresolved_id( $value, $shape, $frm_duplicate_ids ) {
		if ( 'csv' === $shape ) {
			$old_ids = self::split_csv_ids( $value );
		} elseif ( 'int[]' === $shape ) {
			$old_ids = (array) $value;
		} else {
			$old_ids = array( $value );
		}

		foreach ( $old_ids as $old_id ) {
			if ( $old_id && empty( $frm_duplicate_ids[ $old_id ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Drop any map entry whose original id is also one of the new ids.
	 *
	 * On the second pass a stored id may already have been switched. Without this an id that was
	 * switched to a value that happens to match another field's original id would be switched twice.
	 *
	 * @since 6.35
	 *
	 * @param array $frm_duplicate_ids Map of original field ids to their new ids.
	 *
	 * @return array
	 */
	private static function drop_already_switched_ids( $frm_duplicate_ids ) {
		$new_ids = array_flip( $frm_duplicate_ids );

		foreach ( $frm_duplicate_ids as $old_id => $new_id ) {
			if ( isset( $new_ids[ $old_id ] ) ) {
				unset( $frm_duplicate_ids[ $old_id ] );
			}
		}

		return $frm_duplicate_ids;
	}

	/**
	 * Track the field keys that have not yet had replaced their conditional logic to replace after duplicate as they rely on a different field order.
	 *
	 * @param string $field_key
	 */
	private static function mark_field_key_as_unprocessed( $field_key ) {
		global $frm_unprocessed_duplicate_field_keys;

		if ( ! is_array( $frm_unprocessed_duplicate_field_keys ) ) {
			$frm_unprocessed_duplicate_field_keys = array();
		}

		$frm_unprocessed_duplicate_field_keys[] = $field_key;
	}

	public static function delete( $id ) {
		$field = FrmField::getOne( $id );

		if ( ! $field ) {
			return;
		}

		// Delete the form this repeating field created
		self::delete_repeat_field( $field );
		self::reset_form_transition_if_no_break_field( $field );

		// TODO: before delete do something with entries with data field meta_value = field_id
	}

	public static function delete_repeat_field( $field ) {
		if ( ! FrmField::is_repeating_field( $field ) ) {
			return;
		}

		if ( isset( $field->field_options['form_select'] ) && is_numeric( $field->field_options['form_select'] ) && $field->field_options['form_select'] != $field->form_id ) {
			FrmForm::destroy( $field->field_options['form_select'] );
		}
	}

	/**
	 * Reset the form transition if there are no more break fields.
	 *
	 * @param object $field Field object.
	 *
	 * @return void
	 */
	private static function reset_form_transition_if_no_break_field( $field ) {
		if ( 'break' !== FrmField::get_field_type( $field ) ) {
			return;
		}

		$remaining_break_fields = FrmDb::get_count(
			'frm_fields',
			array(
				'form_id' => $field->form_id,
				'type'    => 'break',
				'id !'    => $field->id,
			)
		);

		if ( $remaining_break_fields > 0 ) {
			return;
		}

		$form = FrmForm::getOne( $field->form_id );

		if ( ! $form ) {
			return;
		}

		global $wpdb;
		$form->options['transition'] = '';

		// Use custom query instead of FrmForm::update() to prevent duplicate code runs.
		$wpdb->update(
			$wpdb->prefix . 'frm_forms',
			array( 'options' => serialize( $form->options ) ),
			array( 'id' => $field->form_id )
		);

		FrmForm::clear_form_cache();
	}

	/**
	 * @param stdClass $field
	 *
	 * @return bool
	 */
	public static function is_list_field( $field ) {
		return $field->type === 'data' && ( ! isset( $field->field_options['data_type'] ) || $field->field_options['data_type'] === 'data' || $field->field_options['data_type'] == '' );
	}

	/**
	 * Create the form for a repeating section
	 *
	 * @since 2.0.12
	 *
	 * @param int $form_id
	 * @param array $atts
	 *
	 * @return int Form ID.
	 */
	public static function create_repeat_form( $form_id, $atts ) {
		$form_values = array(
			'parent_form_id' => $atts['parent_form_id'],
			'name'           => $atts['field_name'],
			'status'         => 'published',
		);
		$form_values = FrmFormsHelper::setup_new_vars( $form_values );

		return (int) FrmForm::create( $form_values );
	}

	/**
	 * Return all the field IDs for the fields inside of a section (not necessarily repeating) or an embedded form
	 *
	 * @since 2.0.13
	 *
	 * @param array $field
	 *
	 * @return array Children.
	 */
	public static function get_children( $field ) {
		if ( FrmField::is_repeating_field( $field ) || $field['type'] === 'form' ) {
			// If repeating field or embedded form

			$repeat_id = $field['form_select'] ?? $field['field_options']['form_select'];
			return FrmDb::get_col( 'frm_fields', array( 'form_id' => $repeat_id ) );
		}

		// If regular section
		return self::get_children_from_standard_section( $field );
	}

	/**
	 * Get the field IDs within a regular section
	 *
	 * @since 2.0.25
	 *
	 * @param array $field
	 *
	 * @return array|null
	 */
	private static function get_children_from_standard_section( $field ) {
		$child_where = array( 'form_id' => $field['form_id'] );

		// Get minimum field order for children
		$min_field_order             = $field['field_order'] + 1;
		$child_where['field_order>'] = $min_field_order;

		// Get maximum field order for children
		$where             = array(
			'form_id'      => $field['form_id'],
			'type'         => array( 'end_divider', 'divider', 'break' ),
			'field_order>' => $min_field_order,
		);
		$end_divider_order = FrmDb::get_var( 'frm_fields', $where, 'field_order', array( 'order_by' => 'field_order ASC' ), 1 );

		if ( $end_divider_order ) {
			$child_where['field_order<'] = $end_divider_order - 1;
		}

		return FrmDb::get_col( 'frm_fields', $child_where );
	}

	/**
	 * Get the entry ID from a linked field
	 *
	 * @since 2.0.15
	 *
	 * @param int $linked_field_id
	 * @param string $where_val
	 * @param string $where_is
	 *
	 * @return int Linked ID.
	 */
	public static function get_dynamic_field_entry_id( $linked_field_id, $where_val, $where_is ) {
		$query = array(
			'field_id' => $linked_field_id,
			'meta_value' . FrmDb::append_where_is( $where_is ) => $where_val,
		);
		return FrmDb::get_col( 'frm_item_metas', $query, 'item_id' );
	}

	/**
	 * Get the category ID from the category name
	 *
	 * @since 2.0.15
	 *
	 * @param string $cat_name
	 *
	 * @return int
	 */
	public static function get_cat_id_from_text( $cat_name ) {
		return get_cat_ID( $cat_name );
	}

	/**
	 * Check if the format option isset and true without a regular expression
	 *
	 * @since 2.02.06
	 *
	 * @param array|object $field
	 *
	 * @return bool
	 */
	public static function is_format_option_true_with_no_regex( $field ) {
		if ( is_array( $field ) ) {
			return FrmField::is_option_true_in_array( $field, 'format' ) && ! str_starts_with( $field['format'], '^' );
		}

		return FrmField::is_option_true_in_object( $field, 'format' ) && ! str_starts_with( $field->field_options['format'], '^' );
	}

	/**
	 * Get a list of field types that cannot be used in calculations.
	 *
	 * @since 4.0
	 *
	 * @return array
	 */
	public static function exclude_from_calcs() {
		$exclude   = FrmField::no_save_fields();
		$exclude[] = 'toggle';
		$exclude[] = 'data|select';
		$exclude[] = 'data|radio';
		$exclude[] = 'data|checkbox';
		$exclude[] = 'virtual';
		return $exclude;
	}

	/**
	 * Get a list of field types that can be used in numeric calculations.
	 *
	 * @since 6.34
	 *
	 * @return array
	 */
	public static function numeric_calculation_field_types() {
		$field_types = array(
			'number',
			'scale',
			'star',
			'range',
			'nps',
			'product',
			'quantity',
			'total',
			'radio',
			'dropdown',
			'checkbox',
			'hidden',
			'data',
			'lookup',
			'text',
			'textarea',
		);

		/**
		 * Filters the field types that can be used in numeric calculations.
		 *
		 * @since 6.34
		 *
		 * @param array $field_types Array of field type slugs.
		 */
		return apply_filters( 'frm_numeric_calculation_field_types', $field_types );
	}
}
