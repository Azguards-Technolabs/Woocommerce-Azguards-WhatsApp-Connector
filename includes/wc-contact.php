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
}
