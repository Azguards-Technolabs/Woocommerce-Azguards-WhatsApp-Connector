<?php
/**
 * WA_Message class for sending WhatsApp messages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Message {

    /**
     * Extract placeholders from text and map to variable values.
     *
     * @param string $text               The component text content.
     * @param array  $template_variables The processed variables (index => value).
     *
     * @return array
     */
    private static function extract_placeholders( $text, $template_variables ) {
        if ( empty( $text ) ) {
            return array();
        }

        $placeholders = array();
        preg_match_all( '/\{\{(\d+)\}\}/', $text, $matches );

        if ( ! empty( $matches[1] ) ) {
            $unique_keys = array_unique( $matches[1] );
            foreach ( $unique_keys as $key ) {
                $val = $template_variables[ $key ] ?? ( $template_variables[ (int) $key ] ?? '' );
                $placeholders[] = array(
                    'key'               => (string) $key,
                    'value'             => (string) $val,
                    'is_user_attribute' => false, // Parity: Meta/Azguards API expects false for template-mapped variables
                    'attribute_name'    => (string) $key,
                );
            }
        }

        return $placeholders;
    }

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

        $api_url = rtrim( get_option( 'wa_template_api_url' ), '/' ) . '/messaging-service/api/v1/message/send';

        global $wpdb;
        $table_templates = $wpdb->prefix . 'azguards_whatsapp_templates';
        $template = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_templates} WHERE template_id = %s", $template_id ), ARRAY_A );

        if ( ! $template ) {
            error_log( "[WhatsApp] ERROR: Template '{$template_id}' not found in database. Message sending failed." );
            return new WP_Error( 'template_not_found', __( 'Template not found in database.', 'whatsapp-connector' ) );
        }

        $template_status = $template['status'] ?? '';

        if ( $template_status && strtoupper( $template_status ) === 'PENDING' ) {
            error_log( "[WhatsApp] WARNING: Template '{$template_id}' is still PENDING approval. Message sending may fail." );
        } elseif ( $template_status && strtoupper( $template_status ) !== 'APPROVED' ) {
            error_log( "[WhatsApp] ERROR: Template '{$template_id}' is not APPROVED (Status: " . strtoupper( $template_status ) . "). Message delivery is likely to fail." );
        }

        // Phone Number Normalization (Parity: Duplicate Country Code Stripping)
        $country_code = preg_replace( '/\D/', '', (string) ( $user_detail['countryCode'] ?? '91' ) );
        $mobile_number = preg_replace( '/\D/', '', (string) ( $user_detail['mobileNumber'] ?? '' ) );
        $mobile_number = ltrim( $mobile_number, '0' );

        if ( 0 === strpos( $mobile_number, $country_code ) && strlen( $mobile_number ) > strlen( $country_code ) ) {
            $mobile_number = substr( $mobile_number, strlen( $country_code ) );
        }
        $wa_id = $country_code . $mobile_number;

        $components = array();

        // 1. HEADER
        $header_format = strtoupper( $template['header_format'] ?? '' );
        if ( $header_format === 'TEXT' && ! empty( $template['header_text'] ) ) {
            $components[] = array(
                'component_type'   => 'HEADER',
                'component_format' => 'TEXT',
                'placeholder'      => self::extract_placeholders( $template['header_text'], $template_variables ),
            );
        } elseif ( in_array( $header_format, array( 'IMAGE', 'VIDEO', 'DOCUMENT' ) ) ) {
            $media_handle = json_decode( $template['media_handle'] ?? '{}', true );
            $media_id = $media_handle['document_id'] ?? ( $media_handle['id'] ?? '' );
            if ( $media_id ) {
                $components[] = array(
                    'component_type' => 'HEADER',
                    'header_type'    => $header_format,
                    'media'          => array( 'id' => $media_id ),
                );
            }
        }

        // 2. BODY
        if ( ! empty( $template['body'] ) ) {
            $components[] = array(
                'component_type'   => 'BODY',
                'component_format' => 'TEXT',
                'placeholder'      => self::extract_placeholders( $template['body'], $template_variables ),
            );
        }

        // 3. FOOTER
        if ( ! empty( $template['footer'] ) ) {
            $components[] = array(
                'component_type'   => 'FOOTER',
                'component_format' => 'TEXT',
                'placeholder'      => self::extract_placeholders( $template['footer'], $template_variables ),
            );
        }

        // 4. BUTTONS (Parity: Individual indexed BUTTON components)
        $buttons_data = json_decode( $template['buttons'] ?? '[]', true );
        if ( ! empty( $buttons_data ) ) {
            foreach ( $buttons_data as $index => $btn ) {
                if ( strtoupper( $btn['type'] ?? '' ) === 'URL' ) {
                    $url = $btn['url'] ?? ( $btn['button_url'] ?? '' );
                    $btn_vars = self::extract_placeholders( $url, $template_variables );
                    if ( ! empty( $btn_vars ) ) {
                        $components[] = array(
                            'component_type'   => 'BUTTON',
                            'button_type'      => 'URL',
                            'index'            => (string) $index,
                            'component_format' => 'TEXT',
                            'placeholder'      => $btn_vars,
                        );
                    }
                }
            }
        }

        // 5. COPY CODE Button (Parity: Dedicated logic for coupon_code)
        $coupon_code = '';
        foreach ( $template_variables as $key => $val ) {
            if ( strtolower( (string) $key ) === 'coupon_code' ) {
                $coupon_code = trim( (string) $val );
                break;
            }
        }
        if ( ! empty( $coupon_code ) ) {
            if ( mb_strlen( $coupon_code ) > 15 ) {
                $coupon_code = mb_substr( $coupon_code, 0, 15 );
            }
            $components[] = array(
                'component_type' => 'BUTTON',
                'button_type'    => 'COPY_CODE',
                'coupon_code'    => $coupon_code,
            );
        }

        $body = array(
            'wa_id'        => $wa_id,
            'message_type' => 'template',
            'template'     => array(
                'template_id' => $template_id,
                'components'  => $components,
            ),
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
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( "[WhatsApp] API Connection Error: " . $response->get_error_message() );
            return $response;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_code = wp_remote_retrieve_response_code( $response );

        // Debug logging (Masked token for production)
        $curl_command = "curl -X POST '{$api_url}' \\\n"
            . "-H 'Authorization: Bearer ********' \\\n"
            . "-H 'Content-Type: application/json' \\\n"
            . "-H 'businessId: " . get_option( 'wa_business_id' ) . "' \\\n"
            . "-H 'userId: " . get_option( 'wa_user_id' ) . "' \\\n"
            . "-d '" . wp_json_encode( $body ) . "'";

        error_log( '--------------------------Start ' . $flag . '--------------------------' );
        error_log( "[WhatsApp] API URL: " . $api_url );
        error_log( "[WhatsApp] Request Body: " . wp_json_encode( $body ) );
        error_log( "[WhatsApp] cURL Command:\n" . $curl_command );
        error_log( "[WhatsApp] Response Code: " . $response_code );
        error_log( "[WhatsApp] Response Body: " . $response_body );
        error_log( '--------------------------End ' . $flag . '--------------------------' );

        return json_decode( $response_body, true );
    }
}
