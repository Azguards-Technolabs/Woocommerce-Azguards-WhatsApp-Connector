<?php
/**
 * Contact handler for WhatsApp Connector.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Contact {

    /**
     * Send customer data to WhatsApp Contact API.
     *
     * @param int $user_id The WordPress user ID.
     * @return array|WP_Error
     */
    public static function sync_customer( $user_id ) {
        if ( ! $user_id ) {
            return new WP_Error( 'invalid_user', __( 'Invalid user ID.', 'whatsapp-connector' ) );
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_Error( 'invalid_user', __( 'User not found.', 'whatsapp-connector' ) );
        }

        $token = wa_get_valid_token();
        if ( ! $token ) {
            return new WP_Error( 'auth_error', __( 'Failed to retrieve access token.', 'whatsapp-connector' ) );
        }

        $api_url = rtrim( get_option( 'wa_template_api_url' ), '/' ) . '/messaging-service/api/v1/contacts';

        // Get user meta for WooCommerce customer details
        $first_name = get_user_meta( $user_id, 'billing_first_name', true ) ?: $user->first_name;
        $last_name  = get_user_meta( $user_id, 'billing_last_name', true ) ?: $user->last_name;
        $phone      = get_user_meta( $user_id, 'billing_phone', true );
        $country    = get_user_meta( $user_id, 'billing_country', true );
        $company    = get_user_meta( $user_id, 'billing_company', true );
        
        $country_code = '';
        if ( $country && function_exists( 'wa_get_dial_code_by_country' ) ) {
            $country_code = wa_get_dial_code_by_country( $country );
        }
        
        // Remove +, spaces, dashes from phone and country code
        $clean_phone   = ltrim( preg_replace( '/[^0-9]/', '', $phone ), '0' );
        $clean_country = preg_replace( '/[^0-9]/', '', $country_code );

        // Fallbacks exactly matching Magento to avoid Meta API structural errors
        if ( empty( $clean_phone ) ) {
            $clean_phone = '0000000000';
        }
        if ( empty( $clean_country ) ) {
            $clean_country = wa_get_dial_code_by_country( 'IN' ) ?: '91';
        }

        $body = array(
            'first_name'   => $first_name ?: $user->user_login,
            'last_name'    => $last_name,
            'country_code' => $clean_country,
            'phone_number' => $clean_phone,
        );

        error_log( '[WhatTack Sync Request] User ID: ' . $user_id . ' | Payload: ' . wp_json_encode( $body ) );

        // Send customer data with retries
        $response = null;
        for ( $i = 0; $i < 3; $i++ ) {
            $response = wp_remote_post(
                $api_url,
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode( $body ),
                    'timeout' => 15,
                )
            );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
                break;
            }
            sleep( 1 );
        }

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', __( 'Failed to call contact API. ', 'whatsapp-connector' ) . $response->get_error_message() );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        error_log( '[WhatTack Sync Response] User ID: ' . $user_id . ' | HTTP Code: ' . $response_code . ' | Body: ' . $response_body );
        
        $data = json_decode( $response_body, true );

        if ( $response_code >= 400 ) {
             $err_msg = $data['message'] ?? $data['error'] ?? "HTTP $response_code";
             return new WP_Error( 'api_error', $err_msg );
        }

        // Mark this user as synced to WhatTack, and store their resolved phone for campaign use
        $full_phone = $clean_country ? $clean_country . $clean_phone : ( strlen( $clean_phone ) === 10 ? '91' . $clean_phone : ltrim( $clean_phone, '+' ) );
        update_user_meta( $user_id, 'wa_whatsapp_synced', 1 );
        if ( $full_phone ) {
            update_user_meta( $user_id, 'wa_whatsapp_phone', $full_phone );
        }

        return $data;
    }

    /**
     * Core function to sync all unsynced customers to WhatsApp Contact API.
     *
     * @return int|WP_Error Number of synced contacts or WP_Error on failure.
     */
    public static function wa_sync_contacts() {
        error_log( "[WA Contact Sync] Starting contact sync execution." );

        global $wpdb;

        // Fetch users who are missing 'wa_whatsapp_synced' = 1
        $query = "
            SELECT u.ID FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON (u.ID = um.user_id AND um.meta_key = 'wa_whatsapp_synced')
            WHERE um.meta_value IS NULL OR um.meta_value != '1'
        ";

        $unsynced_user_ids = $wpdb->get_col( $query );

        if ( empty( $unsynced_user_ids ) ) {
            error_log( "[WA Contact Sync] No unsynced contacts found." );
            return 0;
        }

        error_log( "[WA Contact Sync] Found " . count( $unsynced_user_ids ) . " unsynced contacts." );

        $synced_count = 0;
        foreach ( $unsynced_user_ids as $user_id ) {
            $result = self::sync_customer( $user_id );
            if ( is_wp_error( $result ) ) {
                error_log( "[WA Contact Sync] Failed to sync user ID $user_id: " . $result->get_error_message() );
            } else {
                $synced_count++;
            }

            // Limit loop safety for background sync
            if ( $synced_count >= 100 ) {
                error_log( "[WA Contact Sync] Batch limit reached (100). Continuing in next run." );
                break;
            }
        }

        error_log( "[WA Contact Sync] Sync completed. Successfully synced $synced_count contacts." );
        return $synced_count;
    }
}

/**
 * Reset sync status when a user profile is updated.
 */
add_action( 'profile_update', 'wa_reset_user_sync_status', 10, 2 );
add_action( 'woocommerce_update_customer', 'wa_reset_user_sync_status', 10, 1 );

function wa_reset_user_sync_status( $user_id ) {
    update_user_meta( $user_id, 'wa_whatsapp_synced', '0' );
    error_log( "[WA Contact] Reset sync status for user ID: $user_id due to update." );
}
