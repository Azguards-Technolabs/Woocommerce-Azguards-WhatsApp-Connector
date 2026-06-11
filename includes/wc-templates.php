<?php
/**
 * WA_Templates class.
 *
 * @package WhatsApp_Connector
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WA_Templates
 */
class WA_Templates {

    /**
     * Get available WhatsApp templates.
     *
     * @return array
     */
    public static function get_templates() {
        $token = get_transient( 'wa_access_token' );

        if ( ! $token ) {
            $data = WA_Auth::get_token(); // This should return a valid Bearer token.

            if ( is_wp_error( $data ) ) {
                return array( '' => __( 'Authentication failed: ', 'whatsapp-connector' ) . $data->get_error_message() );
            }

            if ( empty( $data['access_token'] ) ) {
                return array( '' => __( 'Access token missing in response', 'whatsapp-connector' ) );
            }

            $token = $data['access_token'];
        }

        $api_url = get_option( 'wa_template_api_url' );

        if ( empty( $api_url ) ) {
            return array( '' => __( 'API URL not set', 'whatsapp-connector' ) );
        }

        $business_id = '18462116-8abf-4960-80b2-dd6c76e2532c';
        $user_id     = 'a008d8b8-bc54-4e43-9a62-67b3c1b546f3';

        $response = wp_remote_get(
            $api_url,
            array(
                'headers' => array(
                    // 'Authorization' => 'Bearer ' . $token,
                    'businessId' => $business_id,
                    'userId'     => $user_id,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array( '' => __( 'Failed to fetch templates.', 'whatsapp-connector' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $options = array( '' => __( 'Select...', 'whatsapp-connector' ) );

        if ( ! empty( $data['Result']['data'] ) && is_array( $data['Result']['data'] ) ) {
            foreach ( $data['Result']['data'] as $item ) {
                if ( isset( $item['id'], $item['templateName'] ) ) {
                    $options[ $item['id'] ] = $item['templateName'];
                }
            }
        }

        return $options;
    }
}
