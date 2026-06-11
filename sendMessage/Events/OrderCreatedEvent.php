<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Order_Created_Event implements WA_Event_Interface {
    
    public function execute( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Only fire if the connector and specific event are enabled.
        if ( get_option( 'wa_enable_connector' ) !== 'yes' || get_option( 'wa_enable_order_created' ) !== 'yes' ) {
            return;
        }

        $template_name = get_option( 'wa_template_order_created_template_name' );
        if ( ! $template_name ) {
            return;
        }

        $body_template = get_option( 'wa_template_order_created_body_template' );
        $processed_body = wa_process_magento_variables( $body_template, $order );

        $user_detail = wa_get_user_detail_data( $order );

        // Map Magento-style variables for the API call (index-based or named depending on API expectation)
        // Since we processed the body locally, we might send it as a whole or as placeholders.
        // Assuming the current send_message expects placeholders, we adjust.
        $variables = [
            'body' => $processed_body
        ];

        WA_Message::send_message( $variables, $template_name, 'Order Created', $user_detail );
    }
}
