<?php
/**
 * Duplicate Fields Helper class.
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Repoint the field and form ids a duplicated field stores in its settings.
 *
 * Fields are duplicated one at a time in field order, so a field that references another field can
 * be copied before the field it points at exists. A repeater makes this common, since its fields
 * live in a child form that is copied alongside the parent form.
 *
 * @since 6.35
 */
class FrmProDuplicateFieldsHelper {

	/**
	 * Point a duplicated field's source form setting at the new form when it referenced the form it
	 * was copied from.
	 *
	 * A Lookup or Dynamic field that reads from a field in its own form stores that form id in
	 * get_values_form. FrmProField::duplicate() switches the field id the setting is paired with, but
	 * nothing switches the form id, so the copy keeps pointing at the form it was copied from. The
	 * field settings then load the original form's fields, the id of the field the copy actually
	 * points at is missing from that list, and the next save writes a field from the original form,
	 * leaving the copy reading the original form's entries.
	 *
	 * A setting that references any other form is left alone, since that form was not copied.
	 *
	 * @since 6.35
	 *
	 * @param int   $form_id Id of the new form.
	 * @param array $values  Values the new form was created with.
	 * @param array $args    Duplication arguments, including 'old_id' => id of the form that was copied.
	 *
	 * @return void
	 */
	public static function switch_source_form_id_after_duplicate( $form_id, $values, $args = array() ) {
		$old_form_id = isset( $args['old_id'] ) ? (int) $args['old_id'] : 0;

		if ( ! $old_form_id || (int) $form_id === $old_form_id ) {
			return;
		}

		// Include the fields inside a repeating section, which live in a child form.
		$where = array(
			array(
				'or'                => 1,
				'fi.form_id'        => $form_id,
				'fr.parent_form_id' => $form_id,
			),
		);

		foreach ( FrmField::getAll( $where, 'field_order' ) as $field ) {
			$source_form_id = $field->field_options['get_values_form'] ?? '';

			if ( ! is_numeric( $source_form_id ) || (int) $source_form_id !== $old_form_id ) {
				continue;
			}

			$field->field_options['get_values_form'] = $form_id;

			FrmField::update( $field->id, array( 'field_options' => $field->field_options ) );
		}
	}

	/**
	 * Switch field ids that reference other fields for fields that were not already duplicated.
	 *
	 * Covers every setting in the FrmProField::field_id_settings() registry, each of which can point
	 * at a field that comes later in the field order and is therefore duplicated afterwards.
	 *
	 * @since 6.35 Fields inside a repeater are included, and every registered setting is switched
	 *            rather than only hide_field and watch_lookup.
	 *
	 * @param int $form_id the new duplicated form id.
	 */
	public static function maybe_fix_field_ids_after_duplicate( $form_id ) {
		global $frm_unprocessed_duplicate_field_keys;

		if ( ! $frm_unprocessed_duplicate_field_keys ) {
			return;
		}

		// A repeater's fields live in a child form, so match on the parent form id as well.
		$where  = array(
			'fi.field_key' => $frm_unprocessed_duplicate_field_keys,
			array(
				'or'                => 1,
				'fi.form_id'        => $form_id,
				'fr.parent_form_id' => $form_id,
			),
		);
		$fields = FrmField::getAll( $where, 'field_order' );
		$id_map = self::get_duplicated_field_id_map();

		foreach ( $fields as $field ) {
			$updated = false;

			foreach ( FrmProField::field_id_settings( $field->type ) as $setting => $shape ) {
				if ( self::switch_unprocessed_field_ids( $field->field_options, $setting, $shape, $id_map ) ) {
					$updated = true;
				}
			}

			if ( $updated ) {
				FrmField::update( $field->id, array( 'field_options' => $field->field_options ) );
			}
		}

		$frm_unprocessed_duplicate_field_keys = array();
	}

	/**
	 * Switch the field ids stored in a duplicated field option to their new ids.
	 *
	 * @since 6.35 Added the $shape and $id_map params so settings other than an array of ids are
	 *            switched too.
	 *
	 * @param array  $field_options Field options, passed by reference.
	 * @param string $setting       Option key holding one or more field ids, such as hide_field or watch_lookup.
	 * @param string $shape         Value shape, one of int, int[] or csv.
	 * @param array  $id_map        Map of original field ids to their new ids.
	 *
	 * @return bool Whether any id was switched.
	 */
	private static function switch_unprocessed_field_ids( &$field_options, $setting, $shape, $id_map ) {
		if ( empty( $field_options[ $setting ] ) ) {
			return false;
		}

		$changed   = false;
		$new_value = FrmProField::switch_ids_in_setting_value( $field_options[ $setting ], $shape, $id_map, $changed );

		if ( $changed ) {
			$field_options[ $setting ] = $new_value;
		}

		return $changed;
	}

	/**
	 * The map of original field ids to their new ids, with any id that is already a new id dropped.
	 *
	 * A stored id may already have been switched on the first pass. Without this an id that was
	 * switched to a value that happens to match another field's original id would be switched twice.
	 *
	 * @since 6.35
	 *
	 * @return array
	 */
	private static function get_duplicated_field_id_map() {
		global $frm_duplicate_ids;

		$id_map  = (array) $frm_duplicate_ids;
		$new_ids = array_flip( $id_map );

		foreach ( array_keys( $id_map ) as $old_id ) {
			if ( isset( $new_ids[ $old_id ] ) ) {
				unset( $id_map[ $old_id ] );
			}
		}

		return $id_map;
	}
}
