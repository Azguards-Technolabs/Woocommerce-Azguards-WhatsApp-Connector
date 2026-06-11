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

        $body = array(
            'templateId'        => $template_id,
            'userDetail'        => $user_detail,
            'messageBody'       => $template_variables['body'] ?? '', // Support for processed body
        );

        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'businessId'    => '18462116-8abf-4960-80b2-dd6c76e2532c',
                    'userId'        => 'a008d8b8-bc54-4e43-9a62-67b3c1b546f3',
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', __( 'Failed to call message API.', 'whatsapp-connector' ) );
        }

        $response_body = wp_remote_retrieve_body( $response );

        // Debug logging.
        error_log( '--------------------------Start ' . $flag . '--------------------------' );
        error_log( print_r( $body, true ) );
        error_log( wp_json_encode( $body ) );
        error_log( print_r( json_decode( $response_body, true ), true ) );
        error_log( '--------------------------End ' . $flag . '--------------------------' );

        return json_decode( $response_body, true );
    }
}
