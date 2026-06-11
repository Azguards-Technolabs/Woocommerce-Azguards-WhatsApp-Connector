<?php
/**
 * Contact handler for WhatsApp Connector.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Contact {

    /**
     * Send contact data to WhatsApp Contact API.
     *
     * @return array|WP_Error
     */
    public static function get_contact() {
        // Get access token from transient.
        $token = get_transient( 'wa_access_token' );

        if ( ! $token ) {
            $data = WA_Auth::get_token();

            if ( is_wp_error( $data ) ) {
                return new WP_Error( 'auth_error', __( 'Authentication failed: ', 'whatsapp-connector' ) . $data->get_error_message() );
            }

            if ( empty( $data['access_token'] ) ) {
                return new WP_Error( 'auth_error', __( 'Access token missing in response.', 'whatsapp-connector' ) );
            }

            $token = $data['access_token'];
        }

        // Prepare API URL.
        $base_url = get_option( 'wa_template_api_url' );
        if ( empty( $base_url ) ) {
            return new WP_Error( 'api_error', __( 'API Base URL not set in WooCommerce settings.', 'whatsapp-connector' ) );
        }
        $api_url = rtrim( $base_url, '/' ) . '/messaging-service/api/v1/contacts';

        // Prepare body.
        $body = array(
            'templateId'    => '123e4567-e89b-12d3-a456-426687878787',
            'firstName'     => 'Camille',
            'lastName'      => 'Smith',
            'countryCode'   => '91',
            'mobileNumber'  => '9898989898',
            'imageURL'      => '',
            'email'         => 'johnsmith@vpomail.com',
            'businessName'  => 'Milind Jio',
            'website'       => 'https://www.asif.com',
        );

        // Make POST request.
        $response = wp_remote_post(
            $api_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', __( 'Failed to call contact API.', 'whatsapp-connector' ) );
        }

        $response_body = wp_remote_retrieve_body( $response );
        return json_decode( $response_body, true );
    }
}
