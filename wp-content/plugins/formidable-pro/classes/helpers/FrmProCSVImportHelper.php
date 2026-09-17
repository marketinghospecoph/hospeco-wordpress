<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Works out how to read the dates in a CSV before its rows are imported.
 *
 * A CSV has usually been through a spreadsheet on its way here, so its dates
 * are often written in an order that does not match the site's date format. A
 * single value cannot say whether 05/04/2021 is April 5 or May 4, but the column
 * it sits in usually can, so the file is read once up front and each date column
 * judged on its own evidence.
 *
 * @since 6.35
 */
class FrmProCSVImportHelper {

	/**
	 * Date formats worked out from the CSV being imported, indexed by field id.
	 *
	 * @since 6.35
	 *
	 * @var array
	 */
	private static $date_formats = array();

	/**
	 * @var bool Set to true when importing a file whose name ends in -legacy.
	 *
	 * @since 6.35
	 */
	private static $legacy_import_format = false;

	/**
	 * How many rows to read when working out the date order of a column. Enough
	 * to find a day above 12 in any realistic file, without reading a very large
	 * file end to end.
	 *
	 * @since 6.35
	 *
	 * @var int
	 */
	private static $sample_rows = 1000;

	/**
	 * How many distinct values to keep per date column. A column repeats the
	 * same handful of dates far more often than not, so keeping only the
	 * distinct ones holds memory flat however long the file is. This many is
	 * already far more than it takes to find a day above 12.
	 *
	 * @since 6.35
	 *
	 * @var int
	 */
	private static $sample_values = 100;

