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

add_action( 'wp_ajax_wa_save_builder_template', 'wa_save_builder_template_handler' );

if ( ! function_exists( 'wa_save_builder_template_handler' ) ) :
    function wa_save_builder_template_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        $hook            = sanitize_text_field( $_POST['hook'] ?? '' );
        $is_standalone   = isset($_POST['is_standalone']) && $_POST['is_standalone'] === 'yes';
        $entity_id       = intval( $_POST['entity_id'] ?? 0 );
        $template_name   = sanitize_text_field( $_POST['template_name'] ?? 'Unnamed' );
        $category        = sanitize_text_field( $_POST['category'] ?? 'UTILITY' );
        $language        = get_locale(); // Enforce WP Store Language
        $header_type     = strtoupper( sanitize_text_field( $_POST['header_type'] ?? 'TEXT' ) );
        $header_text     = sanitize_text_field( $_POST['header_text'] ?? '' );
        $header_handle   = sanitize_text_field( $_POST['header_handle'] ?? '' );
        $header_url      = esc_url_raw( $_POST['header_url'] ?? '' );
        $body_template   = wp_unslash( $_POST['body_template'] ?? '' );
        $footer_template = sanitize_text_field( $_POST['footer_template'] ?? '' );
        $enable_buttons  = sanitize_text_field( $_POST['enable_buttons'] ?? 'yes' );
        $buttons         = isset( $_POST['buttons'] ) && is_array( $_POST['buttons'] ) ? $_POST['buttons'] : [];
        
        $template_type   = strtoupper( sanitize_text_field( $_POST['template_type'] ?? 'TEXT' ) );
        $carousel_cards  = wp_unslash( $_POST['carousel_cards'] ?? '[]' );

        // The new Template Type dropdown sends combined values (TEXT/IMAGE/VIDEO/DOCUMENT/CAROUSEL).
        // Expand them into the real template_type and header_type for the payload builder + DB.
        $media_types = ['IMAGE', 'VIDEO', 'DOCUMENT'];
        if ( $template_type === 'CAROUSEL' ) {
            // Keep as-is
        } elseif ( in_array( $template_type, $media_types ) ) {
            $header_type   = $template_type; // e.g. IMAGE
            $template_type = 'STANDARD';
        } else {
            $header_type   = 'TEXT';
            $template_type = 'STANDARD';
        }

        if ( empty( $hook ) && ! $is_standalone ) {
            wp_send_json_error( __( 'Invalid hook.', 'whatsapp-connector' ) );
        }

        // --- 1. Save local config options (only for nested settings page) ---
        if ( ! $is_standalone ) {
            update_option( "wa_template_{$hook}_template_name", $template_name );
            update_option( "wa_template_{$hook}_category", $category );
            update_option( "wa_template_{$hook}_language", $language );
            update_option( "wa_template_{$hook}_header_type", $header_type );
            update_option( "wa_template_{$hook}_header_text", $header_text );
            update_option( "wa_template_{$hook}_header_handle", $header_handle );
            update_option( "wa_template_{$hook}_header_url", $header_url );
            update_option( "wa_template_{$hook}_body_template", $body_template );
            update_option( "wa_template_{$hook}_footer_template", $footer_template );
            update_option( "wa_template_{$hook}_enable_buttons", $enable_buttons );
            update_option( "wa_template_{$hook}_buttons_json", wp_json_encode( $buttons ) );
            update_option( "wa_template_{$hook}_template_type", $template_type );
            update_option( "wa_template_{$hook}_carousel_cards_json", $carousel_cards );
        }

        // --- 2. Call Meta/WhatsApp API to create template ---
        $token = wa_get_valid_token();
        if ( is_wp_error( $token ) ) {
            // Still save locally even if API fails — return partial success
            wp_send_json_success( [
                'message'    => __( 'Template saved locally. API sync failed: ', 'whatsapp-connector' ) . $token->get_error_message(),
                'api_synced' => false,
            ] );
        }

        $api_base  = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
        $api_url   = $api_base . '/meta-service/v1/template';
        $business_id = get_option( 'wa_business_id', '' );
        $user_id     = get_option( 'wa_user_id', '' );

        $payload = wa_build_template_api_payload(
            $template_name, $category, $language,
            $header_type, $header_text,
            $body_template, $footer_template,
            $enable_buttons === 'yes' ? $buttons : [],
            $header_handle, 
            $header_url,
            $template_type,
            $carousel_cards
        );

        $response = wp_remote_post( $api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'businessId'    => $business_id,
                'userId'        => $user_id,
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_success( [
                'message'    => __( 'Template saved locally. API request failed: ', 'whatsapp-connector' ) . $response->get_error_message(),
                'api_synced' => false,
            ] );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = wp_remote_retrieve_body( $response );
        $data          = json_decode( $body, true );

        error_log( "[WA Builder] API Response ({$response_code}): {$body}" );

        // Extract API template ID (the platform returns it under result.id or result.templateId)
        $api_template_id = $data['result']['id'] ?? $data['result']['templateId'] ?? $data['id'] ?? null;

        if ( $api_template_id && ! $is_standalone ) {
            update_option( "wa_template_{$hook}_assigned_id", $api_template_id );

            // Link to WooCommerce hook option so woocommerce-hooks.php can dispatch it
            $mapped_option_keys = [
                'order_created'     => 'wa_order_creation_template',
                'order_shipment'    => 'wa_order_shipment_template',
                'order_invoice'     => 'wa_order_invoice_template',
                'order_creditmemo'  => 'wa_order_credit_memo_template',
                'order_on_hold'     => 'wa_order_on_hold_template',
                'order_failed'      => 'wa_order_failed_template',
                'order_completed'   => 'wa_order_completed_template',
                'order_draft'       => 'wa_order_draft_template',
                'order_cancellation'=> 'wa_order_cancellation_template',
                'marketing'         => 'wa_campaign_default_template',
            ];
            if ( isset( $mapped_option_keys[ $hook ] ) ) {
                update_option( $mapped_option_keys[ $hook ], $api_template_id );
            }
        }

        // --- 3. Save/update in local templates DB table ---
        global $wpdb;
        $table_name   = $wpdb->prefix . 'azguards_whatsapp_templates';
        
        $template_id = null;
        if ( $is_standalone && $entity_id > 0 ) {
            $existing_row = $wpdb->get_row( $wpdb->prepare( "SELECT template_id FROM {$table_name} WHERE entity_id = %d", $entity_id ) );
            if ( $existing_row ) {
                $template_id = $api_template_id ?? $existing_row->template_id;
            }
        }
        
        if ( ! $template_id ) {
            $template_id = $api_template_id ?? get_option( "wa_template_{$hook}_assigned_id", 'tpl_' . ( $hook ?: 'custom' ) . '_' . uniqid() );
        }
        
        if ( ! $api_template_id && ! $is_standalone ) {
            update_option( "wa_template_{$hook}_assigned_id", $template_id );
        }

        $status   = ( in_array( $response_code, [200, 201], true ) ) ? 'PENDING' : 'LOCAL';
        $existing = null;
        if ( $is_standalone && $entity_id > 0 ) {
            $existing = $entity_id;
        } else {
            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT entity_id FROM {$table_name} WHERE template_id = %s", $template_id ) );
        }

        $db_data = [
            'template_id'    => $template_id,
            'template_name'  => $template_name,
            'template_type'  => $template_type,
            'category'       => $category,
            'language'       => $language,
            'body'           => $body_template,
            'header_format'  => $header_type,
            'header_text'    => $header_text,
            'footer'         => $footer_template,
            'buttons'        => wp_json_encode( $buttons ),
            'carousel_cards' => $carousel_cards,
            'media_handle'   => $header_handle ? wp_json_encode([ 'document_id' => $header_handle, 'preview_link' => $header_url ]) : '',
            'status'         => $status,
            'updated_at'     => current_time( 'mysql' ),
        ];

        if ( $existing ) {
            $wpdb->update( $table_name, $db_data, [ 'entity_id' => $existing ] );
        } else {
            $db_data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_name, $db_data );
        }

        if ( in_array( $response_code, [200, 201], true ) ) {
            wp_send_json_success( [
                'message'     => __( 'Template submitted to Meta API successfully! It will appear as PENDING until approved.', 'whatsapp-connector' ),
                'api_synced'  => true,
                'template_id' => $api_template_id,
            ] );
        } else {
            wp_send_json_success( [
                'message'    => sprintf(
                    __( 'Template saved locally. API returned status %d. Details: %s', 'whatsapp-connector' ),
                    $response_code,
                    substr( $body, 0, 200 )
                ),
                'api_synced' => false,
            ] );
        }
    }
