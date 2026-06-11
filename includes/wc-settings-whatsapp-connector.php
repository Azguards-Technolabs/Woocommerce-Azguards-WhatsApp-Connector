<?php
/**
 * WhatsApp Connector Settings Page for WooCommerce.
 *
 * @package WhatsApp_Connector
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WC_Settings_WhatsApp_Connector
 */
class WC_Settings_WhatsApp_Connector extends WC_Settings_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id    = 'whatsapp_connector';
        $this->label = __( 'WhatsApp Connector', 'whatsapp-connector' );
        parent::__construct();
    }

    /**
     * Get plugin settings.
     *
     * @return array
     */
    public function get_settings() {
        // You can define your full settings array here.
        return array(
            // Example:
            // array(
            //     'title' => __( 'Section Title', 'whatsapp-connector' ),
            //     'type'  => 'title',
            //     'id'    => 'wa_settings_section',
            // ),
        );
    }

    /**
     * Output the settings HTML.
     */
    public function output() {
        WC_Admin_Settings::output_fields( $this->get_settings() );
    }

    /**
     * Save settings.
     */
    public function save() {
        $settings = $this->get_settings();

        foreach ( $settings as $setting ) {
            if (
                isset( $setting['type'], $setting['id'] ) &&
                'wa_custom_table' === $setting['type'] &&
                isset( $_POST[ $setting['id'] ] )
            ) {
                update_option( $setting['id'], wp_unslash( $_POST[ $setting['id'] ] ) );
            }
        }

        WC_Admin_Settings::save_fields( $settings );
    }
}
