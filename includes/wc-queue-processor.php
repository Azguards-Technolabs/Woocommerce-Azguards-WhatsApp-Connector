<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register 1-Minute Cron Schedule.
 */
add_filter( 'cron_schedules', 'wa_add_1min_cron_schedule' );
function wa_add_1min_cron_schedule( $schedules ) {
    $schedules['1min'] = array(
        'interval' => 60,
        'display'  => __( 'Every Minute' ),
    );
    return $schedules;
}

add_action( 'wp', 'wa_schedule_queue_processor_cron' );
function wa_schedule_queue_processor_cron() {
    if ( ! wp_next_scheduled( 'wa_queue_processor_monitor' ) ) {
        wp_schedule_event( time(), '1min', 'wa_queue_processor_monitor' );
    }
}

add_action( 'wa_queue_processor_monitor', 'wa_process_message_queue' );

/**
 * Campaign Queue Worker — mirrors Magento's CampaignWorkerService.
 *
 * Fetches PENDING items from azguards_whatsapp_campaign_queue, sends each message
 * via WA_Message::send_message, then updates the item status to 'SENT' or 'FAILED'.
 * Also updates the parent campaign's sent_count / failed_count.
 *
 * Concurrency is prevented by a short-lived transient lock (55 sec).
 * Batch size: 25 items per run (configurable via wa_queue_batch_size option).
 */