	/**
	 * Works out the date order used by each date column in the CSV.
	 *
	 * A date on its own can be ambiguous, but the column it sits in usually is
	 * not: one value above 12 settles which part is the day for every row. The
	 * file is better evidence than the site's date format setting here, because
	 * the file was very often exported from somewhere else.
	 *
	 * @since 6.35
	 *
	 * @param string $path      Path to the CSV being imported.
	 * @param array  $field_ids Field ids, indexed by their column position.
	 * @param string $del       Column delimiter.
	 *
	 * @return void
	 */
	public static function read_date_formats( $path, $field_ids, $del ) {
		self::$date_formats = array();

		$columns = self::get_date_columns( $field_ids );

		if ( ! $columns ) {
			return;
		}

		// A large import runs as many batches, one request each, and every one
		// of them would otherwise read the file again to reach the same answer.
		// The key covers the file and the column mapping, so a different import
		// cannot pick up the wrong one.
		$cache_key = 'frm_csv_dates_' . md5( $path . '|' . filemtime( $path ) . '|' . filesize( $path ) . '|' . implode( ',', $columns ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			self::$date_formats = $cached;
			return;
		}

		self::$date_formats = self::scan_date_columns( $path, $columns, $del );

		set_transient( $cache_key, self::$date_formats, HOUR_IN_SECONDS );
	}

	/**
	 * Reads the top of the CSV to work out the date order of each date column.
	 *
	 * @since 6.35
	 *
	 * @param string $path    Path to the CSV being imported.
	 * @param array  $columns Field ids indexed by column position.
	 * @param string $del     Column delimiter.
	 *
	 * @return array Date formats indexed by field id.
	 */
	private static function scan_date_columns( $path, $columns, $del ) {
		$handle = fopen( $path, 'r' );

		if ( ! $handle ) {
			return array();
		}

		$row          = 0;
		$values       = array();
		$column_count = count( $columns );

		while ( ( $data = fgetcsv( $handle, 100000, $del, '"', '\\' ) ) !== false ) {
			++$row;

			// Row one holds the headers.
			if ( 1 === $row ) {
				continue;
			}

			$full = 0;

			foreach ( $columns as $column => $field_id ) {
				if ( ! isset( $values[ $column ] ) ) {
					$values[ $column ] = array();
				}

				if ( count( $values[ $column ] ) >= self::$sample_values ) {
					++$full;
					continue;
				}

				if ( isset( $data[ $column ] ) && '' !== $data[ $column ] ) {
					// Keyed by the value itself, so a date the file repeats a
					// thousand times is only held once.
					$values[ $column ][ $data[ $column ] ] = true;
				}
			}

			// Nothing left to learn from the rest of the file.
			if ( $full === $column_count || $row > self::$sample_rows ) {
				break;
			}
		}

		fclose( $handle );

		$formats = array();

		foreach ( $columns as $column => $field_id ) {
			if ( empty( $values[ $column ] ) ) {
				continue;
			}

			$format = FrmProDateFormatHelper::infer_date_format_from_values( array_keys( $values[ $column ] ) );

			if ( $format ) {
				$formats[ $field_id ] = $format;
			}
		}

		return $formats;
	}

	/**
	 * Finds the CSV columns that map to a date field.
	 *
	 * @since 6.35
	 *
	 * @param array $field_ids Field ids, indexed by their column position.
	 *
	 * @return array Field ids indexed by column position.
	 */
	private static function get_date_columns( $field_ids ) {
		$columns = array();

		foreach ( $field_ids as $column => $field_id ) {
			if ( ! is_numeric( $field_id ) ) {
				continue;
			}

			$field = FrmProXMLHelper::get_field( $field_id );

			if ( $field && 'date' === $field->type ) {
				$columns[ $column ] = $field_id;
			}
		}

		return $columns;
	}

	/**
	 * The date format worked out for one column of the CSV being imported.
	 *
	 * @since 6.35
	 *
	 * @param int|string $field_id Field the column maps to.
	 *
	 * @return string Empty string when the column proved nothing.
	 */
	public static function get_date_format( $field_id ) {
		return self::$date_formats[ $field_id ] ?? '';
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 */
	public static function import_csv( $path, $form_id, $field_ids, $entry_key = 0, $start_row = 2, $del = ',', $max = 250 ) {
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		$form_id = (int) $form_id;

		if ( ! $form_id ) {
			return $start_row;
		}

		// Remove time limit to execute this function
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		$field_ids = array_filter( $field_ids );

		/**
		 * Allows adding fixed meta values.
		 *
		 * Some field types might not appear in the CSV file (Example: Likert field), but we need to set the meta value
		 * for them when importing.
		 *
		 * The return value should be an empty array or an array like this:
		 * array(
		 *     13 => 'meta value', // 13 is the field ID, this field is outside of Repeater or Embed Form.
		 *     '13_10' => 'meta value', // 13 is the field ID, 10 is the Repeater or Embed Form ID which that field is inside.
		 * )
		 *
		 * @since 5.4
		 *
		 * @param array $fixed_meta_values Fixed meta values.
		 * @param array $args              Contains `form_id`.
		 */
		$fixed_meta_values = apply_filters( 'frm_pro_csv_import_fixed_meta_values', array(), compact( 'form_id' ) );

		self::check_csv_filename_for_legacy_format( $path );
		self::read_date_formats( $path, $field_ids, $del );

		$f = fopen( $path, 'r' );

		if ( $f ) {
			unset( $path );
			$row       = 0;
			$headers   = array();
			$enclosure = '"';
			$escape    = '\\';

			while ( ( $data = fgetcsv( $f, 100000, $del, $enclosure, $escape ) ) !== false ) {
				++$row;

				if ( $row === 1 ) {
					$headers = $data;
				}

				if ( $start_row > $row ) {
					continue;
				}

				$comments = self::get_comments_from_row( $row, $data, $headers );

				$values = array(
					'form_id'   => $form_id,
					'item_meta' => array(),
				);

				foreach ( $field_ids as $key => $field_id ) {
					self::csv_to_entry_value( $key, $field_id, $data, $values );
					unset( $key, $field_id );
				}

				self::maybe_add_fixed_meta_values( $fixed_meta_values, $values );
				self::convert_db_cols( $values );
				self::convert_timestamps( $values );
				self::save_or_edit_entry( $values, $comments );

				unset( $_POST, $values );
				$_POST['form_id'] = $form_id; // $form_id is set from $_POST['form_id'], so set it back again after the unset line above.

				if ( $row - $start_row >= $max ) {
					fclose( $f );
					return $row;
				}
			}

			fclose( $f );
			return $row;
		}
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $data
	 * @param array $values
	 */
	private static function csv_to_entry_value( $key, $field_id, $data, &$values ) {
		$data[ $key ] = $data[ $key ] ?? '';

		if ( is_numeric( $field_id ) ) {
			self::set_values_for_fields( $key, $field_id, $data, $values );
			return;
		}

		if ( is_array( $field_id ) ) {
			self::set_values_for_data_fields( $key, $field_id, $data, $values );
			return;
		}

		// If this has format `{field_id_number}_{subfield_name}`, this is the combo subfield.
		$check_combo = self::check_combo_field_export_col( $field_id );

		if ( $check_combo ) {
			self::set_values_for_combo_fields( $data[ $key ], $check_combo[0], $check_combo[1], $values );
			return;
		}

		$values[ $field_id ] = $data[ $key ];
	}

	/**
	 * Checks if the given column id is a column of combo field.
	 *
	 * @since 4.10.02
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param string $col_id Column ID.
	 *
	 * @return array|false Return array with first item is the field ID and second item is subfield name.
	 */
	private static function check_combo_field_export_col( $col_id ) {
		if ( ! is_string( $col_id ) ) {
			return false;
		}

		$sep   = '_';
		$parts = explode( $sep, $col_id );

		if ( 2 > count( $parts ) ) {
			return false;
		}

		if ( empty( $parts[0] ) || empty( $parts[1] ) || ! is_numeric( $parts[0] ) ) {
			return false;
		}

		$field_id = array_shift( $parts );

		return array( $field_id, implode( $sep, $parts ) );
	}

	/**
	 * Sets values for combo fields.
	 *
	 * @since 4.11.0
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param string $value          Value get from CSV.
	 * @param int    $field_id       Field ID.
	 * @param string $sub_field_name Subfield name.
	 * @param array  $values         Import values.
	 */
	private static function set_values_for_combo_fields( $value, $field_id, $sub_field_name, &$values ) {
		$field      = FrmProXMLHelper::get_field( $field_id );
		$section_id = self::check_field_for_section_id( $field );

		if ( ! $section_id ) {
			if ( ! isset( $values['item_meta'][ $field_id ] ) || ! is_array( $values['item_meta'][ $field_id ] ) ) {
				$values['item_meta'][ $field_id ] = array();
			}

			$values['item_meta'][ $field_id ][ $sub_field_name ] = $value;
			$_POST['item_meta'][ $field_id ]                     = $values['item_meta'][ $field_id ];
			return;
		}

		if ( isset( $values['item_meta'][ $section_id ] ) ) {
			$index = count( $values['item_meta'][ $section_id ] ) - 2; // Because of 'form' element.
		} else {
			$values['item_meta'][ $section_id ] = array( 'form' => $field->form_id );
			$index                              = 0;
		}

		if ( isset( $values['item_meta'][ $section_id ][ $index ][ $field_id ][ $sub_field_name ] ) ) {
			++$index;
			$values['item_meta'][ $section_id ][ $index ]                                 = array();
			$values['item_meta'][ $section_id ][ $index ][ $field_id ]                    = array();
			$values['item_meta'][ $section_id ][ $index ][ $field_id ][ $sub_field_name ] = $value;
		} else {
			$values['item_meta'][ $section_id ][ $index ][ $field_id ][ $sub_field_name ] = $value;
		}

		$_POST['item_meta'][ $section_id ] = $values['item_meta'][ $section_id ];
	}

	/**
	 * Called by self::csv_to_entry_value
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int   $key
	 * @param array $field_id
	 * @param array $data
	 * @param array $values
	 *
	 * @return void
	 */
	private static function set_values_for_data_fields( $key, $field_id, $data, &$values ) {
		$field_type = $field_id['type'] ?? false;

		if ( $field_type !== 'data' ) {
			return;
		}

		$linked   = $field_id['linked'] ?? false;
		$field_id = $field_id['field_id'];

		if ( $linked ) {
			$entry_id = FrmDb::get_var(
				'frm_item_metas',
				array(
					'meta_value' => $data[ $key ],
					'field_id'   => $linked,
				),
				'item_id'
			);
		} else {
			// get entry id of entry with item_key == $data[$key]
			$entry_id = FrmDb::get_var( 'frm_items', array( 'item_key' => $data[ $key ] ) );
		}

		if ( $entry_id ) {
			$values['item_meta'][ $field_id ] = $entry_id;
		}
	}

	/**
	 * Called by self::csv_to_entry_value
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int        $key
	 * @param int|string $field_id
	 * @param array      $data
	 * @param array      $values
	 */
	private static function set_values_for_fields( $key, $field_id, $data, &$values ) {
		$field = FrmProXMLHelper::get_field( $field_id );

		/**
		 * Allows modifying field to be imported.
		 *
		 * @since 5.4
		 *
		 * @param object $field Field object.
		 * @param array  $args  Contains `field_id`.
		 */
		$field = apply_filters( 'frm_pro_get_field_for_import', $field, compact( 'field_id' ) );

		$section_id = self::check_field_for_section_id( $field );

		$values['item_meta'][ $field_id ] = apply_filters( 'frm_import_val', $data[ $key ], $field );
		FrmProXMLHelper::convert_field_values( $field, $field_id, $values['item_meta'] );
		$value = $values['item_meta'][ $field_id ];

		if ( $field->type === 'user_id' ) {
			$_POST['frm_user_id']  = $value;
			$values['frm_user_id'] = $value;
		}

		if ( $section_id ) {
			self::set_section_field_value( $section_id, $field->form_id, $field_id, $value, $values );
			return;
		}

		$item_meta     = FrmAppHelper::get_post_param( 'item_meta', array() );
		$is_array_type = $field->type === 'checkbox' || ( $field->type === 'data' && $field->field_options['data_type'] !== 'checkbox' );

		if ( $value && $is_array_type && ! empty( $item_meta[ $field_id ] ) ) {
			$value = array_merge( (array) $item_meta[ $field_id ], (array) $value );
		}

		$values['item_meta'][ $field_id ] = $value;
		$_POST['item_meta'][ $field_id ]  = $value;
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param object $field
	 *
	 * @return false|int
	 */
	private static function check_field_for_section_id( $field ) {
		if ( self::is_the_child_of_a_repeater( $field ) ) {
			return $field->field_options['in_section'];
		}

		$form_id = FrmAppHelper::get_post_param( 'form_id', 0, 'absint' );

		if ( $form_id && self::field_is_embedded( $field ) ) {
			return self::get_section_id_from_form_fields( $form_id, $field->form_id );
		}

		return false;
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param object $field
	 *
	 * @return bool
	 */
	private static function field_is_embedded( $field ) {
		return isset( $_POST['form_id'] ) && $field->form_id !== $_POST['form_id'];
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 */
	private static function get_section_id_from_form_fields( $parent_form_id, $embedded_form_id ) {
		$fields = FrmField::get_all_types_in_form( $parent_form_id, 'form' );

		foreach ( $fields as $parent_form_field ) {
			if ( ! empty( $parent_form_field->field_options['form_select'] ) && (int) $parent_form_field->field_options['form_select'] === (int) $embedded_form_id ) {
				return $parent_form_field->id;
			}
		}

		return false;
	}

	/**
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param object $field
	 *
	 * @return bool
	 */
	private static function is_the_child_of_a_repeater( $field ) {
		$form_id = FrmAppHelper::get_post_param( 'form_id', false, 'absint' );

		if ( (int) $field->form_id === $form_id || empty( $field->field_options['in_section'] ) ) {
			return false;
		}

		$section_id = $field->field_options['in_section'];
		$section    = FrmProXMLHelper::get_field( $section_id );

		return $section ? FrmField::is_repeating_field( $section ) : false;
	}

	/**
	 * Update section data when importing, for populating repeater fields.
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int          $section_id
	 * @param int          $form_id
	 * @param int          $field_id
	 * @param array|string $value
	 * @param array        $values
	 */
	private static function set_section_field_value( $section_id, $form_id, $field_id, $value, &$values ) {
		if ( self::$legacy_import_format ) {
			$section_data = self::get_new_section_data_for_legacy_format( $section_id, $form_id, $field_id, $value );
		} else {
			$section_data = self::get_new_section_data_for_multiple_row_format( $section_id, $form_id, $field_id, $value );
		}
		$values['item_meta'][ $section_id ] = $section_data;
		$_POST['item_meta'][ $section_id ]  = $section_data;
		unset( $values['item_meta'][ $field_id ] );
	}

	/**
	 * Get the new section data for legacy single-row format.
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int          $section_id
	 * @param int          $form_id
	 * @param int          $field_id
	 * @param array|string $value
	 */
	private static function get_new_section_data_for_legacy_format( $section_id, $form_id, $field_id, $value ) {
		$value     = array_map( 'trim', explode( ',', $value ) );
		$item_meta = FrmAppHelper::get_post_param( 'item_meta', array() );

		foreach ( $value as $index => $current ) {
			$section_data = isset( $item_meta[ $section_id ] ) ? (array) $item_meta[ $section_id ] : array( 'form' => $form_id );

			foreach ( $value as $index => $current ) {
				if ( ! isset( $section_data[ $index ] ) ) {
					$section_data[ $index ] = array();
				}
				$section_data[ $index ][ $field_id ] = $current;
			}
		}

		return $section_data;
	}

	/**
	 * Get the new section data for current multiple row format.
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int          $section_id
	 * @param int          $form_id
	 * @param int          $field_id
	 * @param array|string $value
	 *
	 * @return array
	 */
	private static function get_new_section_data_for_multiple_row_format( $section_id, $form_id, $field_id, $value ) {
		$item_meta = FrmAppHelper::get_post_param( 'item_meta', array() );

		if ( ! isset( $item_meta[ $section_id ] ) ) {
			return array(
				'form' => $form_id,
				array( $field_id => $value ),
			);
		}

		$section_data = $item_meta[ $section_id ];
		$index        = 0;

		while ( true ) {
			if ( ! array_key_exists( $index, $section_data ) ) {
				$section_data[ $index ] = array();
				break;
			}

			if ( ! array_key_exists( $field_id, $section_data[ $index ] ) ) {
				break;
			}

			++$index;
		}

		$section_data[ $index ][ $field_id ] = $value;
		return $section_data;
	}

	/**
	 * Before support for importing into a repeater with multiple rows, it was possible to import csv values from a single row.
	 * To support this previous format backwards, a file uploaded with -legacy in the filename will import differently.
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param string $path the path we're importing. If it includes -legacy, the flag will be set to true.
	 */
	private static function check_csv_filename_for_legacy_format( $path ) {
		self::$legacy_import_format = str_contains( basename( $path ), '-legacy' );
	}

	/**
	 * Maybe add fixed meta values to entry meta.
	 *
	 * @since 5.4.1
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $fixed_meta_values Fixed meta values.
	 * @param array $values            Entry data.
	 */
	private static function maybe_add_fixed_meta_values( $fixed_meta_values, &$values ) {
		if ( ! $fixed_meta_values ) {
			return;
		}

		foreach ( $fixed_meta_values as $fixed_meta_field => $fixed_meta_value ) {
			if ( is_numeric( $fixed_meta_field ) ) {
				$values['item_meta'][ $fixed_meta_field ] = $fixed_meta_value;
				continue;
			}

			list( $field_id, $section_id ) = explode( '_', $fixed_meta_field );

			foreach ( $values['item_meta'] as $meta_field => $meta_value ) {
				if ( intval( $meta_field ) !== intval( $section_id ) || ! is_array( $meta_value ) ) {
					continue;
				}

				foreach ( $meta_value as $key => $value ) {
					if ( ! is_numeric( $key ) || ! is_array( $value ) ) {
						continue;
					}
					$values['item_meta'][ $meta_field ][ $key ][ $field_id ] = $fixed_meta_value;
				}
			}
		}
	}

	/**
	 * Make sure values are in the format they should be saved in
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $values
	 *
	 * @return void
	 */
	private static function convert_db_cols( &$values ) {
		if ( empty( $values['item_key'] ) ) {
			global $wpdb;
			$values['item_key'] = FrmAppHelper::get_unique_key( '', $wpdb->prefix . 'frm_items', 'item_key' );
		}

		if ( isset( $values['user_id'] ) ) {
			$values['user_id'] = FrmAppHelper::get_user_id_param( $values['user_id'] );
		}

		if ( isset( $values['updated_by'] ) ) {
			$values['updated_by'] = FrmAppHelper::get_user_id_param( $values['updated_by'] );
		}

		if ( isset( $values['is_draft'] ) ) {
			$values['is_draft'] = (int) $values['is_draft'];
		}

		if ( isset( $values['ip'] ) ) {
			$values['ip'] = sanitize_text_field( $values['ip'] );
		}
	}

	/**
	 * Convert timestamps to the database format
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $values
	 */
	private static function convert_timestamps( &$values ) {
		$offset          = get_option( 'gmt_offset' ) * 60 * 60;
		$frmpro_settings = FrmProAppHelper::get_settings();

		foreach ( array( 'created_at', 'updated_at' ) as $stamp ) {
			if ( ! isset( $values[ $stamp ] ) ) {
				continue;
			}

			// Adjust the date format if it starts with the day
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}/', trim( $values[ $stamp ] ) ) && str_starts_with( $frmpro_settings->date_format, 'd' ) ) {
				$reg_ex = str_replace(
					array( '/', '.', '-', 'd', 'j', 'm', 'y', 'Y' ),
					array( '\/', '\.', '\-', '\d{2}', '\d', '\d{2}', '\d{2}', '\d{4}' ),
					$frmpro_settings->date_format
				);

				if ( preg_match( '/^' . $reg_ex . '/', trim( $values[ $stamp ] ) ) ) {
					$values[ $stamp ] = FrmProAppHelper::convert_date( $values[ $stamp ], $frmpro_settings->date_format, 'Y-m-d H:i:s' );
				}
			}

			$values[ $stamp ] = gmdate( 'Y-m-d H:i:s', strtotime( $values[ $stamp ] ) - $offset );

			unset( $stamp );
		}
	}

	/**
	 * Returns an array from a given row values.
	 *
	 * @since 6.12
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int   $row     Row index
	 * @param array $data    The entry values
	 * @param array $headers The csv column headers
	 *
	 * @return array
	 */
	private static function get_comments_from_row( $row, $data, $headers ) {
		$comment_strings = self::get_comment_strings();

		$comments = array(
			$comment_strings['comment']      => array(),
			$comment_strings['comment_user'] => array(),
			$comment_strings['comment_date'] => array(),
		);

		if ( $row <= 1 ) {
			return $comments;
		}

		foreach ( $data as $key => $col ) {
			if ( in_array( $headers[ $key ], array( $comment_strings['comment'], $comment_strings['comment_user'], $comment_strings['comment_date'] ), true ) ) {
				$comments[ $headers[ $key ] ][] = $col;
			}
		}

		return $comments;
	}

	/**
	 * @since 6.12
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @return array
	 */
	private static function get_comment_strings() {
		return array(
			'comment'      => __( 'Comment', 'formidable' ),
			'comment_user' => __( 'Comment User', 'formidable' ),
			'comment_date' => __( 'Comment Date', 'formidable' ),
		);
	}

	/**
	 * @since 6.12
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param int   $entry_id
	 * @param array $comments
	 *
	 * @return void
	 */
	private static function import_entry_comments_from_csv( $entry_id, $comments ) {
		$comment_strings = self::get_comment_strings();
		global $wpdb;

		foreach ( $comments[ $comment_strings['comment'] ] as $key => $comment ) {
			$user       = get_user_by( 'login', $comments[ $comment_strings['comment_user'] ][ $key ] );
			$user_id    = $user ? $user->ID : '';
			$meta_value = array(
				'comment' => $comment,
				'user_id' => $user_id,
			);

			$values = array(
				'meta_value' => serialize( array_filter( $meta_value, 'FrmAppHelper::is_not_empty_value' ) ),
				'item_id'    => $entry_id,
				'field_id'   => 0,
				'created_at' => get_gmt_from_date( $comments[ $comment_strings['comment_date'] ][ $key ] ),
			);

			$wpdb->insert( $wpdb->prefix . 'frm_item_metas', $values );
		}
	}

	/**
	 * Editing CSV entries on import based on id or key
	 *
	 * @since 3.01.03
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $values
	 *
	 * @return int
	 */
	private static function get_entry_to_edit( $values ) {
		$entry_id = 0;
		$query    = array();

		if ( ! empty( $values['id'] ) ) {
			$query['id'] = $values['id'];
		}

		if ( ! empty( $values['item_key'] ) ) {
			$query['item_key'] = $values['item_key'];
		}

		if ( $query ) {
			if ( count( $query ) === 2 ) {
				$query = array_merge( array( 'or' => 1 ), $query );
			}

			$query    = array(
				'form_id' => $values['form_id'],
				$query,
			);
			$entry_id = FrmDb::get_var( 'frm_items', $query );
		}

		/**
		 * When importing entries via CSV set the id of the entry that should be edited
		 *
		 * @since 3.01.03
		 *
		 * @param int $entry_id - The ID of the entry to edit. 0 means a new entry will be created.
		 * @param array $values - The mapped values for this entry
		 */
		return (int) apply_filters( 'frm_editing_entry_by_csv', absint( $entry_id ), $values );
	}

	/**
	 * Save the entry after checking if it should be created or updated
	 *
	 * @since 6.35 Moved here from FrmProXMLHelper.
	 *
	 * @param array $values
	 * @param array $comments
	 */
	private static function save_or_edit_entry( $values, $comments ) {
		$entry_id = self::get_entry_to_edit( $values );

		if ( $entry_id ) {
			FrmEntry::update( $entry_id, $values );
		} else {
			$entry_id = FrmEntry::create( $values );
		}

		self::import_entry_comments_from_csv( $entry_id, $comments );
	}
}
