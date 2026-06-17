<?php
/**
 * Campaign API Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build the payload for the Meta Scheduler API from local WP data.
 * 
 * contactNumber must be an OBJECT of { "phone": "display_name" }
 * This mirrors Magento's buildSchedulerPayload exactly.
 *
 * @param int   $campaign_id  0 for new campaigns
 * @param array $data         Campaign data array from the form
 * @return array|WP_Error
 */
function wa_build_scheduler_payload( $campaign_id, $data ) {
    global $wpdb;
    $tpl_table = $wpdb->prefix . 'azguards_whatsapp_templates';

    // Get the template details and body
    $template = $wpdb->get_row( $wpdb->prepare(
        "SELECT template_id, template_name, body FROM $tpl_table WHERE entity_id = %d",
        $data['template_entity_id']
    ) );

    if ( ! $template ) {
        return new WP_Error( 'invalid_template', 'Invalid template specified.' );
    }

    // Build contact list
    $contact_ids_raw = $data['contact_ids'] ?? [];
    if ( is_string( $contact_ids_raw ) && $contact_ids_raw !== '' ) {
        $contact_ids_raw = json_decode( $contact_ids_raw, true ) ?: [];
    }
    $contact_ids     = is_array( $contact_ids_raw ) ? array_map( 'intval', $contact_ids_raw ) : [];
    $customer_groups = ! empty( $data['customer_groups'] ) ? json_decode( $data['customer_groups'], true ) : [];
    
    // If target type is specific_contacts but no ids are passed, error.
    if ( isset( $data['target_type'] ) && $data['target_type'] === 'specific_contacts' && empty( $contact_ids ) ) {
        return new WP_Error( 'no_contacts', 'Specific contacts were selected but none provided.' );
    }

    $contactNumbers = wa_build_contact_numbers( $contact_ids, $customer_groups );

    if ( empty( $contactNumbers ) ) {
        return new WP_Error( 'no_contacts', 'No synced WhatTack contacts were found for this campaign.' );
    }

    // Resolve userId / businessId (populated by wc-auth.php on token refresh)
    $user_id     = get_option( 'wa_user_id',     '' );
    $business_id = get_option( 'wa_business_id', '' );

    $triggerConfig = [
        'trigger_type' => $data['trigger_type'] === 'cron' ? 'CRON' : 'EXPLICIT_DATE',
        'description'  => $data['campaign_name'],
        'time_zone'    => $data['timezone'] ?: 'UTC',
    ];

    if ( $triggerConfig['trigger_type'] === 'EXPLICIT_DATE' ) {
        // Magento converts to UTC timezone and appends Z
        $timezone_string = $data['timezone'] ?: wp_timezone_string();
        $date = new DateTime( $data['schedule_time'], new DateTimeZone( $timezone_string ) );
        $date->setTimezone( new DateTimeZone( 'UTC' ) );
        $triggerConfig['execution_date_time'] = $date->format( 'Y-m-d\TH:i:s\Z' );
        $triggerConfig['time_zone'] = 'UTC';
    } else {
        $triggerConfig['cron_expression'] = $data['cron_expression'];
    }

    // Extract placeholders from template body mimicking Magento functionality
    $bodyText   = $template->body ?? '';
    $attributes = [];
    
    preg_match_all( '/\{\{\s*([^}]+?)\s*\}\}/', $bodyText, $matches );
    $variables = [];
    foreach ( $matches[1] ?? [] as $variable ) {
        $variable = trim( $variable );
        if ( $variable !== '' && ! in_array( $variable, $variables, true ) ) {
            $variables[] = $variable;
        }
    }

    if ( ! empty( $variables ) ) {
        $bodyPlaceholders = [];
        $bodyOrder        = 1;

        // Give a basic sensible default for {{1}} if no mapping exists, mimicking the Magento placeholder resolver
        $first_contact_name  = reset( $contactNumbers );
        $first_name          = $first_contact_name ? explode( ' ', $first_contact_name )[0] : '';
        $first_contact_phone = key( $contactNumbers );
        
        $variable_mapping = isset( $data['variable_mapping'] ) ? json_decode( $data['variable_mapping'], true ) : [];
        if ( ! is_array( $variable_mapping ) ) {
            $variable_mapping = [];
        }

        foreach ( $variables as $variable ) {
            $is_numeric = is_numeric( $variable );
            $val        = '';
            $attr_name  = $variable;

            $mapped = $variable_mapping[$variable] ?? null;

            if ( $mapped ) {
                if ( ! empty( $mapped['value'] ) ) {
                    $val = $mapped['value'];
                } elseif ( ! empty( $mapped['path'] ) ) {
                    $path = $mapped['path'];
                    if ( in_array( $path, [ 'firstname', 'lastname', 'name' ], true ) ) {
                        $val       = $first_contact_name; // basic fallback
                        $attr_name = $path;
                    } elseif ( $path === 'phone' ) {
                        $val       = $first_contact_phone;
                        $attr_name = 'phone';
                    }
                }
            } else {
                if ( $is_numeric && $variable == '1' ) {
                    $val       = $first_name;
                    $attr_name = 'name';
                } elseif ( $is_numeric && $variable == '2' ) {
                    $attr_name = 'order_id';
                }
            }

            $bodyPlaceholders[] = [
                'key'               => (string) $bodyOrder++,
                'value'             => $val,
                'is_user_attribute' => false,
                'attribute_name'    => $attr_name,
            ];
        }

        $attributes['body'] = [
            'order'        => 1,
            'placeholders' => $bodyPlaceholders,
        ];
    }

    $api_status = 'SCHEDULED';
    if ( $campaign_id ) {
        $api_status = 'RESCHEDULED';
    }

    $payload = [
        'status'         => $api_status,
        'trigger_config' => $triggerConfig,
        'job_data'       => [
            'userId'        => $user_id,
            'businessId'    => $business_id,
            'templateId'    => $template->template_id,
            'templateName'  => $template->template_name ?: $data['campaign_name'],
            'contactNumber' => (object) $contactNumbers,
            'attributes'    => (object) $attributes,
        ],
    ];

    // Log the payload for debugging
    error_log( '[WhatTack Campaign Payload] ' . wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

    return $payload;
}

/**
 * Build the contactNumber map from a list of user IDs or Customer Groups (roles).
 * Falls back to all synced users if $contact_ids and $customer_groups are empty.
 *
 * @param  int[]  $contact_ids     Specific user IDs.
 * @param  string[] $customer_groups Roles array.
 * @return array  phone => display_name
 */
function wa_build_contact_numbers( array $contact_ids = [], array $customer_groups = [] ) {
    $args = [
        'meta_key'   => 'wa_whatsapp_synced',
        'meta_value' => '1',
        'number'     => -1,
        'fields'     => 'ids',
    ];
    if ( ! empty( $contact_ids ) ) {
        $args['include'] = $contact_ids;
    } elseif ( ! empty( $customer_groups ) ) {
        $args['role__in'] = $customer_groups;
    }

    $user_ids = get_users( $args );
    $contacts = [];

    foreach ( $user_ids as $uid ) {
        $phone = get_user_meta( $uid, 'wa_whatsapp_phone', true );
        if ( ! $phone ) {
            // Fall back to live calculation
            $raw_phone   = preg_replace( '/[^0-9]/', '', get_user_meta( $uid, 'billing_phone', true ) );
            $raw_country = preg_replace( '/[^0-9]/', '', wa_get_dial_code_by_country( get_user_meta( $uid, 'billing_country', true ) ) );
            if ( $raw_phone ) {
                $phone = $raw_country ? $raw_country . $raw_phone : ( strlen( $raw_phone ) === 10 ? '91' . $raw_phone : ltrim( $raw_phone, '+' ) );
            }
        }
        if ( $phone ) {
            $first = get_user_meta( $uid, 'billing_first_name', true );
            $last  = get_user_meta( $uid, 'billing_last_name', true );
            $name  = trim( "$first $last" ) ?: get_userdata( $uid )->display_name;
            $contacts[ $phone ] = $name;
        }
    }

    return $contacts;
}

/**
 * AJAX: Return synced WhatTack contacts for the campaign contact-picker.
 */
add_action( 'wp_ajax_wa_get_synced_contacts', 'wa_get_synced_contacts_handler' );
function wa_get_synced_contacts_handler() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $users = get_users( [
        'meta_key'   => 'wa_whatsapp_synced',
        'meta_value' => '1',
        'number'     => -1,
    ] );

    $contacts = [];
    foreach ( $users as $user ) {
        $first = get_user_meta( $user->ID, 'billing_first_name', true );
        $last  = get_user_meta( $user->ID, 'billing_last_name', true );
        $name  = trim( "$first $last" ) ?: $user->display_name;
        $phone = get_user_meta( $user->ID, 'wa_whatsapp_phone', true ) ?: get_user_meta( $user->ID, 'billing_phone', true );
        $contacts[] = [
            'id'    => $user->ID,
            'name'  => $name,
            'email' => $user->user_email,
            'phone' => $phone,
        ];
    }

    wp_send_json_success( $contacts );
}

