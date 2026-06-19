<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Abandoned Cart Monitor Cron.
 */
add_filter( 'cron_schedules', 'wa_add_15min_cron_schedule' );
function wa_add_15min_cron_schedule( $schedules ) {
    $schedules['15min'] = array(
        'interval' => 900,
        'display'  => __( 'Every 15 Minutes' ),
    );
    return $schedules;
}

add_action( 'wp', 'wa_schedule_abandoned_cart_cron' );
function wa_schedule_abandoned_cart_cron() {
    if ( ! wp_next_scheduled( 'wa_abandoned_cart_monitor' ) ) {
        wp_schedule_event( time(), '15min', 'wa_abandoned_cart_monitor' );
    }
}

add_action( 'wa_abandoned_cart_monitor', 'wa_process_abandoned_carts' );
function wa_process_abandoned_carts() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' || get_option( 'wa_enable_abandoned_cart', 'yes' ) !== 'yes' ) {
        return;
    }

    $template_id = get_option( 'wa_abandoned_cart_template' );
    if ( empty( $template_id ) ) {
        return;
    }

    global $wpdb;

    $trigger_delay_mins = (int) get_option( 'wa_abandoned_cart_trigger_delay', 60 );
    $time_threshold     = time() - ( $trigger_delay_mins * 60 );
    $session_table      = $wpdb->prefix . 'woocommerce_sessions';
    $abandoned_table    = $wpdb->prefix . 'azguards_whatsapp_abandoned_cart';

    // session_expiry is set to exactly 48 hours (172800 seconds) from the last update time in WooCommerce.
    // So last_updated_time = session_expiry - 172800.
    // We want last_updated_time < $time_threshold AND last_updated_time > (time() - 48 hours) to ensure session is valid.
    
    $query = $wpdb->prepare( "
        SELECT session_key, session_value, session_expiry 
        FROM {$session_table}
        WHERE (session_expiry - 172800) < %d
        AND session_key NOT IN (
            SELECT session_id FROM {$abandoned_table}
        )
    ", $time_threshold );

    $sessions = $wpdb->get_results( $query );

    if ( ! empty( $sessions ) ) {
        foreach ( $sessions as $sess ) {
            $session_data = maybe_unserialize( $sess->session_value );
            $cart         = isset( $session_data['cart'] ) ? maybe_unserialize( $session_data['cart'] ) : [];
            $customer     = isset( $session_data['customer'] ) ? maybe_unserialize( $session_data['customer'] ) : [];

            if ( empty( $cart ) ) {
                continue; 
            }

            $phone      = $customer['billing_phone'] ?? '';
            $email      = $customer['billing_email'] ?? '';
            $first_name = $customer['billing_first_name'] ?? 'Customer';
            $last_name  = $customer['billing_last_name'] ?? '';
            $country    = $customer['billing_country'] ?? '';

            if ( empty( $phone ) ) {
                continue; // Can't send WhatsApp without phone
            }

            // Record into abandoned cart tracking
            $wpdb->insert(
                $abandoned_table,
                [
                    'session_id'     => $sess->session_key,
                    'customer_email' => $email,
                    'status'         => 'SENT',
                    'notified_at'    => current_time( 'mysql' ),
                ]
            );

            // Construct variables using standard processing or explicitly mapped dummy order
            $variables = wa_process_common_variables( 'wa_abandoned_cart_table_data', $sess->session_key, $cart, $customer );

            $user_detail = [
                'firstName'    => $first_name,
                'lastName'     => $last_name,
                'countryCode'  => wa_get_dial_code_by_country( $country ),
                'mobileNumber' => $phone,
                'email'        => $email,
                'website'      => get_site_url(),
            ];

            // In an enterprise system, this connects to Queue or sends directly
            WA_Message::send_message( $variables, $template_id, 'Abandoned Cart', $user_detail );
        }
    }
}

/**
 * Helper to process template variables precisely for abandoned carts structure
 */
function wa_process_common_variables( $variable_key, $session_key, $cart, $customer ) {
    $template_variable_json = get_option( $variable_key );
    $template_variables     = json_decode( $template_variable_json, true );
    $data                   = [];

    if ( ! is_array( $template_variables ) ) {
        return $data;
    }

    // Attempt to compute total and items
    $total = 0;
    $items = [];
    foreach ( $cart as $cart_item_key => $cart_item ) {
        $product = wc_get_product( $cart_item['product_id'] );
        if ( $product ) {
            $total += $product->get_price() * $cart_item['quantity'];
            $items[] = "{$cart_item['quantity']} x " . $product->get_name();
        }
    }

    foreach ( $template_variables as $variable ) {
        $index    = $variable['index'];
        $property = trim( (string) $variable['max_results'] );
        $property = preg_replace( '/^\s*var\s+/', '', $property );
        $property = str_replace( '()', '', $property );
        $clean_property = $property;
        if ( strpos( $clean_property, '.' ) !== false ) {
            $parts          = explode( '.', $clean_property );
            $clean_property = end( $parts );
        }
        $value    = '';

        if ( $clean_property === 'customer_first_name' || $clean_property === 'customer_firstname' ) {
            $value = $customer['billing_first_name'] ?? '';
        } elseif ( $clean_property === 'customer_last_name' || $clean_property === 'customer_lastname' ) {
            $value = $customer['billing_last_name'] ?? '';
        } elseif ( $clean_property === 'customer_email' ) {
            $value = $customer['billing_email'] ?? '';
        } elseif ( $clean_property === 'grand_total' || $clean_property === 'subtotal' || $clean_property === 'total_amount' ) {
            $value = wc_price( $total );
        } elseif ( $clean_property === 'items_count' ) {
            $value = count( $items );
        } elseif ( $clean_property === 'items_qty' ) {
            $value = array_sum( wp_list_pluck( $cart, 'quantity' ) );
        } elseif ( $clean_property === 'items_summary' || $clean_property === 'cart_items' ) {
            $value = implode( "\n", $items );
        } elseif ( $clean_property === 'base_url' || $clean_property === 'store_base_url' ) {
            $value = trailingslashit( get_site_url() );
        } elseif ( $clean_property === 'cart_url' || strpos($clean_property, 'url') !== false ) {
            $value = wc_get_cart_url();
        }

        $data[ $index ] = $value;
    }

    return $data;
}
