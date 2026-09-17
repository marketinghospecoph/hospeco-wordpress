<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Outputs the Start Value and End Value settings for a range slider field.
 *
 * A range slider has two handles, so its default needs two numbers. Both are stored in the single
 * default_value column, joined with a comma, which is the format the front end has always read.
 *
 * Either half can hold a shortcode instead of a number, so both are text inputs with the smart
 * values picker beside them.
 *
 * @since 6.35
 *
 * @var array        $field      Field data including 'id' and 'field_key'.
 * @var FrmFieldType $field_obj  Field type handler.
 * @var array        $start_atts HTML attributes for the Start Value input.
 * @var array        $end_atts   HTML attributes for the End Value input.
 */
?>
<p class="frm_form_field frm-default-range" id="default-value-for-<?php echo esc_attr( $field['id'] ); ?>">
	<label for="frm_default_value_start_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_help frm-font-semibold frm-text-grey-600 frm-mb-xs" title="<?php esc_attr_e( 'Pre-fill both slider handles with these values. Users can modify them unless the field is read-only.', 'formidable-pro' ); ?>">
		<?php esc_html_e( 'Default Value', 'formidable' ); ?>
	</label>
	<span class="frm_grid_container">
		<span class="frm6">
			<label for="frm_default_value_start_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_form_field frm-block frm-mb-6 frm-text-grey-700">
				<?php esc_html_e( 'Start Value', 'formidable-pro' ); ?>
			</label>
			<span class="frm-flex-col frm-with-right-icon">
				<input<?php FrmAppHelper::array_to_html_params( $start_atts, true ); ?> />
				<?php $field_obj->display_smart_values_modal_trigger_icon( $field ); ?>
			</span>
		</span>
		<span class="frm6">
			<label for="frm_default_value_end_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_last frm_form_field frm-block frm-mb-6 frm-text-grey-700">
				<?php esc_html_e( 'End Value', 'formidable-pro' ); ?>
			</label>
			<span class="frm-flex-col frm-with-right-icon">
				<input<?php FrmAppHelper::array_to_html_params( $end_atts, true ); ?> />
				<?php $field_obj->display_smart_values_modal_trigger_icon( $field ); ?>
			</span>
		</span>
	</span>
</p>
