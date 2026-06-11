<?php

require_once plugin_dir_path( __FILE__ ) . 'Events/EventInterface.php';
require_once plugin_dir_path( __FILE__ ) . 'Events/OrderCreatedEvent.php';
require_once plugin_dir_path( __FILE__ ) . 'Events/OrderCompletedEvent.php';

/**
 * Common function to trigger WhatsApp message for an order event.
 */
function wa_trigger_order_whatsapp_event( $order_id, $event_key ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Check if connector and event are enabled
    if ( get_option( 'wa_enable_connector' ) !== 'yes' || get_option( "wa_enable_{$event_key}" ) !== 'yes' ) {
        return;
    }

    $template_name = get_option( "wa_template_{$event_key}_template_name" );
    $body_template = get_option( "wa_template_{$event_key}_body_template" );

    if ( ! $template_name || ! $body_template ) {
        return;
    }

    $processed_body = wa_process_magento_variables( $body_template, $order );
    $user_detail    = wa_get_user_detail_data( $order );

    $variables = [
        'body' => $processed_body
    ];

    WA_Message::send_message( $variables, $template_name, "Event: {$event_key}", $user_detail );
}

// 1. Order Created
add_action( 'woocommerce_thankyou', function( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'order_created' );
}, 10, 1 );

// 2. Order Invoice (Payment Complete)
add_action( 'woocommerce_payment_complete', function( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'order_invoice' );
}, 10, 1 );

// 3. Order Shipment (Status Shipped/Completed)
add_action( 'woocommerce_order_status_completed', function( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'order_shipment' );
}, 10, 1 );

// 4. Order Cancellation
add_action( 'woocommerce_order_status_cancelled', function( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'order_cancellation' );
}, 10, 1 );

// 5. Order Credit Memo (Refunded)
add_action( 'woocommerce_order_fully_refunded', function( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'order_credit_memo' );
}, 10, 1 );

/**
 * Handle abandoned cart - this usually needs a cron job or a specific plugin hook.
 * Here we provide the logic for manual or external trigger.
 */
function wa_trigger_abandoned_cart_whatsapp( $order_id ) {
    wa_trigger_order_whatsapp_event( $order_id, 'abandon_cart' );
}
