<?php

$product = wc_get_product();
$gateway_options = get_option('woocommerce_billease_settings');
$is_pay_in_four = $gateway_options['installments_widget_is_pay_in_four'];
$tenor = $gateway_options['installments_widget_tenor'];
$dp_pct = $gateway_options['installments_widget_dp_pct'];
$is_zero_interest = $gateway_options['installments_widget_is_zero_interest'];
$marketing_link = $gateway_options['installments_widget_marketing_link'];
$div_id = 'billease_installments_widget_product_page_' . $product->get_id();

?>
    <div id="<?php echo $div_id; ?>" style="margin: 10px 0"></div>
    <script>
        function start() {
            const div_id = "<?php echo $div_id; ?>";
            const marketing_link = "<?php echo $marketing_link; ?>";
            const is_pay_in_four = "<?php echo $is_pay_in_four; ?>";
            const installments = "<?php echo intval($tenor); ?>";
            const dp_pct = "<?php echo intval($dp_pct); ?>";
            const is_zero_interest = "<?php echo $is_zero_interest; ?>";
            const price = <?php echo $product->get_price(); ?>;
            const billease_logo = `<img src="https://s3-ap-southeast-1.amazonaws.com/static.billease.ph/public/billease-logo.svg" width="70" style="display: inline; vertical-align: baseline"/>`;
            const storage_key = 'BILLEASE_INSTALLMENT_FACTOR';
            const learn_more = `<a target="_blank" href="${marketing_link}" style="text-decoration: underline; color: #203AA9">Learn More</a>`;
            function round_up(val) {
                return val % 100 === 0 ? val : (val + 100) - (val % 100);
            }
            function insert_billease_text(installment_factor = null) {
                const cashout = is_pay_in_four === 'yes' ? 0 : (dp_pct ? round_up(price * (dp_pct / 100)) : 0);
                const financed_amount = price - cashout;
                const amount = Math.ceil(is_pay_in_four === 'yes' ? (price / 4) : (installment_factor ? (installment_factor[installments] * financed_amount) : (financed_amount / installments)));
                const billease_text = document.getElementById(div_id);
                let installment_text;
                if (is_pay_in_four === 'yes') {
                    installment_text = 'Or <strong>4 payments</strong>';
                } else if (cashout) {
                    installment_text = `Or pay <strong>₱${cashout.toLocaleString()} upfront</strong>, the rest in <strong>${installments} months</strong>`;
                } else {
                    installment_text = `Or <strong>${installments} months</strong>`;
                }
                billease_text.innerHTML = `<span style="vertical-align: middle">${installment_text} for only <strong>₱${amount.toLocaleString()}</strong> with ${billease_logo}. ${learn_more}.</span>`;
            }
            if (is_pay_in_four === 'yes' || is_zero_interest === 'yes') {
                insert_billease_text();
            } else {
                const billease_installment_factor = localStorage.getItem(storage_key);
                if (billease_installment_factor) {
                    insert_billease_text(JSON.parse(billease_installment_factor));
                } else {
                    fetch('https://billease.ph/billease_installments.json')
                        .then(response => response.json())
                        .then(data => {
                            localStorage.setItem(storage_key, JSON.stringify(data.installment_factor));
                            insert_billease_text(data.installment_factor);
                        });
                }
            }
        }
        start();
    </script>
<?php
