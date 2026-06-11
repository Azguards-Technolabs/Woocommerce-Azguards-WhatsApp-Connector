<?php
/**
 * Plugin Name:       WhatsApp Connector
 * Description:       Adds WhatsApp template configuration to WooCommerce settings.
 * Version:           1.0
 * Author:            Azguards Technolabs
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_WHATSAPP_CONNECTOR_PATH', plugin_dir_path( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'includes/wc-defaults.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-auth.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-templates.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-contact.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-message.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-variable.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-woocommerce-variables.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-variables-configuration-table.php';
require_once plugin_dir_path( __FILE__ ) . 'sendMessage/user-register.php';
require_once plugin_dir_path( __FILE__ ) . 'sendMessage/helper.php';
require_once plugin_dir_path( __FILE__ ) . 'sendMessage/woocommerce-hooks.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/authentication/validate-credentials.php';

/**
 * Set plugin default values on activation.
 */
function whatsapp_connector_plugin_default_values() {
    $defaults = WA_CONNECTOR_DEFAULTS;

    // General Settings
    foreach ( $defaults['general'] as $key => $value ) {
        add_option( $key, $value );
    }

    // Cron Settings
    foreach ( $defaults['cron'] as $key => $value ) {
        add_option( $key, $value );
    }

    // Template Settings
    foreach ( $defaults['templates'] as $hook => $data ) {
        foreach ( $data as $field => $val ) {
            add_option( "wa_template_{$hook}_{$field}", $val );
        }
    }

    // Widget Settings
    foreach ( $defaults['widget'] as $key => $value ) {
        add_option( $key, $value );
    }

    // Legacy/Internal URLs (keeping them just in case, but updating to match defaults where applicable)
    add_option( 'wa_contact_api_url', 'https://wp-conn.aztechstaging.in/v1/contact' );
    add_option( 'wa_message_api_url', $defaults['general']['wa_template_api_url'] . 'v1/message/sendTemplate' );
}
add_action( 'whatsapp_connector_plugin_default_options', 'whatsapp_connector_plugin_default_values' );

require_once plugin_dir_path( __FILE__ ) . 'includes/wc-database.php';

/**
 * Plugin activation hook.
 */
function whatsapp_connector_plugin_activation() {
    whatsapp_connector_plugin_default_values();
    WA_Database::create_tables();
    whatsapp_connector_schedule_crons();
}
register_activation_hook( __FILE__, 'whatsapp_connector_plugin_activation' );

/**
 * Schedule crons on activation or when settings change.
 */
