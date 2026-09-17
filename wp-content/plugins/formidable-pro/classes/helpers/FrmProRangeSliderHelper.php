<?php

/**
 * Range slider helper
 *
 * @since 6.35
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Range checks for a range slider's Default Value setting.
 *
 * A range slider has two handles, so its default is a Start Value and an End Value stored together
 * in the single default_value column. Neither handle can sit outside the slider's own Min Value and
 * Max Value settings, so a default that falls outside them has to be pulled back in before it is
 * stored.
 *
 * @since 6.35
 */
class FrmProRangeSliderHelper {

	/**
	 * Keep both halves of a range slider's Default Value setting inside its Min and Max Values.
	 *
	 * The form builder clears a half that is typed outside the range, but that is only a courtesy:
	 * the pair can still land out of range through an import, an API call, or by narrowing the Min
	 * Value and Max Value settings after the default was set. A handle can never sit outside the
	 * slider's own range, so store the nearest value it can actually reach instead of one the
	 * field will never render.
	 *
	 * @since 6.35
	 *
	 * @param array      $values   The field values about to be saved.
	 * @param int|string $field_id The field ID.
	 *
	 * @return array
	 */
	public static function clamp_default_value_to_range( $values, $field_id ) {
		if ( ! isset( $values['default_value'] ) || ! is_string( $values['default_value'] ) || '' === $values['default_value'] ) {
			return $values;
		}

		if ( ! self::is_saving_range_slider( $values, $field_id ) ) {
			// A single handle slider keeps the one Default Value box it has always had, which is
			// range checked elsewhere.
			return $values;
		}

		$limits = self::get_saved_range_limits( $values, $field_id );

		if ( $limits[1] <= $limits[0] ) {
			// A max at or below the min is rejected as a setting, so there is no range to clamp to.
			return $values;
		}

		$halves = FrmProFieldRange::split_default_value( $values['default_value'] );

		$values['default_value'] = FrmProFieldRange::join_default_value(
			self::clamp_default_value_half( $halves[0], $limits[0], $limits[1] ),
			self::clamp_default_value_half( $halves[1], $limits[0], $limits[1] )
		);

		return $values;
	}

	/**
	 * Pull one half of a Default Value setting inside the slider's range.
	 *
	 * @since 6.35
	 *
	 * @param string $value The Start Value or End Value setting.
	 * @param float  $min   The lowest value the slider can reach.
	 * @param float  $max   The highest value the slider can reach.
	 *
	 * @return string
	 */
	private static function clamp_default_value_half( $value, $min, $max ) {
		if ( ! is_numeric( $value ) ) {
			// An empty half is filled in from the slider's own range, and a shortcode such as [25]
			// only resolves at render time, so neither has a number to compare yet.
			return $value;
		}

		if ( (float) $value < $min ) {
			return (string) $min;
		}

		if ( (float) $value > $max ) {
			return (string) $max;
		}

		// In range, so give the setting back exactly as it was typed rather than reformatting it.
		return $value;
	}

	/**
	 * Whether the field being saved is a two handle range slider.
	 *
	 * @since 6.35
	 *
	 * @param array      $values   The field values about to be saved.
	 * @param int|string $field_id The field ID.
	 *
	 * @return bool
	 */
	private static function is_saving_range_slider( $values, $field_id ) {
		if ( isset( $values['field_options']['is_range_slider'] ) ) {
			return ! empty( $values['field_options']['is_range_slider'] );
		}

		$field = $field_id ? FrmField::getOne( $field_id ) : false;

		return $field && (bool) FrmField::get_option( $field, 'is_range_slider' );
	}

	/**
	 * Get the Min Value and Max Value settings the default value has to fit inside.
	 *
	 * The settings come from the values being saved when they are part of the update, since those
	 * are what the default will be rendered against. Only a partial update, such as one that
	 * touches the default value alone, has to read them back off the stored field.
	 *
	 * @since 6.35
	 *
	 * @param array      $values   The field values about to be saved.
	 * @param int|string $field_id The field ID.
	 *
	 * @return array The min at index 0 and the max at index 1, both as floats.
	 */
	private static function get_saved_range_limits( $values, $field_id ) {
		$options = isset( $values['field_options'] ) && is_array( $values['field_options'] ) ? $values['field_options'] : array();

		if ( ! isset( $options['minnum'] ) && ! isset( $options['maxnum'] ) ) {
			$field = $field_id ? FrmField::getOne( $field_id ) : false;

			if ( $field ) {
				$options = array(
					'minnum' => FrmField::get_option( $field, 'minnum' ),
					'maxnum' => FrmField::get_option( $field, 'maxnum' ),
				);
			}
		}

		return self::get_numeric_range_limits(
			$options['minnum'] ?? '',
			$options['maxnum'] ?? ''
		);
	}

	/**
	 * Fall an unset or non numeric Min Value or Max Value setting back to the field type default.
	 *
	 * @since 6.35
	 *
	 * @param mixed $min The Min Value setting.
	 * @param mixed $max The Max Value setting.
	 *
	 * @return array The min at index 0 and the max at index 1, both as floats.
	 */
	public static function get_numeric_range_limits( $min, $max ) {
		return array(
			is_numeric( $min ) ? (float) $min : (float) FrmProFieldRange::DEFAULT_MIN,
			is_numeric( $max ) ? (float) $max : (float) FrmProFieldRange::DEFAULT_MAX,
		);
	}
}
