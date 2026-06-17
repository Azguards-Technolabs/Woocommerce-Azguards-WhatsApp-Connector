<?php
/**
 * Cron handling for WhatsApp Connector.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register dynamic cron schedules based on plugin settings.
 */
add_filter( 'cron_schedules', 'wa_register_dynamic_cron_schedules' );
function wa_register_dynamic_cron_schedules( $schedules ) {
    $campaign_interval = (int) get_option( 'wa_campaign_sync_schedule', 1 );
    $contact_interval  = (int) get_option( 'wa_contact_sync_schedule', 1 );
    $template_interval = (int) get_option( 'wa_template_sync_schedule', 60 );

    if ( $campaign_interval > 0 ) {
        $schedules["wa_campaign_sync_{$campaign_interval}min"] = array(
            'interval' => $campaign_interval * 60,
            'display'  => sprintf( __( 'Every %d Minutes (WA Campaign)', 'whatsapp-connector' ), $campaign_interval ),
        );
    }

    if ( $contact_interval > 0 ) {
        $schedules["wa_contact_sync_{$contact_interval}min"] = array(
            'interval' => $contact_interval * 60,
            'display'  => sprintf( __( 'Every %d Minutes (WA Contact)', 'whatsapp-connector' ), $contact_interval ),
        );
    }

    if ( $template_interval > 0 ) {
        $schedules["wa_template_sync_{$template_interval}min"] = array(
            'interval' => $template_interval * 60,
            'display'  => sprintf( __( 'Every %d Minutes (WA Template)', 'whatsapp-connector' ), $template_interval ),
        );
    }

    return $schedules;
}

/**
 * Reschedule all cron jobs based on current settings.
 */
function wa_reschedule_crons() {
    error_log( '[WA Cron] Rescheduling sync jobs.' );

    $jobs = [
        'wa_campaign_sync_event' => 'wa_campaign_sync_schedule',
        'wa_contact_sync_event'  => 'wa_contact_sync_schedule',
        'wa_template_sync_event' => 'wa_template_sync_schedule',
    ];

    foreach ( $jobs as $hook => $option ) {
        wp_clear_scheduled_hook( $hook );

        $interval = (int) get_option( $option, 0 );
        if ( $interval > 0 ) {
            $schedule_slug = str_replace( '_schedule', '_sync', $option ) . "_{$interval}min";
            wp_schedule_event( time(), $schedule_slug, $hook );
            error_log( "[WA Cron] Scheduled $hook every $interval minutes ($schedule_slug)." );
        }
    }
}

/**
 * Cron Hook: Campaign Sync
 */
add_action( 'wa_campaign_sync_event', 'wa_campaign_sync_cron_handler' );
function wa_campaign_sync_cron_handler() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    error_log( '[WA Cron] Running Campaign Sync.' );
    if ( function_exists( 'wa_sync_campaigns' ) ) {
        wa_sync_campaigns();
    }
}

/**
 * Cron Hook: Contact Sync
 */
add_action( 'wa_contact_sync_event', 'wa_contact_sync_cron_handler' );
function wa_contact_sync_cron_handler() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    error_log( '[WA Cron] Running Contact Sync.' );
    if ( class_exists( 'WA_Contact' ) && method_exists( 'WA_Contact', 'wa_sync_contacts' ) ) {
        WA_Contact::wa_sync_contacts();
    }
}

/**
 * Cron Hook: Template Sync
 */
add_action( 'wa_template_sync_event', 'wa_template_sync_cron_handler' );
function wa_template_sync_cron_handler() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' ) {
        return;
    }

    error_log( '[WA Cron] Running Template Sync.' );
    if ( function_exists( 'wa_sync_templates' ) ) {
        wa_sync_templates();
    }
}
