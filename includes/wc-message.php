<?php
/**
 * WA_Message class for sending WhatsApp messages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Message {

    /**
     * Send a WhatsApp message using the configured API.
     *
     * @param array  $template_variables Template placeholder values.
     * @param string $template_id        Template ID.
     * @param string $flag               Logging flag for debugging.
     * @param array  $user_detail        User detail array.
     *
     * @return array|WP_Error
     */
    public static function send_message( $template_variables, $template_id, $flag, $user_detail ) {
        $enable_connector = get_option( 'wa_enable_connector' );

        if ( 'no' === $enable_connector ) {
            error_log( 'WhatsApp connector is disabled' );
            return 'WhatsApp connector is disabled';
        }

        $token = get_transient( 'wa_access_token' );

        if ( ! $token ) {
            $auth_token_data = WA_Auth::get_token();

            if ( is_wp_error( $auth_token_data ) ) {
                return new WP_Error( 'auth_error', __( 'Authentication failed: ', 'whatsapp-connector' ) . $auth_token_data->get_error_message() );
            }

            if ( empty( $auth_token_data['access_token'] ) ) {
                return new WP_Error( 'auth_error', __( 'Access token missing in response.', 'whatsapp-connector' ) );
            }

            $token = $auth_token_data['access_token'];
        }

        $api_url = get_option( 'wa_message_api_url' );

        $converted_placeholder_values = array();
        foreach ( $template_variables as $key => $value ) {
            $converted_placeholder_values[] = array(
                'parameterName'  => $key,
                'parameterValue' => $value,
            );
        }

        $body = array(
            'templateId'        => $template_id,
            'userDetail'        => $user_detail,
            'placeholderValues' => $converted_placeholder_values,
        );

        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'businessId'    => get_option( 'wa_business_id' ),
                    'userId'        => get_option( 'wa_user_id' ),
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( "[WhatsApp] API Connection Error: " . $response->get_error_message() );
            return new WP_Error( 'api_error', __( 'Failed to call message API.', 'whatsapp-connector' ) );
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_code = wp_remote_retrieve_response_code( $response );

        // Debug logging.
        error_log( '--------------------------Start ' . $flag . '--------------------------' );
        error_log( "[WhatsApp] API URL: " . $api_url );
        error_log( "[WhatsApp] Request Body: " . wp_json_encode( $body ) );
        error_log( "[WhatsApp] Response Code: " . $response_code );
        error_log( "[WhatsApp] Response Body: " . $response_body );
        error_log( '--------------------------End ' . $flag . '--------------------------' );

        return json_decode( $response_body, true );
    }
}
