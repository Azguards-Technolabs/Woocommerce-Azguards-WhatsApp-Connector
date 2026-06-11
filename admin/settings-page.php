<?php

defined( 'ABSPATH' ) || exit;

class WC_Settings_WhatsApp_Connector extends WC_Settings_Page {

    public function __construct() {
        $this->id    = 'whatsapp_connector';
        $this->label = __( 'WhatsApp Connector', 'whatsapp-connector' );
        parent::__construct();
    }

    /**
     * Get sections.
     *
     * @return array
     */
    public function get_sections() {
        $sections = array(
            ''          => __( 'General Configuration', 'whatsapp-connector' ),
            'templates' => __( 'Templates', 'whatsapp-connector' )
        );
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings() {
        global $current_section;
        $settings = [];

        if ( $current_section === 'templates' ) {
            $settings[] = [
                'title' => __( 'WhatsApp Templates Configuration', 'whatsapp-connector' ),
                'type'  => 'title',
                'id'    => 'wa_templates_config',
            ];

            // Generating custom fields for each hook type
            $hooks = [
                'order_created'    => 'Order Created Template',
                'order_invoice'    => 'Order Invoice Template',
                'order_shipment'   => 'Order Shipment Template',
                'order_cancellation' => 'Order Cancellation Template',
                'order_creditmemo' => 'Order Credit Memo Template'
            ];

            foreach($hooks as $hook => $label) {
                $settings[] = [
                    'type'  => 'wa_embedded_builder',
                    'id'    => 'wa_builder_' . $hook,
                    'hook'  => $hook,
                    'label' => $label
                ];
            }

            $settings[] = [
                'type' => 'sectionend',
                'id'   => 'wa_templates_config',
            ];
            return $settings;
        }

        $options = WA_Templates::get_templates();

        // General Configuration matches Magento Screenshot 4 exactly
        $settings[] = [
            'title' => __( 'General Configuration', 'whatsapp-connector' ),
            'type'  => 'title',
            'id'    => 'wa_general_config',
        ];

        $settings[] = [
            'title'   => __( 'WhatsAppConector Enable', 'whatsapp-connector' ),
            'id'      => 'wa_enable_connector',
            'type'    => 'select',
            'default' => 'yes',
            'options' => [ 'yes' => 'Yes', 'no'  => 'No' ],
        ];

        $settings[] = [
            'title'    => __( 'API Base URL', 'whatsapp-connector' ),
            'id'       => 'wa_template_api_url', /* using template as base for now */
            'type'     => 'wa_readonly',
            'desc'     => __( 'This value is read-only. Contact developer to change the API server.', 'whatsapp-connector' ),
            'default'  => 'https://whatatalk-api.azguardtech.com/',
        ];

        $settings[] = [
            'title'    => __( 'Authentication API URL', 'whatsapp-connector' ),
            'id'       => 'wa_auth_api_url',
            'type'     => 'wa_readonly',
            'default'  => 'https://whatatalk-auth.azguardtech.com/realms/azguards-whatsapp/protocol/openid-connect/token',
        ];

        $settings[] = [
            'title'    => __( 'Client Id', 'whatsapp-connector' ),
            'id'       => 'wa_client_id',
            'type'     => 'text',
            'default'  => 'azguards-magento-integration',
        ];

        $settings[] = [
            'title'    => __( 'Client secret', 'whatsapp-connector' ),
            'id'       => 'wa_client_secret',
            'type'     => 'password',
            'default'  => 'pEkdkm4dKjTcK4SOluavwVYz92tXqeva',
        ];

        $settings[] = [
            'title'    => __( 'Grant Type', 'whatsapp-connector' ),
            'id'       => 'wa_grant_type',
            'type'     => 'text',
            'default'  => 'client_credentials',
        ];

        $settings[] = [
            'title'    => __( 'Generate Authentication Credentials', 'whatsapp-connector' ),
            'id'       => 'wa_validate_credentials_button',
            'type'     => 'wa_validate_button',
        ];

        // Specific Event Toggles (Screenshot 4 logic)
        $toggles = [
            'wa_enable_order_created' => 'Enable Order Created WhatsApp Send Message',
            'wa_enable_order_invoice' => 'Enable Order Invoice WhatsApp Send Message',
            'wa_enable_order_shipment'=> 'Enable Order Shipment WhatsApp Send Message',
            'wa_enable_order_cancellation' => 'Enable Order Cancellation WhatsApp Send Message',
            'wa_enable_order_creditmemo' => 'Enable Order Credit Memo WhatsApp Send Message',
            'wa_enable_abandoned_cart' => 'Enable Abandoned Cart WhatsApp Send Message',
        ];

        foreach($toggles as $id => $label) {
            $settings[] = [
                'title'   => __( $label, 'whatsapp-connector' ),
                'id'      => $id,
                'type'    => 'select',
                'default' => 'yes',
                'options' => [ 'yes' => 'Yes', 'no'  => 'No' ],
            ];
        }

        $settings[] = [
            'type' => 'sectionend',
            'id'   => 'wa_general_config',
        ];

        return $settings;
    }

    public function output() {
        global $current_section;
        // Inject Custom Renderer
        add_action('woocommerce_admin_field_wa_embedded_builder', [$this, 'render_embedded_builder'], 10, 1);
        
        WC_Admin_Settings::output_fields( $this->get_settings() );
    }

    public function render_embedded_builder($value) {
        $hook = $value['hook'];
        $label = $value['label'];
        $accordion_id = 'wa_accordion_body_' . esc_attr($hook);
        ?>
        <tr valign="top">
            <td colspan="2" style="padding:0; padding-bottom: 20px;">
                <div style="border:1px solid #ccc; background:#fff; border-radius:3px;">
                    <div class="wa-accordion-header" onclick="jQuery('#<?php echo $accordion_id; ?>').slideToggle();" style="font-weight:bold; font-size:14px; padding:15px; border-bottom:1px solid #eee; background:#fafafa; color:#333; display:flex; justify-content:space-between; cursor:pointer;">
                        <span><?php echo esc_html($label); ?></span>
                        <span style="border:1px solid #ccc; border-radius:10px; padding:2px 8px; font-size:12px;">▼</span>
                    </div>
                    <div id="<?php echo $accordion_id; ?>" style="padding:20px; display:none;">
                        <!-- Embed Builder UI natively here! -->
                        <?php 
                        // Simulate setting up GET param for the included file
                        $_GET['hook'] = $hook;
                        include WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-settings-template-embed.php'; 
                        ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    public function save() {
        WC_Admin_Settings::save_fields( $this->get_settings() );
    }
}

add_action( 'woocommerce_admin_field_wa_readonly', function( $value ) {
    $option_value = get_option( $value['id'] );
    ?>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="<?php echo esc_attr( $value['id'] ); ?>">
                <?php echo esc_html( $value['title'] ); ?>
            </label>
        </th>
        <td class="forminp forminp-text">
            <input
                type="text"
                readonly
                id="<?php echo esc_attr( $value['id'] ); ?>"
                name="<?php echo esc_attr( $value['id'] ); ?>"
                value="<?php echo esc_attr( $option_value ); ?>"
                style="width: 400px; background: #f9f9f9; border: 1px solid #ccc;"
            />
            <?php if ( ! empty( $value['desc'] ) ) : ?>
                <p class="description"><?php echo esc_html( $value['desc'] ); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
} );
