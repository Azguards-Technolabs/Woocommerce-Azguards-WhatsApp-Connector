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
 * Hook: Shipment Tracking Number Added
 * Fires when a tracking number is added to a WooCommerce order (via Shipment Tracking plugin
 * or any plugin that calls the woocommerce_order_shipment_tracking_added action).
 * Duplicate-prevention: only triggers on the FIRST tracking number per order (mirrors Magento ShipmentTrackSaved).
 */
add_action( 'woocommerce_order_shipment_tracking_added', 'wa_trigger_shipment_track_added', 10, 3 );
function wa_trigger_shipment_track_added( $tracking_item_id, $tracking_data, $order_id ) {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    if ( get_option( 'wa_enable_order_shipment', 'yes' ) !== 'yes' ) {
        error_log( "[WhatsApp] Shipment Track event is disabled. Skipping order #{$order_id}." );
        return;
    }

    // Duplicate-prevention: only fire on the VERY FIRST tracking number added to this order.
    // This mirrors Magento's ShipmentTrackSaved logic which skips if track_id != first_track_id.
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        error_log( "[WhatsApp] ShipmentTrack: Order #{$order_id} not found." );
        return;
    }

    if ( $order->get_meta( '_wa_shipment_tracking_added_sent' ) ) {
        error_log( "[WhatsApp] ShipmentTrack: Duplicate track for Order #{$order_id}, skipping." );
        return;
    }

    $template_id = get_option( 'wa_order_shipment_template' );
    if ( empty( $template_id ) ) {
        error_log( "[WhatsApp] ShipmentTrack: No template configured for 'Order Shipped'. Skipping order #{$order_id}." );
        return;
    }

    error_log( "[WhatsApp] Dispatching 'Shipment Track Added' for Order #{$order_id} with template '{$template_id}'." );

    $variables   = wa_process_template_variables( 'wa_order_shipment_table_data', $order );
    $user_detail = wa_get_user_detail_data( $order );

    WA_Message::send_message( $variables, $template_id, 'Shipment Track Added', $user_detail );

    $order->update_meta_data( '_wa_shipment_tracking_added_sent', current_time( 'mysql', true ) );
    $order->save();
}

/**
 * Hook: Out-of-Stock / Low-Stock Notification
 * Mirrors Magento's SendOutOfStockNotification observer.
 * Fires when a product reaches 0 stock or triggers the low-stock threshold.
 */
add_action( 'woocommerce_no_stock', 'wa_trigger_out_of_stock', 10, 1 );
add_action( 'woocommerce_low_stock', 'wa_trigger_low_stock', 10, 1 );

function wa_trigger_out_of_stock( $product ) {
    wa_dispatch_stock_notification( $product, 'Out of Stock' );
}

function wa_trigger_low_stock( $product ) {
    wa_dispatch_stock_notification( $product, 'Low Stock' );
}

/**
 * Common dispatcher for stock notifications.
 *
 * @param WC_Product $product   The product that hit the stock threshold.
 * @param string     $log_context  'Out of Stock' or 'Low Stock'.
 */
function wa_dispatch_stock_notification( $product, $log_context ) {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    if ( get_option( 'wa_enable_stock_notification', 'no' ) !== 'yes' ) {
        return;
    }

    $template_id = get_option( 'wa_stock_notification_template' );
    if ( empty( $template_id ) ) {
        error_log( "[WhatsApp] No template configured for '{$log_context}'. Skipping product #{" . $product->get_id() . "}." );
        return;
    }

    // Build admin user_detail so the notification is sent to the store admin phone.
    $admin_phone   = get_option( 'wa_admin_phone', '' );
    $admin_country = get_option( 'wa_admin_country', 'IN' );

    if ( empty( $admin_phone ) ) {
        error_log( "[WhatsApp] {$log_context}: Admin phone not configured. Skipping." );
        return;
    }

    $stock_qty = $product->get_stock_quantity();

    // Build a simple variable map: {1} = product name, {2} = SKU, {3} = stock qty.
    $variables = [
        1 => $product->get_name(),
        2 => $product->get_sku() ?: '—',
        3 => (string) ( $stock_qty ?? 0 ),
    ];

    $user_detail = [
        'firstName'    => get_bloginfo( 'name' ),
        'lastName'     => '',
        'countryCode'  => wa_get_dial_code_by_country( $admin_country ),
        'mobileNumber' => preg_replace( '/[^0-9]/', '', $admin_phone ),
        'email'        => get_option( 'admin_email' ),
        'website'      => get_site_url(),
    ];

    error_log( "[WhatsApp] Dispatching '{$log_context}' for Product #{" . $product->get_id() . "} '{" . $product->get_name() . "}'." );

    WA_Message::send_message( $variables, $template_id, $log_context, $user_detail );
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

    error_log( "[WhatsApp] Dispatching '{$log_context}' for Order #{$order_id} with template '{$template_id}'." );

    $variables   = wa_process_template_variables( $variable_key, $order );
    $user_detail = wa_get_user_detail_data( $order );

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
