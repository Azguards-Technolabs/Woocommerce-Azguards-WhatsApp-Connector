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
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-customer-sync.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-campaign-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-message.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-variable.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-woocommerce-variables.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-variables-configuration-table.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-abandoned-cart.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/wc-queue-processor.php';
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
    add_option( 'wa_contact_api_url', 'https://whatatalk-api.azguardstech.com/v1/contact' );
    add_option( 'wa_message_api_url', 'https://whatatalk-api.azguardstech.com/v1/message/sendTemplate' );
}
add_action( 'whatsapp_connector_plugin_default_options', 'whatsapp_connector_plugin_default_values' );

require_once plugin_dir_path( __FILE__ ) . 'includes/wc-database.php';

add_action(
    'plugins_loaded',
    function () {
        if ( class_exists( 'WA_Database' ) ) {
            WA_Database::maybe_upgrade();
        }
    },
    20
);

/**
 * Plugin activation hook.
 */
function whatsapp_connector_plugin_activation() {
    do_action( 'whatsapp_connector_plugin_default_options' );
    WA_Database::create_tables();
}
register_activation_hook( __FILE__, 'whatsapp_connector_plugin_activation' );

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
    // 1. WhatTack Customers (Submenu under WooCommerce)
    add_submenu_page(
        'woocommerce',
        __( 'WhatTack Customers', 'whatsapp-connector' ),
        __( 'WhatTack Customers', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-customers',
        'wa_customers_grid_callback'
    );

    // 2. WhatTack Campaigns (Submenu under WooCommerce)
    add_submenu_page(
        'woocommerce',
        __( 'Campaigns', 'whatsapp-connector' ),
        __( 'WhatTack Campaigns', 'whatsapp-connector' ),
        'manage_woocommerce',
        'wa-campaigns',
        'wa_campaign_grid_callback'
    );

    // 2. WhatTack Templates (Submenu under WooCommerce)
    add_submenu_page(
        'woocommerce',
        __( 'WhatTack Templates', 'whatsapp-connector' ),
        __( 'WhatTack Templates', 'whatsapp-connector' ),
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

/**
 * Ensure WhatTack Template Builder and Campaign Edit pages are highlighted in WooCommerce menu
 */
add_filter( 'parent_file', function ( $parent_file ) {
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ( in_array( $current_page, [ 'wa-template-builder', 'wa-campaign-edit' ] ) ) {
        return 'woocommerce';
    }
    return $parent_file;
}, 9999 );

add_filter( 'submenu_file', function ( $submenu_file ) {
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ( $current_page === 'wa-template-builder' ) {
        return 'wa-templates-grid';
    } elseif ( $current_page === 'wa-campaign-edit' ) {
        return 'wa-campaigns';
    }
    return $submenu_file;
}, 9999 );

/**
 * Register custom screens with WooCommerce so it doesn't collapse the menu
 */
add_filter( 'woocommerce_screen_ids', function ( $ids ) {
    $ids[] = 'admin_page_wa-template-builder';
    $ids[] = 'admin_page_wa-campaign-edit';
    $ids[] = 'toplevel_page_wa-template-builder';
    $ids[] = 'toplevel_page_wa-campaign-edit';
    $ids[] = 'woocommerce_page_wa-customers';
    $ids[] = 'woocommerce_page_wa-campaigns';
    $ids[] = 'woocommerce_page_wa-templates-grid';
    return $ids;
} );

add_action( 'current_screen', function () {
    global $title;
    if ( isset( $_GET['page'] ) ) {
        if ( $_GET['page'] === 'wa-template-builder' ) {
            $title = __( 'Create Template', 'whatsapp-connector' );
        } elseif ( $_GET['page'] === 'wa-campaign-edit' ) {
            $title = __( 'Edit Campaign', 'whatsapp-connector' );
        }
    }
} );



function wa_customers_grid_callback() {
    require_once WC_WHATSAPP_CONNECTOR_PATH . 'admin/wa-customers-grid.php';
}

function wa_campaign_grid_callback() {
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
