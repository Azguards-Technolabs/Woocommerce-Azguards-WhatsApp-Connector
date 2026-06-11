<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Order_Completed_Event implements WA_Event_Interface {
    
    public function execute( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Only fire if the connector is globally enabled.
        if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
            return;
        }

        $template_id = get_option( 'wa_order_completed_template' );
        if ( ! $template_id ) {
            return; // No template mapped for this specific event
        }

        $variables   = wa_process_template_variables( 'wa_order_completed_table_data', $order );
        $user_detail = wa_get_user_detail_data( $order );

        WA_Message::send_message( $variables, $template_id, 'Order Completed/Shipped', $user_detail );
    }
}
