<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook: Order Created
 * Fires when a new order is created.
 */
add_action( 'woocommerce_checkout_order_processed', 'wa_trigger_order_created', 10, 1 );
function wa_trigger_order_created( $order_id ) {
    wa_dispatch_event_template( $order_id, 'wa_enable_order_created', 'wa_order_creation_template', 'wa_order_creation_table_data', 'Order Created' );
}

/**
 * Hook: Invoice Created (Payment Complete)
 * Fires when an order payment is completed.
 */
add_action( 'woocommerce_payment_complete', 'wa_trigger_invoice_created', 10, 1 );
function wa_trigger_invoice_created( $order_id ) {
    wa_dispatch_event_template( $order_id, 'wa_enable_order_invoice', 'wa_order_invoice_template', 'wa_order_invoice_table_data', 'Invoice Created' );
}

/**
 * Hook: Order Shipped (Order Completed)
 * Fires when an order is marked as completed (shipped).
 */
add_action( 'woocommerce_order_status_completed', 'wa_trigger_order_shipped', 10, 1 );
function wa_trigger_order_shipped( $order_id ) {
    wa_dispatch_event_template( $order_id, 'wa_enable_order_shipment', 'wa_order_shipment_template', 'wa_order_shipment_table_data', 'Order Shipped' );
}

/**
 * Hook: Order Cancelled
 * Fires when an order is cancelled.
 */
add_action( 'woocommerce_order_status_cancelled', 'wa_trigger_order_cancelled', 10, 1 );
function wa_trigger_order_cancelled( $order_id ) {
    wa_dispatch_event_template( $order_id, 'wa_enable_order_cancellation', 'wa_order_cancellation_template', 'wa_order_cancellation_table_data', 'Order Cancelled' );
}

/**
 * Hook: Order Refunded
 * Fires when an order is fully refunded.
 */
add_action( 'woocommerce_order_fully_refunded', 'wa_trigger_order_refunded', 10, 1 );
function wa_trigger_order_refunded( $order_id ) {
    wa_dispatch_event_template( $order_id, 'wa_enable_order_creditmemo', 'wa_order_credit_memo_template', 'wa_order_credit_memo_table_data', 'Order Refunded' );
}

/**
 * Common dispatcher for WooCommerce events
 */
function wa_dispatch_event_template( $order_id, $enable_key, $template_key, $variable_key, $log_context ) {
    // Check if the connector is enabled globally
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        error_log( "[WhatsApp] Order #{$order_id} not found for event '{$log_context}'. Skipping." );
        return;
    }

    // Sync Customer if not already synced and the customer has an account
    if ( $log_context === 'Order Created' && $order->get_user_id() > 0 ) {
        $user_id = $order->get_user_id();
        $is_synced = get_user_meta( $user_id, 'wa_whatsapp_synced', true );
        if ( ! $is_synced ) {
            if ( ! class_exists( 'WA_Contact' ) ) {
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wc-contact.php';
            }
            WA_Contact::sync_customer( $user_id );
        }
    }

    // Check if this specific event is enabled
    if ( get_option( $enable_key, 'yes' ) !== 'yes' ) {
        error_log( "[WhatsApp] Event '{$log_context}' is disabled in settings. Skipping." );
        return;
    }

    $template_id = get_option( $template_key );
    if ( empty( $template_id ) ) {
        error_log( "[WhatsApp] No template configured for '{$log_context}' (option key: {$template_key}). Skipping." );
        return;
    }

    $variables   = wa_process_template_variables( $variable_key, $order );
    $user_detail = wa_get_user_detail_data( $order );

    error_log( "[WhatsApp] Dispatching '{$log_context}' for Order #{$order_id}." );
    error_log( "[WhatsApp] Template ID: " . $template_id );
    error_log( "[WhatsApp] Variables: " . wp_json_encode( $variables ) );

    WA_Message::send_message( $variables, $template_id, $log_context, $user_detail );
}

/**
 * Get user detail data from the order.
 *
 * @param WC_Order $order The WooCommerce order object.
 *
 * @return array The array of user details.
 */
function wa_get_user_detail_data( $order ) {
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name  = $order->get_billing_last_name();
    $billing_country    = $order->get_billing_country();
    $billing_phone      = $order->get_billing_phone();
    $billing_email      = $order->get_billing_email();
    $billing_company    = $order->get_billing_company();

    return [
        'firstName'    => $billing_first_name,
        'lastName'     => $billing_last_name,
        'countryCode'  => wa_get_dial_code_by_country( $billing_country ),
        'mobileNumber' => $billing_phone,
        'imageURL'     => 'https://randomuser.me/api/portraits/men/45.jpg',
        'email'        => $billing_email,
        'businessName' => $billing_company ?: 'Verma Creations',
        'website'      => get_site_url(),
    ];
}
