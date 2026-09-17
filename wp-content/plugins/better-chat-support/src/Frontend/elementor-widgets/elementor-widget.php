<?php

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Messenger buttons widget.
 *
 * This class handles the creation of a custom Elementor widget 
 * for displaying Messenger buttons in the Better Chat Support Pro plugin.
 *
 * @since 1.0
 */
class Elementor_MCS_Buttons extends \Elementor\Widget_Base
{

    /**
     * Get widget name.
     *
     * Returns the widget name for use in Elementor.
     *
     * @since 1.0
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'mcs-buttons';
    }

    /**
     * Get widget title.
     *
     * Returns the display title of the widget in Elementor.
     *
     * @since 1.0
     * @return string Widget title.
     */
    public function get_title()
    {
        return esc_html__('Better Chat Support buttons', 'better-chat-support');
    }

    /**
     * Get widget icon.
     *
     * Returns the icon for the widget that will appear in the Elementor editor.
     *
     * @since 1.0
     * @return string Widget icon class.
     */
    public function get_icon()
    {
        return 'eicon-instagram-comments';
    }

    public function get_style_depends(): array
    {
        return ['font-awesome'];
    }

    /**
     * Get widget categories.
     *
     * Defines the category or categories the widget belongs to in Elementor.
     *
     * @since 1.0
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['mcs-elements'];
    }

    protected function _register_controls()
    {

        // ----------------------------------------  Ctw Buttons Settings ------------------------------

        $this->start_controls_section(
            'mcs__general__settings',
            array(
                'label' => esc_html__('General settings', 'better-chat-support'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'fbid', [
                'label'     => esc_html__( 'Facebook ID', 'better-chat-support' ),
                'description' => esc_html__('Add your facebook ID eg: ThemeAtelier', 'better-chat-support'),
                'label_block' => false,
                'type'      => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'style',
            array(
                'label'       => esc_html__('Style', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'label_block' => false,
                'default'     => '1',
                'options'     => array(
                    '1' => esc_html__('Advanced button', 'better-chat-support'),
                    '2' => esc_html__('Basic button', 'better-chat-support'),
                ),

            )
        );

        $this->add_control(
            'agent__photo',
            array(
                'label'       => esc_html__('Agent photo', 'better-chat-support'),
                'description' => esc_html__('Add agent photo to show in button.', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::MEDIA,
                'label_block' => true,
                'default'     => array(
                    'url' => BETTER_CHAT_SUPPORT_DIR_URL . 'assets/image/user.webp',
                ),
                'condition'   => array(
                    'style' => '1',
                ),
            )
        );

        $this->add_control(
            'button_top_label',
            array(
                'label'       => esc_html__('Button Top Label', 'better-chat-support'),
                'description' => esc_html__('Write agent name to show in button.', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => esc_html__('John / Sales Support', 'better-chat-support'),
                'condition'   => array(
                    'style' => '1',
                ),
            )
        );

        $this->add_control(
            'button_main_label',
            array(
                'label'       => esc_html__('Button Main label', 'better-chat-support'),
                'description' => esc_html__('Add custom button label.', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
                'default'     => esc_html__('How can I help you?', 'better-chat-support'),
            )
        );

        $this->add_control(
            'online__text',
            array(
                'label'       => esc_html__('Online badget text', 'better-chat-support'),
                'description' => esc_html__('Add custom badget text when user in online.', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => esc_html__('I\'m avaiable', 'better-chat-support'),
                'condition'   => array(
                    'style' => '1',
                ),
            )
        );

        $this->add_control(
            'offline__text',
            array(
                'label'       => esc_html__('Offline badget text', 'better-chat-support'),
                'description' => esc_html__('Add custom badget text when user in offline.', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => esc_html__('I\'m offline', 'better-chat-support'),
                'condition'   => array(
                    'style' => '1',
                ),
            )
        );
        $this->add_control(
            'open_link_new_window',
            array(
                'label'        => esc_html__('Open link in new window', 'better-chat-support'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'better-chat-support'),
                'label_off'    => esc_html__('No', 'better-chat-support'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );

        $this->end_controls_section(); // End section top content

        $this->start_controls_section(
            'mcs__availablity__manager',
            array(
                'label'     => esc_html__('Chat settings', 'better-chat-support'),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'timezone',
            array(
                'label'       => esc_html__('Timezone', 'better-chat-support'),
                'description' => esc_html__('When using the date and time from the user browser you can transform it to your current timezone (in case your user is in a different timezone)', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'label_block' => true,
                'multiple'    => false,
                'default'     => '1',
                'options'     => array(
                    '1'                                => esc_html__('Select timezone', 'better-chat-support'),
                    'Africa/Abidjan'                   => esc_html__('Africa/Abidjan', 'better-chat-support'),
                    'Africa/Accra'                     => esc_html__('Africa/Accra', 'better-chat-support'),
                    'Africa/Addis_Ababa'               => esc_html__('Africa/Addis_Ababa', 'better-chat-support'),
                    'Africa/Algiers'                   => esc_html__('Africa/Algiers', 'better-chat-support'),
                    'Africa/Asmara'                    => esc_html__('Africa/Asmara', 'better-chat-support'),
                    'Africa/Asmera'                    => esc_html__('Africa/Asmera', 'better-chat-support'),
                    'Africa/Bamako'                    => esc_html__('Africa/Bamako', 'better-chat-support'),
                    'Africa/Bangui'                    => esc_html__('Africa/Bangui', 'better-chat-support'),
                    'Africa/Banjul'                    => esc_html__('Africa/Banjul', 'better-chat-support'),
                    'Africa/Bissau'                    => esc_html__('Africa/Bissau', 'better-chat-support'),
                    'Africa/Blantyre'                  => esc_html__('Africa/Blantyre', 'better-chat-support'),
                    'Africa/Brazzaville'               => esc_html__('Africa/Brazzaville', 'better-chat-support'),
                    'Africa/Bujumbura'                 => esc_html__('Africa/Bujumbura', 'better-chat-support'),
                    'Africa/Cairo'                     => esc_html__('Africa/Cairo', 'better-chat-support'),
                    'Africa/Casablanca'                => esc_html__('Africa/Casablanca', 'better-chat-support'),
                    'Africa/Ceuta'                     => esc_html__('Africa/Ceuta', 'better-chat-support'),
                    'Africa/Conakry'                   => esc_html__('Africa/Conakry', 'better-chat-support'),
                    'Africa/Dakar'                     => esc_html__('Africa/Dakar', 'better-chat-support'),
                    'Africa/Dar_es_Salaam'             => esc_html__('Africa/Dar_es_Salaam', 'better-chat-support'),
                    'Africa/Djibouti'                  => esc_html__('Africa/Djibouti', 'better-chat-support'),
                    'Africa/Douala'                    => esc_html__('Africa/Douala', 'better-chat-support'),
                    'Africa/El_Aaiun'                  => esc_html__('Africa/El_Aaiun', 'better-chat-support'),
                    'Africa/Freetown'                  => esc_html__('Africa/Freetown', 'better-chat-support'),
                    'Africa/Gaborone'                  => esc_html__('Africa/Gaborone', 'better-chat-support'),
                    'Africa/Harare'                    => esc_html__('Africa/Harare', 'better-chat-support'),
                    'Africa/Johannesburg'              => esc_html__('Africa/Johannesburg', 'better-chat-support'),
                    'Africa/Juba'                      => esc_html__('Africa/Juba', 'better-chat-support'),
                    'Africa/Kampala'                   => esc_html__('Africa/Kampala', 'better-chat-support'),
                    'Africa/Khartoum'                  => esc_html__('Africa/Khartoum', 'better-chat-support'),
                    'Africa/Kigali'                    => esc_html__('Africa/Kigali', 'better-chat-support'),
                    'Africa/Kinshasa'                  => esc_html__('Africa/Kinshasa', 'better-chat-support'),
                    'Africa/Lagos'                     => esc_html__('Africa/Lagos', 'better-chat-support'),
                    'Africa/Libreville'                => esc_html__('Africa/Libreville', 'better-chat-support'),
                    'Africa/Lome'                      => esc_html__('Africa/Lome', 'better-chat-support'),
                    'Africa/Luanda'                    => esc_html__('Africa/Luanda', 'better-chat-support'),
                    'Africa/Lubumbashi'                => esc_html__('Africa/Lubumbashi', 'better-chat-support'),
                    'Africa/Lusaka'                    => esc_html__('Africa/Lusaka', 'better-chat-support'),
                    'Africa/Malabo'                    => esc_html__('Africa/Malabo', 'better-chat-support'),
                    'Africa/Maputo'                    => esc_html__('Africa/Maputo', 'better-chat-support'),
                    'Africa/Maseru'                    => esc_html__('Africa/Maseru', 'better-chat-support'),
                    'Africa/Mbabane'                   => esc_html__('Africa/Mbabane', 'better-chat-support'),
                    'Africa/Mogadishu'                 => esc_html__('Africa/Mogadishu', 'better-chat-support'),
                    'Africa/Monrovia'                  => esc_html__('Africa/Monrovia', 'better-chat-support'),
                    'Africa/Nairobi'                   => esc_html__('Africa/Nairobi', 'better-chat-support'),
                    'Africa/Ndjamena'                  => esc_html__('Africa/Ndjamena', 'better-chat-support'),
                    'Africa/Niamey'                    => esc_html__('Africa/Niamey', 'better-chat-support'),
                    'Africa/Nouakchott'                => esc_html__('Africa/Nouakchott', 'better-chat-support'),
                    'Africa/Ouagadougou'               => esc_html__('Africa/Ouagadougou', 'better-chat-support'),
                    'Africa/Porto-Novo'                => esc_html__('Africa/Porto-Novo', 'better-chat-support'),
                    'Africa/Sao_Tome'                  => esc_html__('Africa/Sao_Tome', 'better-chat-support'),
                    'Africa/Timbuktu'                  => esc_html__('Africa/Timbuktu', 'better-chat-support'),
                    'Africa/Tripoli'                   => esc_html__('Africa/Tripoli', 'better-chat-support'),
                    'Africa/Tunis'                     => esc_html__('Africa/Tunis', 'better-chat-support'),
                    'Africa/Windhoek'                  => esc_html__('Africa/Windhoek', 'better-chat-support'),
                    'America/Adak'                     => esc_html__('America/Adak', 'better-chat-support'),
                    'America/Anchorage'                => esc_html__('America/Anchorage', 'better-chat-support'),
                    'America/Anguilla'                 => esc_html__('America/Anguilla', 'better-chat-support'),
                    'America/Antigua'                  => esc_html__('America/Antigua', 'better-chat-support'),
                    'America/Araguaina'                => esc_html__('America/Araguaina', 'better-chat-support'),
                    'America/Argentina/Buenos_Aires'   => esc_html__('America/Argentina/Buenos_Aires', 'better-chat-support'),
                    'America/Argentina/Catamarca'      => esc_html__('America/Argentina/Catamarca', 'better-chat-support'),
                    'America/Argentina/ComodRivadavia' => esc_html__('America/Argentina/ComodRivadavia', 'better-chat-support'),
                    'America/Argentina/Cordoba'        => esc_html__('America/Argentina/Cordoba', 'better-chat-support'),
                    'America/Argentina/Jujuy'          => esc_html__('America/Argentina/Jujuy', 'better-chat-support'),
                    'America/Argentina/La_Rioja'       => esc_html__('America/Argentina/La_Rioja', 'better-chat-support'),
                    'America/Argentina/Mendoza'        => esc_html__('America/Argentina/Mendoza', 'better-chat-support'),
                    'America/Argentina/Rio_Gallegos'   => esc_html__('America/Argentina/Rio_Gallegos', 'better-chat-support'),
                    'America/Argentina/Salta'          => esc_html__('America/Argentina/Salta', 'better-chat-support'),
                    'America/Argentina/San_Juan'       => esc_html__('America/Argentina/San_Juan', 'better-chat-support'),
                    'America/Argentina/San_Luis'       => esc_html__('America/Argentina/San_Luis', 'better-chat-support'),
                    'America/Argentina/Tucuman'        => esc_html__('America/Argentina/Tucuman', 'better-chat-support'),
                    'America/Argentina/Ushuaia'        => esc_html__('America/Argentina/Ushuaia', 'better-chat-support'),
                    'America/Aruba'                    => esc_html__('America/Aruba', 'better-chat-support'),
                    'America/Asuncion'                 => esc_html__('America/Asuncion', 'better-chat-support'),
                    'America/Atikokan'                 => esc_html__('America/Atikokan', 'better-chat-support'),
                    'America/Atka'                     => esc_html__('America/Atka', 'better-chat-support'),
                    'America/Bahia'                    => esc_html__('America/Bahia', 'better-chat-support'),
                    'America/Bahia_Banderas'           => esc_html__('America/Bahia_Banderas', 'better-chat-support'),
                    'America/Barbados'                 => esc_html__('America/Barbados', 'better-chat-support'),
                    'America/Belem'                    => esc_html__('America/Belem', 'better-chat-support'),
                    'America/Belize'                   => esc_html__('America/Belize', 'better-chat-support'),
                    'America/Blanc-Sablon'             => esc_html__('America/Blanc-Sablon', 'better-chat-support'),
                    'America/Boa_Vista'                => esc_html__('America/Boa_Vista', 'better-chat-support'),
                    'America/Bogota'                   => esc_html__('America/Bogota', 'better-chat-support'),
                    'America/Boise'                    => esc_html__('America/Boise', 'better-chat-support'),
                    'America/Buenos_Aires'             => esc_html__('America/Buenos_Aires', 'better-chat-support'),
                    'America/Cambridge_Bay'            => esc_html__('America/Cambridge_Bay', 'better-chat-support'),
                    'America/Campo_Grande'             => esc_html__('America/Campo_Grande', 'better-chat-support'),
                    'America/Cancun'                   => esc_html__('America/Cancun', 'better-chat-support'),
                    'America/Caracas'                  => esc_html__('America/Caracas', 'better-chat-support'),
                    'America/Catamarca'                => esc_html__('America/Catamarca', 'better-chat-support'),
                    'America/Cayenne'                  => esc_html__('America/Cayenne', 'better-chat-support'),
                    'America/Cayman'                   => esc_html__('America/Cayman', 'better-chat-support'),
                    'America/Chicago'                  => esc_html__('America/Chicago', 'better-chat-support'),
                    'America/Chihuahua'                => esc_html__('America/Chihuahua', 'better-chat-support'),
                    'America/Coral_Harbour'            => esc_html__('America/Coral_Harbour', 'better-chat-support'),
                    'America/Cordoba'                  => esc_html__('America/Cordoba', 'better-chat-support'),
                    'America/Costa_Rica'               => esc_html__('America/Costa_Rica', 'better-chat-support'),
                    'America/Creston'                  => esc_html__('America/Creston', 'better-chat-support'),
                    'America/Cuiaba'                   => esc_html__('America/Cuiaba', 'better-chat-support'),
                    'America/Curacao'                  => esc_html__('America/Curacao', 'better-chat-support'),
                    'America/Danmarkshavn'             => esc_html__('America/Danmarkshavn', 'better-chat-support'),
                    'America/Dawson'                   => esc_html__('America/Dawson', 'better-chat-support'),
                    'America/Araguaina'                => esc_html__('America/Araguaina', 'better-chat-support'),
                    'America/Denver'                   => esc_html__('America/Denver', 'better-chat-support'),
                    'America/Araguaina'                => esc_html__('America/Araguaina', 'better-chat-support'),
                    'America/Dominica'                 => esc_html__('America/Dominica', 'better-chat-support'),
                    'America/Edmonton'                 => esc_html__('America/Edmonton', 'better-chat-support'),
                    'America/Eirunepe'                 => esc_html__('America/Eirunepe', 'better-chat-support'),
                    'America/El_Salvador'              => esc_html__('America/El_Salvador', 'better-chat-support'),
                    'America/Ensenada'                 => esc_html__('America/Ensenada', 'better-chat-support'),
                    'America/Fort_Nelson'              => esc_html__('America/Fort_Nelson', 'better-chat-support'),
                    'America/Fort_Wayne'               => esc_html__('America/Fort_Wayne', 'better-chat-support'),
                    'America/Fortaleza'                => esc_html__('America/Fortaleza', 'better-chat-support'),
                    'America/Glace_Bay'                => esc_html__('America/Glace_Bay', 'better-chat-support'),
                    'America/Godthab'                  => esc_html__('America/Godthab', 'better-chat-support'),
                    'America/Goose_Bay'                => esc_html__('America/Goose_Bay', 'better-chat-support'),
                    'America/Grand_Turk'               => esc_html__('America/Grand_Turk', 'better-chat-support'),
                    'America/Grenada'                  => esc_html__('America/Grenada', 'better-chat-support'),
                    'America/Guadeloupe'               => esc_html__('America/Guadeloupe', 'better-chat-support'),
                    'America/Guatemala'                => esc_html__('America/Guatemala', 'better-chat-support'),
                    'America/Guayaquil'                => esc_html__('America/Guayaquil', 'better-chat-support'),
                    'America/Guyana'                   => esc_html__('America/Guyana', 'better-chat-support'),
                    'America/Halifax'                  => esc_html__('America/Halifax', 'better-chat-support'),
                    'America/Havana'                   => esc_html__('America/Havana', 'better-chat-support'),
                    'America/Hermosillo'               => esc_html__('America/Hermosillo', 'better-chat-support'),
                    'America/Indiana/Indianapolis'     => esc_html__('Indiana/Indianapolis', 'better-chat-support'),
                    'America/Indiana/Knox'             => esc_html__('America/Indiana/Knox', 'better-chat-support'),
                    'America/Indiana/Marengo'          => esc_html__('America/Indiana/Marengo', 'better-chat-support'),
                    'America/Indiana/Petersburg'       => esc_html__('America/Indiana/Petersburg', 'better-chat-support'),
                    'America/Indiana/Tell_City'        => esc_html__('America/Indiana/Tell_City', 'better-chat-support'),
                    'America/Indiana/Vevay'            => esc_html__('America/Indiana/Vevay', 'better-chat-support'),
                    'America/Indiana/Vincennes'        => esc_html__('America/Indiana/Vincennes', 'better-chat-support'),
                    'America/Indiana/Winamac'          => esc_html__('America/Indiana/Winamac', 'better-chat-support'),
                    'America/Indianapolis'             => esc_html__('America/Indianapolis', 'better-chat-support'),
                    'America/Inuvik'                   => esc_html__('America/Inuvik', 'better-chat-support'),
                    'America/Iqaluit'                  => esc_html__('America/Iqaluit', 'better-chat-support'),
                    'America/Jamaica'                  => esc_html__('America/Jamaica', 'better-chat-support'),
                    'America/Jujuy'                    => esc_html__('America/Jujuy', 'better-chat-support'),
                    'America/Juneau'                   => esc_html__('America/Juneau', 'better-chat-support'),
                    'America/Kentucky/Louisville'      => esc_html__('America/Kentucky/Louisville', 'better-chat-support'),
                    'America/Kentucky/Monticello'      => esc_html__('America/Kentucky/Monticello', 'better-chat-support'),
                    'America/Knox_IN'                  => esc_html__('America/Knox_IN', 'better-chat-support'),
                    'America/Kralendijk'               => esc_html__('America/Kralendijk', 'better-chat-support'),
                    'America/La_Paz'                   => esc_html__('America/La_Paz', 'better-chat-support'),
                    'America/Lima'                     => esc_html__('America/Lima', 'better-chat-support'),
                    'America/Los_Angeles'              => esc_html__('America/Los_Angeles', 'better-chat-support'),
                    'America/Louisville'               => esc_html__('America/Louisville', 'better-chat-support'),
                    'America/Lower_Princes'            => esc_html__('America/Lower_Princes', 'better-chat-support'),
                    'America/Maceio'                   => esc_html__('America/Maceio', 'better-chat-support'),
                    'America/Managua'                  => esc_html__('America/Managua', 'better-chat-support'),
                    'America/Manaus'                   => esc_html__('America/Manaus', 'better-chat-support'),
                    'America/Marigot'                  => esc_html__('America/Marigot', 'better-chat-support'),
                    'America/Martinique'               => esc_html__('America/Martinique', 'better-chat-support'),
                    'America/Matamoros'                => esc_html__('America/Matamoros', 'better-chat-support'),
                    'America/Mazatlan'                 => esc_html__('America/Mazatlan', 'better-chat-support'),
                    'America/Mendoza'                  => esc_html__('America/Mendoza', 'better-chat-support'),
                    'America/Menominee'                => esc_html__('America/Menominee', 'better-chat-support'),
                    'America/Merida'                   => esc_html__('America/Merida', 'better-chat-support'),
                    'America/Metlakatla'               => esc_html__('America/Metlakatla', 'better-chat-support'),
                    'America/Mexico_City'              => esc_html__('America/Mexico_City', 'better-chat-support'),
                    'America/Miquelon'                 => esc_html__('America/Miquelon', 'better-chat-support'),
                    'America/Moncton'                  => esc_html__('America/Moncton', 'better-chat-support'),
                    'America/Monterrey'                => esc_html__('America/Monterrey', 'better-chat-support'),
                    'America/Montevideo'               => esc_html__('America/Montevideo', 'better-chat-support'),
                    'America/Montreal'                 => esc_html__('America/Montreal', 'better-chat-support'),
                    'America/Montserrat'               => esc_html__('America/Montserrat', 'better-chat-support'),
                    'America/Nassau'                   => esc_html__('America/Nassau', 'better-chat-support'),
                    'America/New_York'                 => esc_html__('America/New_York', 'better-chat-support'),
                    'America/Nipigon'                  => esc_html__('America/Nipigon', 'better-chat-support'),
                    'America/Nome'                     => esc_html__('America/Nome', 'better-chat-support'),
                    'America/Noronha'                  => esc_html__('America/Noronha', 'better-chat-support'),
                    'America/North_Dakota/Beulah'      => esc_html__('America/North_Dakota/Beulah', 'better-chat-support'),
                    'America/North_Dakota/Center'      => esc_html__('America/North_Dakota/Center', 'better-chat-support'),
                    'America/North_Dakota/New_Salem'   => esc_html__('America/North_Dakota/New_Salem', 'better-chat-support'),
                    'America/Ojinaga'                  => esc_html__('America/Ojinaga', 'better-chat-support'),
                    'America/Panama'                   => esc_html__('America/Panama', 'better-chat-support'),
                    'America/Pangnirtung'              => esc_html__('America/Pangnirtung', 'better-chat-support'),
                    'America/Paramaribo'               => esc_html__('America/Paramaribo', 'better-chat-support'),
                    'America/Phoenix'                  => esc_html__('America/Phoenix', 'better-chat-support'),
                    'America/Port-au-Prince'           => esc_html__('America/Port-au-Prince', 'better-chat-support'),
                    'America/Port_of_Spain'            => esc_html__('America/Port_of_Spain', 'better-chat-support'),
                    'America/Porto_Acre'               => esc_html__('America/Porto_Acre', 'better-chat-support'),
                    'America/Porto_Velho'              => esc_html__('America/Porto_Velho', 'better-chat-support'),
                    'America/Puerto_Rico'              => esc_html__('America/Puerto_Rico', 'better-chat-support'),
                    'America/Punta_Arenas'             => esc_html__('America/Punta_Arenas', 'better-chat-support'),
                    'America/Rainy_River'              => esc_html__('America/Rainy_River', 'better-chat-support'),
                    'America/Rankin_Inlet'             => esc_html__('America/Rankin_Inlet', 'better-chat-support'),
                    'America/Recife'                   => esc_html__('America/Recife', 'better-chat-support'),
                    'America/Regina'                   => esc_html__('America/Regina', 'better-chat-support'),
                    'America/Resolute'                 => esc_html__('America/Resolute', 'better-chat-support'),
                    'America/Rio_Branco'               => esc_html__('America/Rio_Branco', 'better-chat-support'),
                    'America/Rosario'                  => esc_html__('America/Rosario', 'better-chat-support'),
                    'America/Santa_Isabel'             => esc_html__('America/Santa_Isabel', 'better-chat-support'),
                    'America/Santarem'                 => esc_html__('America/Santarem', 'better-chat-support'),
                    'America/Santiago'                 => esc_html__('America/Santiago', 'better-chat-support'),
                    'America/Santo_Domingo'            => esc_html__('America/Santo_Domingo', 'better-chat-support'),
                    'America/Sao_Paulo'                => esc_html__('America/Sao_Paulo', 'better-chat-support'),
                    'America/Scoresbysund'             => esc_html__('America/Scoresbysund', 'better-chat-support'),
                    'America/Shiprock'                 => esc_html__('America/Shiprock', 'better-chat-support'),
                    'America/Sitka'                    => esc_html__('America/Sitka', 'better-chat-support'),
                    'America/St_Barthelemy'            => esc_html__('America/St_Barthelemy', 'better-chat-support'),
                    'America/St_Johns'                 => esc_html__('America/St_Johns', 'better-chat-support'),
                    'America/St_Kitts'                 => esc_html__('America/St_Kitts', 'better-chat-support'),
                    'America/St_Lucia'                 => esc_html__('America/St_Lucia', 'better-chat-support'),
                    'America/St_Thomas'                => esc_html__('America/St_Thomas', 'better-chat-support'),
                    'America/St_Vincent'               => esc_html__('America/St_Vincent', 'better-chat-support'),
                    'America/Swift_Current'            => esc_html__('America/Swift_Current', 'better-chat-support'),
                    'America/Tegucigalpa'              => esc_html__('America/Tegucigalpa', 'better-chat-support'),
                    'America/Thule'                    => esc_html__('America/Thule', 'better-chat-support'),
                    'America/Thunder_Bay'              => esc_html__('America/Thunder_Bay', 'better-chat-support'),
                    'America/Tijuana'                  => esc_html__('America/Tijuana', 'better-chat-support'),
                    'America/Toronto'                  => esc_html__('America/Toronto', 'better-chat-support'),
                    'America/Tortola'                  => esc_html__('America/Tortola', 'better-chat-support'),
                    'America/Vancouver'                => esc_html__('America/Vancouver', 'better-chat-support'),
                    'America/Virgin'                   => esc_html__('America/Virgin', 'better-chat-support'),
                    'America/Whitehorse'               => esc_html__('America/Whitehorse', 'better-chat-support'),
                    'America/Winnipeg'                 => esc_html__('America/Winnipeg', 'better-chat-support'),
                    'America/Yakutat'                  => esc_html__('America/Yakutat', 'better-chat-support'),
                    'America/Yellowknife'              => esc_html__('America/Yellowknife', 'better-chat-support'),
                    'Antarctica/Casey'                 => esc_html__('Antarctica/Casey', 'better-chat-support'),
                    'Antarctica/Davis'                 => esc_html__('Antarctica/Davis', 'better-chat-support'),
                    'Antarctica/DumontDUrville'        => esc_html__('Antarctica/DumontDUrville', 'better-chat-support'),
                    'Antarctica/Macquarie'             => esc_html__('Antarctica/Macquarie', 'better-chat-support'),
                    'Antarctica/Mawson'                => esc_html__('Antarctica/Mawson', 'better-chat-support'),
                    'Antarctica/McMurdo'               => esc_html__('Antarctica/McMurdo', 'better-chat-support'),
                    'Antarctica/Palmer'                => esc_html__('Antarctica/Palmer', 'better-chat-support'),
                    'Antarctica/Rothera'               => esc_html__('Antarctica/Rothera', 'better-chat-support'),
                    'Antarctica/South_Pole'            => esc_html__('Antarctica/South_Pole', 'better-chat-support'),
                    'Antarctica/Syowa'                 => esc_html__('Antarctica/Syowa', 'better-chat-support'),
                    'Antarctica/Troll'                 => esc_html__('Antarctica/Troll', 'better-chat-support'),
                    'Antarctica/Vostok'                => esc_html__('Antarctica/Vostok', 'better-chat-support'),
                    'Arctic/Longyearbyen'              => esc_html__('Arctic/Longyearbyen', 'better-chat-support'),
                    'Asia/Aden'                        => esc_html__('Asia/Aden', 'better-chat-support'),
                    'Asia/Almaty'                      => esc_html__('Asia/Almaty', 'better-chat-support'),
                    'Asia/Amman'                       => esc_html__('Asia/Amman', 'better-chat-support'),
                    'Asia/Anadyr'                      => esc_html__('Asia/Anadyr', 'better-chat-support'),
                    'Asia/Aqtau'                       => esc_html__('Asia/Aqtau', 'better-chat-support'),
                    'Asia/Aqtobe'                      => esc_html__('Asia/Aqtobe', 'better-chat-support'),
                    'Asia/Ashgabat'                    => esc_html__('Asia/Ashgabat', 'better-chat-support'),
                    'Asia/Ashkhabad'                   => esc_html__('Asia/Ashkhabad', 'better-chat-support'),
                    'Asia/Atyrau'                      => esc_html__('Asia/Atyrau', 'better-chat-support'),
                    'Asia/Baghdad'                     => esc_html__('Asia/Baghdad', 'better-chat-support'),
                    'Asia/Bahrain'                     => esc_html__('Asia/Bahrain', 'better-chat-support'),
                    'Asia/Baku'                        => esc_html__('Asia/Baku', 'better-chat-support'),
                    'Asia/Bangkok'                     => esc_html__('Asia/Bangkok', 'better-chat-support'),
                    'Asia/Barnaul'                     => esc_html__('Asia/Barnaul', 'better-chat-support'),
                    'Asia/Beirut'                      => esc_html__('Asia/Beirut', 'better-chat-support'),
                    'Asia/Bishkek'                     => esc_html__('Asia/Bishkek', 'better-chat-support'),
                    'Asia/Brunei'                      => esc_html__('Asia/Brunei', 'better-chat-support'),
                    'Asia/Calcutta'                    => esc_html__('Asia/Calcutta', 'better-chat-support'),
                    'Asia/Chita'                       => esc_html__('Asia/Chita', 'better-chat-support'),
                    'Asia/Choibalsan'                  => esc_html__('Asia/Choibalsan', 'better-chat-support'),
                    'Asia/Chongqing'                   => esc_html__('Asia/Chongqing', 'better-chat-support'),
                    'Asia/Chungking'                   => esc_html__('Asia/Chungking', 'better-chat-support'),
                    'Asia/Colombo'                     => esc_html__('Asia/Colombo', 'better-chat-support'),
                    'Asia/Dacca'                       => esc_html__('Asia/Dacca', 'better-chat-support'),
                    'Asia/Damascus'                    => esc_html__('Asia/Damascus', 'better-chat-support'),
                    'Asia/Dhaka'                       => esc_html__('Asia/Dhaka', 'better-chat-support'),
                    'Asia/Dili'                        => esc_html__('Asia/Dili', 'better-chat-support'),
                    'Asia/Dubai'                       => esc_html__('Asia/Dubai', 'better-chat-support'),
                    'Asia/Dushanbe'                    => esc_html__('Asia/Dushanbe', 'better-chat-support'),
                    'Asia/Famagusta'                   => esc_html__('Asia/Famagusta', 'better-chat-support'),
                    'Asia/Gaza'                        => esc_html__('Asia/Gaza', 'better-chat-support'),
                    'Asia/Harbin'                      => esc_html__('Asia/Harbin', 'better-chat-support'),
                    'Asia/Hebron'                      => esc_html__('Asia/Hebron', 'better-chat-support'),
                    'Asia/Ho_Chi_Minh'                 => esc_html__('Asia/Ho_Chi_Minh', 'better-chat-support'),
                    'Asia/Hong_Kong'                   => esc_html__('Asia/Hong_Kong', 'better-chat-support'),
                    'Asia/Hovd'                        => esc_html__('Asia/Hovd', 'better-chat-support'),
                    'Asia/Irkutsk'                     => esc_html__('Asia/Irkutsk', 'better-chat-support'),
                    'Asia/Istanbul'                    => esc_html__('Asia/Istanbul', 'better-chat-support'),
                    'Asia/Jakarta'                     => esc_html__('Asia/Jakarta', 'better-chat-support'),
                    'Asia/Jayapura'                    => esc_html__('Asia/Jayapura', 'better-chat-support'),
                    'Asia/Jerusalem'                   => esc_html__('Asia/Jerusalem', 'better-chat-support'),
                    'Asia/Kabul'                       => esc_html__('Asia/Kabul', 'better-chat-support'),
                    'Asia/Kamchatka'                   => esc_html__('Asia/Kamchatka', 'better-chat-support'),
                    'Asia/Karachi'                     => esc_html__('Asia/Karachi', 'better-chat-support'),
                    'Asia/Kashgar'                     => esc_html__('Asia/Kashgar', 'better-chat-support'),
                    'Asia/Kathmandu'                   => esc_html__('Asia/Kathmandu', 'better-chat-support'),
                    'Asia/Katmandu'                    => esc_html__('Asia/Katmandu', 'better-chat-support'),
                    'Asia/Khandyga'                    => esc_html__('Asia/Khandyga', 'better-chat-support'),
                    'Asia/Kolkata'                     => esc_html__('Asia/Kolkata', 'better-chat-support'),
                    'Asia/Krasnoyarsk'                 => esc_html__('Asia/Krasnoyarsk', 'better-chat-support'),
                    'Asia/Kuala_Lumpur'                => esc_html__('Asia/Kuala_Lumpur', 'better-chat-support'),
                    'Asia/Kuching'                     => esc_html__('Asia/Kuching', 'better-chat-support'),
                    'Asia/Kuwait'                      => esc_html__('Asia/Kuwait', 'better-chat-support'),
                    'Asia/Macao'                       => esc_html__('Asia/Macao', 'better-chat-support'),
                    'Asia/Macau'                       => esc_html__('Asia/Macau', 'better-chat-support'),
                    'Asia/Magadan'                     => esc_html__('Asia/Magadan', 'better-chat-support'),
                    'Asia/Makassar'                    => esc_html__('Asia/Makassar', 'better-chat-support'),
                    'Asia/Manila'                      => esc_html__('Asia/Manila', 'better-chat-support'),
                    'Asia/Muscat'                      => esc_html__('Asia/Muscat', 'better-chat-support'),
                    'Asia/Nicosia'                     => esc_html__('Asia/Nicosia', 'better-chat-support'),
                    'Asia/Novokuznetsk'                => esc_html__('Asia/Novokuznetsk', 'better-chat-support'),
                    'Asia/Novosibirsk'                 => esc_html__('Asia/Novosibirsk', 'better-chat-support'),
                    'Asia/Omsk'                        => esc_html__('Asia/Omsk', 'better-chat-support'),
                    'Asia/Oral'                        => esc_html__('Asia/Oral', 'better-chat-support'),
                    'Asia/Phnom_Penh'                  => esc_html__('Asia/Phnom_Penh', 'better-chat-support'),
                    'Asia/Pontianak'                   => esc_html__('Asia/Pontianak', 'better-chat-support'),
                    'Asia/Pyongyang'                   => esc_html__('Asia/Pyongyang', 'better-chat-support'),
                    'Asia/Qatar'                       => esc_html__('Asia/Qatar', 'better-chat-support'),
                    'Asia/Qyzylorda'                   => esc_html__('Asia/Qyzylorda', 'better-chat-support'),
                    'Asia/Rangoon'                     => esc_html__('Asia/Rangoon', 'better-chat-support'),
                    'Asia/Riyadh'                      => esc_html__('Asia/Riyadh', 'better-chat-support'),
                    'Asia/Saigon'                      => esc_html__('Asia/Saigon', 'better-chat-support'),
                    'Asia/Sakhalin'                    => esc_html__('Asia/Sakhalin', 'better-chat-support'),
                    'Asia/Samarkand'                   => esc_html__('Asia/Samarkand', 'better-chat-support'),
                    'Asia/Seoul'                       => esc_html__('Asia/Seoul', 'better-chat-support'),
                    'Asia/Shanghai'                    => esc_html__('Asia/Shanghai', 'better-chat-support'),
                    'Asia/Singapore'                   => esc_html__('Asia/Singapore', 'better-chat-support'),
                    'Asia/Srednekolymsk'               => esc_html__('Asia/Srednekolymsk', 'better-chat-support'),
                    'Asia/Taipei'                      => esc_html__('Asia/Taipei', 'better-chat-support'),
                    'Asia/Tashkent'                    => esc_html__('Asia/Tashkent', 'better-chat-support'),
                    'Asia/Tbilisi'                     => esc_html__('Asia/Tbilisi', 'better-chat-support'),
                    'Asia/Tehran'                      => esc_html__('Asia/Tehran', 'better-chat-support'),
                    'Asia/Tel_Aviv'                    => esc_html__('Asia/Tel_Aviv', 'better-chat-support'),
                    'Asia/Thimbu'                      => esc_html__('Asia/Thimbu', 'better-chat-support'),
                    'Asia/Thimphu'                     => esc_html__('Asia/Thimphu', 'better-chat-support'),
                    'Asia/Tokyo'                       => esc_html__('Asia/Tokyo', 'better-chat-support'),
                    'Asia/Tomsk'                       => esc_html__('Asia/Tomsk', 'better-chat-support'),
                    'Asia/Ujung_Pandang'               => esc_html__('Asia/Ujung_Pandang', 'better-chat-support'),
                    'Asia/Ulaanbaatar'                 => esc_html__('Asia/Ulaanbaatar', 'better-chat-support'),
                    'Asia/Ulan_Bator'                  => esc_html__('Asia/Ulan_Bator', 'better-chat-support'),
                    'Asia/Urumqi'                      => esc_html__('Asia/Urumqi', 'better-chat-support'),
                    'Asia/Ust-Nera'                    => esc_html__('Asia/Ust-Nera', 'better-chat-support'),
                    'Asia/Vientiane'                   => esc_html__('Asia/Vientiane', 'better-chat-support'),
                    'Asia/Vladivostok'                 => esc_html__('Asia/Vladivostok', 'better-chat-support'),
                    'Asia/Yakutsk'                     => esc_html__('Asia/Yakutsk', 'better-chat-support'),
                    'Asia/Yangon'                      => esc_html__('Asia/Yangon', 'better-chat-support'),
                    'Asia/Yekaterinburg'               => esc_html__('Asia/Yekaterinburg', 'better-chat-support'),
                    'Asia/Yerevan'                     => esc_html__('Asia/Yerevan', 'better-chat-support'),
                    'Atlantic/Azores'                  => esc_html__('Atlantic/Azores', 'better-chat-support'),
                    'Atlantic/Bermuda'                 => esc_html__('Atlantic/Bermuda', 'better-chat-support'),
                    'Atlantic/Canary'                  => esc_html__('Atlantic/Canary', 'better-chat-support'),
                    'Atlantic/Cape_Verde'              => esc_html__('Atlantic/Cape_Verde', 'better-chat-support'),
                    'Atlantic/Faeroe'                  => esc_html__('Atlantic/Faeroe', 'better-chat-support'),
                    'Atlantic/Faroe'                   => esc_html__('Atlantic/Faroe', 'better-chat-support'),
                    'Atlantic/Jan_Mayen'               => esc_html__('Atlantic/Jan_Mayen', 'better-chat-support'),
                    'Atlantic/Madeira'                 => esc_html__('Atlantic/Madeira', 'better-chat-support'),
                    'Atlantic/Reykjavik'               => esc_html__('Atlantic/Reykjavik', 'better-chat-support'),
                    'Atlantic/South_Georgia'           => esc_html__('Atlantic/South_Georgia', 'better-chat-support'),
                    'Atlantic/St_Helena'               => esc_html__('Atlantic/St_Helena', 'better-chat-support'),
                    'Atlantic/Stanley'                 => esc_html__('Atlantic/Stanley', 'better-chat-support'),
                    'Australia/ACT'                    => esc_html__('Australia/ACT', 'better-chat-support'),
                    'Australia/Adelaide'               => esc_html__('Australia/Adelaide', 'better-chat-support'),
                    'Australia/Brisbane'               => esc_html__('Australia/Brisbane', 'better-chat-support'),
                    'Australia/Broken_Hill'            => esc_html__('Asia/Broken_Hill', 'better-chat-support'),
                    'Australia/Canberra'               => esc_html__('Australia/Canberra', 'better-chat-support'),
                    'Australia/Currie'                 => esc_html__('Australia/Currie', 'better-chat-support'),
                    'Australia/Darwin'                 => esc_html__('Australia/Darwin', 'better-chat-support'),
                    'Australia/Eucla'                  => esc_html__('Australia/Eucla', 'better-chat-support'),
                    'Australia/Hobart'                 => esc_html__('Australia/Hobart', 'better-chat-support'),
                    'Australia/LHI'                    => esc_html__('Australia/LHI', 'better-chat-support'),
                    'Australia/Lindeman'               => esc_html__('Australia/Lindeman', 'better-chat-support'),
                    'Australia/Lord_Howe'              => esc_html__('Australia/Lord_Howe', 'better-chat-support'),
                    'Australia/Melbourne'              => esc_html__('Australia/Melbourne', 'better-chat-support'),
                    'Australia/NSW'                    => esc_html__('Australia/NSW', 'better-chat-support'),
                    'Australia/North'                  => esc_html__('Australia/North', 'better-chat-support'),
                    'Australia/Perth'                  => esc_html__('Australia/Perth', 'better-chat-support'),
                    'Australia/Queensland'             => esc_html__('Australia/Queensland', 'better-chat-support'),
                    'Australia/South'                  => esc_html__('Australia/South', 'better-chat-support'),
                    'Australia/Sydney'                 => esc_html__('Australia/Sydney', 'better-chat-support'),
                    'Australia/Tasmania'               => esc_html__('Australia/Tasmania', 'better-chat-support'),
                    'Australia/Victoria'               => esc_html__('Australia/Victoria', 'better-chat-support'),
                    'Australia/West'                   => esc_html__('Australia/West', 'better-chat-support'),
                    'Australia/Yancowinna'             => esc_html__('Australia/Yancowinna', 'better-chat-support'),
                    'Brazil/Acre'                      => esc_html__('Brazil/Acre', 'better-chat-support'),
                    'Brazil/DeNoronha'                 => esc_html__('Brazil/DeNoronha', 'better-chat-support'),
                    'Brazil/East'                      => esc_html__('Brazil/East', 'better-chat-support'),
                    'Brazil/West'                      => esc_html__('Brazil/West', 'better-chat-support'),
                    'CET'                              => esc_html__('CET', 'better-chat-support'),
                    'CST6CDT'                          => esc_html__('CST6CDT', 'better-chat-support'),
                    'Canada/Atlantic'                  => esc_html__('Canada/Atlantic', 'better-chat-support'),
                    'Canada/Central'                   => esc_html__('Canada/Central', 'better-chat-support'),
                    'Canada/Eastern'                   => esc_html__('Canada/Eastern', 'better-chat-support'),
                    'Canada/Mountain'                  => esc_html__('Canada/Mountain', 'better-chat-support'),
                    'Canada/Newfoundland'              => esc_html__('Canada/Newfoundland', 'better-chat-support'),
                    'Canada/Pacific'                   => esc_html__('Canada/Pacific', 'better-chat-support'),
                    'Canada/Saskatchewan'              => esc_html__('Canada/Saskatchewan', 'better-chat-support'),
                    'Canada/Yukon'                     => esc_html__('Canada/Yukon', 'better-chat-support'),
                    'Chile/Continental'                => esc_html__('Chile/Continental', 'better-chat-support'),
                    'Chile/EasterIsland'               => esc_html__('Chile/EasterIsland', 'better-chat-support'),
                    'Cuba'                             => esc_html__('Cuba', 'better-chat-support'),
                    'EET'                              => esc_html__('EET', 'better-chat-support'),
                    'EST'                              => esc_html__('EST', 'better-chat-support'),
                    'EST5EDT'                          => esc_html__('EST5EDT', 'better-chat-support'),
                    'Egypt'                            => esc_html__('Egypt', 'better-chat-support'),
                    'Eire'                             => esc_html__('Eire', 'better-chat-support'),
                    'Etc/GMT'                          => esc_html__('Etc/GMT', 'better-chat-support'),
                    'Etc/GMT+0'                        => esc_html__('Etc/GMT+0', 'better-chat-support'),
                    'Etc/GMT+1'                        => esc_html__('Etc/GMT+1', 'better-chat-support'),
                    'Etc/GMT+10'                       => esc_html__('Etc/GMT+10', 'better-chat-support'),
                    'Etc/GMT+11'                       => esc_html__('Etc/GMT+11', 'better-chat-support'),
                    'Etc/GMT+12'                       => esc_html__('Etc/GMT+12', 'better-chat-support'),
                    'Etc/GMT+2'                        => esc_html__('Etc/GMT+2', 'better-chat-support'),
                    'Etc/GMT+3'                        => esc_html__('Etc/GMT+3', 'better-chat-support'),
                    'Etc/GMT+4'                        => esc_html__('Etc/GMT+4', 'better-chat-support'),
                    'Etc/GMT+5'                        => esc_html__('Etc/GMT+5', 'better-chat-support'),
                    'Etc/GMT+6'                        => esc_html__('Etc/GMT+6', 'better-chat-support'),
                    'Etc/GMT+7'                        => esc_html__('Etc/GMT+7', 'better-chat-support'),
                    'Etc/GMT+8'                        => esc_html__('Etc/GMT+8', 'better-chat-support'),
                    'Etc/GMT+9'                        => esc_html__('Etc/GMT+9', 'better-chat-support'),
                    'Etc/GMT-0'                        => esc_html__('Etc/GMT-0', 'better-chat-support'),
                    'Etc/GMT-1'                        => esc_html__('Etc/GMT-1', 'better-chat-support'),
                    'Etc/GMT-10'                       => esc_html__('Etc/GMT-10', 'better-chat-support'),
                    'Etc/GMT-11'                       => esc_html__('Etc/GMT-11', 'better-chat-support'),
                    'Etc/GMT-12'                       => esc_html__('Etc/GMT-12', 'better-chat-support'),
                    'Etc/GMT-13'                       => esc_html__('Etc/GMT-13', 'better-chat-support'),
                    'Etc/GMT-14'                       => esc_html__('Etc/GMT-14', 'better-chat-support'),
                    'Etc/GMT-2'                        => esc_html__('Etc/GMT-2', 'better-chat-support'),
                    'Etc/GMT-3'                        => esc_html__('Etc/GMT-3', 'better-chat-support'),
                    'Etc/GMT-4'                        => esc_html__('Etc/GMT-4', 'better-chat-support'),
                    'Etc/GMT-5'                        => esc_html__('Etc/GMT-5', 'better-chat-support'),
                    'Etc/GMT-6'                        => esc_html__('Etc/GMT-6', 'better-chat-support'),
                    'Etc/GMT-7'                        => esc_html__('Etc/GMT-7', 'better-chat-support'),
                    'Etc/GMT-8'                        => esc_html__('Etc/GMT-8', 'better-chat-support'),
                    'Etc/GMT-9'                        => esc_html__('Etc/GMT-9', 'better-chat-support'),
                    'Etc/GMT0'                         => esc_html__('Etc/GMT0', 'better-chat-support'),
                    'Etc/Greenwich'                    => esc_html__('Etc/Greenwich', 'better-chat-support'),
                    'Etc/UCT'                          => esc_html__('Etc/UCT', 'better-chat-support'),
                    'Etc/UTC'                          => esc_html__('Etc/UTC', 'better-chat-support'),
                    'Etc/Universal'                    => esc_html__('Etc/Universal', 'better-chat-support'),
                    'Etc/Zulu'                         => esc_html__('Etc/Zulu', 'better-chat-support'),
                    'Europe/Amsterdam'                 => esc_html__('Europe/Amsterdam', 'better-chat-support'),
                    'Europe/Andorra'                   => esc_html__('Europe/Andorra', 'better-chat-support'),
                    'Europe/Astrakhan'                 => esc_html__('Europe/Astrakhan', 'better-chat-support'),
                    'Europe/Athens'                    => esc_html__('Europe/Athens', 'better-chat-support'),
                    'Europe/Belfast'                   => esc_html__('Europe/Belfast', 'better-chat-support'),
                    'Europe/Belgrade'                  => esc_html__('Europe/Belgrade', 'better-chat-support'),
                    'Europe/Berlin'                    => esc_html__('Europe/Berlin', 'better-chat-support'),
                    'Europe/Bratislava'                => esc_html__('Europe/Bratislava', 'better-chat-support'),
                    'Europe/Brussels'                  => esc_html__('Europe/Brussels', 'better-chat-support'),
                    'Europe/Bucharest'                 => esc_html__('Europe/Bucharest', 'better-chat-support'),
                    'Europe/Budapest'                  => esc_html__('Europe/Budapest', 'better-chat-support'),
                    'Europe/Busingen'                  => esc_html__('Europe/Busingen', 'better-chat-support'),
                    'Europe/Chisinau'                  => esc_html__('Europe/Chisinau', 'better-chat-support'),
                    'Europe/Copenhagen'                => esc_html__('Europe/Copenhagen', 'better-chat-support'),
                    'Europe/Dublin'                    => esc_html__('Europe/Dublin', 'better-chat-support'),
                    'Europe/Gibraltar'                 => esc_html__('Europe/Gibraltar', 'better-chat-support'),
                    'Europe/Guernsey'                  => esc_html__('Europe/Guernsey', 'better-chat-support'),
                    'Europe/Helsinki'                  => esc_html__('Europe/Helsinki', 'better-chat-support'),
                    'Europe/Isle_of_Man'               => esc_html__('Europe/Isle_of_Man', 'better-chat-support'),
                    'Europe/Istanbul'                  => esc_html__('Europe/Istanbul', 'better-chat-support'),
                    'Europe/Jersey'                    => esc_html__('Europe/Jersey', 'better-chat-support'),
                    'Europe/Kaliningrad'               => esc_html__('Europe/Kaliningrad', 'better-chat-support'),
                    'Europe/Kiev'                      => esc_html__('Europe/Kiev', 'better-chat-support'),
                    'Europe/Kirov'                     => esc_html__('Europe/Kirov', 'better-chat-support'),
                    'Europe/Lisbon'                    => esc_html__('Europe/Lisbon', 'better-chat-support'),
                    'Europe/Ljubljana'                 => esc_html__('Europe/Ljubljana', 'better-chat-support'),
                    'Europe/London'                    => esc_html__('Europe/London', 'better-chat-support'),
                    'Europe/Luxembourg'                => esc_html__('Europe/Luxembourg', 'better-chat-support'),
                    'Europe/Madrid'                    => esc_html__('Europe/Madrid', 'better-chat-support'),
                    'Europe/Malta'                     => esc_html__('Europe/Malta', 'better-chat-support'),
                    'Europe/Mariehamn'                 => esc_html__('Europe/Mariehamn', 'better-chat-support'),
                    'Europe/Minsk'                     => esc_html__('Europe/Minsk', 'better-chat-support'),
                    'Europe/Monaco'                    => esc_html__('Europe/Monaco', 'better-chat-support'),
                    'Europe/Moscow'                    => esc_html__('Europe/Moscow', 'better-chat-support'),
                    'Europe/Nicosia'                   => esc_html__('Europe/Nicosia', 'better-chat-support'),
                    'Europe/Oslo'                      => esc_html__('Europe/Oslo', 'better-chat-support'),
                    'Europe/Paris'                     => esc_html__('Europe/Paris', 'better-chat-support'),
                    'Europe/Podgorica'                 => esc_html__('Europe/Podgorica', 'better-chat-support'),
                    'Europe/Prague'                    => esc_html__('Europe/Prague', 'better-chat-support'),
                    'Europe/Riga'                      => esc_html__('Europe/Riga', 'better-chat-support'),
                    'Europe/Rome'                      => esc_html__('Europe/Rome', 'better-chat-support'),
                    'Europe/Samara'                    => esc_html__('Europe/Samara', 'better-chat-support'),
                    'Europe/San_Marino'                => esc_html__('Europe/San_Marino', 'better-chat-support'),
                    'Europe/Sarajevo'                  => esc_html__('Europe/Sarajevo', 'better-chat-support'),
                    'Europe/Saratov'                   => esc_html__('Europe/Saratov', 'better-chat-support'),
                    'Europe/Simferopol'                => esc_html__('Europe/Simferopol', 'better-chat-support'),
                    'Europe/Skopje'                    => esc_html__('Europe/Skopje', 'better-chat-support'),
                    'Europe/Sofia'                     => esc_html__('Europe/Sofia', 'better-chat-support'),
                    'Europe/Stockholm'                 => esc_html__('Europe/Stockholm', 'better-chat-support'),
                    'Europe/Tallinn'                   => esc_html__('Europe/Tallinn', 'better-chat-support'),
                    'Europe/Tirane'                    => esc_html__('Europe/Tirane', 'better-chat-support'),
                    'Europe/Tiraspol'                  => esc_html__('Europe/Tiraspol', 'better-chat-support'),
                    'Europe/Ulyanovsk'                 => esc_html__('Europe/Ulyanovsk', 'better-chat-support'),
                    'Europe/Uzhgorod'                  => esc_html__('Europe/Uzhgorod', 'better-chat-support'),
                    'Europe/Vaduz'                     => esc_html__('Europe/Vaduz', 'better-chat-support'),
                    'Europe/Vatican'                   => esc_html__('Europe/Vatican', 'better-chat-support'),
                    'Europe/Vienna'                    => esc_html__('Europe/Vienna', 'better-chat-support'),
                    'Europe/Vilnius'                   => esc_html__('Europe/Vilnius', 'better-chat-support'),
                    'Europe/Volgograd'                 => esc_html__('Europe/Volgograd', 'better-chat-support'),
                    'Europe/Warsaw'                    => esc_html__('Europe/Warsaw', 'better-chat-support'),
                    'Europe/Zagreb'                    => esc_html__('Europe/Zagreb', 'better-chat-support'),
                    'Europe/Zaporozhye'                => esc_html__('Europe/Zaporozhye', 'better-chat-support'),
                    'Europe/Zurich'                    => esc_html__('Europe/Zurich', 'better-chat-support'),
                    'GB'                               => esc_html__('GB', 'better-chat-support'),
                    'GB-Eire'                          => esc_html__('GB-Eire', 'better-chat-support'),
                    'GMT'                              => esc_html__('GMT', 'better-chat-support'),
                    'GMT+0'                            => esc_html__('GMT+0', 'better-chat-support'),
                    'GMT-0'                            => esc_html__('GMT-0', 'better-chat-support'),
                    'GMT0'                             => esc_html__('GMT0', 'better-chat-support'),
                    'Greenwich'                        => esc_html__('Greenwich', 'better-chat-support'),
                    'HST'                              => esc_html__('HST', 'better-chat-support'),
                    'Hongkong'                         => esc_html__('Hongkong', 'better-chat-support'),
                    'Iceland'                          => esc_html__('Iceland', 'better-chat-support'),
                    'Indian/Antananarivo'              => esc_html__('Indian/Antananarivo', 'better-chat-support'),
                    'Indian/Chagos'                    => esc_html__('Indian/Chagos', 'better-chat-support'),
                    'Indian/Christmas'                 => esc_html__('Indian/Christmas', 'better-chat-support'),
                    'Indian/Cocos'                     => esc_html__('Indian/Cocos', 'better-chat-support'),
                    'Indian/Comoro'                    => esc_html__('Indian/Comoro', 'better-chat-support'),
                    'Indian/Kerguelen'                 => esc_html__('Indian/Kerguelen', 'better-chat-support'),
                    'Indian/Mahe'                      => esc_html__('Indian/Mahe', 'better-chat-support'),
                    'Indian/Maldives'                  => esc_html__('Indian/Maldives', 'better-chat-support'),
                    'Indian/Mauritius'                 => esc_html__('Indian/Mauritius', 'better-chat-support'),
                    'Indian/Mayotte'                   => esc_html__('Indian/Mayotte', 'better-chat-support'),
                    'Indian/Reunion'                   => esc_html__('Indian/Reunion', 'better-chat-support'),
                    'Iran'                             => esc_html__('Iran', 'better-chat-support'),
                    'Israel'                           => esc_html__('Israel', 'better-chat-support'),
                    'Jamaica'                          => esc_html__('Jamaica', 'better-chat-support'),
                    'Japan'                            => esc_html__('Japan', 'better-chat-support'),
                    'Kwajalein'                        => esc_html__('Kwajalein', 'better-chat-support'),
                    'Libya'                            => esc_html__('Libya', 'better-chat-support'),
                    'MET'                              => esc_html__('MET', 'better-chat-support'),
                    'MST'                              => esc_html__('MST', 'better-chat-support'),
                    'MST7MDT'                          => esc_html__('MST7MDT', 'better-chat-support'),
                    'Mexico/BajaNorte'                 => esc_html__('Mexico/BajaNorte', 'better-chat-support'),
                    'Mexico/BajaSur'                   => esc_html__('Mexico/BajaSur', 'better-chat-support'),
                    'Mexico/General'                   => esc_html__('Mexico/General', 'better-chat-support'),
                    'NZ'                               => esc_html__('NZ', 'better-chat-support'),
                    'NZ-CHAT'                          => esc_html__('NZ-CHAT', 'better-chat-support'),
                    'Navajo'                           => esc_html__('Navajo', 'better-chat-support'),
                    'PRC'                              => esc_html__('PRC', 'better-chat-support'),
                    'PST8PDT'                          => esc_html__('PST8PDT', 'better-chat-support'),
                    'Pacific/Apia'                     => esc_html__('Pacific/Apia', 'better-chat-support'),
                    'Pacific/Auckland'                 => esc_html__('Pacific/Auckland', 'better-chat-support'),
                    'Pacific/Bougainville'             => esc_html__('Pacific/Bougainville', 'better-chat-support'),
                    'Pacific/Chatham'                  => esc_html__('Pacific/Chatham', 'better-chat-support'),
                    'Pacific/Chuuk'                    => esc_html__('Pacific/Chuuk', 'better-chat-support'),
                    'Pacific/Easter'                   => esc_html__('Pacific/Easter', 'better-chat-support'),
                    'Pacific/Efate'                    => esc_html__('Pacific/Efate', 'better-chat-support'),
                    'Pacific/Enderbury'                => esc_html__('Pacific/Enderbury', 'better-chat-support'),
                    'Pacific/Fakaofo'                  => esc_html__('Pacific/Fakaofo', 'better-chat-support'),
                    'Pacific/Fiji'                     => esc_html__('Pacific/Fiji', 'better-chat-support'),
                    'Pacific/Funafuti'                 => esc_html__('Pacific/Funafuti', 'better-chat-support'),
                    'Pacific/Galapagos'                => esc_html__('Pacific/Galapagos', 'better-chat-support'),
                    'Pacific/Gambier'                  => esc_html__('Pacific/Gambier', 'better-chat-support'),
                    'Pacific/Guadalcanal'              => esc_html__('Pacific/Guadalcanal', 'better-chat-support'),
                    'Pacific/Guam'                     => esc_html__('Pacific/Guam', 'better-chat-support'),
                    'Pacific/Honolulu'                 => esc_html__('Pacific/Honolulu', 'better-chat-support'),
                    'Pacific/Johnston'                 => esc_html__('Pacific/Johnston', 'better-chat-support'),
                    'Pacific/Kiritimati'               => esc_html__('Pacific/Kiritimati', 'better-chat-support'),
                    'Pacific/Kosrae'                   => esc_html__('Pacific/Kosrae', 'better-chat-support'),
                    'Pacific/Kwajalein'                => esc_html__('Pacific/Kwajalein', 'better-chat-support'),
                    'Pacific/Majuro'                   => esc_html__('Pacific/Majuro', 'better-chat-support'),
                    'Pacific/Marquesas'                => esc_html__('Pacific/Marquesas', 'better-chat-support'),
                    'Pacific/Midway'                   => esc_html__('Pacific/Midway', 'better-chat-support'),
                    'Pacific/Nauru'                    => esc_html__('Pacific/Nauru', 'better-chat-support'),
                    'Pacific/Niue'                     => esc_html__('Pacific/Niue', 'better-chat-support'),
                    'Pacific/Norfolk'                  => esc_html__('Pacific/Norfolk', 'better-chat-support'),
                    'Pacific/Noumea'                   => esc_html__('Pacific/Noumea', 'better-chat-support'),
                    'Pacific/Pago_Pago'                => esc_html__('Pacific/Pago_Pago', 'better-chat-support'),
                    'Pacific/Palau'                    => esc_html__('Pacific/Palau', 'better-chat-support'),
                    'Pacific/Pitcairn'                 => esc_html__('Pacific/Pitcairn', 'better-chat-support'),
                    'Pacific/Pohnpei'                  => esc_html__('Pacific/Pohnpei', 'better-chat-support'),
                    'Pacific/Ponape'                   => esc_html__('Pacific/Ponape', 'better-chat-support'),
                    'Pacific/Port_Moresby'             => esc_html__('Pacific/Port_Moresby', 'better-chat-support'),
                    'Pacific/Rarotonga'                => esc_html__('Pacific/Rarotonga', 'better-chat-support'),
                    'Pacific/Saipan'                   => esc_html__('Pacific/Saipan', 'better-chat-support'),
                    'Pacific/Samoa'                    => esc_html__('Pacific/Samoa', 'better-chat-support'),
                    'Pacific/Tahiti'                   => esc_html__('Pacific/Tahiti', 'better-chat-support'),
                    'Pacific/Tarawa'                   => esc_html__('Pacific/Tarawa', 'better-chat-support'),
                    'Pacific/Tongatapu'                => esc_html__('Pacific/Tongatapu', 'better-chat-support'),
                    'Pacific/Truk'                     => esc_html__('Pacific/Truk', 'better-chat-support'),
                    'Pacific/Wake'                     => esc_html__('Pacific/Wake', 'better-chat-support'),
                    'Pacific/Wallis'                   => esc_html__('Pacific/Wallis', 'better-chat-support'),
                    'Pacific/Yap'                      => esc_html__('Pacific/Yap', 'better-chat-support'),
                    'Poland'                           => esc_html__('Poland', 'better-chat-support'),
                    'Portugal'                         => esc_html__('Portugal', 'better-chat-support'),
                    'ROC'                              => esc_html__('ROC', 'better-chat-support'),
                    'ROK'                              => esc_html__('ROK', 'better-chat-support'),
                    'Singapore'                        => esc_html__('Singapore', 'better-chat-support'),
                    'Turkey'                           => esc_html__('Turkey', 'better-chat-support'),
                    'UCT'                              => esc_html__('UCT', 'better-chat-support'),
                    'US/Alaska'                        => esc_html__('US/Alaska', 'better-chat-support'),
                    'US/Aleutian'                      => esc_html__('US/Aleutian', 'better-chat-support'),
                    'US/Arizona'                       => esc_html__('US/Arizona', 'better-chat-support'),
                    'US/Central'                       => esc_html__('US/Central', 'better-chat-support'),
                    'US/East-Indiana'                  => esc_html__('US/East-Indiana', 'better-chat-support'),
                    'US/Eastern'                       => esc_html__('US/Eastern', 'better-chat-support'),
                    'US/Hawaii'                        => esc_html__('US/Hawaii', 'better-chat-support'),
                    'US/Indiana-Starke'                => esc_html__('US/Indiana-Starke', 'better-chat-support'),
                    'US/Michigan'                      => esc_html__('US/Michigan', 'better-chat-support'),
                    'US/Mountain'                      => esc_html__('US/Mountain', 'better-chat-support'),
                    'US/Pacific'                       => esc_html__('US/Pacific', 'better-chat-support'),
                    'US/Pacific-New'                   => esc_html__('US/Pacific-New', 'better-chat-support'),
                    'US/Samoa'                         => esc_html__('US/Samoa', 'better-chat-support'),
                    'UTC'                              => esc_html__('UTC', 'better-chat-support'),
                    'Universal'                        => esc_html__('Universal', 'better-chat-support'),
                    'W-SU'                             => esc_html__('W-SU', 'better-chat-support'),
                    'WET'                              => esc_html__('WET', 'better-chat-support'),
                ),
            )
        );

        // start sunday popover
        $this->add_control(
            'popover-sunday',
            array(
                'label' => esc_html__('Sunday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'sunday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-sunday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'sunday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-sunday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end sunday popover

        // start monday popover
        $this->add_control(
            'popover-monday',
            array(
                'label' => esc_html__('Monday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'monday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-monday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'monday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-monday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end monday popover

        // start tuesday popover
        $this->add_control(
            'popover-tuesday',
            array(
                'label' => esc_html__('Tuesday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'tuesday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-tuesday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'tuesday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-tuesday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end tuesday popover

        // start wednesday popover
        $this->add_control(
            'popover-wednesday',
            array(
                'label' => esc_html__('Wednesday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'wednesday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-wednesday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'wednesday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-wednesday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end wednesday popover

        // start sunday popover
        $this->add_control(
            'popover-thursday',
            array(
                'label' => esc_html__('Thursday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'thursday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-thursday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'thursday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-thursday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end thursday popover

        // start sunday popover
        $this->add_control(
            'popover-friday',
            array(
                'label' => esc_html__('Friday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'friday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-friday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'friday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-friday' => 'yes',
                ),
            )
        );

        $this->end_popover();
        // end friday popover

        $this->add_control(
            'popover-saturday',
            array(
                'label' => esc_html__('Saturday', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            )
        );

        $this->start_popover();
        $this->add_control(
            'saturday__start',
            array(
                'label'       => esc_html__('Start time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '00:00',
                'condition'   => array(
                    'popover-saturday' => 'yes',
                ),
            )
        );

        $this->add_control(
            'saturday__end',
            array(
                'label'       => esc_html__('End time', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'label_block' => false,
                'default'     => '23:59',
                'condition'   => array(
                    'popover-saturday' => 'yes',
                ),
            )
        );
        $this->end_popover();

        $this->end_controls_section(); // End section top content

        $this->start_controls_section(
            'mcs__appearance',
            array(
                'label' => esc_html__('Appearance settings', 'better-chat-support'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'visibility',
            array(
                'label'       => esc_html__('Visibility on', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'label_block' => false,
                'default'     => '',
                'options'     => array(
                    ''    => esc_html__('Everywhere', 'better-chat-support'),
                    'desktop'       => esc_html__('Desktops only', 'better-chat-support'),
                    'tablet'        => esc_html__('Tablets only', 'better-chat-support'),
                    'mobile'        => esc_html__('Mobile only', 'better-chat-support'),
                ),

            )
        );

        $this->add_control(
            'button__sizes',
            array(
                'label'       => esc_html__('Size', 'better-chat-support'),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'label_block' => false,
                'default'     => '1',
                'options'     => array(
                    '0.7' => esc_html__('XS', 'better-chat-support'),
                    '0.8' => esc_html__('S', 'better-chat-support'),
                    '1' => esc_html__('M', 'better-chat-support'),
                    '1.2' => esc_html__('L', 'better-chat-support'),
                    '1.4' => esc_html__('XL', 'better-chat-support'),
                    '1.6' => esc_html__('XXL', 'better-chat-support'),
                ),
            )
        );

        $this->add_control(
            'bg__color',
            array(
                'label'     => esc_html__('Background color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0066ff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn' => 'background-color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'bg__color__hover',
            array(
                'label'     => esc_html__('Hover backgound color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0084ff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover' => 'background-color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'text__color',
            array(
                'label'     => esc_html__('Text color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn' => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'text__color__hover',
            array(
                'label'     => esc_html__('Hover text color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover' => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_responsive_control(
            'padding',
            array(
                'label'      => esc_html__('Padding', 'better-chat-support'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'default' => array(
                    'top'      => '5',
                    'right'    => '15',
                    'bottom'   => '5',
                    'left'     => '6',
                    'unit'     => 'px',
                    'isLinked' => true,
                ),
            )
        );
        $this->add_responsive_control(
            'border-radius',
            array(
                'label'      => esc_html__('Border Radius', 'better-chat-support'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'default' => array(
                    'top'      => '50',
                    'right'    => '50',
                    'bottom'   => '50',
                    'left'     => '50',
                    'unit'     => 'px',
                    'isLinked' => true,
                ),
            )
        );

        $this->start_controls_tabs('border_tabs');
        $this->start_controls_tab(
            'button_border_normal',
            ['label' => __('Normal', 'better-chat-support')]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'           => 'border',
                'label'          => esc_html__('Border', 'better-chat-support'),
                'selector'       => '{{WRAPPER}} .mSupport_button.mSupport_woo_btn',
                'fields_options' => array(
                    'border' => array(
                        'label'   => esc_html__('Border', 'better-chat-support'),
                        'default' => 'solid',
                    ),
                    'width'  => array(
                        'default' => array(
                            'top'      => '0',
                            'right'    => '0',
                            'bottom'   => '0',
                            'left'     => '0',
                            'isLinked' => false,
                        ),
                    ),
                    'color'  => array(
                        'default' => '#0066ff',
                    ),
                ),
            )
        );

        $this->end_controls_tab();
        $this->start_controls_tab(
            'button_border_hover',
            ['label' => __('Hover', 'better-chat-support')]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'           => 'border__hover',
                'label'          => esc_html__('Hover border', 'better-chat-support'),
                'default'        => '#0084ff',
                'selector'       => '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover',
                'fields_options' => array(
                    'border' => array(
                        'label'   => esc_html__('Hover border', 'better-chat-support'),
                        'default' => 'solid',
                    ),
                    'width'  => array(
                        'default' => array(
                            'top'      => '0',
                            'right'    => '0',
                            'bottom'   => '0',
                            'left'     => '0',
                            'isLinked' => false,
                        ),
                    ),
                    'color'  => array(
                        'default' => '#0084ff',
                    ),
                ),
            )
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'icon-border-radius',
            array(
                'label'      => esc_html__('Icon Border Radius', 'better-chat-support'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn img, {{WRAPPER}} .mSupport_button.mSupport_woo_btn .bubble__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'default' => array(
                    'top'      => '50',
                    'right'    => '50',
                    'bottom'   => '50',
                    'left'     => '50',
                    'unit'     => 'px',
                    'isLinked' => true,
                ),
            )
        );
        $this->start_controls_tabs('icon_border_tabs');
        $this->start_controls_tab(
            'icon_border_normal',
            ['label' => __('Normal', 'better-chat-support')]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'           => 'icon_border',
                'label'          => esc_html__('Icon Border', 'better-chat-support'),
                'selector'       => '{{WRAPPER}} .mSupport_button.mSupport_woo_btn img, {{WRAPPER}} .mSupport_button.mSupport_woo_btn .bubble__icon',
                'fields_options' => array(
                    'border' => array(
                        'label'   => esc_html__('Icon Border', 'better-chat-support'),
                        'default' => 'solid',
                    ),
                    'width'  => array(
                        'default' => array(
                            'top'      => '0',
                            'right'    => '0',
                            'bottom'   => '0',
                            'left'     => '0',
                            'isLinked' => false,
                        ),
                    ),
                    'color'  => array(
                        'default' => '#ffffff',
                    ),
                ),
            )
        );

        $this->end_controls_tab();

        /* HOVER */
        $this->start_controls_tab(
            'icon_border_hover',
            ['label' => __('Hover', 'better-chat-support')]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'           => 'icon_border__hover',
                'label'          => esc_html__('Icon Hover border', 'better-chat-support'),
                'selector'       => '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover img, {{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover .bubble__icon',
                'fields_options' => array(
                    'border' => array(
                        'label'   => esc_html__('Hover border', 'better-chat-support'),
                        'default' => 'solid',
                    ),
                    'width'  => array(
                        'default' => array(
                            'top'      => '0',
                            'right'    => '0',
                            'bottom'   => '0',
                            'left'     => '0',
                            'isLinked' => false,
                        ),
                    ),
                    'color'  => array(
                        'default' => '#ffffff',
                    ),
                ),
            )
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'show__button__icon',
            array(
                'label'        => esc_html__('Button Icon', 'better-chat-support'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'better-chat-support'),
                'label_off'    => esc_html__('No', 'better-chat-support'),
                'condition'    => array(
                    'style' => '2',
                ),
                'default'     => 'yes',
            )
        );
        $this->add_control(
            'button__icon',
            [
                'label' => esc_html__('Change icon', 'better-chat-support'),
                'type'  => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'fab fa-facebook-messenger',
                ],
                'condition' => [
                    'show__button__icon' => 'yes',
                    'style' => '2',
                ],
                'recommended' => [
                    'fa-solid' => [
						'envelope',
						'envelope-open',
						'facebook-messenger',
					],
					'fa-regular' => [
						'envelope',
						'envelope-open',
					],
                ],
            ]
        );

        $this->add_control(
            'icon__color',
            array(
                'label'     => esc_html__('Icon Color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'condition' => [
                    'show__button__icon' => 'yes',
                    'style' => '2',
                ],
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn .bubble__icon svg' => 'fill: {{VALUE}}',
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn .bubble__icon i' => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'icon__color_hover',
            array(
                'label'     => esc_html__('Hover Icon Color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'condition' => [
                    'show__button__icon' => 'yes',
                    'style' => '2',
                ],
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover .bubble__icon svg' => 'fill: {{VALUE}}',
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover .bubble__icon i' => 'color: {{VALUE}}',
                ),
            )
        );


        $this->add_control(
            'show__icon__bg__color',
            array(
                'label'        => esc_html__('Icon Background', 'better-chat-support'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'better-chat-support'),
                'label_off'    => esc_html__('No', 'better-chat-support'),
                'condition'    => array(
                    'show__button__icon' => 'yes',
                    'style' => '2',
                ),
            )
        );
        $this->add_control(
            'icon__bg__color',
            array(
                'label'     => esc_html__('Background color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn .bubble__icon.icon_bg' => 'background-color: {{VALUE}}',
                ),
                'condition' => [
                    'show__button__icon' => 'yes',
                    'show__icon__bg__color' => 'yes',
                    'style' => '2',
                ],
            )
        );

        $this->add_control(
            'icon__bg__color_hover',
            array(
                'label'     => esc_html__('Hover background color', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mSupport_button.mSupport_woo_btn:hover .bubble__icon.icon_bg' => 'background-color: {{VALUE}}',
                ),
                'condition' => [
                    'show__button__icon' => 'yes',
                    'show__icon__bg__color' => 'yes',
                    'style' => '2',
                ],
            )
        );

        $this->add_control(
            'text_align',
            array(
                'label'     => esc_html__('Alignment', 'better-chat-support'),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => array(
                    'left'   => array(
                        'title' => esc_html__('Left', 'better-chat-support'),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__('Center', 'better-chat-support'),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right'  => array(
                        'title' => esc_html__('Right', 'better-chat-support'),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default'   => 'left',
                'toggle'    => true,
                'selectors' => array(
                    '{{WRAPPER}} .button-wrapper' => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section(); // End section top content
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        // button settings
        $style      = $settings['style'];
        $facebook_id     = $settings['fbid'];
        $timezone   = $settings['timezone'];
        $visibility = $settings['visibility'];
        $show__button__icon       = isset($settings['show__button__icon']) ? $settings['show__button__icon'] : '';
        $icon       = isset($settings['button__icon']['value']) ? $settings['button__icon']['value'] : '';
        $icon__bg   = $settings['show__icon__bg__color'];
        $sizes      = $settings['button__sizes'];
        $photo      = isset($settings['agent__photo']['url']) ? $settings['agent__photo']['url'] : '';

        $button_top_label        = $settings['button_top_label'];
        $button_main_label   = $settings['button_main_label'];
        $online_text  = $settings['online__text'];
        $offline_text = $settings['offline__text'];
        $open_link_new_window = $settings['open_link_new_window'];
        $open_in_new_tab = $open_link_new_window ? '_blank' : '_self';
        // availablity

        $sunday    = ($settings['sunday__start'] ? $settings['sunday__start'] : '0:00') . '-' . ($settings['sunday__end'] ? $settings['sunday__end'] : '23:59');
        $monday    = ($settings['monday__start'] ? $settings['monday__start'] : '0:00') . '-' . ($settings['monday__end'] ? $settings['monday__end'] : '23:59');
        $tuesday   = ($settings['tuesday__start'] ? $settings['tuesday__start'] : '0:00') . '-' . ($settings['tuesday__end'] ? $settings['tuesday__end'] : '23:59');
        $wednesday = ($settings['wednesday__start'] ? $settings['wednesday__start'] : '0:00') . '-' . ($settings['wednesday__end'] ? $settings['wednesday__end'] : '23:59');
        $thursday  = ($settings['thursday__start'] ? $settings['thursday__start'] : '0:00') . '-' . ($settings['thursday__end'] ? $settings['thursday__end'] : '23:59');
        $friday    = ($settings['friday__start'] ? $settings['friday__start'] : '0:00') . '-' . ($settings['friday__end'] ? $settings['friday__end'] : '23:59');
        $saturday  = ($settings['saturday__start'] ? $settings['saturday__start'] : '0:00') . '-' . ($settings['saturday__end'] ? $settings['saturday__end'] : '23:59');
        $mSupport_icon = $icon ? $icon : 'fab fa-facebook-messenger';
        $options = get_option('cwp_option');
        $ch_settings = get_option('ch_settings');
        $url = 'https://www.m.me/' . $facebook_id;
?>
        <?php if ('1' === $style) : ?>
            <div class="button-wrapper">
                <div style="--mSupport-btn-scale: <?php echo esc_attr($sizes) ?>;" <?php if ($timezone) { ?> data-timezone="<?php echo esc_attr($timezone); ?>" <?php } ?> class="mSupport_button mSupport_woo_btn mSupport_button_advance mSupportButtons avatar-active <?php echo esc_attr($visibility); ?>" data-btnavailablety='{ "sunday":"<?php echo esc_attr($sunday); ?>", "monday":"<?php echo esc_attr($monday); ?>", "tuesday":"<?php echo esc_attr($tuesday); ?>", "wednesday":"<?php echo esc_attr($wednesday); ?>", "thursday":"<?php echo esc_attr($thursday); ?>", "friday":"<?php echo esc_attr($friday); ?>", "saturday":"<?php echo esc_attr($saturday); ?>" }'>
                    <?php if ($photo) { ?>
                        <img src="<?php echo esc_attr($photo); ?>" />
                    <?php } ?>
                    <div class="info-wrapper">
                        <?php if ($button_top_label) { ?>
                            <div class="info">
                                <?php echo esc_html($button_top_label); ?></div>
                        <?php }
                        if ($button_main_label) { ?>
                            <div class="mSupport_title"><?php echo esc_html($button_main_label); ?></div>
                        <?php }
                        if ($online_text) { ?>
                            <div class="online"><?php echo esc_html($online_text); ?></div>
                        <?php }
                        if ($offline_text) { ?>
                            <div class="offline"><?php echo esc_html($offline_text); ?></div>
                        <?php } ?>
                    </div>
                    <a href="<?php echo esc_attr($url); ?>" target="<?php echo esc_attr($open_in_new_tab); ?>"></a>
                </div>
            </div>
        <?php else : ?>
            <div class="button-wrapper">
                <a style="--mSupport-btn-scale: <?php echo esc_attr($sizes) ?>;" href="<?php echo esc_attr($url); ?>" class="mSupport_button mSupport_woo_btn mSupportButtons <?php echo esc_attr($visibility); ?>" target="<?php echo esc_attr($open_in_new_tab); ?>" data-btnavailablety='{ "sunday":"<?php echo esc_attr($sunday); ?>", "monday":"<?php echo esc_attr($monday); ?>", "tuesday":"<?php echo esc_attr($tuesday); ?>", "wednesday":"<?php echo esc_attr($wednesday); ?>", "thursday":"<?php echo esc_attr($thursday); ?>", "friday":"<?php echo esc_attr($friday); ?>", "saturday":"<?php echo esc_attr($saturday); ?>" }'>

                    <?php
                    if ($show__button__icon && !empty($icon)) {
                        echo '<div class="bubble__icon ' . esc_attr($icon__bg == true ? 'icon_bg' : '') . '">';
                        \Elementor\Icons_Manager::render_icon(
                            $settings['button__icon'],
                            ['aria-hidden' => 'true']
                        );
                        echo '</div>';
                    }
                    ?>
                    <?php if ($button_main_label) { ?>
                        <span><?php echo esc_html($button_main_label); ?></span><?php } ?>
                </a>
            </div>
        <?php endif; ?>
<?php
    }
}
