<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProXMLHelper {

	/**
	 * @since 6.34
	 * @since 6.35  imported_fields_with_lookup replaced by imported_fields_with_field_ids, which
	 *             tracks every setting in the FrmProField::field_id_settings() registry.
	 *
	 * @var array {
	 *     fields_id_map: array,                  // Maps original field ids from the imported file to the new field ids in the database.
	 *     imported_fields_with_field_ids: array, // Original values of every setting that stores a field id, indexed by the new field id.
	 * }
	 */
	private static $xml_import_fields_data = array(
		'fields_id_map'                  => array(),
		'imported_fields_with_field_ids' => array(),
	);

	/**
	 * @param array<stdClass> $entries
	 * @param array           $imported
	 *
	 * @return array
	 */
	public static function import_xml_entries( $entries, $imported ) {
		global $frm_duplicate_ids;

		$saved_entries   = array();
		$track_child_ids = array();

		// Import all child entries first
		self::put_child_entries_first( $entries );

		foreach ( $entries as $item ) {
			$entry = array(
				'id'             => (int) $item->id,
				'item_key'       => (string) $item->item_key,
				'name'           => (string) $item->name,
				'description'    => FrmAppHelper::maybe_json_decode( (string) $item->description ),
				'ip'             => (string) $item->ip,
				'form_id'        => $imported['forms'][ (int) $item->form_id ] ?? (int) $item->form_id,
				'post_id'        => $imported['posts'][ (int) $item->post_id ] ?? (int) $item->post_id,
				'user_id'        => FrmAppHelper::get_user_id_param( (string) $item->user_id ),
				'parent_item_id' => (int) $item->parent_item_id,
				'is_draft'       => (int) $item->is_draft,
				'updated_by'     => FrmAppHelper::get_user_id_param( (string) $item->updated_by ),
				'created_at'     => (string) $item->created_at,
				'updated_at'     => (string) $item->updated_at,
			);

			$metas = array();

			foreach ( $item->item_meta as $meta ) {
				$field_id = (int) $meta->field_id;

				if ( is_array( $frm_duplicate_ids ) && isset( $frm_duplicate_ids[ $field_id ] ) ) {
					$field_id = $frm_duplicate_ids[ $field_id ];
				}

				$field = FrmField::getOne( $field_id );

				if ( ! $field ) {
					continue;
				}

				$metas[ $field_id ] = FrmAppHelper::maybe_json_decode( (string) $meta->meta_value );
				$metas[ $field_id ] = apply_filters( 'frm_import_val', $metas[ $field_id ], $field );

				self::convert_field_values( $field, $field_id, $metas, $saved_entries );

				if ( $field->type === 'user_id' && $metas[ $field_id ] && is_numeric( $metas[ $field_id ] ) ) {
					$entry['frm_user_id'] = $metas[ $field_id ];
				}

				unset( $field, $meta );
			}

			$entry['item_meta'] = $metas;
			unset( $metas );

			// Edit entry if the key and created time match.
			$editing = FrmDb::get_var(
				'frm_items',
				array(
					'item_key'   => $entry['item_key'],
					'created_at' => gmdate( 'Y-m-d H:i:s', strtotime( $entry['created_at'] ) ),
				)
			);

			if ( $editing ) {
				FrmEntry::update_entry_from_xml( $entry['id'], $entry );

				if ( empty( $entry['parent_item_id'] ) ) {
					++$imported['updated']['items'];
				}

				$saved_entries[ $entry['id'] ] = $entry['id'];
			} else {
				$e = FrmEntry::create_entry_from_xml( $entry );

				if ( $e ) {
					$saved_entries[ $entry['id'] ] = $e;

					if ( empty( $entry['parent_item_id'] ) ) {
						++$imported['imported']['items'];
					}
				}
			}

			if ( array_key_exists( $entry['id'], $saved_entries ) ) {
				self::track_imported_child_entries( $saved_entries[ $entry['id'] ], $entry['parent_item_id'], $track_child_ids );
			}

			self::import_entry_comments_from_xml( $saved_entries[ $entry['id'] ], $item );
			unset( $item );
			unset( $entry );
		}

		self::update_parent_item_ids( $track_child_ids, $saved_entries );

		return $imported;
	}

	/**
	 * Imports entry comments.
	 *
	 * @since 6.10.1
	 *
	 * @param int              $entry_id
	 * @param SimpleXMLElement $item
	 *
	 * @return void
	 */
	private static function import_entry_comments_from_xml( $entry_id, $item ) {
		foreach ( $item->item_meta as $meta ) {
			if ( 0 !== (int) $meta->field_id ) {
				continue;
			}
			FrmEntryMeta::add_entry_meta( $entry_id, 0, '', FrmAppHelper::maybe_json_decode( (string) $meta->meta_value ) );
		}
	}

	/**
	 * @param array $entries
	 *
	 * @return void
	 */
	private static function put_child_entries_first( &$entries ) {
		$child_entries   = array();
		$regular_entries = array();

		foreach ( $entries as $item ) {
			$parent_item_id = (int) $item->parent_item_id;

			if ( $parent_item_id ) {
				$child_entries[] = $item;
			} else {
				$regular_entries[] = $item;
			}
		}

		$entries = array_merge( $child_entries, $regular_entries );
	}

	/**
	 * Track imported entries if they have a parent_item_id
	 * Use the old parent_item_id as the array key and set the array value to an array of child IDs
	 *
	 * @param bool|int $child_id
	 * @param int         $parent_id
	 * @param array       $track_child_ids - pass by reference
	 *
	 * @return void
	 */
	private static function track_imported_child_entries( $child_id, $parent_id, &$track_child_ids ) {
		if ( ! $parent_id ) {
			return;
		}

		if ( ! isset( $track_child_ids[ $parent_id ] ) ) {
			$track_child_ids[ $parent_id ] = array();
		}

		$track_child_ids[ $parent_id ][] = $child_id;
	}

	/**
	 * Update imported child entries so their parent_item_ids match any imported parent entries
	 *
	 * @since 2.0.12
	 *
	 *  @param array $track_child_ids
	 * @param array $saved_entries
	 */
	private static function update_parent_item_ids( $track_child_ids, $saved_entries ) {
		global $wpdb;

		foreach ( $track_child_ids as $old_parent_id => $new_child_ids ) {
			if ( ! isset( $saved_entries[ $old_parent_id ] ) ) {
				continue;
			}

			$new_parent_id = $saved_entries[ $old_parent_id ];
			$new_child_ids = '(' . implode( ',', $new_child_ids ) . ')';

			// This parent entry was imported and the parent_item_id column needs to be updated on all children
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'UPDATE ' . $wpdb->prefix . 'frm_items SET parent_item_id = %d WHERE id IN ' . $new_child_ids,
					$new_parent_id
				)
			);
		}
	}

	/**
	 * Gets field for import.
	 *
	 * @since 5.4 This method is public.
	 *
	 * @param int $field_id Field ID.
	 *
	 * @return false|object|null
	 */
	public static function get_field( $field_id ) {
		global $importing_fields;

		if ( ! $importing_fields ) {
			$importing_fields = array();
		}

		if ( isset( $importing_fields[ $field_id ] ) ) {
			$field = $importing_fields[ $field_id ];
		} else {
			$field                         = FrmField::getOne( $field_id );
			$importing_fields[ $field_id ] = $field;
		}

		return $field;
	}

	/**
	 * Imports a batch of entries from a CSV.
	 *
	 * The work lives in FrmProCSVImportHelper. This stays here because it is the
	 * entry point other code already calls, including the Locations add on.
	 *
	 * @param string     $path      Path to the CSV.
	 * @param int|string $form_id   Form to import into.
	 * @param array      $field_ids Field ids, indexed by their column position.
	 * @param int|string $entry_key Unused, kept for the existing signature.
	 * @param int        $start_row Row to start this batch at.
	 * @param string     $del       Column delimiter.
	 * @param int        $max       How many rows to import in this batch.
	 *
	 * @return int The last row imported.
	 */
	public static function import_csv( $path, $form_id, $field_ids, $entry_key = 0, $start_row = 2, $del = ',', $max = 250 ) {
		return FrmProCSVImportHelper::import_csv( $path, $form_id, $field_ids, $entry_key, $start_row, $del, $max );
	}

	/**
	 * @param stdClass $field
	 * @param int      $field_id
	 * @param array    $metas
	 * @param int[]    $saved_entries
	 *
	 * @return void
	 */
	public static function convert_field_values( $field, $field_id, &$metas, $saved_entries = array() ) {
		$field_obj   = FrmFieldFactory::get_field_object( $field );
		$atts        = array( 'ids' => $saved_entries );
		$date_format = FrmProCSVImportHelper::get_date_format( $field_id );

		if ( $date_format ) {
			$atts['date_format'] = $date_format;
		}

		$metas[ $field_id ] = $field_obj->get_import_value( $metas[ $field_id ], $atts );

		self::drop_unreadable_date( $field, $metas[ $field_id ] );
	}

	/**
	 * Empties a date that could not be read.
	 *
	 * An import writes entries without validating them, so a date the field
	 * handed back untouched, because no format could read it, would be stored in
	 * a date column as it arrived. Leaving it empty keeps the column holding
	 * dates, and an empty cell reads as missing rather than as some other day,
	 * which is what 1970-01-01 used to look like.
	 *
	 * @since 6.35
	 *
	 * @param stdClass $field Field the value belongs to.
	 * @param mixed    $value Value returned by the field, emptied in place.
	 *
	 * @return void
	 */
	private static function drop_unreadable_date( $field, &$value ) {
		if ( 'date' !== $field->type || ! is_string( $value ) || '' === $value ) {
			return;
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			$value = '';
		}
	}

	/**
	 * Converted an imported XML value to an array
	 *
	 * @since 2.03.08
	 *
	 * @param array|string $imported_value
	 *
	 * @return array
	 */
	public static function convert_imported_value_to_array( $imported_value ) {
		if ( ! is_string( $imported_value ) || ! str_contains( $imported_value, ',' ) ) {
			return (array) $imported_value;
		}

		FrmAppHelper::unserialize_or_decode( $imported_value );

		if ( ! is_array( $imported_value ) ) {
			return array_map( 'ltrim', explode( ',', $imported_value ) );
		}

		return $imported_value;
	}

	/**
	 * Update field settings before it's saved.
	 *
	 * @since 4.0
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public static function run_field_migrations( $field ) {
		$update = self::migrate_dyn_default_value( $field['type'], $field['field_options'] );

		foreach ( $update as $k => $v ) {
			$field[ $k ] = $v;
		}

		self::migrate_lookup_checkbox_setting( $field['field_options'] );
		self::migrate_lookup_placeholder( $field['field_options'] );

		return $field;
	}

	/**
	 * @since 4.0
	 *
	 * @param array $field_options
	 *
	 * @return void
	 */
	public static function migrate_lookup_placeholder( &$field_options ) {
		if ( empty( $field_options['lookup_placeholder_text'] ) ) {
			return;
		}

		if ( ! empty( $field_options['placeholder'] ) ) {
			// Don't overwrite an existing value.
			return;
		}

		$field_options['placeholder'] = $field_options['lookup_placeholder_text'];
		unset( $field_options['lookup_placeholder_text'] );
	}

	/**
	 * @since 4.0
	 *
	 * @param array $field_options
	 *
	 * @return void
	 */
	public static function migrate_lookup_checkbox_setting( &$field_options ) {
		$is_not_selected = empty( $field_options['get_values_field'] );
		$is_on           = ! isset( $field_options['autopopulate_value'] ) || ! empty( $field_options['autopopulate_value'] );

		if ( $is_not_selected || $is_on ) {
			return;
		}

		// Remove the unused settings used when a field selected, but autopopulate is off.
		$field_options['get_values_field'] = '';
		$field_options['get_values_form']  = '';
		unset( $field_options['autopopulate_value'] );
	}

	/**
	 * @since 4.0
	 *
	 * @param string $type
	 * @param array  $field_options
	 *
	 * @return array
	 */
	public static function migrate_dyn_default_value( $type, $field_options ) {
		$field_types = array( 'file', 'range', 'scale', 'star', 'time', 'toggle', 'user_id' );
		$has_default = ! empty( $field_options['dyn_default_value'] );

		if ( ! in_array( $type, $field_types, true ) || ! $has_default ) {
			return array();
		}

		$default_value                      = $field_options['dyn_default_value'];
		$field_options['dyn_default_value'] = '';
		return compact( 'field_options', 'default_value' );
	}

	/**
	 * Perform an action after a field is imported.
	 *
	 * Builds a map of original field ids to their new ids and remembers the original value of every
	 * setting that stores another field's id. Those settings are switched to the new ids later in
	 * switch_field_ids_after_import(), once every field in every imported form has an id. This avoids
	 * losing the value when a field is imported before the field it points at.
	 *
	 * @since 2.0.25
	 * @since 6.34    Added the $old_field_id param so existing fields updated on import are mapped too.
	 * @since 6.35     Records every setting in the FrmProField::field_id_settings() registry, not just watch_lookup.
	 *
	 * @param array $field_array
	 * @param int   $field_id
	 * @param int   $old_field_id Original id of the field, when updating a field that already exists.
	 *
	 * @return void
	 */
	public static function after_field_is_imported( $field_array, $field_id, $old_field_id = 0 ) {
		if ( ! $old_field_id ) { // If importing a new field.
			self::add_in_section_value_to_repeating_fields( $field_array, $field_id );
			self::update_page_titles( $field_array, $field_id );
			$old_field_id = $field_array['id'];
		}

		self::$xml_import_fields_data['fields_id_map'][ $old_field_id ] = $field_id;

		$field_type   = $field_array['type'] ?? '';
		$id_settings  = FrmProField::field_id_settings( $field_type );
		$original_ids = array();

		foreach ( $id_settings as $setting => $shape ) {
			if ( empty( $field_array['field_options'][ $setting ] ) ) {
				continue;
			}

			$original_ids[ $setting ] = $field_array['field_options'][ $setting ];
		}

		if ( $original_ids ) {
			self::$xml_import_fields_data['imported_fields_with_field_ids'][ $field_id ] = $original_ids;
		}
	}

	/**
	 * Switch every imported field setting that stores another field's id to the new field ids.
	 *
	 * Runs once every form in the file has been imported, so it resolves a reference to a field in
	 * the same form and to a field in a form imported later. A setting where no id could be resolved
	 * is left as it is in the database, so a fix-up made elsewhere during the import is not undone.
	 *
	 * @since 6.35
	 *
	 * @return void
	 */
	public static function switch_field_ids_after_import() {
		$imported_fields = self::$xml_import_fields_data['imported_fields_with_field_ids'];

		if ( ! $imported_fields ) {
			return;
		}

		$fields_id_map = array_map( 'intval', self::$xml_import_fields_data['fields_id_map'] );
		$new_fields    = FrmDb::get_results( 'frm_fields', array( 'id' => array_keys( $imported_fields ) ), 'id,type,field_options' );

		foreach ( (array) $new_fields as $new_field ) {
			$field_options = $new_field->field_options;
			FrmAppHelper::unserialize_or_decode( $field_options );

			if ( ! is_array( $field_options ) ) {
				continue;
			}

			$id_settings  = FrmProField::field_id_settings( $new_field->type );
			$original_ids = $imported_fields[ (int) $new_field->id ];
			$updated      = false;

			foreach ( $original_ids as $setting => $original_value ) {
				if ( ! isset( $id_settings[ $setting ] ) ) {
					continue;
				}

				$changed   = false;
				$new_value = FrmProField::switch_ids_in_setting_value( $original_value, $id_settings[ $setting ], $fields_id_map, $changed );

				if ( ! $changed ) {
					// Nothing resolved, so leave the value in the database alone. Another import
					// fix-up may have already corrected it, as happens for a repeater's in_section.
					continue;
				}

				$field_options[ $setting ] = $new_value;
				$updated                   = true;
			}

			if ( $updated ) {
				FrmField::update( $new_field->id, array( 'field_options' => $field_options ) );
			}
		}
	}

	/**
	 * Switch the watch_lookup ids of imported lookup fields to the ids of the newly imported fields.
	 *
	 * @since 6.34
	 * @since 6.35  Switches every setting that stores a field id, for every imported form. The
	 *             $form_id param is no longer used, since the switch is no longer scoped to one form.
	 *
	 * @param int $form_id Id of the form that was just imported.
	 *
	 * @return void
	 */
	public static function after_import_form( $form_id = 0 ) {
		self::switch_field_ids_after_import();
	}

	/**
	 * Switch the form ids stored in field settings that point at a form imported later.
	 *
	 * FrmXMLHelper::maybe_update_form_select() and maybe_update_get_values_form_setting() run while
	 * each field is created, so they cannot resolve a form that has not been imported yet. Child
	 * forms are imported first, which covers a repeating section, but an embedded form and a Dynamic
	 * or Lookup source form can appear anywhere in the file. Once every form has an id, a reference
	 * still holding an old id can be switched.
	 *
	 * @since 6.35
	 *
	 * @param array $imported Summary of imported items, including 'forms' => array( old_form_id => new_form_id ).
	 *
	 * @return void
	 */
	public static function switch_form_ids_after_import( $imported ) {
		if ( empty( $imported['forms'] ) ) {
			return;
		}

		$new_form_ids = array_map( 'intval', array_values( $imported['forms'] ) );

		foreach ( array( 'form_select', 'get_values_form' ) as $setting ) {
			$fields = FrmField::getAll(
				array(
					'fi.form_id'            => $new_form_ids,
					'fi.field_options like' => '"' . $setting . '"',
				),
				'field_order'
			);

			foreach ( $fields as $field ) {
				if ( 'form_select' === $setting && ! self::form_select_holds_a_form_id( $field ) ) {
					// On a Dynamic field form_select holds a field id, which the field id registry covers.
					continue;
				}

				$old_form_id = $field->field_options[ $setting ] ?? 0;

				if ( ! $old_form_id || ! is_numeric( $old_form_id ) ) {
					continue;
				}

				// Skip a value that is already one of the newly imported form ids, so an id that was
				// switched already is not switched a second time.
				if ( in_array( (int) $old_form_id, $new_form_ids, true ) || ! isset( $imported['forms'][ $old_form_id ] ) ) {
					continue;
				}

				$field->field_options[ $setting ] = $imported['forms'][ $old_form_id ];

				FrmField::update( $field->id, array( 'field_options' => $field->field_options ) );
			}
		}
	}

	/**
	 * @since 6.35
	 *
	 * @param stdClass $field
	 *
	 * @return bool True when the field's form_select setting holds a form id rather than a field id.
	 */
	private static function form_select_holds_a_form_id( $field ) {
		if ( 'form' === $field->type ) {
			return true;
		}

		return 'divider' === $field->type && FrmField::is_option_true( $field->field_options, 'repeat' );
	}

	/**
	 * Reset the data collected while importing fields.
	 *
	 * Runs after a full XML import so the static data does not leak into a later import in the same request.
	 *
	 * @since 6.34
	 *
	 * @return void
	 */
	public static function reset_xml_import_fields_data() {
		self::$xml_import_fields_data = array(
			'fields_id_map'                  => array(),
			'imported_fields_with_field_ids' => array(),
		);
	}

	/**
	 * Fixes broken form_select field options after xml is imported.
	 *
	 * @since 6.6
	 *
	 * @param array $imported
	 *
	 * @return array
	 */
	public static function after_xml_imported( $imported ) {
		$imported_forms  = $imported['forms'];
		$form_ids        = array_map( 'intval', $imported_forms );
		$imported_fields = FrmDb::get_results(
			'frm_fields',
			array(
				'form_id' => $form_ids,
				'type'    => 'form',
			),
			'id,field_options'
		);

		foreach ( $imported_fields as $field ) {
			$field_options = $field->field_options;
			FrmAppHelper::unserialize_or_decode( $field_options );

			if ( empty( $field_options['form_select'] ) || empty( $imported_forms[ $field_options['form_select'] ] ) ) {
				continue;
			}

			$field_options['form_select'] = $imported_forms[ $field_options['form_select'] ];
			FrmField::update( $field->id, array( 'field_options' => $field_options ) );
		}

		return $imported;
	}

	/**
	 * Update page title indexes after import
	 *
	 * @since 2.03.06
	 *
	 * @param array $field_array
	 * @param int|string $new_id
	 *
	 * @return void
	 */
	private static function update_page_titles( $field_array, $new_id ) {
		if ( $field_array['type'] !== 'break' ) {
			return;
		}

		$form   = FrmForm::getOne( $field_array['form_id'] );
		$old_id = $field_array['id'];

		if ( ! isset( $form->options['rootline_titles'][ $old_id ] ) ) {
			return;
		}

		$form->options['rootline_titles'][ $new_id ] = $form->options['rootline_titles'][ $old_id ];
		unset( $form->options['rootline_titles'][ $old_id ] );
		FrmForm::update( $form->id, array( 'options' => $form->options ) );
	}

	/**
	 * Add the in_section value to fields in a repeating section
	 *
	 * @since 2.0.25
	 *
	 * @param array $f
	 * @param int $section_id
	 *
	 * @return void
	 */
	private static function add_in_section_value_to_repeating_fields( $f, $section_id ) {
		$is_repeating_section = $f['type'] === 'divider'
			&& FrmField::is_option_true( $f['field_options'], 'repeat' )
			&& FrmField::is_option_true( $f['field_options'], 'form_select' );

		if ( ! $is_repeating_section ) {
			return;
		}

		$new_form_id  = $f['field_options']['form_select'];
		$child_fields = FrmDb::get_col( 'frm_fields', array( 'form_id' => $new_form_id ), 'id' );

		if ( ! $child_fields ) {
			return;
		}

		self::add_in_section_value_to_field_ids( $child_fields, $section_id );
	}

	/**
	 * Add specific in_section value to an array of field IDs
	 *
	 * @since 2.0.25
	 *
	 * @param array $field_ids
	 * @param int $section_id
	 *
	 * @return void
	 */
	public static function add_in_section_value_to_field_ids( $field_ids, $section_id ) {
		foreach ( $field_ids as $child_id ) {
			$child_field_options = FrmDb::get_var( 'frm_fields', array( 'id' => $child_id ), 'field_options' );
			FrmAppHelper::unserialize_or_decode( $child_field_options );
			$child_field_options['in_section'] = $section_id;

			// Update now
			$update_values = array( 'field_options' => $child_field_options );
			FrmField::update( $child_id, $update_values );
		}
	}

	/**
	 * Update the in_section value after all of the fields have been imported
	 * In come cases the in_section value will be lost otherwise
	 * And the repeater data will not properly associate with the repeating field data
	 *
	 * @param int $child_form_id
	 * @param int $parent_form_id
	 *
	 * @return void
	 */
	public static function maybe_update_in_section_variables_for_repeater_children( $child_form_id, $parent_form_id ) {
		$child_form_fields = FrmField::get_all_for_form( $child_form_id );

		foreach ( $child_form_fields as $child_field ) {
			if ( $child_field->field_options['in_section'] ) {
				continue;
			}

			if ( ! isset( $dividers ) ) {
				$dividers = FrmField::get_all_types_in_form( $parent_form_id, 'divider' );
			}

			foreach ( $dividers as $divider ) {
				if ( FrmField::is_repeating_field( $divider ) && (int) $divider->field_options['form_select'] === (int) $child_form_id ) {
					$child_field->field_options['in_section'] = $divider->id;
					self::add_in_section_value_to_field_ids( array( $child_field->id ), $divider->id );
					break;
				}
			}
		}
	}
}