/**
 * Core function to sync campaign statuses from the external Meta Scheduler API.
 *
 * @return int|WP_Error Number of synced campaigns or WP_Error on failure.
 */
if ( ! function_exists( 'wa_sync_campaigns' ) ) :
    function wa_sync_campaigns() {
        error_log( "[WA Campaign Sync] Starting campaign sync execution." );

        global $wpdb;
        $campaign_table = $wpdb->prefix . 'azguards_whatsapp_campaigns';
        $template_table = $wpdb->prefix . 'azguards_whatsapp_templates';

        if ( ! function_exists( 'wa_get_valid_token' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'wc-templates.php';
        }

        $token = wa_get_valid_token();
        if ( is_wp_error( $token ) ) {
            error_log( "[WA Campaign Sync] Authentication failed: " . $token->get_error_message() );
            return $token;
        }

        $api_base     = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
        $api_url      = $api_base . '/scheduler-service/api/v1/schedule';
        $business_id  = get_option( 'wa_business_id' );
        $user_id      = get_option( 'wa_user_id' );

        if ( empty( $business_id ) || empty( $user_id ) ) {
            error_log( "[WA Campaign Sync] Business ID or User ID missing. Attempting refresh." );
            $auth_data = WA_Auth::get_token();
            if ( is_wp_error( $auth_data ) ) {
                 return new WP_Error( 'wa_sync_config_error', 'Could not retrieve business or user IDs.' );
            }
            $business_id = get_option( 'wa_business_id' );
            $user_id     = get_option( 'wa_user_id' );
            $token       = $auth_data['access_token'] ?? $token;
        }

        $counts = [
            'received' => 0,
            'inserted' => 0,
            'updated'  => 0,
            'skipped'  => 0,
        ];

        // Fetch all remote campaigns (implementing retries)
        $response = null;
        for ( $i = 0; $i < 3; $i++ ) {
            error_log( "[WA Campaign Sync] Fetching remote campaigns. Attempt " . ($i+1) . ". URL: $api_url" );
            $response = wp_remote_get( $api_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'businessId'    => $business_id,
                    'userId'        => $user_id,
                ],
                'timeout' => 30,
            ] );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                break;
            }
            sleep( 1 );
        }

        if ( is_wp_error( $response ) ) {
            error_log( "[WA Campaign Sync] API Request Error: " . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        error_log( "[WA Campaign Sync] API Response (HTTP $code): " . $body );

        if ( $code !== 200 ) {
            return new WP_Error( 'wa_api_error', "API returned HTTP $code" );
        }

        $api_data = json_decode( $body, true );
        $remote_campaigns = $api_data['result']['data'] ?? $api_data['data'] ?? [];

        if ( empty( $remote_campaigns ) ) {
            error_log( "[WA Campaign Sync] No campaigns found on provider." );
            return 0;
        }

        $counts['received'] = count( $remote_campaigns );
        error_log( "[WA Campaign Sync] Received " . $counts['received'] . " campaigns from API." );

        foreach ( $remote_campaigns as $remote_camp ) {
            $scheduler_id = $remote_camp['id'] ?? null;
            if ( ! $scheduler_id ) {
                error_log( "[WA Campaign Sync] Skipping campaign without scheduler ID." );
                $counts['skipped']++;
                continue;
            }

            $remote_status = $remote_camp['status'] ?? 'SCHEDULED';
            $remote_name   = $remote_camp['trigger_config']['description'] ?? 'Unnamed Remote Campaign';

            // Try to find remote template ID
            $remote_template_id = $remote_camp['job_data']['templateId'] ?? null;

            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT campaign_id, status FROM $campaign_table WHERE scheduler_id = %s", $scheduler_id ) );

            if ( $existing ) {
                if ( $existing->status !== $remote_status ) {
                    error_log( "[WA Campaign Sync] Updating campaign ID " . $existing->campaign_id . " status to " . $remote_status );
                    $wpdb->update( $campaign_table, [ 'status' => $remote_status ], [ 'campaign_id' => $existing->campaign_id ] );
                    $counts['updated']++;
                } else {
                    $counts['skipped']++;
                }
            } else {
                // Insert new remote campaign
                $template_entity_id = 0;
                if ( $remote_template_id ) {
                    $template_entity_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT entity_id FROM $template_table WHERE template_id = %s", $remote_template_id ) );
                }

                error_log( "[WA Campaign Sync] Inserting new remote campaign: " . $remote_name );
                $wpdb->insert( $campaign_table, [
                    'campaign_name'      => $remote_name,
                    'template_entity_id' => $template_entity_id,
                    'target_type'        => 'remote',
                    'scheduler_id'       => $scheduler_id,
                    'status'             => $remote_status,
                    'created_at'         => current_time( 'mysql' ),
                ] );
                $counts['inserted']++;
            }
        }

        error_log( sprintf(
            "[WA Campaign Sync] Sync completed. Received: %d, Inserted: %d, Updated: %d, Skipped: %d",
            $counts['received'], $counts['inserted'], $counts['updated'], $counts['skipped']
        ) );

        return $counts['inserted'] + $counts['updated'];
    }
endif;

/**
 * AJAX: Sync campaign statuses from the external Meta Scheduler API.
 */
add_action( 'wp_ajax_wa_sync_campaigns', 'wa_sync_campaigns_handler' );
function wa_sync_campaigns_handler() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
    }

    check_ajax_referer( 'wa_sync_campaigns', 'security' );

    $result = wa_sync_campaigns();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( sprintf( __( 'Synced %d campaign(s) from Meta API.', 'whatsapp-connector' ), $result ) );
}

/**
 * Helper: get a country dial code (numeric, no +) by ISO2 code.
 * Returns '' if not found. Kept here as a fallback.
 */
if ( ! function_exists( 'wa_get_dial_code_by_country' ) ) {
    function wa_get_dial_code_by_country( $country_code ) {
        $map = [
            'IN' => '91', 'US' => '1', 'GB' => '44', 'AU' => '61',
            'CA' => '1',  'DE' => '49', 'FR' => '33', 'AE' => '971',
            'SG' => '65', 'PK' => '92', 'BD' => '880', 'NP' => '977',
        ];
        return $map[ strtoupper( (string) $country_code ) ] ?? '';
    }
}
