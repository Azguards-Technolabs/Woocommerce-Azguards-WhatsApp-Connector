<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_wa_sync_templates', 'wa_sync_templates_handler' );

function wa_sync_templates_handler() {
    ob_start(); // Buffer any accidental output (e.g. PHP deprecated notices from other plugins)

    // Check nonce or permissions if needed
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        ob_end_clean();
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    $token = get_transient( 'wa_access_token' );
    if ( ! $token ) {
        if ( class_exists('WA_Auth') ) {
            $data = WA_Auth::get_token();
            if ( ! is_wp_error( $data ) && ! empty( $data['access_token'] ) ) {
                $token = $data['access_token'];
            }
        }
    }

    $base_url = get_option( 'wa_template_api_url' );
    if ( empty( $base_url ) ) {
        ob_end_clean();
        wp_send_json_error( [ 'message' => 'API Base URL not set' ] );
    }

    $api_url = rtrim( $base_url, '/' ) . '/meta-service/v1/template';

    $response = wp_remote_get( $api_url, [
        'headers' => [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ],
        'timeout' => 30
    ] );

    if ( is_wp_error( $response ) ) {
        ob_end_clean();
        wp_send_json_error( [ 'message' => 'API request failed: ' . $response->get_error_message() ] );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    error_log("WA_API_URL: " . $api_url);
    error_log("WA_TOKEN: " . $token);
    error_log("WA_RESPONSE: " . $body);

    if ( empty( $data['result']['data'] ) || ! is_array( $data['result']['data'] ) ) {
        ob_end_clean();
        wp_send_json_error( [ 
            'message' => 'No templates found in API response.',
            'debug_url' => $api_url,
            'debug_token' => $token,
            'debug_raw_response' => $body 
        ] );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'azguards_whatsapp_templates';

    $created = 0;
    $updated = 0;

    foreach ( $data['result']['data'] as $item ) {
        if ( empty( $item['id'] ) || empty( $item['name'] ) ) {
            continue;
        }

        $template_id = sanitize_text_field( $item['id'] );
        $template_name = sanitize_text_field( $item['name'] );
        $template_type = sanitize_text_field( $item['type'] ?? 'TEXT' );
        $body_text = '';
        $header_format = 'TEXT';
        
        if ( ! empty( $item['components'] ) && is_array( $item['components'] ) ) {
            foreach ( $item['components'] as $component ) {
                $type = strtoupper( $component['componentType'] ?? '' );
                if ( $type === 'BODY' ) {
                    $body_text = $component['componentData'] ?? '';
                } elseif ( $type === 'HEADER' ) {
                    $header_format = strtoupper( $component['componentFormat'] ?? 'TEXT' );
                }
            }
        }

        $status = sanitize_text_field( $item['status'] ?? 'APPROVED' );

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT entity_id FROM $table_name WHERE template_id = %s", $template_id ) );

        $data_to_save = [
            'template_name' => $template_name,
            'template_type' => $template_type,
            'body'          => $body_text,
            'header_format' => $header_format,
            'status'        => $status,
            'updated_at'    => current_time( 'mysql' )
        ];

        if ( $existing ) {
            $wpdb->update(
                $table_name,
                $data_to_save,
                [ 'template_id' => $template_id ]
            );
            $updated++;
        } else {
            $data_to_save['template_id'] = $template_id;
            $data_to_save['created_at']  = current_time( 'mysql' );
            $wpdb->insert( $table_name, $data_to_save );
            $created++;
        }
    }

    ob_end_clean();
    wp_send_json_success( [
        'message' => sprintf( 'Templates synced. %d created, %d updated.', $created, $updated )
    ] );
}

add_action( 'wp_ajax_wa_save_template_config', 'wa_save_template_config_handler' );

function wa_save_template_config_handler() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    $hook_type = sanitize_text_field( $_POST['hook_type'] ?? '' );
    if ( empty( $hook_type ) ) {
        wp_send_json_error( [ 'message' => 'No hook type provided.' ] );
    }

    $fields = [
        'header_type', 'header_text', 'message_body', 'footer_text', 'enable_buttons', 'buttons_data'
    ];

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = wp_unslash( $_POST[ $field ] );
            update_option( "wa_template_{$hook_type}_{$field}", $value );
        }
    }

    wp_send_json_success( [ 'message' => 'Template configuration saved successfully.' ] );
}
