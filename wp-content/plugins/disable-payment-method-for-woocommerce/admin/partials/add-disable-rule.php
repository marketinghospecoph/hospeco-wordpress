<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="row border-bottom bg-dark2 align-items-center pi-primary-border-left">
    <div class="col-12 py-2">
        <strong class="h5 text-light"><?php echo esc_html(isset($_GET['action']) && $_GET['action'] === 'edit' ?  __('Edit rule', 'disable-payment-method-for-woocommerce') : __('Add new payment method rule', 'disable-payment-method-for-woocommerce')); ?></strong>
    </div>
</div>

<form method="post" id="pisol-dpmw-new-method">
    <div class="pi-step-container">
        <div class="pi-step-content">
            <div class="pi-step-header bg-primary text-light">
                <div>
                    <strong class="pi-step-title"><?php echo esc_html__('Step 1: Basic Settings', 'disable-payment-method-for-woocommerce'); ?><small>(Required)</small></strong>
                    <p><?php echo esc_html__('Basic setting of the rule', 'disable-payment-method-for-woocommerce'); ?></p>
                </div>
                <div>
                    <span class="dashicons dashicons-plus-alt2 mr-4"></span>
                    <span class="dashicons dashicons-minus mr-4"></span>
                </div>
            </div>
            <div class="pi-step-description">
                <!-- Basic start -->
                <div class="row py-4 border-bottom align-items-center">
                    <div class="col-12 col-sm-5">
                        <label for="pi_status" class="h6"><?php echo esc_html__('Status', 'disable-payment-method-for-woocommerce'); ?></label>
                    </div>
                    <div class="col-12 col-sm">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" value="1" <?php echo esc_attr($data['pi_status']); ?> class="custom-control-input" name="pi_status" id="pi_status">
                            <label class="custom-control-label" for="pi_status"></label>
                        </div>
                    </div>
                </div>

                <div class="row py-4 border-bottom align-items-center">
                    <div class="col-12 col-sm-5">
                        <label for="pi_status" class="h6"><?php echo esc_html__('Rule type', 'disable-payment-method-for-woocommerce'); ?></label>
                    </div>
                    <div class="col-12 col-sm">
                        <select name="pi_rule_type" id="pi_rule_type" class="form-control">
                            <option value="disable" <?php selected($data['pi_rule_type'], 'disable'); ?>><?php echo esc_html__('Disable payment method', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="fees" <?php selected($data['pi_rule_type'], 'fees'); ?>><?php echo esc_html__('Payment method fees', 'disable-payment-method-for-woocommerce'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="row py-4 border-bottom align-items-center">
                    <div class="col-12 col-sm-5">
                        <label for="pi_title" class="h6 pi-rule-type" data-type="disable"><?php esc_html_e('Name the disable payment method rule', 'disable-payment-method-for-woocommerce'); ?> <span class="text-primary">*</span></label>
                        <label for="pi_title" class="h6 pi-rule-type" data-type="fees"><?php esc_html_e('Name the payment method fees', 'disable-payment-method-for-woocommerce'); ?> <span class="text-primary">*</span></label>
                    </div>
                    <div class="col-12 col-sm">
                        <input type="text" required value="<?php echo esc_attr($data['pi_title']); ?>" class="form-control" name="pi_title" id="pi_title">
                    </div>
                </div>


                <div class="row py-4 border-bottom align-items-center">
                    <div class="col-12 col-sm-5">
                        <label for="disable_payment_methods" class="h6  pi-rule-type" data-type="disable"><?php echo esc_html__('Disable this payment methods', 'disable-payment-method-for-woocommerce'); ?> <span class="text-primary">*</span></label>
                        <label for="pi_title" class="h6 pi-rule-type" data-type="fees"><?php esc_html_e('Apply fees on this payment methods', 'disable-payment-method-for-woocommerce'); ?> <span class="text-primary">*</span></label>
                    </div>
                    <div class="col-12 col-sm">
                        <select required class="form-control" name="disable_payment_methods[]" id="disable_payment_methods" multiple="multiple">
                            <?php
                            foreach ($data['all_payment_methods'] as $payment_method => $payment_method_name) {
                                $selected = is_array($data['disable_payment_methods']) && in_array($payment_method, $data['disable_payment_methods']) ? ' selected ' : '';
                                echo '<option value="' . esc_attr($payment_method) . '" ' . esc_attr($selected) . '>' . esc_html($payment_method_name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="font-italic my-1 mb-0">If your desired payment method is not listed, please visit your checkout page so the plugin can record it, then refresh this page and your payment method will be in the list.</p>
                    </div>
                </div>

                <div class="row py-4 border-bottom align-items-center pi-rule-type" data-type="disable">
                    <div class="col-12 col-sm-5">
                        <label for="disable_payment_methods" class="h6  pi-rule-type" data-type="disable"><?php echo esc_html__('Warning notification', 'disable-payment-method-for-woocommerce'); ?></label>
                        <p class="font-italic"><?php echo esc_html__('This message will be shown to the customer when this rule is triggered and it has removed the payment method', 'disable-payment-method-for-woocommerce'); ?></p>
                        <p class="font-italic"><?php echo esc_html__('Note: This message won\'t show on the checkout page made using Blocks, as Block based checkout page does not support custom notifications as of now', 'disable-payment-method-for-woocommerce'); ?></p>
                    </div>
                    <div class="col-12 col-sm">
                        <textarea class="form-control" name="pi_payment_hiding_warning_message" id="pi_payment_hiding_warning_message"><?php echo esc_html($data['pi_payment_hiding_warning_message']); ?></textarea>
                    </div>
                </div>

                <div class="row py-3 border-bottom align-items-center pi-rule-type" data-type="fees">
                    <div class="col-12 col-sm-5">
                        <label for="pi_cost" class="h6"><?php esc_html_e('Extra Fees', 'disable-payment-method-for-woocommerce'); ?> <span class="text-primary">*</span></label>
                    </div>
                    <div class="col-4">
                        <select class="form-control" name="pi_fees_type" id="pi_fees_type">
                            <option value="fixed" <?php selected($data['pi_fees_type'], "fixed"); ?>><?php esc_html_e('Fixed fees', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="percentage" <?php selected($data['pi_fees_type'], "percentage"); ?> title="This include subtotal and subtotal tax"><?php esc_html_e('Cart subtotal percentage', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="subtotal_discount" <?php selected($data['pi_fees_type'], "subtotal_discount"); ?> title="This include subtotal, subtotal tax minus coupon discount"><?php esc_html_e('Cart (subtotal  - discount) percentage', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="subtotal_shipping" <?php selected($data['pi_fees_type'], "subtotal_shipping"); ?> title="This include subtotal, shipping total and shipping tax"><?php esc_html_e('Cart (subtotal  + shipping) percentage', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="subtotal_shipping_discount" <?php selected($data['pi_fees_type'], "subtotal_shipping_discount"); ?> title="This include subtotal, subtotal tax, shipping total, shipping tax minus coupon discount"><?php esc_html_e('Cart (subtotal + shipping - discount) percentage', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="shipping_percentage" <?php selected($data['pi_fees_type'], "shipping_percentage"); ?>><?php esc_html_e('Shipping amount percentage', 'disable-payment-method-for-woocommerce'); ?></option>
                        </select>
                    </div>
                    <div class="col-3 col-sm">
                        <input type="text" value="<?php echo esc_attr($data['pi_fees']); ?>" class="form-control" name="pi_fees" id="pi_fees">
                    </div>
                </div>

                <div class="row py-3 border-bottom align-items-center pi-rule-type" data-type="fees">
                    <div class="col-12 col-sm-5">
                        <label for="pi_fees_taxable" class="h6 pi-rule-type" data-type="fees"><?php esc_html_e('Fee Taxable and its tax class', 'disable-payment-method-for-woocommerce'); ?></label>
                    </div>
                    <div class="col-4">
                        <select class="form-control" name="pi_fees_taxable" id="pi_fees_taxable">
                            <option value="no" <?php selected($data['pi_fees_taxable'], "no"); ?>><?php esc_html_e('No', 'disable-payment-method-for-woocommerce'); ?></option>
                            <option value="yes" <?php selected($data['pi_fees_taxable'], "yes"); ?>><?php esc_html_e('Yes', 'disable-payment-method-for-woocommerce'); ?></option>
                        </select>
                    </div>
                    <div class="col-3 col-sm">
                        <select class="form-control" name="pi_fees_tax_class" id="pi_fees_tax_class">

                            <?php
                            echo '<option value="standard" ' . selected($data['pi_fees_tax_class'], 'standard', true) . ' >Standard</option>';
                            if (!empty($data['tax_classes']) && is_array($data['tax_classes'])) {
                                foreach ($data['tax_classes'] as $tax_class) {
                                    echo '<option value="' . esc_attr($tax_class->slug) . '" ' . selected($data['pi_fees_tax_class'], $tax_class->slug, true) . ' >' . esc_html($tax_class->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row py-4 border-bottom align-items-center">
                    <div class="col-12 col-sm-5">
                        <label for="pi_currency" class="h6"><?php esc_html_e('Apply for currency (useful for multi currency website only)', 'disable-payment-method-for-woocommerce'); ?></label><?php pisol_help::tooltip('Select the currency for which to apply the rule, if left blank it will apply for all the currency'); ?>
                    </div>
                    <div class="col-12 col-sm">
                        <select name="pi_currency[]" id="pi_currency" multiple="multiple">
                            <?php self::get_currency($data['pi_currency']); ?>
                        </select>
                    </div>
                </div>
                <!-- Basic end -->
            </div>
        </div>
    </div>
    <!-- Step 1  end -->

    <div class="pi-step-container">
        <div class="pi-step-content">
            <div class="pi-step-header bg-primary text-light">
                <div class="pi-rule-type" data-type="fees">
                    <strong class="pi-step-title"><?php echo esc_html__('Step 2: When to apply this payment method fee', 'disable-payment-method-for-woocommerce'); ?><small>(Required)</small></strong>
                    <p class="font-italic"><?php echo esc_html__('Condition that will decide when to apply this fee', 'disable-payment-method-for-woocommerce'); ?></p>
                </div>
                <div class="pi-rule-type" data-type="disable">
                    <strong class="pi-step-title"><?php echo esc_html__('Step 2: When to disable the payment method', 'disable-payment-method-for-woocommerce'); ?><small>(Required)</small></strong>
                    <p class="font-italic"><?php echo esc_html__('Condition that will decide when to disable the payment method', 'disable-payment-method-for-woocommerce'); ?></p>
                </div>
                <div>
                    <span class="dashicons dashicons-plus-alt2 mr-4"></span>
                    <span class="dashicons dashicons-minus mr-4"></span>
                </div>
            </div>
            <div class="pi-step-description">
                <!-- Conditions start -->
                <div class="border-top">
                    <?php
                    $selection_rule_obj = new Pi_dpmw_selection_rule_main(
                        __('Below conditions determine when to disable payment methods', 'disable-payment-method-for-woocommerce'),
                        $data['pi_metabox'],
                        $data
                    );
                    wp_nonce_field('add_disable_payment_method_rule', 'pisol_dpmw_nonce');
                    ?>
                </div>
                <!-- Conditions end -->
            </div>
        </div>
    </div>
    <!-- Step 2 end -->                         



    

    <input type="hidden" name="post_type" value="pi_dpmw_rules">
    <input type="hidden" name="post_id" value="<?php echo esc_attr($data['post_id']); ?>">
    <input type="hidden" name="action" value="pisol_dpmw_save_disable_rule">
    <input type="submit" value="Save Rule" name="submit" class="my-3 btn btn-primary btn-md" id="pi-dpmw-new-rule" style="margin-left:0;">
</form>