endif;

/**
 * Centralized helper to retrieve a valid bearer token.
 * Returns the token string on success, WP_Error on failure.
 *
 * @return string|WP_Error
 */
if ( ! function_exists( 'wa_get_valid_token' ) ) :
    function wa_get_valid_token() {
        $token = get_transient( 'wa_access_token' );
        if ( $token ) {
            return $token;
        }

        $auth_data = WA_Auth::get_token();
        if ( is_wp_error( $auth_data ) ) {
            return $auth_data;
        }
        if ( empty( $auth_data['access_token'] ) ) {
            return new WP_Error( 'wa_no_token', __( 'Access token missing in auth response.', 'whatsapp-connector' ) );
        }

        $expires_in = (int) ( $auth_data['expires_in'] ?? 3600 );
        set_transient( 'wa_access_token', $auth_data['access_token'], max( $expires_in - 60, 60 ) );

        return $auth_data['access_token'];
    }
endif;

/**
 * Build the Meta API payload for template create/update.
 *
 * @param string $name        Template name (lowercase, underscores).
 * @param string $category    e.g. 'UTILITY', 'MARKETING'.
 * @param string $language    e.g. 'en_US'.
 * @param string $header_type 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'.
 * @param string $header_text Header text (for TEXT type).
 * @param string $body        Body text with {{var}} placeholders.
 * @param string $footer      Footer text.
 * @param array  $buttons     Array of button configs.
 *
 * @return array
 */
