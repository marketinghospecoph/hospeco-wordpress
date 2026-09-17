<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Pi_dpmw_selection_rule_advance_shipping{
    public $slug;
    public $condition;
    
    function __construct($slug){
        $this->slug = $slug;
        $this->condition = 'advance_shipping';
        /* this adds the condition in set of rules dropdown */
        add_filter("pi_".$this->slug."_condition", array($this, 'addRule'));
        
        /* this gives value field to store condition value either select or text box */
        add_action( 'wp_ajax_pi_'.$this->slug.'_value_field_'.$this->condition, array( $this, 'ajaxCall' ) );

        /* This gives our field with saved value */
        add_filter('pi_'.$this->slug.'_saved_values_'.$this->condition, array($this, 'savedDropdown'), 10, 3);

        /* This perform condition check */
        add_filter('pi_'.$this->slug.'_condition_check_'.$this->condition,array($this,'conditionCheck'),10,4);

        /* This gives out logic dropdown */
        add_action('pi_'.$this->slug.'_logic_'.$this->condition, array($this, 'logicDropdown'));

        /* This give saved logic dropdown */
        add_filter('pi_'.$this->slug.'_saved_logic_'.$this->condition, array($this, 'savedLogic'),10,3);
    }

    static function datePluginInstalled(){
        if(is_plugin_active( 'advanced-free-flat-shipping-woocommerce/extended-flat-rate-shipping-woocommerce.php') || is_plugin_active( 'advanced-free-flat-shipping-woocommerce-pro/extended-flat-rate-shipping-woocommerce.php')) return true;

        return false;
    }

    function addRule($rules){
        $rules[$this->condition] = array(
            'name'=>__('Advance Shipping method'),
            'group'=>'advance_shipping',
            'condition'=>$this->condition
        );
        return $rules;
    }

    function logicDropdown(){
        $html = "";
        $html .= 'var pi_logic_'.$this->condition.'= "<select class=\'form-control\' name=\'pi_selection[{count}][pi_'.esc_attr($this->slug).'_logic]\'>';
        
            $html .= '<option value=\'equal_to\'>Equal to (=)</option>';
            $html .= '<option value=\'not_equal_to\'>Not Equal to (!=)</option>';
           
        
        $html .= '</select>";';
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $html;
    }

    function savedLogic($html_in, $saved_logic, $count){
        $html = "";
        $html .= '<select class="form-control" name="pi_selection['.$count.'][pi_'.$this->slug.'_logic]">';
        
            $html .= '<option value="equal_to" '.selected($saved_logic , "equal_to",false ).'>Equal to (=)</option>';
            $html .= '<option value="not_equal_to" '.selected($saved_logic , "not_equal_to",false ).'>Not Equal to (!=)</option>';
           
        
        $html .= '</select>';
        return $html;
    }

    function ajaxCall(){
        $access = Pi_dpmw_Menu::getCapability();
        if(!current_user_can( $access )) {
            return;
            die;
        }
        if(self::datePluginInstalled()){
            $count = filter_input(INPUT_POST,'count',FILTER_VALIDATE_INT);
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo Pi_dpmw_selection_rule_main::createSelect($this->advance_shipping(), $count, $this->condition,  "multiple",null,'static');
        }else{
            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::msgNoDateTimePlugin();
        }
        die;
    }

    function savedDropdown($html, $values, $count){
        if(self::datePluginInstalled()){
            $html = Pi_dpmw_selection_rule_main::createSelect($this->advance_shipping(), $count, $this->condition,  "multiple", $values,'static');
            return $html;
        }

        return self::msgNoDateTimePlugin();
    }

    static function msgNoDateTimePlugin(){
        if(self::isPluginInstalled( 'advanced-free-flat-shipping-woocommerce/extended-flat-rate-shipping-woocommerce.php')){
            $url = self::activatePluginUrl();
            return sprintf('<div class="alert alert-danger">This feature requires <a href="%s" target="_blank">Advance shipping plugin</a> activated in your website, <a href="%s">Click to activate</a></div>', 'https://wordpress.org/plugins/advanced-free-flat-shipping-woocommerce/', $url);
        }

        $url = self::installPluginUrl();
        $plugin_page = 'https://wordpress.org/plugins/advanced-free-flat-shipping-woocommerce/';
        return sprintf('<div class="alert alert-danger">This feature requires <a href="%s" target="_blank">Advance shipping plugin</a> installed in your website, <a href="%s">Click to install</a></div>',$plugin_page, $url);
    }

    static function activatePluginUrl(){
        $plugin_file = 'advanced-free-flat-shipping-woocommerce/extended-flat-rate-shipping-woocommerce.php';
        $url  = wp_nonce_url(
            admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
            'activate-plugin_' . $plugin_file
        );
        return $url;
    }

    static function installPluginUrl(){
        $action = 'install-plugin';
        $slug = 'advanced-free-flat-shipping-woocommerce';
        return wp_nonce_url(
            add_query_arg(
                array(
                    'action' => $action,
                    'plugin' => $slug
                ),
                admin_url( 'update.php' )
            ),
            $action.'_'.$slug
        );
    }

    static function isPluginInstalled( $plugin_file ) {

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        return isset( $plugins[ $plugin_file ] );
    }

    function advance_shipping(){
        $shipping_methods = get_posts(array(
            'post_type'   => 'pi_shipping_method',
            'posts_per_page' => -1,
        ));

        $value = array();
        foreach($shipping_methods as $method){
            $value['pisol_extended_flat_shipping:'.$method->ID] = $method->post_title;
        }

        return $value;
    }

    function selectedShippingMethod($package){
        if(is_a($package, 'WC_Cart')){
            if(!function_exists('WC') || !is_object(WC()->session)) return array();

            //if its wordpress api request
            if(defined('REST_REQUEST') && REST_REQUEST){
                $api_selected_method = $this->shipping_method_selected_in_api_request();
                if($api_selected_method !== false){
                    return $api_selected_method;
                }
            }

            $chosen_method = WC()->session->get( 'chosen_shipping_methods' );
            return is_array($chosen_method) && isset($chosen_method[0]) ? $chosen_method[0] : '';
        }elseif(is_a($package, 'WC_Order')){
            $chosen_method = self::get_shipping_methods( $package );
            return is_array($chosen_method)  && isset($chosen_method[0]) ? $chosen_method[0] : '';
        }
    }

    /**
     * we used this as our custom code to store system name of shipping mehtod is failing to store it in block based checkout page 
     */
    static function get_shipping_methods( $order ){
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return array();
        }

        $shipping_methods = array();

        foreach ( $order->get_shipping_methods() as $shipping_item ) {
            $method_id   = $shipping_item->get_method_id();
            $instance_id = $shipping_item->get_instance_id();
            if ( empty( $instance_id ) ) {
                $shipping_methods[] = $method_id;
            } else {
                $shipping_methods[] = $method_id . ':' . $instance_id;
            }
        }

        return $shipping_methods;
    }

    function shipping_method_selected_in_api_request() {
        $request_body = file_get_contents('php://input');

        // Decode the JSON data into an associative array
        $request_data = json_decode($request_body, true);
        if(isset($request_data['requests']) && isset($request_data['requests'][0]['path']) && strpos($request_data['requests'][0]['path'], 'cart/select-shipping-rate') !== false && isset( $request_data['requests'][0]['data']['rate_id'])){
            return $request_data['requests'][0]['data']['rate_id'];
        }

        return false;
    }

    function conditionCheck($result, $package, $logic, $values){
        
                    $or_result = false;
                    $selected_day = $this->selectedShippingMethod($package);

                    if($selected_day === false) return $or_result;

                    $rule_days = $values;
                    if($logic == 'equal_to'){
                        if(in_array($selected_day, $rule_days)){
                            $or_result = true;
                        }else{
                            $or_result = false;
                        }
                    }else{
                        if(in_array($selected_day, $rule_days)){
                            $or_result = false;
                        }else{
                            $or_result = true;
                        }
                    }
               
        return  apply_filters('pisol_dpmw_selected_shipping_method_filter', $or_result, $values);
    }
}

new Pi_dpmw_selection_rule_advance_shipping(PI_DPMW_SELECTION_RULE_SLUG);