function whatsapp_connector_schedule_crons() {
    $campaign_interval = get_option( 'wa_campaign_sync_schedule', 1 );
    $contact_interval  = get_option( 'wa_contact_sync_schedule', 1 );
    $template_interval = get_option( 'wa_template_sync_schedule', 60 );

    if ( ! wp_next_scheduled( 'wa_campaign_sync_event' ) ) {
        wp_schedule_event( time(), 'wa_custom_campaign', 'wa_campaign_sync_event' );
    }
    if ( ! wp_next_scheduled( 'wa_contact_sync_event' ) ) {
        wp_schedule_event( time(), 'wa_custom_contact', 'wa_contact_sync_event' );
    }
    if ( ! wp_next_scheduled( 'wa_template_sync_event' ) ) {
        wp_schedule_event( time(), 'wa_custom_template', 'wa_template_sync_event' );
    }
    if ( ! wp_next_scheduled( 'wa_abandoned_cart_check_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'wa_abandoned_cart_check_event' );
    }
}

/**
 * Add custom cron intervals.
 */
add_filter( 'cron_schedules', function ( $schedules ) {
    $campaign_min = get_option( 'wa_campaign_sync_schedule', 1 );
    $contact_min  = get_option( 'wa_contact_sync_schedule', 1 );
    $template_min = get_option( 'wa_template_sync_schedule', 60 );

    $schedules['wa_custom_campaign'] = [
        'interval' => $campaign_min * 60,
        'display'  => sprintf( __( 'Every %d Minutes', 'whatsapp-connector' ), $campaign_min ),
    ];
    $schedules['wa_custom_contact'] = [
        'interval' => $contact_min * 60,
        'display'  => sprintf( __( 'Every %d Minutes', 'whatsapp-connector' ), $contact_min ),
    ];
    $schedules['wa_custom_template'] = [
        'interval' => $template_min * 60,
        'display'  => sprintf( __( 'Every %d Minutes', 'whatsapp-connector' ), $template_min ),
    ];
    return $schedules;
} );

/**
 * Hook cron events to their respective functions.
 */
add_action( 'wa_campaign_sync_event', 'wa_sync_campaigns' );
add_action( 'wa_contact_sync_event', 'wa_sync_contacts' );
add_action( 'wa_template_sync_event', 'wa_sync_templates' );
add_action( 'wa_abandoned_cart_check_event', 'wa_check_abandoned_carts' );

function wa_sync_campaigns() {
    // Logic to sync campaigns from API
    error_log( 'WhatsApp Connector: Syncing Campaigns...' );
}

function wa_sync_contacts() {
    // Logic to push new customers to WhatTalk
    error_log( 'WhatsApp Connector: Syncing Contacts...' );
}

function wa_sync_templates() {
    // Logic to pull templates from API
    error_log( 'WhatsApp Connector: Syncing Templates...' );
}

function wa_check_abandoned_carts() {
    if ( get_option( 'wa_enable_connector' ) !== 'yes' || get_option( 'wa_enable_abandoned_cart' ) !== 'yes' ) {
        return;
    }

    $abandon_after = get_option( 'wa_abandon_after_minutes', 60 );
    $max_per_run   = get_option( 'wa_max_per_run', 50 );

    $args = [
        'status'         => 'pending',
        'limit'          => $max_per_run,
        'date_modified'  => '<' . ( time() - ( $abandon_after * 60 ) ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $orders = wc_get_orders( $args );

    foreach ( $orders as $order ) {
        // Check if already notified
        $already_sent = get_post_meta( $order->get_id(), '_wa_abandoned_notified', true );
        if ( ! $already_sent ) {
            wa_trigger_abandoned_cart_whatsapp( $order->get_id() );
            update_post_meta( $order->get_id(), '_wa_abandoned_notified', 'yes' );
        }
    }
}

/**
 * Enqueue admin styles and scripts.
 */
add_action( 'admin_enqueue_scripts', function () {
    wp_enqueue_style( 'wa-connector-css', plugins_url( 'assets/admin.css', __FILE__ ) );
    wp_enqueue_script( 'wa-connector-js', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery' ), null, true );
    wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
    wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), null, true );
} );

/**
 * Add settings page to WooCommerce.
 */
add_filter( 'woocommerce_get_settings_pages', function ( $pages ) {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/settings-page.php';
    $pages[] = new WC_Settings_WhatsApp_Connector();
    return $pages;
} );

/**
 * Add Top Level Menu for WhatsApp Grids instead of hiding in WooCommerce
 */
add_action( 'admin_menu', function () {
    // 1. WhatsApp Campaigns (Top Level Menu)
    add_menu_page(
        __( 'WhatsApp Campaigns', 'whatsapp-connector' ),
        __( 'WhatsApp Campaigns', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-campaigns',
        'wa_render_campaigns_grid',
        'dashicons-megaphone',
        56
    );

    // 2. WhatsApp Templates (Submenu under Campaigns or Top Level)
    add_submenu_page(
        'wa-campaigns',
        __( 'Templates', 'whatsapp-connector' ),
        __( 'Templates', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-templates-grid',
        'wa_render_templates_grid'
    );

    // Hidden builder routes
    add_submenu_page(
        null, // Hidden menu
        __( 'Create Template', 'whatsapp-connector' ),
        __( 'Create Template', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-template-builder',
        'wa_render_template_builder_page'
    );
    add_submenu_page(
        null,
        __( 'Edit Campaign', 'whatsapp-connector' ),
        __( 'Edit Campaign', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-campaign-edit',
        'wa_render_campaign_edit_page'
    );
} );

function wa_render_campaigns_grid() {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-campaigns-grid.php';
}

function wa_render_templates_grid() {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-templates-grid.php';
}

function wa_render_template_builder_page() {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-templates-page.php';
}

function wa_render_campaign_edit_page() {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-campaigns-edit.php';
}

/**
 * Load plugin after all plugins are loaded.
 */
function wa_load_plugin() {
    if ( ! class_exists( 'WC_Settings_Page' ) ) {
        return; // WooCommerce not active.
    }

    // require_once plugin_dir_path( __FILE__ ) . 'includes/wc-settings-whatsapp-connector.php';

    add_filter( 'woocommerce_get_settings_pages', function ( $settings ) {
        require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/settings-page.php';
        $settings[] = new WC_Settings_WhatsApp_Connector();
        return $settings;
    } );
}
add_action( 'plugins_loaded', 'wa_load_plugin' );
