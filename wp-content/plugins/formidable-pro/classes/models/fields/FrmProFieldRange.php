<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmProFieldRange extends FrmFieldType {
	/**
	 * @since 6.23
	 *
	 * @var int
	 */
	const DEFAULT_MIN = 0;

	/**
	 * @since 6.23
	 *
	 * @var int
	 */
	const DEFAULT_MAX = 100;

	/**
	 * @since 6.23
	 *
	 * @var int
	 */
	const DEFAULT_STEP = 1;

	/**
	 * @since 6.23
	 *
	 * @var int
	 */
	const DEFAULT_MIN_GAP = 10;

	use FrmProFieldTypeTrait;

	/**
	 * @var string
	 *
	 * @since 3.0
	 */
	protected $type = 'range';

	/**
	 * @var bool
	 */
	protected $array_allowed = false;

	protected function field_settings_for_type() {
		$settings = array(
			'invalid' => true,
			'range'   => true,
			'prefix'  => true,
			'format'  => true,
		);

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * Returns true if the min/max values should be shown under the input.
	 *
	 * The min/max values are shown as long as the range field has a Before or After input value.
	 * This is shown regardless of the show_slider_range option for backward compatibility.
	 *
	 * @since 6.20 A new show_slider_range option was added. If this is on, the range can be shown as well.
	 *
	 * @return bool
	 */
	private function get_slider_checked_value() {
		$has_unit          = FrmField::get_option( $this->field, 'prepend' ) || FrmField::get_option( $this->field, 'append' );
		$show_slider_range = FrmField::get_option( $this->field, 'show_slider_range' );
		return '' === $show_slider_range ? $has_unit : $show_slider_range;
	}

	/**
	 * @since 5.4.3
	 *
	 * @param array $args - Includes 'field', 'display', and 'values'
	 *
	 * @return void
	 */
	public function show_primary_options( $args ) {
		$field = $args['field'];

		if ( ! empty( $field['is_range_slider'] ) ) {
			include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/gap-range.php';
		}

		$show_range_checked = $this->get_slider_checked_value();
		$value_position     = $this->get_value_position();
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/slider-options.php';
	}

	/**
	 * Returns the position of the slider value.
	 *
	 * For backward compatibility, if the field does not have before/after input values, the value will be shown
	 * at the bottom center. It would otherwise be shown at the bottom left.
	 *
	 * @since 6.20
	 *
	 * @return string
	 */
	private function get_value_position() {
		$value_position = FrmField::get_option( $this->field, 'value_position' );

		if ( '' === $value_position ) {
			$has_unit = FrmField::get_option( $this->field, 'prepend' ) || FrmField::get_option( $this->field, 'append' );
			return $has_unit ? 'top-left' : 'bottom-center';
		}

		return $value_position;
	}

	protected function builder_text_field( $name = '' ) {
		if ( is_object( $this->field ) ) {
			$min = FrmField::get_option( $this->field, 'minnum' );
			$max = FrmField::get_option( $this->field, 'maxnum' );
		} else {
			$min = 0;
			$max = 100;
		}

		$default_value        = $this->get_default_value( $min, $max );
		$show_value_at_bottom = str_contains( $this->get_value_position(), 'bottom' );
		$output               = '<div>' . $this->output_selected_value( $default_value, true ) . '</div>';
		$is_range_slider      = FrmField::get_option( $this->field, 'is_range_slider' );

		if ( ! $is_range_slider ) {
			$output = $this->add_classes_to_displayed_value( $output );
		}

		$type  = $is_range_slider ? 'hidden' : 'range';
		$input = '<div class="frm_range_container">';

		if ( ! $show_value_at_bottom ) {
			$input .= $output;
		}

		$input_html = '';
		$this->add_min_max( array(), $input_html );
		$input .= '<input type="' . $type . '" name="' . esc_attr( $this->html_name( $name ) ) . '" value="' . esc_attr( $default_value ) . '" ' . $input_html . ' ';

		if ( 'hidden' === $type ) {
			$input .= FrmAppHelper::array_to_html_params( $this->get_gap_data_atts() ) . ' />';
		} else {
			$input .= 'min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '"';
			$input .= $this->get_style_for_default_value( $default_value, $min, $max ) . ' />';
		}

		if ( ! $is_range_slider ) {
			$input .= $this->output_min_max_value();
		}

		if ( $show_value_at_bottom ) {
			$input .= $output;
		}

		return $input . '</div>';
	}

	/**
	 * @since 6.23
	 *
	 * @return array
	 */
	private function get_gap_data_atts() {
		$min_gap = FrmField::get_option( $this->field, 'mingap' );
		$max_gap = FrmField::get_option( $this->field, 'maxgap' );
		$min_gap = $min_gap ? $min_gap : self::DEFAULT_MIN_GAP;

		$min  = FrmField::get_option( $this->field, 'minnum' );
		$max  = FrmField::get_option( $this->field, 'maxnum' );
		$min  = $min ? $min : self::DEFAULT_MIN;
		$max  = $max ? $max : self::DEFAULT_MAX;
		$span = $max - $min;

		if ( ! $max_gap ) {
			$max_gap = $span;
		}

		// Gaps wider than the min/max span can be saved (the builder only
		// warns), but the front-end slider rejects them. Clamp for display.
		$min_gap = min( $min_gap, $span );
		$max_gap = min( max( $max_gap, $min_gap ), $span );

		return array(
			'data-min-gap' => $min_gap,
			'data-max-gap' => $max_gap,
		);
	}

	/**
	 * Reset the default value if it's out of range
	 *
	 * @since 3.0.06
	 *
	 * @param mixed $min
	 * @param mixed $max
	 */
	private function get_default_value( $min, $max ) {
		$default_value = $this->get_field_column( 'default_value' );

		if ( FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			return self::get_range_slider_default_value( $default_value, $min, $max );
		}

		$out_of_range = $default_value < $min || $default_value > $max;

		if ( $default_value !== '' && $out_of_range ) {
			return '';
		}

		return $default_value;
	}

	/**
	 * Reset a range slider default value if either half is out of range.
	 *
	 * A range slider stores both handles in one column, so the pair has to be checked a half at a
	 * time. Comparing the whole "20,40" string against the min and max compares it as a string and
	 * reports a perfectly valid default as out of range.
	 *
	 * @since 6.35
	 *
	 * @param mixed $default_value The stored default value.
	 * @param mixed $min           The Min Value setting.
	 * @param mixed $max           The Max Value setting.
	 *
	 * @return string
	 */
	private static function get_range_slider_default_value( $default_value, $min, $max ) {
		if ( ! is_string( $default_value ) || '' === $default_value ) {
			return '';
		}

		$limits = FrmProRangeSliderHelper::get_numeric_range_limits( $min, $max );
		$min    = $limits[0];
		$max    = $limits[1];

		foreach ( self::split_default_value( $default_value ) as $value ) {
			if ( ! is_numeric( $value ) ) {
				// A shortcode such as [25] resolves at render time, so there is nothing to range
				// check here. Keep it rather than throwing the whole default away.
				continue;
			}

			if ( (float) $value < $min || (float) $value > $max ) {
				return '';
			}
		}

		return $default_value;
	}

	/**
	 * Check whether either half of a default value holds something other than a number.
	 *
	 * A shortcode only resolves at render time, so until it does the form builder has no position
	 * to place a handle at. An empty half is not a shortcode: the slider fills that side in from
	 * its own range.
	 *
	 * @since 6.35
	 *
	 * @param mixed $default_value The default value, already shortcode processed on the front end.
	 *
	 * @return bool
	 */
	private static function default_value_has_shortcode( $default_value ) {
		if ( ! is_string( $default_value ) || '' === $default_value ) {
			return false;
		}

		foreach ( self::split_default_value( $default_value ) as $value ) {
			if ( '' !== $value && ! is_numeric( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Split a stored range slider default value into its start and end halves.
	 *
	 * Both handles share the default_value column, joined with a comma. The split stops after the
	 * first comma so that a value carrying one of its own never loses its tail.
	 *
	 * @since 6.35
	 *
	 * @param mixed $default_value The stored default value.
	 *
	 * @return array The start value at index 0 and the end value at index 1.
	 */
	public static function split_default_value( $default_value ) {
		if ( ! is_string( $default_value ) || '' === $default_value ) {
			return array( '', '' );
		}

		$values = explode( ',', $default_value, 2 );

		return array( trim( $values[0] ), isset( $values[1] ) ? trim( $values[1] ) : '' );
	}

	/**
	 * Join a Start Value and an End Value back into the single stored default value.
	 *
	 * An empty End Value stores the start on its own rather than leaving a trailing comma. Defaults
	 * written before this setting was split are single shortcodes with no comma, so re-saving one
	 * has to give the column back exactly what it held. Anything the shortcode resolves to that
	 * carries its own comma is separated later, once it has a value.
	 *
	 * @since 6.35
	 *
	 * @param string|null $start The Start Value setting. Null when only the other half was posted.
	 * @param string|null $end   The End Value setting. Null when only the other half was posted.
	 *
	 * @return string
	 */
	public static function join_default_value( $start, $end ) {
		$start = is_string( $start ) ? trim( $start ) : '';
		$end   = is_string( $end ) ? trim( $end ) : '';

		return '' === $end ? $start : $start . ',' . $end;
	}

	/**
	 * Join the posted Start Value and End Value into the single default_value column.
	 *
	 * The pair is also range checked here, so a default outside the slider's Min Value and Max
	 * Value settings cannot be stored.
	 *
	 * @since 6.35
	 *
	 * @param array $values   The field values about to be saved.
	 * @param int   $field_id The field ID.
	 *
	 * @return array
	 */
	public static function clean_field_options_before_update( $values, $field_id ) {
		// Pass an empty sanitize callback so that an absent setting stays null. get_simple_request
		// sanitizes the fallback as well, and sanitize_text_field turns null into an empty string,
		// which would read as a deliberately cleared value.
		$start = FrmAppHelper::get_post_param( 'default_value_start_' . $field_id, null, '' );
		$end   = FrmAppHelper::get_post_param( 'default_value_end_' . $field_id, null, '' );

		if ( null !== $start || null !== $end ) {
			FrmAppHelper::sanitize_value( 'sanitize_text_field', $start );
			FrmAppHelper::sanitize_value( 'sanitize_text_field', $end );

			$values['default_value'] = self::join_default_value( $start, $end );
		}

		// The split settings are absent for an import or an API call, so the default value still
		// has to be range checked here rather than only on the way in from the form builder.
		return FrmProRangeSliderHelper::clamp_default_value_to_range( $values, $field_id );
	}

	/**
	 * Show the Default Value setting as separate Start Value and End Value inputs for a range slider.
	 *
	 * A single handle slider keeps the one Default Value box it has always had.
	 *
	 * @since 6.35
	 *
	 * @param array  $field               Field data including 'id', 'default_value', 'dyn_default_value'.
	 * @param object $field_obj           Field type handler.
	 * @param array  $default_value_types Default value types available for this field.
	 * @param array  $display             Display options; may include 'default_value'.
	 *
	 * @return void
	 */
	public function show_default_value_setting( $field, $field_obj, $default_value_types, $display ) {
		if ( empty( $field['is_range_slider'] ) || ! empty( $display['default_value'] ) ) {
			parent::show_default_value_setting( $field, $field_obj, $default_value_types, $display );
			return;
		}

		$values = self::split_default_value( $field['default_value'] );

		// Text inputs rather than number inputs, so a default can be a shortcode such as [25] or
		// [get param=low] instead of a literal number.
		$start_atts = array(
			'type'  => 'text',
			'id'    => 'frm_default_value_start_' . $field['field_key'],
			'name'  => 'default_value_start_' . $field['id'],
			'value' => $values[0],
		);
		$end_atts   = array(
			'type'  => 'text',
			'id'    => 'frm_default_value_end_' . $field['field_key'],
			'name'  => 'default_value_end_' . $field['id'],
			'value' => $values[1],
		);

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/default-range-value.php';
	}

	protected function extra_field_opts() {
		return array(
			'minnum'            => self::DEFAULT_MIN,
			'maxnum'            => self::DEFAULT_MAX,
			'step'              => self::DEFAULT_STEP,
			'show_slider_range' => '',
			'value_position'    => '',
			'mingap'            => self::DEFAULT_MIN_GAP,
			'maxgap'            => self::DEFAULT_MAX,
		);
	}

	/**
	 * @since 6.20
	 *
	 * {@inheritdoc}
	 */
	protected function add_min_max( $args, &$input_html ) {
		$this->add_formatted_min_max( $args, $input_html );
	}

	public function front_field_input( $args, $shortcode_atts ) {
		$input_html = $this->get_field_input_html_hook( $this->field );
		$this->add_aria_description( $args, $input_html );
		$this->add_min_max( $args, $input_html );

		$default = $this->get_field_column( 'default_value' );
		$field   = is_object( $this->field ) ? $this->field : FrmField::getOne( $this->field_id );
		$default = apply_filters( 'frm_get_default_value', $default, $field, true );

		$output = $this->output_selected_value( $default );
		$output = apply_filters( 'frm_range_output', $output, array( 'field' => $this->field ) );
		$input  = '<div class="frm_range_container">';

		$show_value_at_bottom = str_contains( $this->get_value_position(), 'bottom' );
		$output               = '<div>' . $output . '</div>';

		if ( ! FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			$output = $this->add_classes_to_displayed_value( $output );
		}

		if ( ! $show_value_at_bottom ) {
			$input .= $output;
		}

		$this->adjust_value_if_field_is_hidden( $field );

		$type   = FrmField::get_option( $field, 'is_range_slider' ) ? 'hidden' : 'range';
		$frmval = '' === $this->field['default_value'] ? 'data-frmval=""' : '';
		$input .= '<input ' . $input_html;
		$input .= FrmAppHelper::array_to_html_params(
			array(
				'type'  => $type,
				'id'    => $args['html_id'],
				'name'  => $args['field_name'],
				'value' => $this->get_unformatted_value( $this->field['value'] ),
			)
		);

		if ( 'hidden' === $type ) {
			$input .= FrmAppHelper::array_to_html_params( $this->get_gap_data_atts() ) . ' />';
		} else {
			$input .= $frmval . ' data-frmrange />';
		}

		if ( ! FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			$input .= $this->output_min_max_value();
		}

		if ( $show_value_at_bottom ) {
			$input .= $output;
		}

		return $input . '</div>';
	}

	/**
	 * Returns inline CSS for showing the default value, particularly helpful when JS is disabled.
	 *
	 * @since 6.35
	 *
	 * @param int|string $default_value
	 * @param false|int  $min
	 * @param false|int  $max
	 *
	 * @return string
	 */
	private function get_style_for_default_value( $default_value, $min = false, $max = false ) {
		// A default of 0 is a real value, so test for a filled in setting rather than truthiness.
		if ( ! is_numeric( $default_value ) ) {
			return '';
		}

		$is_field_object = is_object( $this->field );

		if ( false === $min ) {
			$min = $is_field_object ? FrmField::get_option( $this->field, 'minnum' ) : self::DEFAULT_MIN;
		}

		if ( false === $max ) {
			$max = $is_field_object ? FrmField::get_option( $this->field, 'maxnum' ) : self::DEFAULT_MAX;
		}

		$min = is_numeric( $min ) ? (float) $min : (float) self::DEFAULT_MIN;
		$max = is_numeric( $max ) ? (float) $max : (float) self::DEFAULT_MAX;

		if ( $min === $max ) {
			return '';
		}

		$progress_percent = ( (float) $default_value - $min ) / ( $max - $min ) * 100;
		$progress_percent = min( 100, max( 0, $progress_percent ) );
		return 'style="background: linear-gradient(to right, var(--slider-color) 0%, var(--slider-color) ' . esc_attr( $progress_percent ) . '%, var(--slider-bar-color) ' . esc_attr( $progress_percent ) . '% 100%)"';
	}

	/**
	 * Returns the unformatted value.
	 *
	 * @since 6.29
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function get_unformatted_value( $value ) {
		if ( FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $this->field, 'format' ) ) ) {
			return FrmProCurrencyHelper::normalize_formatted_numbers( $this->field, $value );
		}

		return $value;
	}

	/**
	 * @since 6.20
	 *
	 * @param string $output
	 *
	 * @return string
	 */
	private function add_classes_to_displayed_value( $output ) {
		$value_position = $this->get_value_position();
		$class          = 'frm-text-';
		$class         .= $value_position ? str_replace( array( 'top-', 'bottom-' ), '', $value_position ) : 'left';

		if ( FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			$class .= ' range-value';
		}

		return str_replace( '<div', '<div class="' . esc_attr( $class ) . '"', $output );
	}

	/**
	 * If a slider is conditional, the calculated value should be 0.
	 * When the field is conditionally shown its default value will be restored.
	 *
	 * @param object $field
	 */
	private function adjust_value_if_field_is_hidden( $field ) {
		// phpcs:ignore
		if ( empty( $_POST ) ) {
			return;
		}
		// phpcs:ignore
		$values = wp_unslash( $_POST );

		if ( ! FrmProFieldsHelper::is_field_hidden( $field, $values ) ) {
			return;
		}

		$this->field['value']             = 0;
		$_POST['item_meta'][ $field->id ] = 0;
	}

	private function format_min_max_value( $value ) {
		$is_currency = ! empty( $this->field->field_options['is_currency'] )
			|| isset( $this->field->field_options['format'] ) && FrmProCurrencyHelper::is_currency_format( $this->field->field_options['format'] );

		if ( $is_currency ) {
			return FrmProCurrencyHelper::maybe_format_currency( $value, $this->field, array() );
		}

		return $value;
	}

	/**
	 * @since 6.23
	 *
	 * @param string $default The default value, used when the field has no value of its own.
	 *
	 * @return string
	 */
	private function get_range_slider_html( $default = '' ) {
		$min_value = FrmField::get_option( $this->field, 'minnum' );
		$min_value = floatval( $min_value ? $min_value : self::DEFAULT_MIN );
		$max_value = FrmField::get_option( $this->field, 'maxnum' );
		$max_value = floatval( $max_value ? $max_value : self::DEFAULT_MAX );
		$max_gap   = FrmField::get_option( $this->field, 'maxgap' );
		$max_gap   = floatval( $max_gap ? $max_gap : $max_value - $min_value );
		// A saved gap wider than the min/max span would render the handle past the track.
		$max_gap = min( $max_gap, $max_value - $min_value );

		// Position the handles from the current value (e.g. when editing an
		// entry) so the slider is correct on load, before the JS syncs it.
		// This mirrors the initialization math in initializeRangeSlider().
		$value = FrmField::get_option( $this->field, 'value' );

		if ( '' === $value || false === $value || null === $value || array() === $value ) {
			// The form builder has no entry value, so fall back to the Start Value and End Value
			// settings. Without this the preview always sits at the full min/max span. A default
			// still holding a shortcode has no position until it resolves, so ignore it here and
			// let the slider's own range decide where the handles sit. By the time the front end
			// gets here the shortcode has been processed, so anything it produced is used, and a
			// comma it brought with it separates the two halves below.
			$value = self::default_value_has_shortcode( $default ) ? '' : $default;
		}

		$values = is_array( $value ) ? array_values( $value ) : explode( ',', (string) $value );
		$low    = isset( $values[0] ) && is_numeric( $values[0] ) ? floatval( $values[0] ) : $min_value;
		$high   = isset( $values[1] ) && is_numeric( $values[1] ) ? floatval( $values[1] ) : $max_value;
		$low    = max( $low, $min_value );
		$high   = min( $high, $low + $max_gap, $max_value );

		// An end value below the start value is not a supported range. Collapse the handles rather
		// than swapping them, so the preview reads as no range at all instead of a valid looking
		// one, and the filled section is never given a negative width.
		$high = max( $high, $low );

		$span    = $max_value - $min_value;
		$min_pos = ( $low - $min_value ) / $span * 100;
		$max_pos = ( $high - $min_value ) / $span * 100;

		$value_html = $this->get_range_slider_value_html( $this->format_min_max_value( $low ), $this->format_min_max_value( $high ) );

		$value_html  = $this->add_classes_to_displayed_value( $value_html );
		$slider_html = '
		<div class="frm-slider-wrapper">
			<div class="frm-slider-track"></div>
			<div class="frm-slider-range" style="left: ' . esc_attr( $min_pos ) . '%; width: ' . esc_attr( $max_pos - $min_pos ) . '%;"></div>
			<div class="frm-slider-handle min-handle" style="left: ' . esc_attr( $min_pos ) . '%;"></div>
			<div class="frm-slider-handle max-handle" style="right:' . esc_attr( 100 - $max_pos ) . '%;"></div>
		</div>
		';

		$slider_html         .= $this->output_min_max_value();
		$show_value_at_bottom = str_contains( $this->get_value_position(), 'bottom' );

		if ( $show_value_at_bottom ) {
			return $slider_html . $value_html;
		}

		return $value_html . $slider_html;
	}

	/**
	 * @since 6.23
	 *
	 * @param float $min_value
	 * @param float $max_value
	 *
	 * @return string
	 */
	private function get_range_slider_value_html( $min_value, $max_value ) {
		$pre  = $this->format_unit( 'prepend', false );
		$unit = $this->format_unit( 'append', false );

		$html  = '<div>';
		$html .= wp_kses_post( $pre );
		$html .= '<span class="min-value">' . esc_html( $min_value ) . '</span>';
		$html .= ' ';
		$html .= '<span>-</span>';
		$html .= ' ';
		$html .= wp_kses_post( $pre );
		$html .= '<span class="max-value">' . esc_html( $max_value ) . '</span>';
		$html .= wp_kses_post( $unit );

		return $html . '</div>';
	}

	/**
	 * @since 4.03.05
	 *
	 * @param mixed $default
	 * @param bool  $is_builder
	 *
	 * @return string
	 */
	private function output_selected_value( $default, $is_builder = false ) {
		if ( FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			return $this->get_range_slider_html( $default );
		}

		$value = FrmField::get_option( $this->field, 'value' );

		$starting_value = '' === $value || false === $value ? $default : $value;
		$starting_value = $this->get_mid_value( $starting_value );

		$is_currency = ! empty( $this->field->field_options['is_currency'] )
			|| isset( $this->field->field_options['format'] ) && FrmProCurrencyHelper::is_currency_format( $this->field->field_options['format'] );

		if ( $is_currency ) {
			$starting_value = FrmProCurrencyHelper::maybe_format_currency( $starting_value, $this->field, array() );
		}

		$num  = '<span class="frm_range_value">' . esc_html( $starting_value ) . '</span>';
		$pre  = $this->format_unit( 'prepend', $is_builder );
		$unit = $this->format_unit( 'append', $is_builder );

		return $pre . $num . $unit;
	}

	/**
	 * @since 6.23
	 *
	 * @param array|string $value
	 * @param array        $atts
	 *
	 * @return array|string
	 */
	protected function prepare_display_value( $value, $atts ) {
		if ( FrmField::get_option( $this->field, 'is_range_slider' ) ) {
			return $this->prepare_range_slider_value( $value, $atts );
		}

		return $value;
	}

	/**
	 * Make sure that a range slider values appear like a range instead of a CSV by default.
	 * And support show="min" and show="max" shortcode options.
	 *
	 * @since 6.23
	 *
	 * @param array|string $value
	 * @param array        $atts
	 *
	 * @return array|string
	 */
	private function prepare_range_slider_value( $value, $atts ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$value = str_replace( ',', ' - ', $value );

		// Support show="min" and show="max" options.
		if ( empty( $atts['show'] ) ) {
			return $value;
		}

		$split              = explode( ' - ', $value );
		$in_expected_format = 2 === count( $split );
		switch ( $atts['show'] ) {
			case 'min':
				$value = $in_expected_format ? $split[0] : '';
				break;
			case 'max':
				$value = $in_expected_format ? $split[1] : '';
				break;
		}

		return $value;
	}

	/**
	 * Get the middle value so the label isn't alone.
	 *
	 * @since 4.06
	 *
	 * @param mixed $value
	 */
	private function get_mid_value( $value ) {
		if ( $value !== '' && $value !== false ) {
			return $value;
		}

		$defaults = $this->extra_field_opts();
		$min      = $this->get_unformatted_value( FrmField::get_option( $this->field, 'minnum' ) );
		$max      = $this->get_unformatted_value( FrmField::get_option( $this->field, 'maxnum' ) );

		if ( ! is_numeric( $min ) ) {
			$min = $defaults['minnum'];
		}

		if ( ! is_numeric( $max ) ) {
			$max = $defaults['maxnum'];
		}

		$mid  = ( $max - $min ) / 2 + $min;
		$step = FrmField::get_option( $this->field, 'step' );

		if ( ! $step || ! is_numeric( $step ) ) {
			// Avoid division by zero or division by non-numeric string.
			$step = $defaults['step'];
		}

		$mid_steps = round( $mid / $step ) * $step; // Get the minimum valid value for the step.
		return is_int( $mid_steps ) ? $mid_steps : round( $mid / $step ) * $step;
	}

	/**
	 * Ranges will show the min and max values under the input when a "Before Input" or "After Input" value is set.
	 *
	 * @since 4.05
	 *
	 * @param bool $is_builder
	 *
	 * @return string
	 */
	private function output_min_max_value( $is_builder = false ) {
		$pre                 = $this->format_unit( 'prepend', $is_builder );
		$unit                = $this->format_unit( 'append', $is_builder );
		$show_slider_setting = $this->get_slider_checked_value();

		if ( ! $show_slider_setting ) {
			return '';
		}

		$min = FrmField::get_option( $this->field, 'minnum' );
		$max = FrmField::get_option( $this->field, 'maxnum' );

		if ( FrmField::get_option( $this->field, 'is_currency' ) || FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $this->field, 'format' ) ) ) {
			$min = FrmProCurrencyHelper::maybe_format_currency( $min, $this->field, array() );
			$max = FrmProCurrencyHelper::maybe_format_currency( $max, $this->field, array() );
		}

		$min     = $pre . esc_html( $min ) . $unit;
		$max     = $pre . esc_html( $max ) . $unit;
		$output  = '<div class="frm_description">';
		$output .= '<span class="frm_range_min">' . $min . '</span>';
		$output .= '<span class="frm_range_max">' . $max . '</span>';

		return $output . '</div>';
	}

	/**
	 * @since 4.05
	 *
	 * @param string $setting
	 * @param bool   $is_builder
	 *
	 * @return string
	 */
	private function format_unit( $setting, $is_builder = false ) {
		$unit = FrmField::get_option( $this->field, $setting );

		if ( $unit ) {
			return '<span class="frm_range_unit"' . ( $is_builder ? ' id="range_unit_' . esc_attr( $this->get_field_column( 'id' ) ) . '"' : '' ) . '>' . esc_html( $unit ) . '</span>';
		}

		return '';
	}

	/**
	 * @since 4.0.04
	 *
	 * @param mixed $value
	 */
	public function sanitize_value( &$value ) {
		FrmAppHelper::sanitize_value( 'sanitize_text_field', $value );
		$value = $this->get_unformatted_value( $value );
	}
}
