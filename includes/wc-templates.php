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

/**
 * AJAX: Sync templates from API to local database.
 */
add_action( 'wp_ajax_wa_sync_templates', 'wa_sync_templates_handler' );

if ( ! function_exists( 'wa_sync_templates_handler' ) ) :
    /**
     * Handle AJAX request to sync templates.
     */
    function wa_sync_templates_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        check_ajax_referer( 'wa_sync_templates', 'security' );

        $token = get_transient( 'wa_access_token' );
        if ( ! $token ) {
            $data = WA_Auth::get_token();
            if ( is_wp_error( $data ) ) {
                wp_send_json_error( __( 'Authentication failed.', 'whatsapp-connector' ) );
            }
            $token = $data['access_token'] ?? '';
        }

        $api_base_url = get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com/' );
        $api_url = rtrim( $api_base_url, '/' ) . '/meta-service/v1/template';

        // Log the API URL for debugging. DO NOT log the token.
        error_log( "WA_API_URL: $api_url" );

        $business_id = get_option( 'wa_business_id' );
        $user_id     = get_option( 'wa_user_id' );

        if ( empty( $business_id ) || empty( $user_id ) ) {
            // Force a token refresh to extract business/user IDs
            $auth_data = WA_Auth::get_token();
            if ( is_wp_error( $auth_data ) ) {
                wp_send_json_error( __( 'Could not retrieve business or user IDs. Please check credentials.', 'whatsapp-connector' ) );
            }
            $business_id = get_option( 'wa_business_id' );
            $user_id     = get_option( 'wa_user_id' );
            $token       = $auth_data['access_token'] ?? '';
        }

        $response = wp_remote_get(
            $api_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'businessId'    => $business_id,
                    'userId'        => $user_id,
                ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'Failed to fetch templates from API.', 'whatsapp-connector' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        error_log( "WA_RESPONSE: $body" );

        $data = json_decode( $body, true );
        if ( empty( $data['result']['data'] ) || ! is_array( $data['result']['data'] ) ) {
            wp_send_json_error( __( 'No templates found or invalid response.', 'whatsapp-connector' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'azguards_whatsapp_templates';

        // Ensure table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            WA_Database::create_tables();
        }

        $synced_count = 0;
        foreach ( $data['result']['data'] as $template ) {
            $template_id   = $template['id'];
            $template_name = $template['name'];
            $status        = $template['status'];
            $type          = $template['type'];
            $category      = $template['category']['name'] ?? '';
            $language      = $template['language']['code'] ?? '';

            $body_text     = '';
            $header_format = '';
            $media_handle  = '';

            if ( ! empty( $template['components'] ) ) {
                foreach ( $template['components'] as $component ) {
                    if ( 'BODY' === $component['componentType'] ) {
                        $body_text = $component['componentData'];
                    }
                    if ( 'HEADER' === $component['componentType'] ) {
                        $header_format = $component['componentFormat'] ?? '';
                        if ( 'IMAGE' === $header_format || 'VIDEO' === $header_format || 'DOCUMENT' === $header_format ) {
                            $media_handle = maybe_serialize( $component['componentData'] );
                        }
                    }
                }
            }

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT entity_id FROM $table_name WHERE template_id = %s", $template_id ) );

            $data_to_save = array(
                'template_id'   => $template_id,
                'template_name' => $template_name,
                'template_type' => $type,
                'category'      => $category,
                'language'      => $language,
                'body'          => $body_text,
                'header_format' => $header_format,
                'media_handle'  => $media_handle,
                'status'        => $status,
            );

            if ( $existing ) {
                $wpdb->update( $table_name, $data_to_save, array( 'entity_id' => $existing ) );
            } else {
                $wpdb->insert( $table_name, $data_to_save );
            }
            $synced_count++;
        }

        wp_send_json_success( sprintf( __( 'Successfully synced %d templates.', 'whatsapp-connector' ), $synced_count ) );
    }
endif;
