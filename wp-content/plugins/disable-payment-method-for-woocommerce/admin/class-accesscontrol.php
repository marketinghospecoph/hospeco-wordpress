<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Pi_dpmw_AccessControl{

    static $instance = null;

    static $setting_groups = array(
        'dpmw_extra_setting',
        'dpmw_cod_deposit_setting'
    );

    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){
        foreach(self::$setting_groups as $group){
            add_filter("option_page_capability_{$group}", array('Pi_dpmw_Menu', 'getCapability'));
        }
        
    }
}

Pi_dpmw_AccessControl::getInstance();