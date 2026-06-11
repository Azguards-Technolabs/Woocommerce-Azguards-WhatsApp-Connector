<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_wa_save_campaign', 'wa_save_campaign_handler' );

function wa_save_campaign_handler() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    $campaign_name = sanitize_text_field( $_POST['campaign_name'] ?? '' );
    $template_entity_id = intval( $_POST['template_entity_id'] ?? 0 );
    $target_type = sanitize_text_field( $_POST['target_type'] ?? 'Specific Contacts' );
    $schedule_time = sanitize_text_field( $_POST['schedule_time'] ?? '' );
    $targets = isset( $_POST['targets'] ) ? array_map( 'sanitize_text_field', (array)$_POST['targets'] ) : [];

    if ( empty( $campaign_name ) || empty( $template_entity_id ) || empty( $schedule_time ) ) {
        wp_send_json_error( [ 'message' => 'Missing required fields.' ] );
    }

    global $wpdb;
    $table_campaigns = $wpdb->prefix . 'azguards_whatsapp_campaigns';
    $table_queue = $wpdb->prefix . 'azguards_whatsapp_campaign_queue';
    
    // Insert campaign
    $data_to_save = [
        'campaign_name'      => $campaign_name,
        'template_entity_id' => $template_entity_id,
        'target_type'        => $target_type,
        'schedule_time'      => gmdate( 'Y-m-d H:i:s', strtotime( $schedule_time ) ),
        'status'             => 'SCHEDULED',
        'created_at'         => current_time( 'mysql' )
    ];

    $inserted = $wpdb->insert( $table_campaigns, $data_to_save );

    if ( ! $inserted ) {
        wp_send_json_error( [ 'message' => 'Failed to create campaign.' ] );
    }

    $campaign_id = $wpdb->insert_id;

    // Build the queue for each target (phone number)
    // Assume targets array contains Phone Numbers. In a real scenario it might contain Customer IDs, but we will store phone directly in queue.
    foreach ( $targets as $phone ) {
        $wpdb->insert( $table_queue, [
            'campaign_id'      => $campaign_id,
            'recipient_phone'  => $phone,
            'variable_mapping' => '{}', // Assuming static mapping for now
            'status'           => 'PENDING',
            'created_at'       => current_time( 'mysql' )
        ] );
    }

    wp_send_json_success( [ 'message' => 'Campaign saved successfully!', 'redirect' => admin_url( 'admin.php?page=wa-campaigns' ) ] );
}

add_action( 'wp_ajax_wa_get_customers', 'wa_get_customers_handler' );
function wa_get_customers_handler() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    $users = get_users();
    $contacts = [];

    foreach ( $users as $user ) {
        $phone = get_user_meta( $user->ID, 'billing_phone', true );
        if ( ! empty( $phone ) ) {
            $contacts[] = [
                'id'    => $user->ID,
                'name'  => $user->display_name,
                'email' => $user->user_email,
                'phone' => $phone
            ];
        }
    }

    wp_send_json_success( [ 'contacts' => $contacts ] );
}