if ( ! function_exists( 'wa_build_template_api_payload' ) ) :
    function wa_build_template_api_payload( $name, $category, $language, $header_type, $header_text, $body, $footer, $buttons = [], $header_handle = '', $header_url = '', $template_type = 'STANDARD', $carousel_cards_json = '[]' ) {

        // --- Helper: convert {{var_name}} → {{1}}, {{2}}, ... and collect param names ---
        $process_vars = function( string $text, bool $is_url = false ) use ( &$counter_ref ) {
            $params  = [];
            $varMap  = [];
            $counter = 0;

            $transformed = preg_replace_callback(
                '/\{\{\s*(?:var\s+)?(.*?)\s*\}\}/',
                function ( $m ) use ( &$params, &$varMap, &$counter, $is_url ) {
                    $original = trim( $m[1] );
                    $prop     = $original;
                    if ( strpos( $prop, '.' ) !== false ) {
                        $parts = explode( '.', $prop );
                        $prop  = end( $parts );
                    }
                    $prop     = str_replace( '()', '', $prop );
                    $clean    = preg_replace( '/[^a-zA-Z0-9_]/', '', $prop ) ?: 'var';

                    if ( $is_url ) {
                        if ( ! isset( $varMap[ $clean ] ) ) {
                            $varMap[ $clean ] = $clean;
                            $params[]         = $clean;
                        }
                        return '{{' . $varMap[ $clean ] . '}}';
                    }

                    if ( ! isset( $varMap[ $clean ] ) ) {
                        $counter++;
                        $varMap[ $clean ] = $counter;
                        $params[]         = $clean;
                    }
                    return '{{' . $varMap[ $clean ] . '}}';
                },
                $text
            );

            return [ 'text' => trim( $transformed ), 'params' => $params ];
        };

        // Normalise template name: lowercase, underscores, no specials
        $safe_name = strtolower( preg_replace( '/[^a-z0-9_]/i', '_', $name ) );
        $safe_name = preg_replace( '/_+/', '_', $safe_name );

        // Determine API type field (mirrors Magento MetaTemplatePayloadBuilder)
        $type = strtoupper( $template_type );
        if ( $type === 'CAROUSEL' ) {
            $api_type = 'CAROUSEL';
        } elseif ( in_array( strtoupper( $header_type ), [ 'IMAGE', 'VIDEO', 'DOCUMENT' ] ) ) {
            $api_type = strtoupper( $header_type );
        } else {
            $api_type = 'TEXT';
        }

        $payload = [
            'name'     => $safe_name,
            'category' => strtoupper( $category ),
            'type'     => $api_type,
            'language' => $language,
        ];

        // --- CAROUSEL ---
        if ( $template_type === 'CAROUSEL' ) {
            $cards_data = json_decode( $carousel_cards_json, true );
            if ( ! is_array( $cards_data ) ) {
                $cards_data = [];
            }

            $cards = [];
            foreach ( $cards_data as $card ) {
                $c_header_type   = strtoupper( $card['header_type'] ?? 'IMAGE' );
                $c_header_handle = $card['header_handle'] ?? '';

                $card_entry = [
                    'header' => [ 'type' => 'HEADER', 'format' => $c_header_type ],
                    'body'   => [ 'type' => 'BODY', 'format' => 'TEXT', 'text' => $card['body'] ?? '' ],
                ];

                if ( $c_header_handle ) {
                    $card_entry['header']['media'] = [ 'id' => $c_header_handle ];
                }

                $card_buttons = [];
                foreach ( $card['buttons'] ?? [] as $btn ) {
                    $btn_type  = strtoupper( $btn['type'] ?? 'URL' );
                    $btn_entry = [ 'type' => $btn_type, 'text' => $btn['text'] ?? '' ];
                    if ( $btn_type === 'URL' ) {
                        $btn_entry['url'] = $btn['button_url'] ?? '';
                    } elseif ( $btn_type === 'PHONE_NUMBER' ) {
                        $btn_entry['phone_number'] = $btn['phone_number'] ?? '';
                    }
                    $card_buttons[] = $btn_entry;
                }
                if ( ! empty( $card_buttons ) ) {
                    $card_entry['buttons'] = $card_buttons;
                }

                $cards[] = $card_entry;
            }

            $payload['cards'] = $cards;
            return $payload;
        }

        // --- STANDARD Template ---

        // HEADER
        $header_type_upper = strtoupper( $header_type );
        if ( $header_type_upper && $header_type_upper !== 'NONE' ) {
            $header = [ 'type' => 'HEADER', 'format' => $header_type_upper ];
            if ( $header_type_upper === 'TEXT' && $header_text ) {
                $header['text'] = $header_text;
            } elseif ( in_array( $header_type_upper, [ 'IMAGE', 'VIDEO', 'DOCUMENT' ] ) ) {
                if ( $header_handle ) {
                    $header['media'] = [ 'id' => $header_handle ];
                }
            }
            $payload['header'] = $header;
        }

        // BODY — convert {{var}} → {{1}} and collect params
        if ( $body ) {
            // Collapse items loop into a single {{items_summary}} var before numbering
            $body_clean  = preg_replace( '/\{\{#items\}\}[\s\S]*?\{\{\/items\}\}/', '{{items_summary}}', $body );
            $body_result = $process_vars( $body_clean );

            $body_section = [
                'type'   => 'BODY',
                'format' => 'TEXT',
                'text'   => $body_result['text'],
            ];
            if ( ! empty( $body_result['params'] ) ) {
                $body_section['param'] = $body_result['params'];
            }
            $payload['body'] = $body_section;
        }

        // FOOTER
        if ( $footer ) {
            $payload['footer'] = [ 'type' => 'FOOTER', 'text' => $footer ];
        }

        // BUTTONS
        if ( ! empty( $buttons ) ) {
            $button_list = [];
            foreach ( $buttons as $btn ) {
                $btn_type = strtoupper( $btn['type'] ?? 'QUICK_REPLY' );
                $btn_text = trim( $btn['text'] ?? '' );
                if ( empty( $btn_text ) && $btn_type !== 'CATALOG' ) {
                    continue;
                }
                $btn_entry = [ 'type' => $btn_type ];
                if ( $btn_text ) {
                    $btn_entry['text'] = $btn_text;
                }
                if ( $btn_type === 'URL' ) {
                    $url_val = trim( $btn['url'] ?? $btn['button_url'] ?? '' );
                    if ( empty( $url_val ) ) {
                        continue;
                    }
                    $url_result        = $process_vars( $url_val, true );
                    $btn_entry['url']  = $url_result['text'];
                    if ( ! empty( $url_result['params'] ) ) {
                        $btn_entry['param'] = $url_result['params'];
                    }
                } elseif ( $btn_type === 'PHONE_NUMBER' ) {
                    $phone = trim( $btn['phone_number'] ?? $btn['url'] ?? '' );
                    if ( empty( $phone ) ) {
                        continue;
                    }
                    $btn_entry['phone_number'] = $phone;
                }
                $button_list[] = $btn_entry;
            }
            if ( ! empty( $button_list ) ) {
                $payload['buttons'] = $button_list;
            }
        }

        return $payload;
    }
