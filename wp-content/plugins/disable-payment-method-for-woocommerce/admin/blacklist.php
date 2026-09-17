<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class pisol_dpmw_blacklist_settings {

    public $plugin_name;

    private $settings = array();

    private $active_tab;

    private $this_tab = 'blacklist';

    private $tab_name = "Blacklist";

    private $setting_key = 'dpmw_blacklist_setting';

    public $tab;

    function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;


        $this->settings = array();

        $this->tab = sanitize_text_field(filter_input(INPUT_GET, 'tab'));
        $this->active_tab = $this->tab != "" ? $this->tab : 'default';

        if ($this->this_tab == $this->active_tab) {
            add_action($this->plugin_name . '_tab_content', array($this, 'tab_content'));
        }


        add_action($this->plugin_name . '_tab', array($this, 'tab'), 20);

        add_action('admin_post_piws_blacklist_add', [$this, 'piws_handle_blacklist_add']);

        add_action('admin_post_piws_blacklist_delete', [$this, 'piws_handle_blacklist_delete']);


        $this->register_settings();
    }


    function delete_settings() {
        foreach ($this->settings as $setting) {
            delete_option($setting['field']);
        }
    }

    function register_settings() {

        foreach ($this->settings as $setting) {
            register_setting($this->setting_key, $setting['field']);
        }
    }

    function tab() {
        $page = sanitize_text_field(filter_input(INPUT_GET, 'page'));
?>
        <a class=" px-3 py-2 text-light d-flex align-items-center  border-left border-right  <?php echo ($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $page . '&tab=' . $this->this_tab)); ?>">
            <span class="dashicons dashicons-shield"></span> <?php echo esc_html($this->tab_name); ?>
        </a>
<?php
    }

    function tab_content() {
        include_once plugin_dir_path(__FILE__) . 'partials/blacklist.php';
    }

    function piws_handle_blacklist_add() {
        if (
            !isset($_POST['blacklist_value'], $_POST['blacklist_type']) ||
            !wp_verify_nonce($_POST['piws_blacklist_nonce'], 'piws_blacklist_add_entry')
        ) {
            wp_die(esc_html__('Invalid request', 'disable-payment-method-for-woocommerce'));
        }

        $value = sanitize_text_field($_POST['blacklist_value']);
        $note  = sanitize_text_field($_POST['blacklist_note'] ?? '');
        $type  = sanitize_text_field($_POST['blacklist_type']);

        $valid = false;

        $message = '';

        switch ($type) {
            case 'email':
                $valid = is_email($value);
                if (!$valid) {
                    $message = __('Invalid email address.', 'disable-payment-method-for-woocommerce');
                }
                break;

            case 'ip':
                $valid = filter_var($value, FILTER_VALIDATE_IP);
                if (!$valid) {
                    $message = __('Invalid IP address.', 'disable-payment-method-for-woocommerce');
                }
                break;

            case 'phone':
                // Allow only digits, +, -, and spaces. Customize as needed.
                $valid = preg_match('/^\+?[0-9\s\-]{6,20}$/', $value);
                if (!$valid) {
                    $message = __('Invalid phone number format.', 'disable-payment-method-for-woocommerce');
                }
                break;
        }

        if (!$valid) {
            $redirect_url = add_query_arg([
                'page'       => 'pisol-dpmw-settings',
                'tab'        => 'blacklist',
                'list_type'  => $type,
                'status'     => 'warning',
                'message'    => urlencode($message),
            ], admin_url('admin.php'));

            wp_redirect($redirect_url);
            exit;
        }

        if (!Pi_dpmw_Blocklist_DB::is_blocked($type, $value)) {
            Pi_dpmw_Blocklist_DB::add_row($type, $value, $note);
            $status = 'success';
            $message = __('Entry added successfully.', 'disable-payment-method-for-woocommerce');
        } else {
            $status = 'warning';
            $message = __('Entry already exists.', 'disable-payment-method-for-woocommerce');
        }

        $redirect_url = add_query_arg([
            'page'       => 'pisol-dpmw-settings',
            'tab'        => 'blacklist',
            'list_type'  => $type,
            'status'     => $status,
            'message'    => urlencode($message),
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }


    function piws_handle_blacklist_delete() {
        if (
            !isset($_POST['id'], $_POST['type'], $_POST['piws_blacklist_delete_nonce']) ||
            !wp_verify_nonce($_POST['piws_blacklist_delete_nonce'], 'piws_blacklist_delete_entry_' . $_POST['id'])
        ) {
            wp_die(esc_html__('Invalid request.', 'disable-payment-method-for-woocommerce'));
        }

        $id   = absint($_POST['id']);
        $type = sanitize_text_field($_POST['type']);

        // Delete from DB
        Pi_dpmw_Blocklist_DB::delete_row($id);

        // Redirect back with success notice
        $redirect_url = add_query_arg([
            'page'       => 'pisol-dpmw-settings',
            'tab'       => 'blacklist',
            'list_type' => $type,
            'deleted'   => '1',
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }
}

add_action('init', function () {
    new pisol_dpmw_blacklist_settings($this->plugin_name);
});