function wa_process_message_queue() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    // --- Concurrency lock (mirrors Magento LockManagerInterface) ---
    if ( get_transient( 'az_wa_queue_lock' ) ) {
        error_log( '[WA Queue] Skipped: another execution is already running.' );
        return;
    }
    set_transient( 'az_wa_queue_lock', true, 55 );

    global $wpdb;
    $table_queue     = $wpdb->prefix . 'azguards_whatsapp_campaign_queue';
    $table_campaigns = $wpdb->prefix . 'azguards_whatsapp_campaigns';
    $table_templates = $wpdb->prefix . 'azguards_whatsapp_templates';

    $batch_size = (int) get_option( 'wa_queue_batch_size', 25 );

    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT q.id, q.campaign_id, q.recipient_phone, q.variable_mapping
         FROM {$table_queue} q
         WHERE q.status = 'PENDING'
         ORDER BY q.created_at ASC
         LIMIT %d",
        $batch_size
    ), ARRAY_A );

    if ( empty( $items ) ) {
        delete_transient( 'az_wa_queue_lock' );
        return;
    }

    error_log( sprintf( '[WA Queue] Processing %d queued message(s).', count( $items ) ) );

    // Pre-load campaign → template mapping to avoid N+1 queries.
    $campaign_ids = array_unique( array_filter( array_column( $items, 'campaign_id' ) ) );
    $campaign_map = [];
    if ( ! empty( $campaign_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $campaign_ids ), '%d' ) );
        $campaigns    = $wpdb->get_results( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            "SELECT c.campaign_id, c.template_entity_id, t.template_id, t.status AS template_status
             FROM {$table_campaigns} c
             LEFT JOIN {$table_templates} t ON t.entity_id = c.template_entity_id
             WHERE c.campaign_id IN ({$placeholders})",
            ...$campaign_ids
        ), ARRAY_A );

        foreach ( $campaigns as $row ) {
            $campaign_map[ (int) $row['campaign_id'] ] = $row;
        }
    }

    // Counters per campaign for batch stat update.
    $sent_counts   = [];
    $failed_counts = [];

    foreach ( $items as $item ) {
        $item_id     = (int) $item['id'];
        $campaign_id = (int) $item['campaign_id'];

        $campaign = $campaign_map[ $campaign_id ] ?? null;

        if ( ! $campaign || empty( $campaign['template_id'] ) ) {
            wa_queue_mark_item( $item_id, 'FAILED', 'Template not found for campaign.' );
            $failed_counts[ $campaign_id ] = ( $failed_counts[ $campaign_id ] ?? 0 ) + 1;
            continue;
        }

        if ( strtoupper( $campaign['template_status'] ?? '' ) !== 'APPROVED' ) {
            wa_queue_mark_item( $item_id, 'FAILED', 'Template is not APPROVED.' );
            $failed_counts[ $campaign_id ] = ( $failed_counts[ $campaign_id ] ?? 0 ) + 1;
            continue;
        }

        // Build user_detail from the recipient_phone stored in the queue.
        $phone       = preg_replace( '/[^0-9]/', '', $item['recipient_phone'] ?? '' );
        $country     = get_option( 'wa_default_country', 'IN' );
        $user_detail = [
            'firstName'    => '',
            'lastName'     => '',
            'countryCode'  => wa_get_dial_code_by_country( $country ),
            'mobileNumber' => $phone,
            'email'        => '',
            'website'      => get_site_url(),
        ];

        // Decode per-item variable mapping (JSON: { "1": "value", "2": "value" }).
        $variables = [];
        if ( ! empty( $item['variable_mapping'] ) ) {
            $mapping = json_decode( $item['variable_mapping'], true );
            if ( is_array( $mapping ) ) {
                $variables = $mapping;
            }
        }

        // Mark as PROCESSING to prevent re-processing if cron overlaps.
        $wpdb->update( $table_queue, [ 'status' => 'PROCESSING' ], [ 'id' => $item_id ] );

        $result = WA_Message::send_message( $variables, $campaign['template_id'], "Campaign Queue Item #{$item_id}", $user_detail );

        if ( is_wp_error( $result ) ) {
            $error_msg = $result->get_error_message();
            wa_queue_mark_item( $item_id, 'FAILED', $error_msg );
            $failed_counts[ $campaign_id ] = ( $failed_counts[ $campaign_id ] ?? 0 ) + 1;
            error_log( "[WA Queue] Item #{$item_id} FAILED: {$error_msg}" );
        } else {
            // Check API-level failure (some APIs return success=false in body).
            $api_success = isset( $result['success'] ) ? (bool) $result['success'] : true;
            if ( $api_success ) {
                wa_queue_mark_item( $item_id, 'SENT', null );
                $sent_counts[ $campaign_id ] = ( $sent_counts[ $campaign_id ] ?? 0 ) + 1;
                error_log( "[WA Queue] Item #{$item_id} SENT successfully." );
            } else {
                $error_msg = $result['message'] ?? 'API returned failure.';
                wa_queue_mark_item( $item_id, 'FAILED', $error_msg );
                $failed_counts[ $campaign_id ] = ( $failed_counts[ $campaign_id ] ?? 0 ) + 1;
                error_log( "[WA Queue] Item #{$item_id} FAILED (API): {$error_msg}" );
            }
        }
    }

    // --- Batch-update campaign sent_count / failed_count ---
    foreach ( $sent_counts as $cid => $count ) {
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$table_campaigns} SET sent_count = sent_count + %d WHERE campaign_id = %d",
            $count,
            $cid
        ) );
    }
    foreach ( $failed_counts as $cid => $count ) {
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$table_campaigns} SET failed_count = failed_count + %d WHERE campaign_id = %d",
            $count,
            $cid
        ) );
    }

    error_log( sprintf(
        '[WA Queue] Batch complete. Sent=%d Failed=%d',
        array_sum( $sent_counts ),
        array_sum( $failed_counts )
    ) );

    delete_transient( 'az_wa_queue_lock' );
}

/**
 * Update a queue item's status and processed_at timestamp.
 *
 * @param int         $item_id   Queue item ID.
 * @param string      $status    'SENT', 'FAILED', or 'PROCESSING'.
 * @param string|null $error_msg Optional error message.
 */
function wa_queue_mark_item( $item_id, $status, $error_msg = null ) {
    global $wpdb;
    $table_queue = $wpdb->prefix . 'azguards_whatsapp_campaign_queue';
    $wpdb->update(
        $table_queue,
        [
            'status'       => $status,
            'error_message'=> $error_msg,
            'processed_at' => current_time( 'mysql' ),
        ],
        [ 'id' => $item_id ]
    );
}
