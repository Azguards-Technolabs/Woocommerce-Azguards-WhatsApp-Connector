<?php
/**
 * Authentication helper for WhatsApp Connector.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Auth {

    /**
     * Get access token from API.
     *
     * @param string|null $url           Auth API URL.
     * @param string|null $client_id     Client ID.
     * @param string|null $client_secret Client Secret.
     * @param string|null $grant_type    Grant Type.
     *
     * @return array|WP_Error
     */
    public static function get_token( $url = null, $client_id = null, $client_secret = null, $grant_type = null ) {
        $auth_url      = $url          ? $url : get_option( 'wa_auth_api_url' );
        $client_id     = $client_id    ? $client_id : get_option( 'wa_client_id' );
        $client_secret = $client_secret ? $client_secret : get_option( 'wa_client_secret' );
        $grant_type    = $grant_type   ? $grant_type : get_option( 'wa_grant_type' );

        if ( empty( $auth_url ) || empty( $client_id ) || empty( $client_secret ) || empty( $grant_type ) ) {
            return new WP_Error( 'missing_credentials', __( 'One or more required credentials are missing.', 'whatsapp-connector' ) );
        }

        $body = array(
            'grant_type'    => $grant_type,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        );

        $response = wp_remote_post(
            $auth_url,
            array(
                'method'  => 'POST',
                'body'    => $body,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'auth_error', __( 'Failed to fetch token.', 'whatsapp-connector' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'api_error', isset( $data['error_description'] ) ? $data['error_description'] : $data['error'] );
        }

        $token     = $data['access_token'];
        $expires_in = isset( $data['expires_in'] ) ? absint( $data['expires_in'] ) : 3600;

        set_transient( 'wa_access_token', $token, $expires_in );

        // Extract tenant and user IDs from JWT token
        $token_parts = explode( '.', $token );
        if ( count( $token_parts ) === 3 ) {
            $payload = json_decode( base64_decode( str_replace( array( '-', '_' ), array( '+', '/' ), $token_parts[1] ) ), true );
            if ( ! empty( $payload['active_tenant']['tenant_id'] ) ) {
                update_option( 'wa_business_id', $payload['active_tenant']['tenant_id'] );
            }
            if ( ! empty( $payload['sub'] ) ) {
                update_option( 'wa_user_id', $payload['sub'] );
            }
        }

        return $data;
    }
}