endif;


/**
 * AJAX: Save or update a campaign.
 */
add_action( 'wp_ajax_wa_save_campaign', 'wa_save_campaign_handler' );

if ( ! function_exists( 'wa_save_campaign_handler' ) ) :
    function wa_save_campaign_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        $campaign_id        = intval( $_POST['campaign_id'] ?? 0 );
        $campaign_name      = sanitize_text_field( $_POST['campaign_name'] ?? '' );
        $template_entity_id = intval( $_POST['template_entity_id'] ?? 0 );
        $target_type        = sanitize_text_field( $_POST['target_type'] ?? 'all_customers' );
        $timezone           = sanitize_text_field( $_POST['timezone'] ?? '' );
        $trigger_type       = sanitize_text_field( $_POST['trigger_type'] ?? 'explicit_date' );
        $schedule_time      = sanitize_text_field( $_POST['schedule_time'] ?? '' );
        $cron_expression    = sanitize_text_field( $_POST['cron_expression'] ?? '' );
        $customer_groups    = isset( $_POST['customer_groups'] ) && is_array( $_POST['customer_groups'] ) ? json_encode( array_map( 'sanitize_text_field', $_POST['customer_groups'] ) ) : '';
        $variable_mapping   = isset( $_POST['variable_mapping'] ) ? wp_unslash( $_POST['variable_mapping'] ) : '';
        $contact_ids        = isset( $_POST['contact_ids'] ) && is_array( $_POST['contact_ids'] ) ? array_map( 'intval', $_POST['contact_ids'] ) : [];
        if ( empty( $campaign_name ) || empty( $template_entity_id ) ) {
            wp_send_json_error( __( 'Campaign name and template are required.', 'whatsapp-connector' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'azguards_whatsapp_campaigns';

        // Check for existing campaign to preserve status and scheduler_id
        $existing_scheduler_id = null;
        $status                = 'SCHEDULED';
        if ( $campaign_id ) {
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT scheduler_id, status FROM $table WHERE campaign_id = %d", $campaign_id ) );
            if ( $existing ) {
                $existing_scheduler_id = $existing->scheduler_id;
                $status                = $existing->status ?: 'SCHEDULED';
            }
        }

        // Convert datetime-local format to MySQL datetime
        $schedule_mysql = '0000-00-00 00:00:00';
        if ( ! empty( $schedule_time ) ) {
            $schedule_mysql = date( 'Y-m-d H:i:s', strtotime( $schedule_time ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'azguards_whatsapp_campaigns';

        $data = [
            'campaign_name'      => $campaign_name,
            'template_entity_id' => $template_entity_id,
            'target_type'        => $target_type,
            'timezone'           => $timezone,
            'trigger_type'       => $trigger_type,
            'schedule_time'      => $schedule_mysql,
            'cron_expression'    => $cron_expression,
            'customer_groups'    => $customer_groups,
            'variable_mapping'   => $variable_mapping,
            'contact_ids'        => ! empty( $contact_ids ) ? json_encode( $contact_ids ) : null,
            'status'             => $status,
        ];

        // Ensure wc-campaign-api.php is loaded if not already
        if ( ! function_exists( 'wa_build_scheduler_payload' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'wc-campaign-api.php';
        }

        $payload = wa_build_scheduler_payload( $campaign_id, $data );
        if ( is_wp_error( $payload ) ) {
            $error_msg = $payload->get_error_message();
            error_log( '[WA Campaign] Payload build error: ' . $error_msg );
            wp_send_json_error( __( 'Failed to build payload: ', 'whatsapp-connector' ) . $error_msg );
        }
        
        // contact_ids is persisted as JSON - payload uses it but don't remove from $data

        $token    = wa_get_valid_token();
        if ( is_wp_error( $token ) ) {
            wp_send_json_error( __( 'Failed to retrieve access token: ', 'whatsapp-connector' ) . $token->get_error_message() );
        }
        $api_base = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
        
        $api_error = '';

        if ( $existing_scheduler_id ) {
            // Update external schedule
            $url = $api_base . '/scheduler-service/api/v1/schedule/' . $existing_scheduler_id;
            $response = wp_remote_request( $url, [
                'method'  => 'PUT',
                'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            ] );
            
            $code = wp_remote_retrieve_response_code( $response );
            error_log( '[WA Campaign] UPDATE response code: ' . $code . ' body: ' . wp_remote_retrieve_body( $response ) );
            if ( is_wp_error( $response ) || $code >= 400 ) {
                 $api_error = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response );
                 error_log( '[WA Campaign] UPDATE API error: ' . $api_error );
            } else {
                 $res_body = json_decode( wp_remote_retrieve_body( $response ), true );
                 $extracted_status = $res_body['result']['status'] 
                    ?? $res_body['data']['status'] 
                    ?? $res_body['result']['data']['status'] 
                    ?? $res_body['status'] 
                    ?? null;
                 if ( $extracted_status ) {
                     $data['status'] = $extracted_status;
                 }
            }

            $wpdb->update( $table, $data, [ 'campaign_id' => $campaign_id ] );
            
            if ( $api_error ) {
                wp_send_json_error( 'Saved locally but API update failed: ' . $api_error );
            } else {
                wp_send_json_success( [ 'message' => __( 'Campaign updated successfully on Meta API.', 'whatsapp-connector' ), 'campaign_id' => $campaign_id ] );
            }
        } else {
            // Create new external schedule
            $url = $api_base . '/scheduler-service/api/v1/schedule';
            $response = wp_remote_post( $url, [
                'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            ] );

            $code = wp_remote_retrieve_response_code( $response );
            error_log( '[WA Campaign] CREATE response code: ' . $code . ' body: ' . wp_remote_retrieve_body( $response ) );
            if ( is_wp_error( $response ) || $code >= 400 ) {
                 $api_error = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code . ': ' . wp_remote_retrieve_body( $response );
                 error_log( '[WA Campaign] CREATE API error: ' . $api_error );
            } else {
                 $res_body = json_decode( wp_remote_retrieve_body( $response ), true );
                 $extracted_status = $res_body['result']['status'] 
                    ?? $res_body['data']['status'] 
                    ?? $res_body['result']['data']['status'] 
                    ?? $res_body['status'] 
                    ?? null;
                 if ( $extracted_status ) {
                     $data['status'] = $extracted_status;
                 }
                 // Try to extract scheduler job ID from result (matching Magento logic)
                 $scheduler_id = $res_body['id'] 
                    ?? $res_body['result']['id'] 
                    ?? $res_body['data']['id'] 
                    ?? $res_body['result']['data']['id'] 
                    ?? null;
                 if ( $scheduler_id ) {
                     $data['scheduler_id'] = $scheduler_id;
                 }
            }

            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            $new_id = $wpdb->insert_id;

            if ( ! $new_id ) {
                wp_send_json_error( __( 'Failed to create campaign in DB.', 'whatsapp-connector' ) );
            }

            if ( $api_error ) {
                wp_send_json_error( 'Saved locally but Meta API returned error: ' . $api_error );
            } else {
                wp_send_json_success( [ 'message' => __( 'Campaign created and scheduled on Meta API successfully.', 'whatsapp-connector' ), 'campaign_id' => $new_id ] );
            }
        }
    }
endif;

/**
 * AJAX: Upload media to API and return document_id and preview_link.
 */
add_action( 'wp_ajax_wa_upload_template_media', 'wa_upload_template_media_handler' );

if ( ! function_exists( 'wa_upload_template_media_handler' ) ) :
    function wa_upload_template_media_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        $attachment_id = intval( $_POST['attachment_id'] ?? 0 );
        if ( ! $attachment_id ) {
            wp_send_json_error( __( 'Invalid attachment ID.', 'whatsapp-connector' ) );
        }

        $file_path = get_attached_file( $attachment_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_send_json_error( __( 'File not found.', 'whatsapp-connector' ) );
        }

        $mime_type = get_post_mime_type( $attachment_id );
        $file_name = basename( $file_path );

        $token = wa_get_valid_token();
        if ( is_wp_error( $token ) ) {
            wp_send_json_error( __( 'Authentication failed: ', 'whatsapp-connector' ) . $token->get_error_message() );
        }

        $api_base = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
        
        // 1. Create Document
        $url = $api_base . '/data-manager-service/v1/document';
        $payload = [
            'name' => $file_name,
            'dataSetName' => 'TEMPLATE_MEDIA',
            'contentType' => $mime_type
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( __( 'Failed to create document: ', 'whatsapp-connector' ) . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        $document_id = $data['id'] ?? $data['docId'] ?? $data['result']['id'] ?? $data['result']['docId'] ?? null;
        $presign_url = $data['preSignLink'] ?? $data['result']['preSignLink'] ?? null;

        if ( ! $document_id || ! $presign_url ) {
            wp_send_json_error( __( 'Failed to retrieve preSignLink from API response.', 'whatsapp-connector' ) );
        }

        // 2. Upload to S3
        $file_contents = file_get_contents( $file_path );
        $s3_response = wp_remote_request( $presign_url, [
            'method'  => 'PUT',
            'headers' => [
                'Content-Type' => $mime_type,
            ],
            'body'    => $file_contents,
            'timeout' => 30, // Upload might take longer
        ] );

        $s3_code = wp_remote_retrieve_response_code( $s3_response );
        if ( is_wp_error( $s3_response ) || $s3_code >= 300 ) {
            wp_send_json_error( __( 'Failed to upload to S3.', 'whatsapp-connector' ) );
        }

        // 3. Fetch Preview Link (with retries)
        $preview_url = $api_base . '/data-manager-service/v1/document/' . $document_id . '?fetchPreviewLink=true';
        $preview_link = '';
        
        for ( $i = 0; $i < 6; $i++ ) {
            $prev_res = wp_remote_get( $preview_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ],
                'timeout' => 10,
            ] );

            if ( ! is_wp_error( $prev_res ) ) {
                $prev_data = json_decode( wp_remote_retrieve_body( $prev_res ), true );
                $link = $prev_data['previewLink'] ?? $prev_data['result']['previewLink'] ?? $prev_data['result']['preSignLink'] ?? $prev_data['preSignLink'] ?? null;
                if ( $link ) {
                    $preview_link = $link;
                    break;
                }
            }
            sleep( 1 ); // Wait 1 second before retrying
        }

        wp_send_json_success( [
            'document_id'  => $document_id,
            'preview_link' => $preview_link ?: wp_get_attachment_url( $attachment_id ),
            'local_url'    => wp_get_attachment_url( $attachment_id ),
        ] );
    }
endif;

/**
 * AJAX: Delete a campaign.
 */
add_action( 'wp_ajax_wa_delete_campaign', 'wa_delete_campaign_handler' );

if ( ! function_exists( 'wa_delete_campaign_handler' ) ) :
    function wa_delete_campaign_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        $campaign_id = intval( $_POST['campaign_id'] ?? 0 );
        if ( ! $campaign_id ) {
            wp_send_json_error( __( 'Invalid campaign ID.', 'whatsapp-connector' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'azguards_whatsapp_campaigns';
        $wpdb->delete( $table, [ 'campaign_id' => $campaign_id ] );

        wp_send_json_success( [ 'message' => __( 'Campaign deleted.', 'whatsapp-connector' ) ] );
    }
endif;
