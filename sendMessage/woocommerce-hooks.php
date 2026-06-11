<?php

require_once plugin_dir_path( __FILE__ ) . 'Events/EventInterface.php';
require_once plugin_dir_path( __FILE__ ) . 'Events/OrderCreatedEvent.php';
require_once plugin_dir_path( __FILE__ ) . 'Events/OrderCompletedEvent.php';

/**
 * Handle the "Order Created" event using isolated Observer.
 */
add_action( 'woocommerce_thankyou', 'wa_handle_order_created_isolated', 10, 1 );
function wa_handle_order_created_isolated( $order_id ) {
    $event = new WA_Order_Created_Event();
    $event->execute( $order_id );
}

/**
 * Handle the "Completed/Shipped" event using isolated Observer.
 */
add_action( 'woocommerce_order_status_completed', 'wa_handle_order_completed_isolated', 10, 1 );
function wa_handle_order_completed_isolated( $order_id ) {
    $event = new WA_Order_Completed_Event();
    $event->execute( $order_id );
}

/**
 * Handle the "On Hold" status change.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_status_on-hold', 'wa_handle_order_on_hold', 10, 1 );
function wa_handle_order_on_hold( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_on_hold_template', 'wa_order_on_hold_table_data', 'Order On Hold' );
}

/**
 * Handle the "Failed" status change.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_status_failed', 'wa_handle_order_failed', 10, 1 );
function wa_handle_order_failed( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_failed_template', 'wa_order_failed_table_data', 'Order Failed' );
}

/**
 * Handle the "Completed" status change.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_status_completed', 'wa_handle_order_completed', 10, 1 );
function wa_handle_order_completed( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_completed_template', 'wa_order_completed_table_data', 'Order Completed' );
}

/**
 * Handle the "Draft" (Custom Status) change.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_status_draft', 'wa_handle_order_draft', 10, 1 );
function wa_handle_order_draft( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_draft_template', 'wa_order_draft_table_data', 'Order Draft' );
}

/**
 * Handle the "Payment Complete" event.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_payment_complete', 'wa_handle_invoice_created', 10, 1 );
function wa_handle_invoice_created( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_invoice_template', 'wa_order_invoice_table_data', 'Order Invoice' );
}

/**
 * Handle the "Order Fully Refunded" event.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_fully_refunded', 'wa_handle_order_refunded', 10, 1 );
function wa_handle_order_refunded( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_credit_memo_template', 'wa_order_credit_memo_table_data', 'Order Refunded' );
}

/**
 * Handle the "Order Cancelled" event.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_order_status_cancelled', 'wa_handle_order_cancelled', 10, 1 );
function wa_handle_order_cancelled( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_cancellation_template', 'wa_order_cancellation_table_data', 'Order Cancelled' );
}

/**
 * Handle the "Order Created" event.
 *
 * @param int $order_id The order ID.
 */
add_action( 'woocommerce_thankyou', 'wa_handle_order_created', 10, 1 );
function wa_handle_order_created( $order_id ) {
    wa_handle_generic_order_event( $order_id, 'wa_order_creation_template', 'wa_order_creation_table_data', 'Order Created' );
}

/**
 * Generic handler for all order status events.
 *
 * @param int    $order_id        The order ID.
 * @param string $template_option_key The template option key.
 * @param string $variable_key    The variable key for template data.
 * @param string $log_context     The log context for error logging.
 */
function wa_handle_generic_order_event( $order_id, $template_option_key, $variable_key, $log_context = '' ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $template_id = get_option( $template_option_key );
    $variables   = wa_process_template_variables( $variable_key, $order );
    $user_detail = wa_get_user_detail_data( $order );

    $billing_country = $order->get_billing_country();
    // Uncomment and use this line if you want to set country code.
    // $variables['countryCode'] = wa_get_dial_code_by_country( $billing_country );

    if ( $template_id ) {
        WA_Message::send_message( $variables, $template_id, $log_context, $user_detail );
    } else {
        error_log( 'Select Template Admin Config for ' . $log_context );
    }
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
